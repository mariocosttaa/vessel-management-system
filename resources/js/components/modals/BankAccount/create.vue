<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue';
import { useNotifications } from '@/composables/useNotifications';
import { useI18n } from '@/composables/useI18n';
import bankAccounts from '@/routes/panel/bank-accounts';

interface Country {
    id: number;
    name: string;
    code: string;
}

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
    formatted_display: string;
}

interface Props {
    open: boolean;
    countries: Country[];
    currencies: Currency[];
    statuses: Record<string, string>;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const { addNotification } = useNotifications();
const { t } = useI18n();
const page = usePage();

// Get vessel currency from shared props
const vesselCurrency = computed(() => {
    return (page.props.auth as any)?.current_vessel?.currency_code || 'EUR';
});

// Checkbox states
const useIban = ref(true);
const useAccountNumber = ref(false);
const hasInitialBalance = ref(false);

const form = useForm({
    name: '',
    bank_name: '',
    account_number: '',
    iban: '',
    country_id: null as number | null,
    initial_balance: null as number | null,
    notes: '',
});

// Computed properties
const showIbanField = computed(() => useIban.value);
const showAccountNumberField = computed(() => useAccountNumber.value);
const showInitialBalanceField = computed(() => hasInitialBalance.value);
const showCountrySelect = computed(() => {
    // Show country select only when account_number is used (not IBAN)
    return useAccountNumber.value && !useIban.value;
});

// Convert to Select component options format
const countryOptions = computed(() => {
    const options = [{ value: null, label: t('Select a country') }];
    props.countries.forEach(country => {
        options.push({ value: country.id, label: `${country.name} (${country.code})` });
    });
    return options;
});

// Watch for changes in checkbox states
watch(useIban, (newValue) => {
    if (newValue) {
        // When IBAN is selected, clear account_number
        form.account_number = '';
        // Auto-detect country from IBAN if entered
        if (form.iban) {
            detectCountryFromIban(form.iban);
        }
    }
});

watch(useAccountNumber, (newValue) => {
    if (newValue) {
        // When account_number is selected, clear IBAN
        form.iban = '';
        form.country_id = null;
    }
});

watch(hasInitialBalance, (newValue) => {
    if (!newValue) {
        form.initial_balance = null;
    }
});

// Watch IBAN input to auto-detect country
watch(() => form.iban, (newIban) => {
    if (newIban && useIban.value && !useAccountNumber.value) {
        detectCountryFromIban(newIban);
    }
});

// Function to detect country from IBAN
const detectCountryFromIban = (iban: string) => {
    if (!iban || iban.length < 2) return;

    // Extract country code from IBAN (first 2 characters)
    const countryCode = iban.substring(0, 2).toUpperCase();

    // Find matching country
    const country = props.countries.find(c => c.code.toUpperCase() === countryCode);
    if (country) {
        form.country_id = country.id;
    }
};

// Reset form when modal opens/closes
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.country_id = null;
        form.initial_balance = null;
        form.clearErrors();
        // Reset checkbox states
        useIban.value = true;
        useAccountNumber.value = false;
        hasInitialBalance.value = false;
    }
});

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

const submit = () => {
    // Clear fields based on checkbox selection
    if (!useIban.value) {
        form.iban = '';
    }
    if (!useAccountNumber.value) {
        form.account_number = '';
    }
    if (!hasInitialBalance.value) {
        form.initial_balance = null;
    }

    console.log('Submitting form with data:', {
        name: form.name,
        bank_name: form.bank_name,
        iban: form.iban,
        account_number: form.account_number,
        country_id: form.country_id,
        status: form.status,
        initial_balance: form.initial_balance,
        notes: form.notes,
    });

    form.post(bankAccounts.store.url({ vessel: getCurrentVesselId() }), {
        onSuccess: () => {
            addNotification({
                type: 'success',
                message: `${t('Bank account')} '${form.name}' ${t('has been created successfully.')}`,
            });
            emit('success');
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
            addNotification({
                type: 'error',
                message: t('Failed to create bank account. Please check the form for errors.'),
            });
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ t('Add Bank Account') }}</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Account Name -->
                    <div class="space-y-2">
                        <Label for="name">{{ t('Account Name') }} *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            :placeholder="t('e.g., Main Business Account')"
                            :class="{ 'border-red-500': form.errors.name }"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Bank Name -->
                    <div class="space-y-2">
                        <Label for="bank_name">{{ t('Bank Name') }} *</Label>
                        <Input
                            id="bank_name"
                            v-model="form.bank_name"
                            type="text"
                            :placeholder="t('e.g., Banco Santander')"
                            :class="{ 'border-red-500': form.errors.bank_name }"
                        />
                        <InputError :message="form.errors.bank_name" />
                    </div>
                </div>

                <!-- Account Information Checkboxes -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <input
                                type="checkbox"
                                id="use_iban"
                                v-model="useIban"
                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary focus:ring-offset-2"
                            />
                            <Label for="use_iban" class="text-sm font-medium cursor-pointer">{{ t('Use IBAN') }}</Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <input
                                type="checkbox"
                                id="use_account_number"
                                v-model="useAccountNumber"
                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary focus:ring-offset-2"
                            />
                            <Label for="use_account_number" class="text-sm font-medium cursor-pointer">{{ t('Use Account Number') }}</Label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Account Number -->
                        <div class="space-y-2" v-if="showAccountNumberField">
                            <Label for="account_number">{{ t('Account Number') }} *</Label>
                            <Input
                                id="account_number"
                                v-model="form.account_number"
                                type="text"
                                :placeholder="t('e.g., 1234567890')"
                                :class="{ 'border-red-500': form.errors.account_number }"
                            />
                            <InputError :message="form.errors.account_number" />
                        </div>

                        <!-- IBAN -->
                        <div class="space-y-2" v-if="showIbanField">
                            <Label for="iban">{{ t('IBAN') }} *</Label>
                            <Input
                                id="iban"
                                v-model="form.iban"
                                type="text"
                                :placeholder="t('e.g., PT50 0000 0000 0000 0000 0000 0')"
                                :class="{ 'border-red-500': form.errors.iban }"
                            />
                            <InputError :message="form.errors.iban" />
                            <p class="text-xs text-muted-foreground mt-1" v-if="form.country_id">
                                💡 {{ t('Country automatically detected') }}: {{ countries.find(c => c.id === form.country_id)?.name }}
                            </p>
                        </div>
                    </div>

                    <!-- Country Select (only shown when account_number is used) -->
                    <div class="space-y-2" v-if="showCountrySelect">
                        <Label for="country_id">{{ t('Country') }} *</Label>
                        <Select
                            id="country_id"
                            v-model="form.country_id"
                            :options="countryOptions"
                            :placeholder="t('Select a country')"
                            searchable
                            :error="!!form.errors.country_id"
                        />
                        <InputError :message="form.errors.country_id" />
                    </div>
                </div>

                <!-- Initial Balance -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <input
                            type="checkbox"
                            id="has_initial_balance"
                            v-model="hasInitialBalance"
                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary focus:ring-offset-2"
                        />
                        <Label for="has_initial_balance" class="text-sm font-medium cursor-pointer">{{ t('Set Initial Balance') }}</Label>
                    </div>

                    <div v-if="showInitialBalanceField">
                        <MoneyInputWithLabel
                            v-model="form.initial_balance"
                            :label="t('Initial Balance')"
                            :currency="vesselCurrency"
                            placeholder="0,00"
                            :error="form.errors.initial_balance"
                            :show-currency="false"
                            return-type="int"
                        />
                    </div>
                </div>

                <!-- Notes -->
                <div class="space-y-2">
                    <Label for="notes">{{ t('Notes') }}</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        :placeholder="t('Additional notes about this bank account...')"
                        rows="3"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.notes }"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <Button type="button" variant="outline" @click="emit('close')">
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? t('Creating...') : t('Create Bank Account') }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
