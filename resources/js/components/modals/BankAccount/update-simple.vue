<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseModal from '../BaseModal.vue';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { useNotifications } from '@/composables/useNotifications';
import { useI18n } from '@/composables/useI18n';
import bankAccounts from '@/routes/bank-accounts';

interface Country {
    id: number;
    name: string;
    code: string;
}

interface BankAccount {
    id: number;
    name: string;
    bank_name: string;
    account_number: string | null;
    iban: string | null;
    country_id: number | null;
    initial_balance: number;
    status: string;
    notes: string | null;
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
    bankAccount: BankAccount;
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
    initial_balance: 0,
    status: 'active',
    notes: '',
});

// Convert to Select component options format
const countryOptions = computed(() => {
    const options = [{ value: null, label: t('Select a country') }];
    props.countries.forEach(country => {
        options.push({ value: country.id, label: `${country.name} (${country.code})` });
    });
    return options;
});

const statusOptions = computed(() => {
    const options: Array<{ value: string; label: string }> = [];
    Object.entries(props.statuses).forEach(([value, label]) => {
        options.push({ value, label: label as string });
    });
    return options;
});

// Watch for data loaded from API
const handleDataLoaded = (data: any) => {
    if (data?.bankAccount) {
        const bankAccount = data.bankAccount;

        // Populate form with detailed data
        form.name = bankAccount.name;
        form.bank_name = bankAccount.bank_name;
        form.account_number = bankAccount.account_number || '';
        form.iban = bankAccount.iban || '';
        form.country_id = bankAccount.country_id;
        form.initial_balance = bankAccount.initial_balance / 100; // Convert from cents
        form.status = bankAccount.status;
        form.notes = bankAccount.notes || '';
        form.clearErrors();

        // Set checkbox states based on existing data
        useIban.value = !!bankAccount.iban;
        useAccountNumber.value = !!bankAccount.account_number;
        hasInitialBalance.value = bankAccount.initial_balance > 0;
    }
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
        form.initial_balance = 0;
    }

    form.put(bankAccounts.update.url({ bankAccount: props.bankAccount.id }), {
        onSuccess: () => {
            addNotification({
                type: 'success',
                message: `${t('Bank account')} '${form.name}' ${t('has been updated successfully.')}`,
            });
            emit('success');
        },
        onError: () => {
            addNotification({
                type: 'error',
                message: t('Failed to update bank account. Please try again.'),
            });
        },
    });
};
</script>

<template>
    <BaseModal
        :open="open"
        :title="t('Edit Bank Account')"
        size="2xl"
        :api-url="`/api/bank-accounts/${bankAccount.id}/details`"
        :enable-lazy-loading="true"
        :confirm-text="t('Update')"
        @close="emit('close')"
        @confirm="submit"
        @data-loaded="handleDataLoaded"
    >
        <template #default="{ data, loading }">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <Label for="name">{{ t('Account Name') }} *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                :placeholder="t('Enter account name')"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div>
                            <Label for="bank_name">{{ t('Bank Name') }} *</Label>
                            <Input
                                id="bank_name"
                                v-model="form.bank_name"
                                type="text"
                                :placeholder="t('Enter bank name')"
                                :class="{ 'border-red-500': form.errors.bank_name }"
                            />
                            <InputError :message="form.errors.bank_name" />
                        </div>

                        <div>
                            <Label for="country_id">{{ t('Country') }}</Label>
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

                    <div class="space-y-4">
                        <div>
                            <Label for="status">{{ t('Status') }} *</Label>
                            <Select
                                id="status"
                                v-model="form.status"
                                :options="statusOptions"
                                :error="!!form.errors.status"
                            />
                            <InputError :message="form.errors.status" />
                        </div>

                        <div>
                            <Label for="notes">{{ t('Notes') }}</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :placeholder="t('Enter any additional notes')"
                            ></textarea>
                            <InputError :message="form.errors.notes" />
                        </div>
                    </div>
                </div>

                <!-- Account Details -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                            <input
                                v-model="useIban"
                                type="checkbox"
                                class="mr-2"
                            />
                            {{ t('Use IBAN') }}
                        </label>
                        <label class="flex items-center">
                            <input
                                v-model="useAccountNumber"
                                type="checkbox"
                                class="mr-2"
                            />
                            {{ t('Use Account Number') }}
                        </label>
                        <label class="flex items-center">
                            <input
                                v-model="hasInitialBalance"
                                type="checkbox"
                                class="mr-2"
                            />
                            {{ t('Set Initial Balance') }}
                        </label>
                    </div>

                    <div v-if="useIban" class="space-y-2">
                        <Label for="iban">{{ t('IBAN') }}</Label>
                        <Input
                            id="iban"
                            v-model="form.iban"
                            type="text"
                            :placeholder="t('Enter IBAN')"
                            :class="{ 'border-red-500': form.errors.iban }"
                        />
                        <InputError :message="form.errors.iban" />
                    </div>

                    <div v-if="useAccountNumber" class="space-y-2">
                        <Label for="account_number">{{ t('Account Number') }}</Label>
                        <Input
                            id="account_number"
                            v-model="form.account_number"
                            type="text"
                            :placeholder="t('Enter account number')"
                            :class="{ 'border-red-500': form.errors.account_number }"
                        />
                        <InputError :message="form.errors.account_number" />
                    </div>

                    <div v-if="hasInitialBalance" class="space-y-2">
                        <Label for="initial_balance">{{ t('Initial Balance') }}</Label>
                        <Input
                            id="initial_balance"
                            v-model="form.initial_balance"
                            type="number"
                            step="0.01"
                            :placeholder="t('Enter initial balance')"
                            :class="{ 'border-red-500': form.errors.initial_balance }"
                        />
                        <InputError :message="form.errors.initial_balance" />
                    </div>
                </div>
            </form>
        </template>
    </BaseModal>
</template>
