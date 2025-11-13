<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue';
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
import { useMoney } from '@/composables/useMoney';
import { useI18n } from '@/composables/useI18n';
import crewMembers from '@/routes/panel/crew-members';
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue';

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
    administrative?: boolean;
    notes?: string;
    login_permitted: boolean;
    has_existing_account?: boolean;
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
const { t } = useI18n();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'saved': [];
    'remove': [crewMember: CrewMember];
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

// Track original email to detect changes
const originalEmail = ref<string>('');

// Email checking state
const emailExists = ref<boolean | null>(null);
const existingUser = ref<{ id: number; name: string; email: string } | null>(null);
const checkingEmail = ref(false);
const emailChecked = ref(false);
const emailChanged = ref(false);

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
    skip_salary: false,
    compensation_type: 'fixed',
    fixed_amount: null as number | null,
    percentage: null as number | null,
    currency: 'EUR',
    payment_frequency: 'monthly',
    status: 'active',
    administrative: false,
    notes: '',
    login_permitted: false,
});

// Wizard state
const currentStep = ref(0);
const hasInteracted = ref(false);

// Determine wizard steps - always show 3 steps
const steps = computed(() => {
    return ['crew-info', 'salary', 'system-access'];
});

// Active tab computed property
const activeTab = computed(() => {
    return steps.value[currentStep.value];
});

// Step validation state
const stepValidation = ref<Record<string, { valid: boolean; errors: string[] }>>({
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

// Convert to Select component options format
const positionOptions = computed(() => {
    return props.positions.map(position => ({
        value: position.id,
        label: position.name
    }));
});

const statusOptions = computed(() => {
    return Object.entries(props.statuses).map(([value, label]) => ({
        value,
        label: label as string
    }));
});

const compensationTypeOptions = computed(() => {
    return [
        { value: 'fixed', label: t('Fixed Salary') },
        { value: 'percentage', label: t('Percentage of Revenue') }
    ];
});

const paymentFrequencyOptions = computed(() => {
    return Object.entries(props.paymentFrequencies).map(([value, label]) => ({
        value,
        label: label as string
    }));
});

// Validation function
const validateStep = (step: string) => {
    const errors: string[] = [];

    if (step === 'crew-info') {
        if (!form.name?.trim()) errors.push('Name is required');
        if (!form.hire_date) errors.push('Hire date is required');
        if (!form.position_id) errors.push('Position is required');
        if (!form.status) errors.push('Status is required');
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
        // Only validate email if system access is enabled
        if (form.login_permitted) {
            // Email is required when enabling system access
            if (!form.email?.trim()) {
                errors.push('Email is required when system access is enabled');
            }
            // Basic email format validation
            else if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
                errors.push('Please enter a valid email address');
            }
            // Check if email was checked and exists (and is different from current user)
            else if (emailChecked.value && emailExists.value && existingUser.value) {
                // Only error if the existing user is different from current crew member
                if (existingUser.value.id !== props.crewMember?.id) {
                    errors.push('This email is already registered to another user');
                }
            }
            // If email is being checked, wait for result
            else if (checkingEmail.value) {
                // Don't show error while checking - wait for result
            }
            // If email is checked and available, step is valid
            else if (emailChecked.value && !emailExists.value && form.email && form.email !== originalEmail.value) {
                // Email is available - step is valid
            }
        }
        // If system access is disabled, step is always valid
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

// Check if a step is valid
const isStepValid = (step: string) => {
    if (step === 'system-access' && form.login_permitted) {
        // For system-access step, if email is checked and available, step is valid
        if (emailChecked.value && !emailExists.value && form.email && form.email !== originalEmail.value) {
            return true;
        }
        // If email is being checked, wait for result
        if (checkingEmail.value) {
            return false;
        }
        // If email exists and belongs to another user, step is invalid
        if (emailChecked.value && emailExists.value && existingUser.value && existingUser.value.id !== props.crewMember?.id) {
            return false;
        }
    }
    return stepValidation.value[step]?.valid || false;
};

// Navigation functions - allow free navigation between steps
const goToStep = (stepIndex: number) => {
    if (stepIndex >= 0 && stepIndex < steps.value.length) {
        currentStep.value = stepIndex;
        // Validate the new step after navigation (if user has interacted)
        nextTick(() => {
            if (hasInteracted.value) {
                validateStep(activeTab.value);
            }
        });
    }
};

// Next step - allow navigation without validation (users can save later)
const nextStep = () => {
    if (currentStep.value < steps.value.length - 1) {
        currentStep.value++;
        // Validate the new step after navigation
        nextTick(() => {
            if (hasInteracted.value) {
                validateStep(activeTab.value);
            }
        });
    }
};

// Previous step
const previousStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

// Check if email exists (when email is changed)
const checkEmailExists = async () => {
    if (!form.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
        emailExists.value = null;
        existingUser.value = null;
        emailChecked.value = false;
        return;
    }

    // Don't check if email hasn't changed
    if (form.email === originalEmail.value) {
        emailExists.value = null;
        existingUser.value = null;
        emailChecked.value = false;
        return;
    }

    checkingEmail.value = true;
    try {
        const vesselId = getCurrentVesselId();
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
            existingUser.value = data.user || null;
            emailChecked.value = true;

            // Re-validate the step after email check completes
            if (hasInteracted.value && activeTab.value === 'system-access') {
                nextTick(() => validateStep('system-access'));
            }
            emailChanged.value = true;
        } else {
            console.error('Failed to check email:', response.status, response.statusText);
        }
    } catch (error) {
        console.error('Error checking email:', error);
    } finally {
        checkingEmail.value = false;
    }
};

// Computed to check if user can disable system access (only if they don't have existing account)
const canDisableSystemAccess = computed(() => {
    // If user has existing account, they cannot disable system access - must be removed
    return !hasExistingAccount.value;
});

// Auto-validate steps when form fields change
watch(() => [
    form.name,
    form.hire_date,
    form.position_id,
    form.status
], () => {
    if (hasInteracted.value && activeTab.value === 'crew-info') {
        nextTick(() => validateStep('crew-info'));
    }
});

watch(() => [
    form.skip_salary,
    form.compensation_type,
    form.fixed_amount,
    form.percentage,
    form.currency,
    form.payment_frequency
], () => {
    if (hasInteracted.value && activeTab.value === 'salary') {
        nextTick(() => validateStep('salary'));
    }
});

watch(() => form.email, () => {
    // Reset email check state when email changes
    if (form.email !== originalEmail.value) {
        emailChecked.value = false;
        emailExists.value = null;
        existingUser.value = null;
        emailChanged.value = true;
    } else {
        emailChanged.value = false;
    }

    // Auto-check email when user finishes typing (debounced) - only in system-access step
    if (form.email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email) && form.email !== originalEmail.value && activeTab.value === 'system-access') {
        const timeout = setTimeout(() => {
            checkEmailExists();
        }, 500);
        return () => clearTimeout(timeout);
    }

    // Validate system-access step when email changes
    if (hasInteracted.value && activeTab.value === 'system-access') {
        nextTick(() => validateStep('system-access'));
    }
});

watch(() => form.login_permitted, (newValue) => {
    // When enabling access, ensure email is checked if provided
    if (newValue && form.email && form.email !== originalEmail.value) {
        checkEmailExists();
    }
    // Don't validate immediately when switching - only validate when user tries to proceed
    // This prevents showing "Email is required" error immediately after enabling access
});

// Reset form when modal opens
const resetForm = () => {
    if (props.crewMember) {
        // Populate form for editing
        form.name = props.crewMember.name;
        form.email = props.crewMember.email || '';
        originalEmail.value = props.crewMember.email || '';
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
            form.skip_salary = false;
        } else {
            form.skip_salary = true;
        }
        form.status = props.crewMember.status;
        form.administrative = props.crewMember.administrative || false;
        form.notes = props.crewMember.notes || '';
        form.login_permitted = props.crewMember.login_permitted || false;
    }

    form.clearErrors();
    // Reset wizard state
    currentStep.value = 0;
    hasInteracted.value = false;
    emailExists.value = null;
    existingUser.value = null;
    checkingEmail.value = false;
    emailChecked.value = false;
    emailChanged.value = false;
    stepValidation.value = {
        'crew-info': { valid: false, errors: [] },
        'salary': { valid: false, errors: [] },
        'system-access': { valid: false, errors: [] }
    };
};

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        resetForm();
    }
});

const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

// Save current step only
const saveCurrentStep = () => {
    // Prevent double submission
    if (form.processing) {
        return;
    }

    if (props.crewMember) {
        hasInteracted.value = true;

        // Validate only the current step
        const currentStepValid = validateStep(activeTab.value);

        if (!currentStepValid) {
            return;
        }

        // If enabling access and email changed, ensure email was checked
        if (form.login_permitted && activeTab.value === 'system-access' && emailChanged.value && !emailChecked.value) {
            // Check email now
            checkEmailExists().then(() => {
                if (!validateStep('system-access')) {
                    return;
                }
                // Continue with save
                performSave();
            });
            return;
        }

        performSave();
    }
};

// Save all steps (original behavior - kept for final step)
const handleSave = () => {
    saveCurrentStep();
};

const performSave = () => {
    const vesselId = getCurrentVesselId();
    if (!vesselId) {
        console.error('Could not determine vessel ID');
        return;
    }

    // Prepare form data with all current form values
    const formData = { ...form.data() };

    // Remove salary fields if salary is skipped
    if (formData.skip_salary) {
        delete (formData as any).compensation_type;
        delete (formData as any).fixed_amount;
        delete (formData as any).percentage;
        delete (formData as any).currency;
        delete (formData as any).payment_frequency;
    }

    // Only include email if it changed or if enabling access
    if (!emailChanged.value && form.email === originalEmail.value) {
        // Email hasn't changed, don't send it (unless we're on system-access step and login_permitted is true)
        if (activeTab.value !== 'system-access' || !form.login_permitted) {
            delete (formData as any).email;
        }
    }

    form.transform(() => formData).put(crewMembers.update.url({ vessel: vesselId, crewMember: props.crewMember!.id }), {
        preserveScroll: false,
        onSuccess: () => {
            // Don't reset form or close modal - just emit saved event to refresh data
            emit('saved');
            // Mark current step as saved
            stepValidation.value[activeTab.value] = {
                valid: true,
                errors: []
            };
        },
        onError: (errors) => {
            console.error('Update crew member errors:', errors);
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
                <DialogTitle>{{ t('Edit Crew Member') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Update crew member information using the step-by-step wizard') }}
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
                                    activeTab === 'crew-info'
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : currentStep > 0 && isStepValid('crew-info')
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-muted bg-muted text-muted-foreground'
                                ]"
                            >
                                <span class="font-semibold">{{ currentStep > 0 && isStepValid('crew-info') ? '✓' : '1' }}</span>
                            </button>
                            <div class="ml-2 text-sm">
                                <div class="font-medium text-foreground">{{ t('Crew Information') }}</div>
                                <div class="text-xs text-muted-foreground">{{ t('Basic details & position') }}</div>
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
                                @click="goToStep(steps.indexOf('salary'))"
                                :class="[
                                    'relative flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all',
                                    activeTab === 'salary'
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : currentStep > steps.indexOf('salary') && isStepValid('salary')
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-muted bg-muted text-muted-foreground'
                                ]"
                            >
                                <span class="font-semibold">{{ currentStep > steps.indexOf('salary') && isStepValid('salary') ? '✓' : '2' }}</span>
                            </button>
                            <div class="ml-2 text-sm">
                                <div class="font-medium text-foreground">{{ t('Salary & Compensation') }}</div>
                                <div class="text-xs text-muted-foreground">{{ t('Payment details') }}</div>
                            </div>
                        </div>

                        <!-- Connector to System Access -->
                        <div
                            class="flex-1 h-0.5 mx-2"
                            :class="currentStep > steps.indexOf('salary') ? 'bg-primary' : 'bg-muted'"
                        ></div>

                        <!-- Step 3: System Access -->
                        <div class="flex items-center flex-1">
                            <button
                                type="button"
                                @click="goToStep(steps.indexOf('system-access'))"
                                :class="[
                                    'relative flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all',
                                    activeTab === 'system-access'
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : currentStep > steps.indexOf('system-access') && isStepValid('system-access')
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-muted bg-muted text-muted-foreground'
                                ]"
                            >
                                <span class="font-semibold">{{ currentStep > steps.indexOf('system-access') && isStepValid('system-access') ? '✓' : '3' }}</span>
                            </button>
                            <div class="ml-2 text-sm">
                                <div class="font-medium text-foreground">{{ t('System Access') }}</div>
                                <div class="text-xs text-muted-foreground">{{ t('Access settings') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step Content -->
                <div class="space-y-6">
                    <!-- Step 1: Crew Information -->
                    <div v-show="activeTab === 'crew-info'" class="grid grid-cols-1 md:grid-cols-2 gap-6" role="tabpanel">
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
                                :class="{ 'border-destructive dark:border-destructive': form.errors.name || getStepErrors('crew-info').some(e => e.includes('Name')) }"
                            />
                            <InputError :message="form.errors.name" class="mt-1" />
                            <div v-if="getStepErrors('crew-info').some(e => e.includes('Name'))" class="text-sm text-destructive mt-1">
                                {{ getStepErrors('crew-info').find(e => e.includes('Name')) }}
                            </div>
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
                                :class="{ 'border-destructive dark:border-destructive': form.errors.hire_date || getStepErrors('crew-info').some(e => e.includes('Hire date')) }"
                            />
                            <InputError :message="form.errors.hire_date" class="mt-1" />
                            <div v-if="getStepErrors('crew-info').some(e => e.includes('Hire date'))" class="text-sm text-destructive mt-1">
                                {{ getStepErrors('crew-info').find(e => e.includes('Hire date')) }}
                            </div>
                        </div>

                        <!-- Administrative -->
                        <div class="md:col-span-2">
                            <div class="flex items-center space-x-2">
                                <input
                                    id="administrative"
                                    v-model="form.administrative"
                                    type="checkbox"
                                    class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                />
                                <Label for="administrative" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    {{ t('Administrative Member') }}
                                </Label>
                            </div>
                            <p class="text-sm text-muted-foreground mt-1">
                                {{ t('Check this if this member is an administrative member rather than a crew member') }}
                            </p>
                            <InputError :message="form.errors.administrative" class="mt-1" />
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

                        <!-- Status -->
                        <div>
                            <Label for="status" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Status') }} <span class="text-destructive">*</span>
                            </Label>
                            <Select
                                id="status"
                                v-model="form.status"
                                :options="statusOptions"
                                :placeholder="t('Choose a status')"
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

                    <!-- Step 2: Salary & Compensation -->
                    <div v-show="activeTab === 'salary'" class="space-y-6" role="tabpanel">
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
                                        {{ t('Skip salary configuration') }}
                                    </label>
                                </div>
                                <p class="text-sm text-muted-foreground mt-1">
                                    {{ t('Check this if you don\'t want to set up salary compensation for this crew member.') }}
                                </p>
                            </div>

                            <!-- Salary fields (only show when not skipped) -->
                            <template v-if="!form.skip_salary">
                                <!-- Compensation Type -->
                                <div>
                                    <Label for="compensation_type" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                        {{ t('Compensation Type') }} <span class="text-destructive">*</span>
                                    </Label>
                                    <Select
                                        id="compensation_type"
                                        v-model="form.compensation_type"
                                        :options="compensationTypeOptions"
                                        :error="!!(form.errors.compensation_type || getStepErrors('salary').some(e => e.includes('Compensation type')))"
                                    />
                                    <InputError :message="form.errors.compensation_type" class="mt-1" />
                                    <div v-if="getStepErrors('salary').some(e => e.includes('Compensation type'))" class="text-sm text-destructive mt-1">
                                        {{ getStepErrors('salary').find(e => e.includes('Compensation type')) }}
                                    </div>
                                </div>

                                <!-- Fixed Amount -->
                                <div v-show="form.compensation_type === 'fixed'">
                                    <MoneyInputWithLabel
                                        v-model="form.fixed_amount"
                                        :label="t('Fixed Salary Amount')"
                                        :currency="form.currency"
                                        :decimals="2"
                                        placeholder="0,00"
                                        :error="form.errors.fixed_amount || getStepErrors('salary').find(e => e.includes('Fixed amount'))"
                                        :show-currency="false"
                                        required
                                    />
                                </div>

                                <!-- Percentage -->
                                <div v-show="form.compensation_type === 'percentage'">
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
                                        :class="{ 'border-destructive dark:border-destructive': form.errors.percentage || getStepErrors('salary').some(e => e.includes('Percentage')) }"
                                    />
                                    <InputError :message="form.errors.percentage" class="mt-1" />
                                    <div v-if="getStepErrors('salary').some(e => e.includes('Percentage'))" class="text-sm text-destructive mt-1">
                                        {{ getStepErrors('salary').find(e => e.includes('Percentage')) }}
                                    </div>
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
                                        :error="!!(form.errors.currency || getStepErrors('salary').some(e => e.includes('Currency')))"
                                    />
                                    <InputError :message="form.errors.currency" class="mt-1" />
                                    <div v-if="getStepErrors('salary').some(e => e.includes('Currency'))" class="text-sm text-destructive mt-1">
                                        {{ getStepErrors('salary').find(e => e.includes('Currency')) }}
                                    </div>
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
                                        :error="!!(form.errors.payment_frequency || getStepErrors('salary').some(e => e.includes('Payment frequency')))"
                                    />
                                    <InputError :message="form.errors.payment_frequency" class="mt-1" />
                                    <div v-if="getStepErrors('salary').some(e => e.includes('Payment frequency'))" class="text-sm text-destructive mt-1">
                                        {{ getStepErrors('salary').find(e => e.includes('Payment frequency')) }}
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Step 3: System Access -->
                    <div v-show="activeTab === 'system-access'" class="space-y-6" role="tabpanel">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- System Access Switch -->
                            <div class="md:col-span-2">
                                <div class="flex items-center justify-between p-4 border rounded-lg bg-card">
                                    <div class="flex-1">
                                        <Label for="login_permitted" class="text-sm font-medium text-card-foreground dark:text-card-foreground mb-1 block">
                                            {{ t('System Access') }}
                                        </Label>
                                        <p class="text-sm text-muted-foreground">
                                            {{ hasExistingAccess
                                                ? t('This crew member currently has system access. To remove access, you must remove the crew member.')
                                                : t('Allow this crew member to log into the system.') }}
                                        </p>
                                    </div>
                                    <div class="ml-4">
                                        <Switch
                                            id="login_permitted"
                                            :checked="form.login_permitted"
                                            :disabled="hasExistingAccess && hasExistingAccount"
                                            @update:checked="(val) => {
                                                if (hasExistingAccess && hasExistingAccount) {
                                                    // Cannot disable if user has existing account
                                                    return;
                                                }
                                                form.login_permitted = val;
                                                // Don't validate immediately - let user enter email first
                                                // Validation will happen when they try to proceed
                                            }"
                                        />
                                    </div>
                                </div>

                                <!-- Warning if user has existing account -->
                                <div v-if="hasExistingAccess && hasExistingAccount" class="mt-3 p-3 bg-warning/10 border border-warning/20 rounded-lg">
                                    <p class="text-sm text-warning-foreground">
                                        {{ t('This user has an existing account. To remove system access, you must remove the crew member from the vessel.') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Email Field (only show if system access is enabled or user has existing access) -->
                            <div v-if="form.login_permitted || hasExistingAccess" class="md:col-span-2">
                                <Label for="email_access" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    {{ t('Email') }} <span v-if="form.login_permitted" class="text-destructive">*</span>
                                </Label>
                                <div class="relative">
                                    <Input
                                        id="email_access"
                                        v-model="form.email"
                                        type="email"
                                        :placeholder="t('Enter email address')"
                                        :disabled="hasExistingAccount"
                                        :class="{
                                            'border-destructive dark:border-destructive': form.errors.email || (hasInteracted && getStepErrors('system-access').some(e => e.includes('Email'))) || (emailChecked && emailExists && existingUser && existingUser.id !== props.crewMember?.id),
                                            'border-green-600 dark:border-green-400': emailChecked && !emailExists && form.email && form.email !== originalEmail,
                                            'opacity-60 cursor-not-allowed': hasExistingAccount,
                                            'pr-10': checkingEmail || (emailChecked && !emailExists)
                                        }"
                                    />
                                    <!-- Spinner/Status Icon -->
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                        <Icon
                                            v-if="checkingEmail"
                                            name="loader"
                                            class="w-4 h-4 animate-spin text-primary"
                                        />
                                        <Icon
                                            v-else-if="emailChecked && !emailExists && form.email && form.email !== originalEmail"
                                            name="check-circle"
                                            class="w-4 h-4 text-green-600 dark:text-green-400"
                                        />
                                        <Icon
                                            v-else-if="emailChecked && emailExists && existingUser && existingUser.id !== props.crewMember?.id"
                                            name="x-circle"
                                            class="w-4 h-4 text-destructive"
                                        />
                                    </div>
                                </div>
                                <InputError :message="form.errors.email" class="mt-1" />
                                <div v-if="hasInteracted && getStepErrors('system-access').some(e => e.includes('Email'))" class="text-sm text-destructive mt-1">
                                    {{ getStepErrors('system-access').find(e => e.includes('Email')) }}
                                </div>
                                <div v-if="emailChecked && emailExists && existingUser && existingUser.id !== props.crewMember?.id" class="text-sm text-destructive mt-1">
                                    {{ t('This email is already registered to another user') }}
                                </div>
                                <div v-if="emailChecked && !emailExists && form.email && form.email !== originalEmail" class="text-sm text-green-600 dark:text-green-400 mt-1">
                                    {{ t('Email is available') }}
                                </div>
                                <p v-if="hasExistingAccount" class="text-sm text-muted-foreground mt-1">
                                    {{ t('Email cannot be changed for existing accounts.') }}
                                </p>
                            </div>

                            <!-- Remove Crew Member Option (only if user has existing access) -->
                            <div v-if="hasExistingAccess && hasExistingAccount" class="md:col-span-2 mt-4">
                                <div class="p-4 border border-destructive/20 rounded-lg bg-destructive/5">
                                    <h4 class="text-sm font-medium text-destructive mb-2">
                                        {{ t('Remove Crew Member') }}
                                    </h4>
                                    <p class="text-sm text-muted-foreground mb-3">
                                        {{ t('To remove system access for this crew member, you must remove them from the vessel. This action cannot be undone.') }}
                                    </p>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        @click="emit('remove', props.crewMember!)"
                                    >
                                        <Icon name="trash" class="w-4 h-4 mr-2" />
                                        {{ t('Remove Crew Member') }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex items-center justify-between mt-6">
                <div class="flex items-center space-x-4">
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
                </div>

                <div class="flex items-center space-x-4">
                    <Button
                        variant="outline"
                        @click="handleClose"
                        :disabled="form.processing"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <!-- Save button for current step -->
                    <Button
                        type="button"
                        @click="saveCurrentStep"
                        :disabled="form.processing || (hasInteracted && !isStepValid(activeTab))"
                        class="bg-primary text-primary-foreground hover:bg-primary/90"
                    >
                        <Icon v-if="form.processing" name="loader" class="w-4 h-4 mr-2 animate-spin" />
                        {{ form.processing ? t('Saving...') : t('Save') }}
                    </Button>
                    <!-- Next button (only show if not on last step) -->
                    <Button
                        v-if="currentStep < steps.length - 1"
                        type="button"
                        variant="outline"
                        @click="nextStep"
                        :disabled="form.processing"
                    >
                        {{ t('Next') }}
                        <Icon name="arrow-right" class="w-4 h-4 ml-2" />
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
