<script setup lang="ts">
import { watch, computed, ref, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DateInput } from '@/components/ui/date-input';
import { Select } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Icon from '@/components/Icon.vue';
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue';
// import { useMoney } from '@/composables/useMoney';
import { useI18n } from '@/composables/useI18n';
import crewMembers from '@/routes/panel/crew-members';

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
    positions: CrewPosition[];
    statuses: Record<string, string>;
    currencies: Currency[];
    paymentFrequencies: Record<string, string>;
}

const props = defineProps<Props>();
const { t } = useI18n();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'saved': [];
}>();

// const { format } = useMoney(); // Not used in this component

const currentStep = ref(0);

// Helper function to get today's date in YYYY-MM-DD format
const getTodayDate = () => {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const form = useForm({
    name: '',
    email: '',
    phone: '',
    date_of_birth: '',
    hire_date: getTodayDate(),
    position_id: null as number | null,
    skip_salary: false, // Default to showing salary step (can be toggled)
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

// Wizard steps - always show all steps (salary can be skipped via checkbox)
// Always show all 3 steps - don't hide based on skip_salary
const steps = computed(() => {
    return ['crew-info', 'salary', 'system-access'];
});

// Active tab for the modal (synced with currentStep)
const activeTab = computed(() => {
    if (!steps.value || steps.value.length === 0) {
        return 'crew-info';
    }
    return steps.value[currentStep.value] || 'crew-info';
});

// Track validation status for each step
const stepValidation = ref({
    'crew-info': { valid: false, errors: [] },
    'salary': { valid: false, errors: [] },
    'system-access': { valid: false, errors: [] }
});

// Check if step is valid
const isStepValid = (step: string) => {
    const validation = stepValidation.value as Record<string, { valid: boolean; errors: string[] }>;
    return validation[step]?.valid || false;
};

// Get step validation errors
const getStepErrors = (step: string) => {
    const validation = stepValidation.value as Record<string, { valid: boolean; errors: string[] }>;
    return validation[step]?.errors || [];
};

// Validate individual step
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
        // Only validate salary fields if salary is not skipped
        if (!form.skip_salary) {
            if (!form.compensation_type) errors.push('Compensation type is required');
            if (!form.currency) errors.push('Currency is required');
            if (form.compensation_type === 'fixed' && !form.fixed_amount) {
                errors.push('Fixed amount is required');
            }
            if (form.compensation_type === 'percentage' && !form.percentage) {
                errors.push('Percentage is required');
            }
            if (!form.payment_frequency) errors.push('Payment frequency is required');
        }
    } else if (step === 'system-access') {
        if (form.login_permitted && !form.email?.trim()) {
            errors.push('Email is required when system access is enabled');
        }
        // Note: Password is not required if email belongs to existing user (handled by backend)
        // For new users, password is required
        if (form.login_permitted && form.email && !form.password) {
            // Only show error if email is provided (existing users will be handled by backend)
            errors.push('Password is required when system access is enabled');
        }
        if (form.login_permitted && form.password && !form.password_confirmation) {
            errors.push('Password confirmation is required when system access is enabled');
        }
        if (form.login_permitted && form.password && form.password !== form.password_confirmation) {
            errors.push('Passwords do not match');
        }
        // Basic email format validation if email is provided
        if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
            errors.push('Please enter a valid email address');
        }
    }

    const validation = stepValidation.value as Record<string, { valid: boolean; errors: string[] }>;
    validation[step] = {
        valid: errors.length === 0,
        errors
    };

    return errors.length === 0;
};

// Navigate to step
const goToStep = (index: number) => {
    if (index >= 0 && index < steps.value.length) {
        // Validate current step before moving
        if (currentStep.value < index && !validateStep(steps.value[currentStep.value])) {
            return; // Don't allow progression if current step is invalid
        }
        currentStep.value = index;
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

// Computed property for formatted currency options
const currencyOptions = computed(() => {
    return props.currencies.map((currency: Currency) => ({
        value: currency.code,
        label: `${currency.symbol} ${currency.name} (${currency.code})`
    }));
});

// Convert to Select component options format
const positionOptions = computed(() => {
    const options = [{ value: '', label: t('Choose an option!') }];
    props.positions.forEach(position => {
        options.push({ value: position.id, label: position.name });
    });
    return options;
});

const statusOptions = computed(() => {
    const options = [{ value: '', label: t('Choose an option!') }];
    Object.entries(props.statuses).forEach(([value, label]) => {
        options.push({ value, label: label as string });
    });
    return options;
});

const compensationTypeOptions = computed(() => {
    return [
        { value: 'fixed', label: t('Fixed Salary') },
        { value: 'percentage', label: t('Percentage of Revenue') }
    ];
});

const paymentFrequencyOptions = computed(() => {
    const options: Array<{ value: string; label: string }> = [];
    Object.entries(props.paymentFrequencies).forEach(([value, label]) => {
        options.push({ value, label: label as string });
    });
    return options;
});

// Computed properties for disabled states
const isNextDisabled = computed(() => {
    return !validateStep(activeTab.value);
});

const isPreviousDisabled = computed(() => {
    return currentStep.value === 0;
});

const isCreateDisabled = computed(() => {
    // Check if all steps are valid
    return steps.value.some(step => !validateStep(step));
});

// paymentFrequencies comes from props

// No need for user selection watcher anymore

// Track if user has interacted with the form
const hasInteracted = ref(false);

// Reset form when modal opens/closes
watch(() => props.open, (isOpen: boolean) => {
    if (isOpen) {
        form.reset();
        form.hire_date = getTodayDate(); // Set to today's date
        form.skip_salary = true; // Default to skipping salary
        form.fixed_amount = null;
        form.percentage = null;
        form.currency = 'EUR';
        form.payment_frequency = 'monthly';
        form.status = 'active';
        form.clearErrors();
        // Reset wizard state
        currentStep.value = 0;
        hasInteracted.value = false; // Reset interaction flag
        stepValidation.value = {
            'crew-info': { valid: false, errors: [] },
            'salary': { valid: false, errors: [] },
            'system-access': { valid: false, errors: [] }
        };
    }
});

// Auto-validate steps when form fields change (only after user interaction)
watch(() => [
    form.name,
    form.hire_date,
    form.position_id,
    form.status,
    form.email,
    form.login_permitted
], () => {
    if (hasInteracted.value) {
        if (currentStep.value === 0) {
            nextTick(() => validateStep('crew-info'));
        }
        if (currentStep.value === 2) {
            nextTick(() => validateStep('system-access'));
        }
    }
});

// Watch for skip_salary changes and step changes
watch(() => form.skip_salary, () => {
    // Just clear validation when toggle changes
    stepValidation.value['salary'] = { valid: false, errors: [] };
});

// Set skip_salary to true when moving from step 1 to step 2
watch(() => currentStep.value, (newStep, oldStep) => {
    if (oldStep === 0 && newStep === 1) {
        // Moving from crew-info to salary, ensure skip_salary is true
        form.skip_salary = true;
    }
});

watch(() => [
    form.skip_salary,
    form.compensation_type,
    form.currency,
    form.fixed_amount,
    form.percentage,
    form.payment_frequency
], () => {
    // Only validate if we're on the salary step
    const stepIndex = form.skip_salary ? steps.value.indexOf('system-access') : steps.value.indexOf('salary');
    if (currentStep.value === stepIndex) {
        nextTick(() => validateStep('salary'));
    }
});

watch(() => [
    form.login_permitted,
    form.password,
    form.password_confirmation
], () => {
    if (currentStep.value === 2) {
        nextTick(() => validateStep('system-access'));
    }
});

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

const handleSave = () => {
    // Validate all steps before saving
    let isValid = true;
    let lastInvalidStep = '';

    for (const step of steps.value) {
        if (!validateStep(step)) {
            isValid = false;
            lastInvalidStep = step;
            break;
        }
    }

    if (!isValid) {
        // Navigate to the first invalid step
        const stepIndex = steps.value.indexOf(lastInvalidStep);
        if (stepIndex !== -1) {
            currentStep.value = stepIndex;
        }
        return;
    }

    // Prepare form data - only include password fields if system access is enabled
    const formData = { ...form.data() };

    if (!formData.login_permitted) {
        // Remove password fields if system access is not enabled
        delete (formData as any).password;
        delete (formData as any).password_confirmation;
    }

    // Remove salary fields if salary is skipped
    if (formData.skip_salary) {
        delete (formData as any).compensation_type;
        delete (formData as any).fixed_amount;
        delete (formData as any).percentage;
        delete (formData as any).currency;
        delete (formData as any).payment_frequency;
    }

    form.transform(() => formData).post(crewMembers.store.url({ vessel: getCurrentVesselId() }), {
        onSuccess: () => {
            emit('saved');
            emit('update:open', false);
        },
    });
};

const handleClose = () => {
    emit('update:open', false);
    form.reset();
    form.clearErrors();
};

</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{{ t('Create New Crew Member') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Add a new crew member to your vessel management system') }}
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
                <!-- Dynamic Wizard Progress Bar -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <!-- Step 1: Crew Information -->
                        <div class="flex items-center flex-1">
                            <button
                                type="button"
                                @click="goToStep(0)"
                                :class="[
                                    'relative flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all',
                                    currentStep === 0
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : currentStep > 0
                                        ? isStepValid('crew-info')
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-destructive bg-destructive text-destructive-foreground'
                                        : 'border-muted bg-muted text-muted-foreground'
                                ]"
                            >
                                <span class="font-semibold">{{ currentStep > 0 ? '✓' : '1' }}</span>
                            </button>
                            <div class="ml-2 text-sm">
                                <div class="font-medium text-foreground">{{ t('Crew Information') }}</div>
                                <div class="text-xs text-muted-foreground">{{ t('Basic details') }}</div>
                            </div>
                        </div>

                        <!-- Connector to Step 2: Salary -->
                        <div
                            :class="[
                                'flex-1 h-0.5 mx-2',
                                currentStep > 0 ? 'bg-primary' : 'bg-muted'
                            ]"
                        ></div>
                        <div class="flex items-center flex-1">
                            <button
                                type="button"
                                @click="goToStep(1)"
                                :class="[
                                    'relative flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all',
                                    currentStep === 1
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : currentStep > 1
                                        ? isStepValid('salary')
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-destructive bg-destructive text-destructive-foreground'
                                        : 'border-muted bg-muted text-muted-foreground'
                                ]"
                            >
                                <span class="font-semibold">{{ currentStep > 1 ? '✓' : '2' }}</span>
                            </button>
                            <div class="ml-2 text-sm">
                                <div class="font-medium text-foreground">{{ t('Salary & Compensation') }}</div>
                                <div class="text-xs text-muted-foreground">{{ t('Payment details') }}</div>
                            </div>
                        </div>

                        <!-- Connector to Step 3: System Access -->
                        <div
                            :class="[
                                'flex-1 h-0.5 mx-2',
                                currentStep > 1 ? 'bg-primary' : 'bg-muted'
                            ]"
                        ></div>

                        <!-- Final Step: System Access -->
                        <div class="flex items-center flex-1">
                            <button
                                type="button"
                                @click="goToStep(2)"
                                :class="[
                                    'relative flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all',
                                    currentStep === 2
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : isStepValid('system-access') && currentStep > 1
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-muted bg-muted text-muted-foreground'
                                ]"
                            >
                                <span class="font-semibold">3</span>
                            </button>
                            <div class="ml-2 text-sm">
                                <div class="font-medium text-foreground">{{ t('System Access') }}</div>
                                <div class="text-xs text-muted-foreground">{{ t('Login credentials') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="handleSave" class="space-y-6">
                    <!-- Crew Information Tab -->
                    <div v-show="activeTab === 'crew-info'" class="grid grid-cols-1 md:grid-cols-2 gap-6" role="tabpanel" id="crew-info-tabpanel">

                        <!-- Name -->
                        <div class="md:col-span-2">
                            <Label for="name" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Full Name') }} <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                :placeholder="t('Enter full name')"
                                required
                                :class="{ 'border-destructive dark:border-destructive': form.errors.name }"
                            />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>

                        <!-- Email -->
                        <div>
                            <Label for="email" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Email') }}
                            </Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                :placeholder="t('Enter email address')"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.email }"
                            />
                            <InputError :message="form.errors.email" class="mt-1" />
                        </div>

                        <!-- Phone -->
                        <div>
                            <Label for="phone" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Phone') }}
                            </Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                :placeholder="t('Enter phone number')"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.phone }"
                            />
                            <InputError :message="form.errors.phone" class="mt-1" />
                        </div>

                        <!-- Date of Birth -->
                        <div>
                            <Label for="date_of_birth" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Date of Birth') }}
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
                                {{ t('Hire Date') }} <span class="text-destructive">*</span>
                            </Label>
                            <DateInput
                                id="hire_date"
                                v-model="form.hire_date"
                                required
                                :class="{ 'border-destructive dark:border-destructive': form.errors.hire_date }"
                            />
                            <InputError :message="form.errors.hire_date" class="mt-1" />
                        </div>

                        <!-- Position -->
                        <div>
                            <Label for="position_id" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Position') }} <span class="text-destructive">*</span>
                            </Label>
                            <Select
                                id="position_id"
                                v-model="form.position_id"
                                :options="positionOptions"
                                :placeholder="t('Choose an option!')"
                                searchable
                                :error="!!(form.errors.position_id || getStepErrors('crew-info').some(e => e.includes('Position')))"
                            />
                            <InputError :message="form.errors.position_id" class="mt-1" />
                            <div v-if="getStepErrors('crew-info').some(e => e.includes('Position'))" class="text-sm text-destructive mt-1">
                                {{ getStepErrors('crew-info').find(e => e.includes('Position')) }}
                            </div>
                        </div>


                        <!-- Status -->
                        <div>
                            <Label for="status" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Status') }} <span class="text-destructive">*</span>
                            </Label>
                            <Select
                                id="status"
                                v-model="form.status"
                                :options="statusOptions"
                                :placeholder="t('Choose an option!')"
                                :error="!!(form.errors.status || getStepErrors('crew-info').some(e => e.includes('Status')))"
                            />
                            <InputError :message="form.errors.status" class="mt-1" />
                            <div v-if="getStepErrors('crew-info').some(e => e.includes('Status'))" class="text-sm text-destructive mt-1">
                                {{ getStepErrors('crew-info').find(e => e.includes('Status')) }}
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-2">
                            <Label for="notes" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Notes') }}
                            </Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                :placeholder="t('Enter additional notes')"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.notes }"
                            ></textarea>
                            <InputError :message="form.errors.notes" class="mt-1" />
                        </div>
                    </div>

                    <!-- System Access Tab -->
                    <div v-show="activeTab === 'system-access'" class="space-y-6" role="tabpanel" id="system-access-tabpanel">
                        <!-- System Access Toggle -->
                        <div>
                            <Label for="login_permitted" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('System Access') }}
                            </Label>
                            <div class="flex items-center space-x-2 mt-2">
                                <input
                                    id="login_permitted"
                                    v-model="form.login_permitted"
                                    type="checkbox"
                                    class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                />
                                <label for="login_permitted" class="text-sm text-muted-foreground">
                                    {{ t('Allow this crew member to access the system') }}
                                </label>
                            </div>
                            <p class="text-sm text-muted-foreground mt-1">
                                {{ t('If enabled, the crew member will be able to log into the system. If disabled, they will only be a crew member record.') }}
                            </p>
                        </div>

                        <!-- Password Fields (only show when system access is enabled) -->
                        <div v-show="form.login_permitted" class="space-y-4">
                            <div class="border-t pt-4">
                                <h3 class="text-lg font-medium text-card-foreground mb-4">{{ t('Login Credentials') }}</h3>

                                <!-- Info Message -->
                                <div class="bg-muted/50 border border-border rounded-lg p-3 mb-4">
                                    <p class="text-sm text-muted-foreground">
                                        <strong>{{ t('Note') }}:</strong> {{ t('If the email belongs to an existing user, they will be linked to this vessel and their existing account credentials will be preserved.') }}
                                    </p>
                                </div>

                                <!-- Email Field -->
                                <div class="mb-4">
                                    <Label for="email" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                        {{ t('Email') }} <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        :placeholder="t('Enter email address')"
                                        class="mt-1"
                                        :class="{ 'border-destructive dark:border-destructive': form.errors.email || getStepErrors('system-access').some(e => e.includes('Email')) }"
                                    />
                                    <InputError :message="form.errors.email" class="mt-1" />
                                    <div v-if="getStepErrors('system-access').some(e => e.includes('Email'))" class="text-sm text-destructive mt-1">
                                        {{ getStepErrors('system-access').find(e => e.includes('Email')) }}
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Password -->
                                    <div>
                                        <Label for="password" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                            {{ t('Password') }} <span class="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="password"
                                            v-model="form.password"
                                            type="password"
                                            :placeholder="t('Enter password')"
                                            class="mt-1"
                                            :class="{ 'border-destructive dark:border-destructive': form.errors.password }"
                                        />
                                        <InputError :message="form.errors.password" class="mt-1" />
                                    </div>

                                    <!-- Password Confirmation -->
                                    <div>
                                        <Label for="password_confirmation" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                            {{ t('Confirm Password') }} <span class="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="password_confirmation"
                                            v-model="form.password_confirmation"
                                            type="password"
                                            :placeholder="t('Confirm password')"
                                            class="mt-1"
                                            :class="{ 'border-destructive dark:border-destructive': form.errors.password_confirmation }"
                                        />
                                        <InputError :message="form.errors.password_confirmation" class="mt-1" />
                                    </div>
                                </div>

                                <p class="text-sm text-muted-foreground mt-2">
                                    {{ t('The crew member will use these credentials to log into the system.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Salary/Compensation Tab -->
                    <div v-show="activeTab === 'salary'" class="space-y-6" role="tabpanel" id="salary-tabpanel">
                        <div class="border-b pb-4">
                            <h3 class="text-lg font-medium text-card-foreground">{{ t('Salary & Compensation') }}</h3>
                            <p class="text-sm text-muted-foreground mt-1">
                                {{ t('Configure how this crew member will be compensated.') }}
                            </p>
                        </div>

                        <!-- Skip Salary Checkbox -->
                        <div class="bg-muted/50 border border-border rounded-lg p-4">
                            <div class="flex items-center space-x-2">
                                <input
                                    id="skip_salary"
                                    v-model="form.skip_salary"
                                    type="checkbox"
                                    class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                />
                                <Label for="skip_salary" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    {{ t('Skip salary definition for this crew member') }}
                                </Label>
                            </div>
                            <p class="text-sm text-muted-foreground mt-2">
                                {{ t('If checked, you can define salary details later. If unchecked, please provide salary information below.') }}
                            </p>
                        </div>

                        <div v-show="!form.skip_salary" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Compensation Type -->
                            <div>
                                <Label for="compensation_type" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    {{ t('Compensation Type') }} <span class="text-destructive">*</span>
                                </Label>
                                <Select
                                    id="compensation_type"
                                    v-model="form.compensation_type"
                                    :options="compensationTypeOptions"
                                    :error="!!form.errors.compensation_type"
                                />
                                <InputError :message="form.errors.compensation_type" class="mt-1" />
                            </div>

                            <!-- Currency -->
                            <div>
                                <Label for="currency" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    {{ t('Currency') }} <span class="text-destructive">*</span>
                                </Label>
                                <Select
                                    id="currency"
                                    v-model="form.currency"
                                    :options="currencyOptions"
                                    searchable
                                    :error="!!form.errors.currency"
                                />
                                <InputError :message="form.errors.currency" class="mt-1" />
                            </div>

                            <!-- Fixed Amount (shown when compensation_type is 'fixed') -->
                            <div v-show="form.compensation_type === 'fixed'" class="md:col-span-2">
                                <MoneyInputWithLabel
                                    v-model="form.fixed_amount"
                                    :label="t('Fixed Salary Amount')"
                                    :currency="form.currency"
                                    :decimals="2"
                                    placeholder="0,00"
                                    :error="form.errors.fixed_amount"
                                    :show-currency="false"
                                    required
                                />
                            </div>

                            <!-- Percentage (shown when compensation_type is 'percentage') -->
                            <div v-show="form.compensation_type === 'percentage'" class="md:col-span-2">
                                <Label for="percentage" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    {{ t('Percentage of Revenue') }} <span class="text-destructive">*</span>
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
                                    :class="{ 'border-destructive dark:border-destructive': form.errors.percentage }"
                                />
                                <InputError :message="form.errors.percentage" class="mt-1" />
                                <p class="text-sm text-muted-foreground mt-1">
                                    {{ t('Enter the percentage of total revenue this crew member should receive (0.00 - 100.00)') }}
                                </p>
                            </div>

                            <!-- Payment Frequency -->
                            <div>
                                <Label for="payment_frequency" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    {{ t('Payment Frequency') }} <span class="text-destructive">*</span>
                                </Label>
                                <Select
                                    id="payment_frequency"
                                    v-model="form.payment_frequency"
                                    :options="paymentFrequencyOptions"
                                    :error="!!form.errors.payment_frequency"
                                />
                                <InputError :message="form.errors.payment_frequency" class="mt-1" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="flex items-center justify-between">
                <!-- Wizard Navigation -->
                <div class="flex items-center space-x-3">
                    <Button
                        v-if="currentStep > 0"
                        type="button"
                        variant="outline"
                        @click="previousStep"
                        :disabled="form.processing"
                    >
                        <Icon name="arrow-left" class="w-4 h-4 mr-2" />
                        {{ t('Previous') }}
                    </Button>
                    <Button
                        v-if="steps && currentStep < steps.length - 1"
                        type="button"
                        @click="nextStep"
                        :disabled="form.processing || isNextDisabled"
                    >
                        {{ t('Next') }}
                        <Icon name="arrow-right" class="w-4 h-4 ml-2" />
                    </Button>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-3">
                    <Button
                        variant="outline"
                        type="button"
                        @click="handleClose"
                        :disabled="form.processing"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        v-if="steps && currentStep === steps.length - 1"
                        type="button"
                        @click="handleSave"
                        :disabled="form.processing || isCreateDisabled"
                    >
                        <Icon v-if="form.processing" name="loader" class="w-4 h-4 mr-2 animate-spin" />
                        {{ t('Create Crew Member') }}
                    </Button>
                </div>
            </div>

            <!-- Step Validation Errors -->
            <div v-if="getStepErrors(activeTab).length > 0 && currentStep > 0" class="mt-4 p-3 bg-destructive/10 border border-destructive rounded-md">
                <div class="flex items-center space-x-2">
                    <Icon name="alert-circle" class="w-4 h-4 text-destructive" />
                    <div class="text-sm font-medium text-destructive">{{ t('Please fix the following errors:') }}</div>
                </div>
                <ul class="mt-2 list-disc list-inside text-sm text-destructive">
                    <li v-for="error in getStepErrors(activeTab)" :key="error">{{ error }}</li>
                </ul>
            </div>
        </DialogContent>
    </Dialog>
</template>
