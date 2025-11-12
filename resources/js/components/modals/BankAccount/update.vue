<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import BaseModal from '../BaseModal.vue';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { useNotifications } from '@/composables/useNotifications';
import { useI18n } from '@/composables/useI18n';
import bankAccounts from '@/routes/panel/bank-accounts';

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
const page = usePage();

// Get vessel currency from shared props
const vesselCurrency = computed(() => {
    return (page.props.auth as any)?.current_vessel?.currency_code || 'EUR';
});

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

const form = useForm({
    name: '',
    bank_name: '',
    status: 'active',
    notes: '',
});

// Convert to Select component options format
const statusOptions = computed(() => {
    const options: Array<{ value: string; label: string }> = [];
    Object.entries(props.statuses).forEach(([value, label]) => {
        options.push({ value, label: label as string });
    });
    return options;
});

// Store read-only account information
const accountInfo = ref<{
    iban: string | null;
    account_number: string | null;
    country_id: number | null;
    country_name: string | null;
}>({
    iban: null,
    account_number: null,
    country_id: null,
    country_name: null,
});

// API URL for fetching bank account details
const apiUrl = computed(() => {
    const vesselId = getCurrentVesselId();
    return `/panel/${vesselId}/api/bank-accounts/${props.bankAccount.id}/details`;
});

// Handle data loaded from API
const handleDataLoaded = (data: any) => {
    if (data?.bankAccount) {
        const bankAccount = data.bankAccount;

        // Populate form with editable data only
        form.name = bankAccount.name;
        form.bank_name = bankAccount.bank_name;
        form.status = bankAccount.status;
        form.notes = bankAccount.notes || '';
        form.clearErrors();

        // Store read-only account information for display
        const country = bankAccount.country_id
            ? props.countries.find((c: Country) => c.id === bankAccount.country_id)
            : null;

        accountInfo.value = {
            iban: bankAccount.iban || null,
            account_number: bankAccount.account_number || null,
            country_id: bankAccount.country_id || null,
            country_name: country?.name || null,
        };
    }
};

const submit = () => {
    const vesselId = getCurrentVesselId();
    form.put(bankAccounts.update.url({ vessel: vesselId, bankAccount: props.bankAccount.id }), {
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: t('Success'),
                message: `${t('Bank account')} '${form.name}' ${t('has been updated successfully.')}`,
            });
            emit('success');
            emit('close');
        },
        onError: () => {
            addNotification({
                type: 'error',
                title: t('Error'),
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
        :api-url="apiUrl"
        :enable-lazy-loading="true"
        :confirm-text="t('Update')"
        @close="emit('close')"
        @confirm="submit"
        @data-loaded="handleDataLoaded"
    >
        <template #default>
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

                <!-- Account Information Notice -->
                <div class="p-3 bg-muted rounded-md border border-border">
                    <p class="text-sm text-muted-foreground">
                        <strong class="text-foreground">{{ t('Note') }}:</strong> {{ t('IBAN and Account Number cannot be changed. If you need to update this information, please contact support or delete this account and create a new one.') }}
                    </p>
                </div>

                <!-- Status -->
                <div class="space-y-2">
                    <Label for="status">{{ t('Status') }} *</Label>
                    <Select
                        id="status"
                        v-model="form.status"
                        :options="statusOptions"
                        :error="!!form.errors.status"
                    />
                    <InputError :message="form.errors.status" />
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

            </form>
        </template>
    </BaseModal>
</template>
