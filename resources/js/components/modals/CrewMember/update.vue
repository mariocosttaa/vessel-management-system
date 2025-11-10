<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DateInput } from '@/components/ui/date-input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Icon from '@/components/Icon.vue';
import { useMoney } from '@/composables/useMoney';
import crewMembers from '@/routes/panel/crew-members';

interface CrewMember {
    id: number;
    name: string;
    email?: string;
    phone?: string;
    date_of_birth?: string;
    hire_date: string;
    position_id: number;
    salary_compensation?: {
        compensation_type: string;
        fixed_amount: number | null;
        percentage: number | null;
        currency: string;
        payment_frequency: string;
    };
    payment_frequency: string;
    status: string;
    notes?: string;
    login_permitted: boolean;
}

interface CrewPosition {
    id: number;
    name: string;
}

interface Currency {
    code: string;
    name: string;
    symbol: string;
}

interface Props {
    open: boolean;
    crewMember?: CrewMember | null;
    positions: CrewPosition[];
    statuses: Record<string, string>;
    currencies: Currency[];
    paymentFrequencies: Record<string, string>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'saved': [];
}>();

const { format } = useMoney();

// Track if crew member currently has system access
const hasExistingAccess = computed(() => {
    return props.crewMember?.login_permitted === true;
});

// Check if user has an existing account (not just a crew member account)
const hasExistingAccount = computed(() => {
    return props.crewMember?.has_existing_account === true;
});

// Format currency display for dropdown options
const formatCurrencyDisplay = (code: string, name: string, symbol: string) => {
    return `${symbol} ${name} (${code})`;
};

const form = useForm({
    name: '',
    email: '',
    phone: '',
    date_of_birth: '',
    hire_date: '',
    position_id: null as number | null,
    skip_salary: false, // Always false for updates (existing crew members have salary)
    compensation_type: 'fixed',
    fixed_amount: null as number | null,
    percentage: null as number | null,
    currency: 'EUR',
    payment_frequency: 'monthly',
    status: 'active',
    notes: '',
    login_permitted: false,
    password: '',
    password_confirmation: '',
});

// Wizard state
const currentStep = ref(0);
const hasInteracted = ref(false);

// Always show all 3 steps for updates
const steps = computed(() => {
    return ['crew-info', 'salary', 'system-access'];
});

// Active tab computed property
const activeTab = computed(() => {
    return steps.value[currentStep.value];
});

// Step validation state
const stepValidation = ref({
    'crew-info': { valid: false, errors: [] },
    'salary': { valid: false, errors: [] },
    'system-access': { valid: false, errors: [] }
});

// Computed property for formatted currency options
const currencyOptions = computed(() => {
    return props.currencies.map(currency => ({
        value: currency.code,
        label: formatCurrencyDisplay(currency.code, currency.name, currency.symbol)
    }));
});

const paymentFrequencies = {
    'weekly': 'Weekly',
    'bi_weekly': 'Bi-weekly',
    'monthly': 'Monthly',
    'quarterly': 'Quarterly',
    'annually': 'Annually'
};

// Validation function
const validateStep = (step: string) => {
    const errors: string[] = [];

    if (step === 'crew-info') {
        if (!form.name?.trim()) errors.push('Name is required');
        if (!form.hire_date) errors.push('Hire date is required');
        if (!form.position_id) errors.push('Position is required');
        if (!form.status) errors.push('Status is required');
        // Email is required when system access is enabled
        if (form.login_permitted && !form.email?.trim()) {
            errors.push('Email is required when system access is enabled');
        }
        // Basic email format validation if email is provided
        if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
            errors.push('Please enter a valid email address');
        }
    } else if (step === 'salary') {
        if (!form.skip_salary) {
            if (!form.compensation_type) errors.push('Compensation type is required');
            if (form.compensation_type === 'fixed' && !form.fixed_amount) {
                errors.push('Fixed amount is required');
            }
            if (form.compensation_type === 'percentage' && !form.percentage) {
                errors.push('Percentage is required');
            }
            if (!form.currency) errors.push('Currency is required');
            if (!form.payment_frequency) errors.push('Payment frequency is required');
        }
    } else if (step === 'system-access') {
        // Email is only required if user doesn't have existing account
        if (!hasExistingAccount.value && form.login_permitted && !form.email?.trim()) {
            errors.push('Email is required when system access is enabled');
        }

        // Password validation depends on whether user has existing account
        if (hasExistingAccount.value) {
            // For existing accounts, password is optional (only validate if provided)
            if (form.password && form.password !== form.password_confirmation) {
                errors.push('Passwords do not match');
            }
            // Password is not required for existing accounts
        } else {
            // For new crew member accounts, password is required when enabling system access
            if (form.login_permitted && !hasExistingAccess.value) {
                if (!form.password) {
                    errors.push('Password is required when enabling system access');
                }
                if (!form.password_confirmation) {
                    errors.push('Password confirmation is required when enabling system access');
                }
            }

            // If password is provided, it must match confirmation
            if (form.login_permitted && form.password && form.password !== form.password_confirmation) {
                errors.push('Passwords do not match');
            }
        }

        // Basic email format validation if email is provided
        if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
            errors.push('Please enter a valid email address');
        }
    }

    stepValidation.value[step] = {
        valid: errors.length === 0,
        errors: errors
    };

    return errors.length === 0;
};

// Get errors for a specific step
const getStepErrors = (step: string) => {
    return stepValidation.value[step]?.errors || [];
};

// Navigation functions
const goToStep = (stepIndex: number) => {
    if (stepIndex >= 0 && stepIndex < steps.value.length) {
        currentStep.value = stepIndex;
    }
};

// Next step
const nextStep = () => {
    hasInteracted.value = true; // Mark that user has interacted
    const currentStepValid = validateStep(activeTab.value);
    if (currentStepValid && currentStep.value < steps.value.length - 1) {
        currentStep.value++;
    }
};

// Previous step
const previousStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

// Computed properties for button states
const isNextDisabled = computed(() => {
    return !validateStep(activeTab.value);
});

const isPreviousDisabled = computed(() => {
    return currentStep.value === 0;
});

const isUpdateDisabled = computed(() => {
    // Check if all steps are valid
    return steps.value.some(step => !validateStep(step));
});

// Auto-validate steps when form fields change (only after user interaction)
watch(() => [
    form.name,
    form.hire_date,
    form.position_id,
    form.status,
    form.email,
    form.login_permitted,
    form.compensation_type,
    form.fixed_amount,
    form.percentage,
    form.currency,
    form.payment_frequency,
    form.password,
    form.password_confirmation
], () => {
    if (hasInteracted.value) {
        if (currentStep.value === 0) {
            nextTick(() => validateStep('crew-info'));
        }
        if (currentStep.value === 1) {
            nextTick(() => validateStep('salary'));
        }
        if (currentStep.value === 2) {
            nextTick(() => validateStep('system-access'));
        }
    }
});

// Reset form when modal opens/closes or crew member changes
watch(() => props.open, (isOpen) => {
    if (isOpen && props.crewMember) {
        // Populate form for editing
        form.name = props.crewMember.name;
        form.email = props.crewMember.email || '';
        form.phone = props.crewMember.phone || '';
        form.date_of_birth = props.crewMember.date_of_birth || '';
        form.hire_date = props.crewMember.hire_date;
        form.position_id = props.crewMember.position_id;
        // Load salary compensation data
        if (props.crewMember.salary_compensation) {
            form.compensation_type = props.crewMember.salary_compensation.compensation_type;
            form.fixed_amount = props.crewMember.salary_compensation.fixed_amount;
            form.percentage = props.crewMember.salary_compensation.percentage;
            form.currency = props.crewMember.salary_compensation.currency;
            form.payment_frequency = props.crewMember.salary_compensation.payment_frequency;
        }
        form.status = props.crewMember.status;
        form.notes = props.crewMember.notes || '';
        form.login_permitted = props.crewMember.login_permitted || false;
        form.skip_salary = false; // Always false for updates
    } else if (isOpen) {
        // Reset form for creating
        form.reset();
    }
    form.clearErrors();
    // Reset wizard state
    currentStep.value = 0;
    hasInteracted.value = false; // Reset interaction flag
    stepValidation.value = {
        'crew-info': { valid: false, errors: [] },
        'salary': { valid: false, errors: [] },
        'system-access': { valid: false, errors: [] }
    };
});

const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)\//);
    return vesselMatch ? vesselMatch[1] : null;
};

const handleSave = () => {
    if (props.crewMember) {
        hasInteracted.value = true; // Mark interaction

        // Validate all steps before saving
        const allStepsValid = steps.value.every(step => validateStep(step));

        if (!allStepsValid) {
            // Find first invalid step and navigate to it
            const firstInvalidStep = steps.value.findIndex(step => !validateStep(step));
            if (firstInvalidStep !== -1) {
                currentStep.value = firstInvalidStep;
            }
            return;
        }

        const vesselId = getCurrentVesselId();

        // Prepare form data - only include password fields if system access is enabled
        const formData = { ...form.data() };

        if (!formData.login_permitted) {
            // Remove password fields if system access is not enabled
            delete formData.password;
            delete formData.password_confirmation;
        }

        // Add skip_salary flag for update (always false since we're updating existing)
        formData.skip_salary = false;

        form.transform(() => formData).put(crewMembers.update.url({ vessel: vesselId, crewMember: props.crewMember.id }), {
            onSuccess: () => {
                emit('saved');
                emit('update:open', false);
            },
        });
    }
};

const handleClose = () => {
    emit('update:open', false);
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Edit Crew Member</DialogTitle>
                <DialogDescription>
                    Update crew member information using the step-by-step wizard
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
                <!-- Wizard Progress -->
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center flex-1">
                        <!-- Step 1: Crew Information -->
                        <div class="flex items-center flex-1">
                            <button
                                type="button"
                                @click="goToStep(0)"
                                :class="[
                                    'relative flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all',
                                    currentStep === 0
                                        ? 'bg-primary border-primary text-primary-foreground'
                                        : currentStep > 0
                                        ? 'bg-primary border-primary text-primary-foreground'
                                        : 'bg-background border-muted text-muted-foreground'
                                ]"
                            >
                                <Icon v-if="currentStep > 0" name="check" class="w-5 h-5" />
                                <span v-else class="text-sm font-medium">1</span>
                            </button>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-card-foreground">Crew Information</p>
                                <p class="text-xs text-muted-foreground">Basic details</p>
                            </div>
                        </div>

                        <!-- Connector -->
                        <div
                            :class="[
                                'flex-1 h-0.5 mx-2',
                                currentStep > 0 ? 'bg-primary' : 'bg-muted'
                            ]"
                        ></div>

                        <!-- Step 2: Salary -->
                        <div class="flex items-center flex-1">
                            <button
                                type="button"
                                @click="goToStep(1)"
                                :class="[
                                    'relative flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all',
                                    currentStep === 1
                                        ? 'bg-primary border-primary text-primary-foreground'
                                        : currentStep > 1
                                        ? 'bg-primary border-primary text-primary-foreground'
                                        : 'bg-background border-muted text-muted-foreground'
                                ]"
                            >
                                <Icon v-if="currentStep > 1" name="check" class="w-5 h-5" />
                                <span v-else class="text-sm font-medium">2</span>
                            </button>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-card-foreground">Salary & Compensation</p>
                                <p class="text-xs text-muted-foreground">Payment details</p>
                            </div>
                        </div>

                        <!-- Connector -->
                        <div
                            :class="[
                                'flex-1 h-0.5 mx-2',
                                currentStep > 1 ? 'bg-primary' : 'bg-muted'
                            ]"
                        ></div>

                        <!-- Step 3: System Access -->
                        <div class="flex items-center flex-1">
                            <button
                                type="button"
                                @click="goToStep(2)"
                                :class="[
                                    'relative flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all',
                                    currentStep === 2
                                        ? 'bg-primary border-primary text-primary-foreground'
                                        : 'bg-background border-muted text-muted-foreground'
                                ]"
                            >
                                <span class="text-sm font-medium">3</span>
                            </button>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-card-foreground">System Access</p>
                                <p class="text-xs text-muted-foreground">Login permissions</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step Content -->
                <div class="space-y-6">
                    <!-- Step 1: Crew Information -->
                    <div v-if="activeTab === 'crew-info'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div class="md:col-span-2">
                                <Label for="name" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    Full Name <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Enter full name"
                                    required
                                    :class="{ 'border-destructive dark:border-destructive': form.errors.name || getStepErrors('crew-info').some(e => e.includes('Name')) }"
                                />
                                <InputError :message="form.errors.name" class="mt-1" />
                                <div v-if="getStepErrors('crew-info').some(e => e.includes('Name'))" class="text-sm text-destructive mt-1">
                                    {{ getStepErrors('crew-info').find(e => e.includes('Name')) }}
                                </div>
                            </div>

                            <!-- Email -->
                            <div>
                                <Label for="email" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    Email
                                </Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="Enter email address"
                                    :class="{ 'border-destructive dark:border-destructive': form.errors.email || getStepErrors('crew-info').some(e => e.includes('Email')) }"
                                />
                                <InputError :message="form.errors.email" class="mt-1" />
                                <div v-if="getStepErrors('crew-info').some(e => e.includes('Email'))" class="text-sm text-destructive mt-1">
                                    {{ getStepErrors('crew-info').find(e => e.includes('Email')) }}
                                </div>
                            </div>

                            <!-- Phone -->
                            <div>
                                <Label for="phone" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    Phone
                                </Label>
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    placeholder="Enter phone number"
                                    :class="{ 'border-destructive dark:border-destructive': form.errors.phone }"
                                />
                                <InputError :message="form.errors.phone" class="mt-1" />
                            </div>

                            <!-- Date of Birth -->
                            <div>
                                <Label for="date_of_birth" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    Date of Birth
                                </Label>
                                    <DateInput
                                        id="date_of_birth"
                                        v-model="form.date_of_birth"
                                        :class="{ 'border-destructive dark:border-destructive': form.errors.date_of_birth }"
                                    />
                                <InputError :message="form.errors.date_of_birth" class="mt-1" />
                            </div>

                            <!-- Hire Date -->
                            <div>
                                <Label for="hire_date" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    Hire Date <span class="text-destructive">*</span>
                                </Label>
                                    <DateInput
                                        id="hire_date"
                                        v-model="form.hire_date"
                                    required
                                    :class="{ 'border-destructive dark:border-destructive': form.errors.hire_date || getStepErrors('crew-info').some(e => e.includes('Hire date')) }"
                                />
                                <InputError :message="form.errors.hire_date" class="mt-1" />
                                <div v-if="getStepErrors('crew-info').some(e => e.includes('Hire date'))" class="text-sm text-destructive mt-1">
                                    {{ getStepErrors('crew-info').find(e => e.includes('Hire date')) }}
                                </div>
                            </div>

                            <!-- Position -->
                            <div>
                                <Label for="position_id" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    Position <span class="text-destructive">*</span>
                                </Label>
                                <select
                                    id="position_id"
                                    v-model="form.position_id"
                                    required
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="{
                                        'border-destructive dark:border-destructive': form.errors.position_id || getStepErrors('crew-info').some(e => e.includes('Position'))
                                    }"
                                >
                                    <option value="">Choose an option!</option>
                                    <option v-for="position in positions" :key="position.id" :value="position.id">
                                        {{ position.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.position_id" class="mt-1" />
                                <div v-if="getStepErrors('crew-info').some(e => e.includes('Position'))" class="text-sm text-destructive mt-1">
                                    {{ getStepErrors('crew-info').find(e => e.includes('Position')) }}
                                </div>
                            </div>

                            <!-- Status -->
                            <div>
                                <Label for="status" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    Status <span class="text-destructive">*</span>
                                </Label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    required
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="{
                                        'border-destructive dark:border-destructive': form.errors.status || getStepErrors('crew-info').some(e => e.includes('Status'))
                                    }"
                                >
                                    <option value="">Choose an option!</option>
                                    <option v-for="(label, value) in statuses" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.status" class="mt-1" />
                                <div v-if="getStepErrors('crew-info').some(e => e.includes('Status'))" class="text-sm text-destructive mt-1">
                                    {{ getStepErrors('crew-info').find(e => e.includes('Status')) }}
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="md:col-span-2">
                                <Label for="notes" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    Notes
                                </Label>
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="3"
                                    placeholder="Enter additional notes"
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="{ 'border-destructive dark:border-destructive': form.errors.notes }"
                                ></textarea>
                                <InputError :message="form.errors.notes" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Salary & Compensation -->
                    <div v-if="activeTab === 'salary'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Skip Salary Checkbox -->
                            <div class="md:col-span-2">
                                <div class="flex items-center space-x-2">
                                    <input
                                        id="skip_salary"
                                        v-model="form.skip_salary"
                                        type="checkbox"
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                    />
                                    <label for="skip_salary" class="text-sm font-medium text-card-foreground">
                                        Skip salary configuration
                                    </label>
                                </div>
                                <p class="text-sm text-muted-foreground mt-1">
                                    Check this if you don't want to set up salary compensation for this crew member.
                                </p>
                            </div>

                            <!-- Salary fields (only show when not skipped) -->
                            <template v-if="!form.skip_salary">
                                <!-- Compensation Type -->
                                <div>
                                    <Label for="compensation_type" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                        Compensation Type <span class="text-destructive">*</span>
                                    </Label>
                                    <select
                                        id="compensation_type"
                                        v-model="form.compensation_type"
                                        required
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="{ 'border-destructive dark:border-destructive': form.errors.compensation_type || getStepErrors('salary').some(e => e.includes('Compensation type')) }"
                                    >
                                        <option value="fixed">Fixed Salary</option>
                                        <option value="percentage">Percentage of Revenue</option>
                                    </select>
                                    <InputError :message="form.errors.compensation_type" class="mt-1" />
                                    <div v-if="getStepErrors('salary').some(e => e.includes('Compensation type'))" class="text-sm text-destructive mt-1">
                                        {{ getStepErrors('salary').find(e => e.includes('Compensation type')) }}
                                    </div>
                                </div>

                                <!-- Fixed Amount (shown when compensation_type is 'fixed') -->
                                <div v-show="form.compensation_type === 'fixed'">
                                    <MoneyInputWithLabel
                                        v-model="form.fixed_amount"
                                        label="Fixed Salary Amount"
                                        :currency="form.currency"
                                        :decimals="2"
                                        placeholder="0,00"
                                        :error="form.errors.fixed_amount || getStepErrors('salary').find(e => e.includes('Fixed amount'))"
                                        :show-currency="false"
                                        required
                                    />
                                </div>

                                <!-- Percentage (shown when compensation_type is 'percentage') -->
                                <div v-show="form.compensation_type === 'percentage'">
                                    <Label for="percentage" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                        Percentage of Revenue <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="percentage"
                                        v-model="form.percentage"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        placeholder="0.00"
                                        class="mt-1"
                                        :class="{ 'border-destructive dark:border-destructive': form.errors.percentage || getStepErrors('salary').some(e => e.includes('Percentage')) }"
                                    />
                                    <InputError :message="form.errors.percentage" class="mt-1" />
                                    <div v-if="getStepErrors('salary').some(e => e.includes('Percentage'))" class="text-sm text-destructive mt-1">
                                        {{ getStepErrors('salary').find(e => e.includes('Percentage')) }}
                                    </div>
                                    <p class="text-sm text-muted-foreground mt-1">
                                        Enter the percentage of total revenue this crew member should receive (0.00 - 100.00)
                                    </p>
                                </div>

                                <!-- Currency -->
                                <div>
                                    <Label for="currency" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                        Currency <span class="text-destructive">*</span>
                                    </Label>
                                    <select
                                        id="currency"
                                        v-model="form.currency"
                                        required
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="{ 'border-destructive dark:border-destructive': form.errors.currency || getStepErrors('salary').some(e => e.includes('Currency')) }"
                                    >
                                        <option v-for="currency in currencyOptions" :key="currency.value" :value="currency.value">
                                            {{ currency.label }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.currency" class="mt-1" />
                                    <div v-if="getStepErrors('salary').some(e => e.includes('Currency'))" class="text-sm text-destructive mt-1">
                                        {{ getStepErrors('salary').find(e => e.includes('Currency')) }}
                                    </div>
                                </div>

                                <!-- Payment Frequency -->
                                <div>
                                    <Label for="payment_frequency" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                        Payment Frequency <span class="text-destructive">*</span>
                                    </Label>
                                    <select
                                        id="payment_frequency"
                                        v-model="form.payment_frequency"
                                        required
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="{ 'border-destructive dark:border-destructive': form.errors.payment_frequency || getStepErrors('salary').some(e => e.includes('Payment frequency')) }"
                                    >
                                        <option v-for="(label, value) in paymentFrequencies" :key="value" :value="value">
                                            {{ label }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.payment_frequency" class="mt-1" />
                                    <div v-if="getStepErrors('salary').some(e => e.includes('Payment frequency'))" class="text-sm text-destructive mt-1">
                                        {{ getStepErrors('salary').find(e => e.includes('Payment frequency')) }}
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Step 3: System Access -->
                    <div v-if="activeTab === 'system-access'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- System Access Toggle -->
                            <div class="md:col-span-2">
                                <Label for="login_permitted" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    System Access
                                </Label>
                                <div class="flex items-center space-x-2 mt-2">
                                    <input
                                        id="login_permitted"
                                        v-model="form.login_permitted"
                                        type="checkbox"
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                    />
                                    <label for="login_permitted" class="text-sm font-medium text-card-foreground">
                                        Allow this crew member to access the system
                                    </label>
                                </div>
                                <p class="text-sm text-muted-foreground mt-1">
                                    If enabled, the crew member will be able to log into the system. If disabled, they will only be a crew member record.
                                </p>
                            </div>

                            <!-- Email Field (always show when system access is enabled) -->
                            <div v-show="form.login_permitted" class="md:col-span-2">
                                <Label for="email_system" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    Email <span v-if="!hasExistingAccount" class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="email_system"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="Enter email address"
                                    :disabled="hasExistingAccount"
                                    :class="{ 'border-destructive dark:border-destructive': form.errors.email || getStepErrors('system-access').some(e => e.includes('Email')), 'opacity-60 cursor-not-allowed': hasExistingAccount }"
                                />
                                <InputError :message="form.errors.email" class="mt-1" />
                                <div v-if="getStepErrors('system-access').some(e => e.includes('Email'))" class="text-sm text-destructive mt-1">
                                    {{ getStepErrors('system-access').find(e => e.includes('Email')) }}
                                </div>
                                <p v-if="hasExistingAccount" class="text-sm text-muted-foreground mt-1">
                                    Email cannot be changed for existing user accounts.
                                </p>
                            </div>

                            <!-- Password Fields Section -->
                            <div v-show="form.login_permitted" class="md:col-span-2 border-t pt-4">
                                <div class="mb-4">
                                    <h4 class="text-lg font-medium text-card-foreground mb-2">Change Password</h4>
                                    <p v-if="hasExistingAccount" class="text-sm text-muted-foreground">
                                        Password cannot be changed for existing user accounts. Users must change their password through their profile settings.
                                    </p>
                                    <p v-else class="text-sm text-muted-foreground">
                                        Leave blank to keep current password. Enter new password to change it.
                                    </p>
                                </div>

                                <div v-if="!hasExistingAccount" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Password -->
                                    <div>
                                        <Label for="password" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                            New Password <span v-if="!hasExistingAccess" class="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="password"
                                            v-model="form.password"
                                            type="password"
                                            :placeholder="hasExistingAccess ? 'Leave blank to keep current' : 'Enter password'"
                                            :class="{ 'border-destructive dark:border-destructive': form.errors.password || getStepErrors('system-access').some(e => e.includes('Password')) }"
                                        />
                                        <InputError :message="form.errors.password" class="mt-1" />
                                        <div v-if="getStepErrors('system-access').some(e => e.includes('Password'))" class="text-sm text-destructive mt-1">
                                            {{ getStepErrors('system-access').find(e => e.includes('Password')) }}
                                        </div>
                                    </div>

                                    <!-- Password Confirmation -->
                                    <div>
                                        <Label for="password_confirmation" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                            Confirm Password <span v-if="!hasExistingAccess" class="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="password_confirmation"
                                            v-model="form.password_confirmation"
                                            type="password"
                                            :placeholder="hasExistingAccess ? 'Leave blank to keep current' : 'Confirm password'"
                                            :class="{ 'border-destructive dark:border-destructive': form.errors.password_confirmation || getStepErrors('system-access').some(e => e.includes('Password')) }"
                                        />
                                        <InputError :message="form.errors.password_confirmation" class="mt-1" />
                                        <div v-if="getStepErrors('system-access').some(e => e.includes('Passwords do not match'))" class="text-sm text-destructive mt-1">
                                            {{ getStepErrors('system-access').find(e => e.includes('Passwords do not match')) }}
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="bg-muted/50 border border-border rounded-lg p-4">
                                    <p class="text-sm text-muted-foreground">
                                        This user has an existing account. Password management is not available through the crew member management interface.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Button
                        v-if="currentStep > 0"
                        type="button"
                        variant="outline"
                        @click="previousStep"
                        :disabled="form.processing || isPreviousDisabled"
                    >
                        <Icon name="arrow-left" class="w-4 h-4 mr-2" />
                        Previous
                    </Button>
                </div>

                <div class="flex items-center space-x-4">
                    <Button
                        variant="outline"
                        @click="handleClose"
                        :disabled="form.processing"
                    >
                        Cancel
                    </Button>
                    <Button
                        v-if="currentStep < steps.length - 1"
                        type="button"
                        @click="nextStep"
                        :disabled="form.processing || isNextDisabled"
                    >
                        Next
                        <Icon name="arrow-right" class="w-4 h-4 ml-2" />
                    </Button>
                    <Button
                        v-if="currentStep === steps.length - 1"
                        type="button"
                        @click="handleSave"
                        :disabled="form.processing || isUpdateDisabled"
                    >
                        <Icon v-if="form.processing" name="loader" class="w-4 h-4 mr-2 animate-spin" />
                        Update Crew Member
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
