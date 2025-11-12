<script setup lang="ts">
import { watch, computed, ref, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DateInput } from '@/components/ui/date-input';
import { Select } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
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
    skip_salary: true, // Default to skipping salary
    compensation_type: 'fixed',
    fixed_amount: null as number | null,
    percentage: null as number | null,
    currency: 'EUR',
    payment_frequency: 'monthly',
    status: 'active',
    notes: '',
    create_without_email: false, // Allow creating without email/account access
});

// Email checking state
const emailExists = ref<boolean | null>(null);
const existingUser = ref<{ id: number; name: string; email: string } | null>(null);
const checkingEmail = ref(false);
const emailChecked = ref(false);
const createWithoutEmail = ref(false);

// Wizard steps - always start with access choice (email vs no email)
const steps = computed(() => {
    // Always start with access-choice step
    const baseSteps = ['access-choice'];

    // If creating without email, skip email step and go straight to crew-info
    if (createWithoutEmail.value) {
        return [...baseSteps, 'crew-info', 'salary'];
    }

    // If user has progressed past access-choice, include email step
    // This allows navigation even if email hasn't been checked yet
    if (currentStep.value > 0 || emailChecked.value) {
        // If email exists, only show crew-info after email (position is in crew-info)
        if (emailExists.value) {
            return [...baseSteps, 'email', 'crew-info'];
        }

        // If email doesn't exist, show full flow
        return [...baseSteps, 'email', 'crew-info', 'salary'];
    }

    // If email not checked yet and still on first step, only show access-choice
    return baseSteps;
});

// Active tab for the modal (synced with currentStep)
const activeTab = computed(() => {
    if (!steps.value || steps.value.length === 0) {
        return 'access-choice';
    }
    return steps.value[currentStep.value] || 'access-choice';
});

// Track validation status for each step
const stepValidation = ref({
    'access-choice': { valid: false, errors: [] },
    'email': { valid: false, errors: [] },
    'crew-info': { valid: false, errors: [] },
    'salary': { valid: false, errors: [] }
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

    if (step === 'access-choice') {
        // Access choice is always valid - user just needs to choose
        // No validation needed, but we mark it as valid once they've made a choice
        return true;
    } else if (step === 'email') {
        if (!form.email?.trim()) {
            errors.push('Email is required');
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
            errors.push('Please enter a valid email address');
        }
    } else if (step === 'crew-info') {
        if (!form.name?.trim()) errors.push('Name is required');
        if (!form.position_id) errors.push('Position is required');
        if (!form.hire_date) errors.push('Hire date is required');
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
    }

    const validation = stepValidation.value as Record<string, { valid: boolean; errors: string[] }>;
    // Only show errors if user has interacted (tried to advance)
    validation[step] = {
        valid: errors.length === 0,
        errors: hasInteracted.value ? errors : [] // Only show errors after interaction
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
    // Mark that user has interacted when trying to advance
    hasInteracted.value = true;

    // Validate current step before advancing
    const currentStepValid = validateStep(activeTab.value);
    if (!currentStepValid) {
        // Re-validate to show errors now that hasInteracted is true
        validateStep(activeTab.value);
        return;
    }

    if (currentStep.value < steps.value.length - 1) {
        currentStep.value++;
    }
};

// Handle create without email change
const handleCreateWithoutEmailChange = () => {
    form.create_without_email = createWithoutEmail.value;
    if (createWithoutEmail.value) {
        // Reset email-related state
        emailChecked.value = true;
        emailExists.value = false;
        form.email = '';
        // Don't automatically advance - user must click Continue
    } else {
        // Reset state
        emailChecked.value = false;
        emailExists.value = null;
        // Stay on access-choice step - user can change their mind
    }
};

// Update checkEmailExists to move to next step after checking
const checkEmailExistsAndContinue = async () => {
    // Mark that user has interacted when checking email
    hasInteracted.value = true;

    await checkEmailExists();
    if (emailChecked.value && validateStep('email')) {
        // Automatically move to next step
        nextTick(() => {
            if (currentStep.value < steps.value.length - 1) {
                currentStep.value++;
            }
        });
    } else if (emailChecked.value) {
        // Re-validate to show errors now that hasInteracted is true
        validateStep('email');
    }
};

// Previous step
const previousStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

// Handle continue from access-choice step
const handleAccessChoiceContinue = () => {
    // Mark that user has interacted
    hasInteracted.value = true;

    // Validate access-choice step (always valid, but we need to mark it)
    validateStep('access-choice');

    // Move to next step - use nextTick to ensure reactive updates complete
    if (createWithoutEmail.value) {
        // Skip email step, go directly to crew-info step
        nextTick(() => {
            currentStep.value = 1; // access-choice is 0, crew-info will be 1
        });
    } else {
        // Go to email step - temporarily mark as checked to allow steps to include email
        // This will be properly validated when email is actually entered
        emailChecked.value = true;
        nextTick(() => {
            // Now advance to email step (index 1)
            currentStep.value = 1;
        });
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

// Reset form function - used both on close and after successful save
const resetForm = () => {
    form.reset();
    form.clearErrors();
    // Reset all form fields explicitly
    form.name = '';
    form.email = '';
    form.phone = '';
    form.date_of_birth = '';
    form.hire_date = getTodayDate();
    form.position_id = null;
    form.skip_salary = true;
    form.compensation_type = 'fixed';
    form.fixed_amount = null;
    form.percentage = null;
    form.currency = 'EUR';
    form.payment_frequency = 'monthly';
    form.status = 'active';
    form.notes = '';
    form.create_without_email = false;

    // Reset wizard state
    currentStep.value = 0;
    createWithoutEmail.value = false;
    emailChecked.value = false;
    emailExists.value = null;
    existingUser.value = null;
    checkingEmail.value = false;
    hasInteracted.value = false;
    stepValidation.value = {
        'access-choice': { valid: false, errors: [] },
        'email': { valid: false, errors: [] },
        'crew-info': { valid: false, errors: [] },
        'salary': { valid: false, errors: [] }
    };
};

// Reset form when modal opens
watch(() => props.open, (isOpen: boolean) => {
    if (isOpen) {
        resetForm();
    }
});

// Auto-validate steps when form fields change (only after user interaction)
watch(() => [
    form.email
], () => {
    if (hasInteracted.value && currentStep.value === 0) {
        nextTick(() => validateStep('email'));
    }
});

watch(() => [
    form.name,
    form.position_id,
    form.hire_date
], () => {
    if (hasInteracted.value && activeTab.value === 'crew-info') {
        nextTick(() => validateStep('crew-info'));
    }
});


// Watch for skip_salary changes and step changes
watch(() => form.skip_salary, () => {
    // Just clear validation when toggle changes
    stepValidation.value['salary'] = { valid: false, errors: [] };
});

// Set skip_salary to true when moving to salary step
watch(() => currentStep.value, (newStep, oldStep) => {
    if (activeTab.value === 'salary' && !form.skip_salary) {
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


// Get current vessel ID from URL (hashed ID)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel ID (alphanumeric string after /panel/)
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

// Check if email exists
const checkEmailExists = async () => {
    if (!form.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
        return;
    }

    checkingEmail.value = true;
    try {
        // Get vessel ID from URL path (it's the hashed ID in the URL)
        const path = window.location.pathname;
        const vesselMatch = path.match(/\/panel\/([^/]+)/);
        const vesselId = vesselMatch ? vesselMatch[1] : null;

        if (!vesselId) {
            console.error('Could not determine vessel ID from URL');
            checkingEmail.value = false;
            return;
        }

        const response = await fetch(`/panel/${vesselId}/api/crew-members/check-email`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ email: form.email }),
        });

        if (response.ok) {
            const data = await response.json();
            emailExists.value = data.exists;
            existingUser.value = data.user;
            emailChecked.value = true;

            // If user exists, pre-fill name
            if (data.exists && data.user) {
                form.name = data.user.name;
            }
        } else {
            console.error('Failed to check email:', response.status, response.statusText);
        }
    } catch (error) {
        console.error('Error checking email:', error);
    } finally {
        checkingEmail.value = false;
    }
};

const handleSave = () => {
    // Prevent double submission
    if (form.processing) {
        return;
    }

    // Mark that user has interacted when trying to save
    hasInteracted.value = true;

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

    // Ensure position is set before saving (double-check validation)
    if (!form.position_id) {
        // Navigate to crew-info step if somehow position is missing
        const crewInfoStepIndex = steps.value.indexOf('crew-info');
        if (crewInfoStepIndex !== -1) {
            currentStep.value = crewInfoStepIndex;
        }
        validateStep('crew-info');
        return;
    }

    // Prepare form data
    const formData = { ...form.data() };

    // Include create_without_email flag
    formData.create_without_email = createWithoutEmail.value;

    // Remove salary fields if salary is skipped
    if (formData.skip_salary) {
        delete (formData as any).compensation_type;
        delete (formData as any).fixed_amount;
        delete (formData as any).percentage;
        delete (formData as any).currency;
        delete (formData as any).payment_frequency;
    }

    // If creating without email, remove email from form data
    if (createWithoutEmail.value) {
        delete (formData as any).email;
    }

    form.transform(() => formData).post(crewMembers.store.url({ vessel: getCurrentVesselId() }), {
        onSuccess: () => {
            // Reset form completely after successful creation
            resetForm();
            emit('saved');
            emit('update:open', false);
        },
        onError: () => {
            // Don't reset on error - keep form data for user to fix
        },
    });
};

const handleClose = () => {
    emit('update:open', false);
    resetForm();
};

</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{{ t('Create New Crew Member') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Add a new crew member to your vessel management system') }}
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
                <!-- Dynamic Wizard Progress Bar - Always show -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <!-- Step 1: Access Choice (always first) -->
                        <div class="flex items-center flex-1">
                            <button
                                type="button"
                                @click="goToStep(0)"
                                :class="[
                                    'relative flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all',
                                    currentStep === 0
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : currentStep > 0
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-muted bg-muted text-muted-foreground'
                                ]"
                            >
                                <span class="font-semibold">{{ currentStep > 0 ? '✓' : '1' }}</span>
                            </button>
                            <div class="ml-2 text-sm">
                                <div class="font-medium text-foreground">{{ t('Access Type') }}</div>
                                <div class="text-xs text-muted-foreground">{{ createWithoutEmail ? t('No account access') : t('With email invitation') }}</div>
                            </div>
                        </div>

                        <!-- Connector to Email (only if not creating without email) -->
                        <div
                            v-if="!createWithoutEmail && steps.includes('email')"
                            class="flex-1 h-0.5 mx-2"
                            :class="currentStep >= 1 ? 'bg-primary' : 'bg-muted'"
                        ></div>

                        <!-- Step 2: Email (only if not creating without email) -->
                        <div v-if="!createWithoutEmail && steps.includes('email')" class="flex items-center flex-1">
                            <button
                                type="button"
                                @click="goToStep(steps.indexOf('email'))"
                                :class="[
                                    'relative flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all',
                                    activeTab === 'email'
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : currentStep > steps.indexOf('email') && emailChecked
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-muted bg-muted text-muted-foreground'
                                ]"
                            >
                                <span class="font-semibold">{{ currentStep > steps.indexOf('email') && emailChecked ? '✓' : '2' }}</span>
                            </button>
                            <div class="ml-2 text-sm">
                                <div class="font-medium text-foreground">{{ t('Email') }}</div>
                                <div class="text-xs text-muted-foreground">{{ form.email || t('Enter email') }}</div>
                            </div>
                        </div>

                        <!-- Connector to Crew Info -->
                        <div
                            v-if="steps.includes('crew-info')"
                            class="flex-1 h-0.5 mx-2"
                            :class="currentStep > (createWithoutEmail ? 0 : steps.indexOf('email')) ? 'bg-primary' : 'bg-muted'"
                        ></div>

                        <!-- Step 3 (or 2): Crew Information -->
                        <div v-if="steps.includes('crew-info')" class="flex items-center flex-1">
                            <button
                                type="button"
                                @click="goToStep(steps.indexOf('crew-info'))"
                                :class="[
                                    'relative flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all',
                                    activeTab === 'crew-info'
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : currentStep > steps.indexOf('crew-info') && isStepValid('crew-info')
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-muted bg-muted text-muted-foreground'
                                ]"
                            >
                                <span class="font-semibold">{{ currentStep > steps.indexOf('crew-info') && isStepValid('crew-info') ? '✓' : (createWithoutEmail ? '2' : '3') }}</span>
                            </button>
                            <div class="ml-2 text-sm">
                                <div class="font-medium text-foreground">{{ t('Crew Information') }}</div>
                                <div class="text-xs text-muted-foreground">{{ t('Basic details & position') }}</div>
                            </div>
                        </div>

                        <!-- Connector to Salary (only for new users) -->
                        <div
                            v-if="steps.includes('salary')"
                            class="flex-1 h-0.5 mx-2"
                            :class="currentStep > steps.indexOf('crew-info') ? 'bg-primary' : 'bg-muted'"
                        ></div>

                        <!-- Step 4 (or 3): Salary & Compensation (only for new users) -->
                        <div v-if="steps.includes('salary')" class="flex items-center flex-1">
                            <button
                                type="button"
                                @click="goToStep(steps.indexOf('salary'))"
                                :class="[
                                    'relative flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all',
                                    activeTab === 'salary'
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : isStepValid('salary') && currentStep > steps.indexOf('salary')
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-muted bg-muted text-muted-foreground'
                                ]"
                            >
                                <span class="font-semibold">{{ createWithoutEmail ? '3' : '4' }}</span>
                            </button>
                            <div class="ml-2 text-sm">
                                <div class="font-medium text-foreground">{{ t('Salary & Compensation') }}</div>
                                <div class="text-xs text-muted-foreground">{{ t('Payment details') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="handleSave" class="space-y-6">
                    <!-- Access Choice Step - First step always -->
                    <div v-show="activeTab === 'access-choice'" class="space-y-6" role="tabpanel" id="access-choice-tabpanel">
                        <div class="text-center mb-6">
                            <h3 class="text-lg font-medium text-card-foreground mb-2">{{ t('Choose Access Type') }}</h3>
                            <p class="text-muted-foreground">{{ t('Select how this crew member will access the system') }}</p>
                        </div>

                        <!-- Switch to create without email -->
                        <div class="flex items-center justify-between p-6 border border-input rounded-lg bg-muted/30 hover:bg-muted/50 transition-colors">
                            <div class="flex-1">
                                <Label for="create_without_email" class="text-base font-medium text-card-foreground dark:text-card-foreground cursor-pointer">
                                    {{ t('Create without email and account access') }}
                                </Label>
                                <p class="text-sm text-muted-foreground mt-2">
                                    {{ t('Create a crew member record without email invitation. They will not have system access.') }}
                                </p>
                            </div>
                            <div class="flex items-center ml-4">
                                <Switch
                                    id="create_without_email"
                                    :checked="createWithoutEmail"
                                    @update:checked="(value) => { createWithoutEmail = value; handleCreateWithoutEmailChange(); }"
                                />
                            </div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                @click="handleClose"
                            >
                                {{ t('Cancel') }}
                            </Button>
                            <Button
                                type="button"
                                @click="handleAccessChoiceContinue"
                            >
                                {{ t('Continue') }}
                                <Icon name="arrow-right" class="w-4 h-4 ml-2" />
                            </Button>
                        </div>
                    </div>

                    <!-- Email Step - Only shown if not creating without email -->
                    <div v-show="activeTab === 'email'" class="space-y-6" role="tabpanel" id="email-tabpanel">
                        <div class="text-center mb-6">
                            <h3 class="text-lg font-medium text-card-foreground mb-2">{{ t('Enter Email Address') }}</h3>
                            <p class="text-muted-foreground">{{ t('Enter the email address to invite a crew member') }}</p>
                        </div>

                        <div>
                            <Label for="email" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Email Address') }} <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                :placeholder="t('Enter email address')"
                                :disabled="checkingEmail"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.email || getStepErrors('email').length > 0 }"
                                @keyup.enter="checkEmailExistsAndContinue"
                            />
                            <InputError :message="form.errors.email" class="mt-1" />
                            <div v-if="getStepErrors('email').length > 0" class="text-sm text-destructive mt-1">
                                {{ getStepErrors('email')[0] }}
                            </div>
                            <div v-if="emailExists !== null && !checkingEmail" class="mt-2">
                                <div v-if="emailExists" class="text-sm text-muted-foreground">
                                    <span class="text-green-600 dark:text-green-400">{{ t('User with this email already exists') }}</span>
                                    <span v-if="existingUser"> - {{ existingUser.name }}</span>
                                </div>
                                <div v-else class="text-sm text-muted-foreground">
                                    <span class="text-blue-600 dark:text-blue-400">{{ t('New user - will be created') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                @click="previousStep"
                            >
                                <Icon name="arrow-left" class="w-4 h-4 mr-2" />
                                {{ t('Back') }}
                            </Button>
                            <Button
                                type="button"
                                @click="checkEmailExistsAndContinue"
                                :disabled="checkingEmail || !form.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)"
                            >
                                <Icon v-if="checkingEmail" name="loader" class="w-4 h-4 mr-2 animate-spin" />
                                {{ checkingEmail ? t('Checking...') : t('Continue') }}
                                <Icon v-if="!checkingEmail" name="arrow-right" class="w-4 h-4 ml-2" />
                            </Button>
                        </div>
                    </div>

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

                        <!-- Position -->
                        <div>
                            <Label for="position_id" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Position') }} <span class="text-destructive">*</span>
                            </Label>
                            <Select
                                id="position_id"
                                v-model="form.position_id"
                                :options="positionOptions"
                                :placeholder="t('Choose a position')"
                                searchable
                                :error="!!(form.errors.position_id || getStepErrors('crew-info').some(e => e.includes('Position')))"
                            />
                            <InputError :message="form.errors.position_id" class="mt-1" />
                            <div v-if="getStepErrors('crew-info').some(e => e.includes('Position'))" class="text-sm text-destructive mt-1">
                                {{ getStepErrors('crew-info').find(e => e.includes('Position')) }}
                            </div>
                        </div>

                        <!-- Email is already entered in first step, so we don't show it here -->

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

                        <div class="flex justify-end gap-3 mt-6">
                            <Button
                                type="button"
                                variant="outline"
                                @click="previousStep"
                            >
                                <Icon name="arrow-left" class="w-4 h-4 mr-2" />
                                {{ t('Back') }}
                            </Button>
                            <Button
                                type="button"
                                @click="nextStep"
                                :disabled="!form.name || !form.position_id || !form.hire_date"
                            >
                                {{ t('Next') }}
                                <Icon name="arrow-right" class="w-4 h-4 ml-2" />
                            </Button>
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

                        <div class="flex justify-end gap-3 mt-6">
                            <Button
                                type="button"
                                variant="outline"
                                @click="previousStep"
                            >
                                <Icon name="arrow-left" class="w-4 h-4 mr-2" />
                                {{ t('Back') }}
                            </Button>
                            <Button
                                type="button"
                                @click="handleSave"
                                :disabled="form.processing"
                            >
                                <Icon v-if="form.processing" name="loader" class="w-4 h-4 mr-2 animate-spin" />
                                {{ form.processing ? t('Creating...') : t('Create Crew Member') }}
                            </Button>
                        </div>
                    </div>
                </form>
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
