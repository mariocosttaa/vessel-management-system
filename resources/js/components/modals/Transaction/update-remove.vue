<script setup lang="ts">
import { watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue';
import { useNotifications } from '@/composables/useNotifications';
import transactions from '@/routes/panel/transactions';

interface Transaction {
    id: number;
    transaction_number: string;
    type: string;
    amount: number;
    currency: string;
    house_of_zeros: number;
    transaction_date: string;
    description: string | null;
    notes: string | null;
    reference: string | null;
    status: string;
    bank_account_id: number;
    category_id: number;
    supplier_id: number | null;
    crew_member_id: number | null;
}

interface TransactionCategory {
    id: number;
    name: string;
    type: string;
    color: string;
}

interface BankAccount {
    id: number;
    name: string;
    bank_name: string;
    currency?: string;
}

interface Supplier {
    id: number;
    company_name: string;
}

interface CrewMember {
    id: number;
    name: string;
    email: string;
}

interface Props {
    open: boolean;
    transaction: Transaction;
    categories: TransactionCategory[];
    bankAccounts: BankAccount[];
    suppliers: Supplier[];
    crewMembers: CrewMember[];
    defaultCurrency?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const { addNotification } = useNotifications();
const page = usePage();

// Get vessel currency from shared props
// For update modals, prioritize transaction currency since it's what was saved
const vesselCurrency = computed(() => {
    // Priority: transaction currency (what was saved) > defaultCurrency > current_vessel currency > EUR
    if (props.transaction?.currency) {
        return props.transaction.currency;
    }
    const propsCurrency = (props as any).defaultCurrency;
    if (propsCurrency) {
        return propsCurrency;
    }
    const currency = (page.props.auth as any)?.current_vessel?.currency_code;
    return currency || 'EUR';
});

// Get currency symbol and decimals from currency table
const vesselCurrencyData = computed(() => {
    const currencyCode = vesselCurrency.value;
    const currencies = (page.props as any)?.currencies || [];
    const currency = currencies.find((c: any) => c.code === currencyCode);
    if (currency) {
        return {
            code: currency.code,
            symbol: currency.symbol || currencyCode,
            decimals: currency.decimal_separator || 2,
        };
    }
    return {
        code: currencyCode,
        symbol: currencyCode === 'EUR' ? '€' : currencyCode,
        decimals: 2,
    };
});

// Get selected bank account currency
const selectedBankAccount = computed(() => {
    if (!form.bank_account_id) return null;
    return props.bankAccounts.find(acc => acc.id === form.bank_account_id);
});

// Currency priority: transaction currency (what was saved) > vessel_settings > bank account currency > EUR
const currentCurrency = computed(() => {
    // Always prioritize transaction currency for update modals
    if (props.transaction?.currency) {
        return props.transaction.currency;
    }
    return vesselCurrencyData.value.code || selectedBankAccount.value?.currency || 'EUR';
});

// Get current currency decimals
const currentCurrencyDecimals = computed(() => {
    if (vesselCurrencyData.value.decimals) {
        return vesselCurrencyData.value.decimals;
    }
    if (selectedBankAccount.value?.currency) {
        const currencies = (page.props as any)?.currencies || [];
        const currency = currencies.find((c: any) => c.code === selectedBankAccount.value?.currency);
        return currency?.decimal_separator || 2;
    }
    return props.transaction.house_of_zeros || 2;
});

// Filter categories for expense only
const expenseCategories = computed(() => {
    return props.categories.filter(cat => cat.type === 'expense');
});

// Show supplier field for expenses
const showSupplierField = computed(() => {
    return true; // Always show for expenses
});

// Show crew member field for salary expenses
const showCrewMemberField = computed(() => {
    if (!form.category_id) return false;
    const category = props.categories.find(cat => cat.id === form.category_id);
    return category && category.name.toLowerCase().includes('salário');
});

// Initialize form with transaction data
const form = useForm({
    bank_account_id: props.transaction.bank_account_id,
    category_id: props.transaction.category_id,
    type: 'expense' as string,
    amount: props.transaction.amount,
    currency: props.transaction.currency,
    house_of_zeros: props.transaction.house_of_zeros,
    vat_rate_id: null as number | null,
    vat_profile_id: null as number | null,
    amount_includes_vat: false,
    transaction_date: props.transaction.transaction_date,
    description: props.transaction.description || '',
    notes: props.transaction.notes || '',
    supplier_id: props.transaction.supplier_id || null,
    crew_member_id: props.transaction.crew_member_id || null,
    status: props.transaction.status,
});

// Reset form when modal opens/closes or transaction changes
watch(() => [props.open, props.transaction?.id], ([isOpen, transactionId]) => {
    if (isOpen && transactionId && props.transaction) {
        form.bank_account_id = props.transaction.bank_account_id;
        form.category_id = props.transaction.category_id;
        form.type = 'expense';
        form.amount = props.transaction.amount;
        form.currency = props.transaction.currency || vesselCurrencyData.value.code || 'EUR';
        form.house_of_zeros = props.transaction.house_of_zeros || currentCurrencyDecimals.value;
        form.transaction_date = props.transaction.transaction_date;
        form.description = props.transaction.description || '';
        form.notes = props.transaction.notes || '';
        form.supplier_id = props.transaction.supplier_id || null;
        form.crew_member_id = props.transaction.crew_member_id || null;
        form.status = props.transaction.status;
        // Remove VAT completely from expenses
        form.amount_includes_vat = false;
        form.vat_rate_id = null;
        form.vat_profile_id = null;
        form.clearErrors();
    }
});

// Watch type change to reset category
watch(() => form.category_id, () => {
    if (!showCrewMemberField.value) {
        form.crew_member_id = null;
    }
});

// Watch bank account change to update currency
watch(() => form.bank_account_id, (newAccountId) => {
    if (newAccountId && selectedBankAccount.value) {
        form.currency = vesselCurrencyData.value.code || selectedBankAccount.value.currency || props.transaction.currency || 'EUR';
        form.house_of_zeros = currentCurrencyDecimals.value;
    }
});

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

const submit = () => {
    // No VAT for expenses/removals
    form.amount_includes_vat = false;
    form.vat_profile_id = null;
    form.vat_rate_id = null;

    if (!form.currency) {
        form.currency = vesselCurrencyData.value.code || selectedBankAccount.value?.currency || props.transaction.currency || 'EUR';
    }

    if (!form.house_of_zeros) {
        form.house_of_zeros = currentCurrencyDecimals.value;
    }

    form.currency = vesselCurrencyData.value.code || selectedBankAccount.value?.currency || props.transaction.currency || 'EUR';
    form.house_of_zeros = currentCurrencyDecimals.value;

    form.put(transactions.update.url({ vessel: getCurrentVesselId(), transaction: props.transaction.id }), {
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: 'Success',
                message: `Transaction '${props.transaction.transaction_number}' has been updated successfully.`,
            });
            emit('success');
            emit('close');
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
            addNotification({
                type: 'error',
                title: 'Error',
                message: 'Failed to update transaction. Please check the form for errors.',
            });
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="text-red-600 dark:text-red-400">Update Transaction #{{ transaction.transaction_number }}</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Bank Account -->
                <div class="space-y-2">
                    <Label for="bank_account_id">Bank Account <span class="text-destructive">*</span></Label>
                    <select
                        id="bank_account_id"
                        v-model="form.bank_account_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.bank_account_id }"
                        required
                    >
                        <option :value="null">Select a bank account</option>
                        <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                            {{ account.name }} ({{ account.bank_name }})
                        </option>
                    </select>
                    <InputError :message="form.errors.bank_account_id" />
                </div>

                <!-- Category -->
                <div class="space-y-2">
                    <Label for="category_id">Category <span class="text-destructive">*</span></Label>
                    <select
                        id="category_id"
                        v-model="form.category_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.category_id }"
                        required
                    >
                        <option :value="null">Select a category</option>
                        <option v-for="category in expenseCategories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.category_id" />
                </div>

                <!-- Amount and Date -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Amount -->
                    <div class="space-y-2">
                        <MoneyInputWithLabel
                            v-model="form.amount"
                            label="Amount"
                            :currency="currentCurrency"
                            placeholder="0,00"
                            :error="form.errors.amount"
                            :show-currency="true"
                            return-type="int"
                            :decimals="currentCurrencyDecimals"
                            required
                        />
                    </div>

                    <!-- Transaction Date -->
                    <div class="space-y-2">
                        <Label for="transaction_date">Transaction Date <span class="text-destructive">*</span></Label>
                        <Input
                            id="transaction_date"
                            v-model="form.transaction_date"
                            type="date"
                            :max="new Date().toISOString().split('T')[0]"
                            :class="{ 'border-destructive dark:border-destructive': form.errors.transaction_date }"
                            required
                        />
                        <InputError :message="form.errors.transaction_date" />
                    </div>
                </div>

                <!-- Supplier (for expenses) -->
                <div v-if="showSupplierField" class="space-y-2">
                    <Label for="supplier_id">Supplier</Label>
                    <select
                        id="supplier_id"
                        v-model="form.supplier_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.supplier_id }"
                    >
                        <option :value="null">Select a supplier</option>
                        <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                            {{ supplier.company_name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.supplier_id" />
                </div>

                <!-- Crew Member (for salary expenses) -->
                <div v-if="showCrewMemberField" class="space-y-2">
                    <Label for="crew_member_id">Crew Member <span class="text-destructive">*</span></Label>
                    <select
                        id="crew_member_id"
                        v-model="form.crew_member_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.crew_member_id }"
                        required
                    >
                        <option :value="null">Select a crew member</option>
                        <option v-for="member in crewMembers" :key="member.id" :value="member.id">
                            {{ member.name }} ({{ member.email }})
                        </option>
                    </select>
                    <InputError :message="form.errors.crew_member_id" />
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Input
                        id="description"
                        v-model="form.description"
                        type="text"
                        placeholder="Enter transaction description"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.description }"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <!-- Notes -->
                <div class="space-y-2">
                    <Label for="notes">Notes</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        placeholder="Additional notes about this transaction"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.notes }"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <!-- Status -->
                <div class="space-y-2">
                    <Label for="status">Status</Label>
                    <select
                        id="status"
                        v-model="form.status"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.status }"
                    >
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <InputError :message="form.errors.status" />
                </div>

                <!-- Hidden fields -->
                <input type="hidden" v-model="form.currency" />
                <input type="hidden" v-model="form.house_of_zeros" />
                <input type="hidden" v-model="form.type" />

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <Button type="button" variant="outline" @click="emit('close')">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Updating...' : 'Update Transaction' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

