<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { useNotifications } from '@/composables/useNotifications';
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

// Loading state
const loading = ref(false);
const error = ref<string | null>(null);
const detailedBankAccount = ref<BankAccount | null>(null);

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

// Computed properties
const showIbanField = computed(() => useIban.value);
const showAccountNumberField = computed(() => useAccountNumber.value);
const showInitialBalanceField = computed(() => hasInitialBalance.value);

// Fetch bank account details from API
const fetchBankAccountDetails = async () => {
    loading.value = true;
    error.value = null;

    const url = `/api/bank-accounts/${props.bankAccount.id}/details`;

    try {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Failed to fetch bank account details');
        }

        const data = await response.json();
        detailedBankAccount.value = data.bankAccount;

        // Populate form with detailed data
        populateForm(data.bankAccount);
    } catch (err) {
        error.value = 'Failed to load bank account details';
        console.error('Error fetching bank account details:', err);
    } finally {
        loading.value = false;
    }
};

// Populate form with bank account data
const populateForm = (bankAccount: BankAccount) => {
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
};

// Watch for modal open to fetch details
watch(() => props.open, async (isOpen) => {
    if (isOpen && props.bankAccount) {
        await fetchBankAccountDetails();
    } else {
        // Reset state when modal closes
        detailedBankAccount.value = null;
        error.value = null;
    }
}, { immediate: true });

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
                message: `Bank account '${form.name}' has been updated successfully.`,
            });
            emit('success');
        },
        onError: () => {
            addNotification({
                type: 'error',
                message: 'Failed to update bank account. Please try again.',
            });
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Edit Bank Account</DialogTitle>
            </DialogHeader>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="flex items-center space-x-2">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                    <span class="text-muted-foreground">Loading bank account details...</span>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="flex items-center justify-center py-8">
                <div class="text-center">
                    <p class="text-red-600 mb-4">{{ error }}</p>
                    <Button @click="fetchBankAccountDetails" variant="outline">
                        Try Again
                    </Button>
                </div>
            </div>

            <!-- Form Content -->
            <form v-else @submit.prevent="submit" class="space-y-6" :class="{ 'blur-sm pointer-events-none': loading }">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Account Name -->
                    <div class="space-y-2">
                        <Label for="name">Account Name *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g., Main Business Account"
                            :class="{ 'border-red-500': form.errors.name }"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Bank Name -->
                    <div class="space-y-2">
                        <Label for="bank_name">Bank Name *</Label>
                        <Input
                            id="bank_name"
                            v-model="form.bank_name"
                            type="text"
                            placeholder="e.g., Banco Santander"
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
                            <Label for="use_iban" class="text-sm font-medium cursor-pointer">Use IBAN</Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <input
                                type="checkbox"
                                id="use_account_number"
                                v-model="useAccountNumber"
                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary focus:ring-offset-2"
                            />
                            <Label for="use_account_number" class="text-sm font-medium cursor-pointer">Use Account Number</Label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Account Number -->
                        <div class="space-y-2" v-if="showAccountNumberField">
                            <Label for="account_number">Account Number</Label>
                            <Input
                                id="account_number"
                                v-model="form.account_number"
                                type="text"
                                placeholder="e.g., 1234567890"
                                :class="{ 'border-red-500': form.errors.account_number }"
                            />
                            <InputError :message="form.errors.account_number" />
                        </div>

                        <!-- IBAN -->
                        <div class="space-y-2" v-if="showIbanField">
                            <Label for="iban">IBAN</Label>
                            <Input
                                id="iban"
                                v-model="form.iban"
                                type="text"
                                placeholder="e.g., PT50 0000 0000 0000 0000 0000 0"
                                :class="{ 'border-red-500': form.errors.iban }"
                            />
                            <InputError :message="form.errors.iban" />
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="space-y-2">
                    <Label for="status">Status *</Label>
                    <select
                        id="status"
                        v-model="form.status"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.status }"
                    >
                        <option value="">Select status</option>
                        <option
                            v-for="(label, value) in statuses"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.status" />
                    <p class="text-xs text-muted-foreground mt-1">
                        💡 Country will be automatically detected from IBAN
                    </p>
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
                        <Label for="has_initial_balance" class="text-sm font-medium cursor-pointer">Set Initial Balance</Label>
                    </div>

                    <div class="space-y-2" v-if="showInitialBalanceField">
                        <Label for="initial_balance">Initial Balance *</Label>
                        <Input
                            id="initial_balance"
                            v-model="form.initial_balance"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            :class="{ 'border-red-500': form.errors.initial_balance }"
                        />
                        <InputError :message="form.errors.initial_balance" />
                    </div>
                </div>

                <!-- Notes -->
                <div class="space-y-2">
                    <Label for="notes">Notes</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        placeholder="Additional notes about this bank account..."
                        rows="3"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.notes }"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <Button type="button" variant="outline" @click="emit('close')">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Updating...' : 'Update Bank Account' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
