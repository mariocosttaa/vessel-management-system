<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import InputError from '@/components/InputError.vue';
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import { useNotifications } from '@/composables/useNotifications';
import { useMoney } from '@/composables/useMoney';
import transactions from '@/routes/panel/transactions';

interface Transaction {
    id: number;
    transaction_number: string;
    type: string;
    amount: number;
    vat_amount?: number;
    total_amount?: number;
    currency: string;
    house_of_zeros: number;
    transaction_date: string;
    description: string | null;
    notes: string | null;
    reference: string | null;
    status: string;
    bank_account_id: number;
    category_id: number;
    vat_profile_id: number | null;
    amount_includes_vat?: boolean;
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

interface VatProfile {
    id: number;
    name: string;
    percentage: number;
    country_id?: number | null;
}

interface Props {
    open: boolean;
    transaction: Transaction;
    categories: TransactionCategory[];
    bankAccounts: BankAccount[];
    vatProfiles?: VatProfile[];
    defaultVatProfile?: VatProfile | null;
    defaultCurrency?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const { addNotification } = useNotifications();
const { calculateVat, calculateTotal } = useMoney();
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

// Filter categories for income only
const incomeCategories = computed(() => {
    return props.categories.filter(cat => cat.type === 'income');
});

// Determine if amount includes VAT based on existing transaction
// First check if amount_includes_vat field exists, otherwise calculate based on amounts
const initialAmountIncludesVat = computed(() => {
    // If amount_includes_vat field is explicitly set, use it
    if (props.transaction.amount_includes_vat !== undefined) {
        return props.transaction.amount_includes_vat;
    }
    // Otherwise, try to infer from amounts
    // If total_amount is very close to amount (within rounding error), VAT was included
    if (props.transaction.vat_amount && props.transaction.total_amount) {
        const expectedTotalIfExcluded = props.transaction.amount + props.transaction.vat_amount;
        // If total is closer to amount than to amount + vat, VAT was included
        const diffToAmount = Math.abs(props.transaction.total_amount - props.transaction.amount);
        const diffToExpected = Math.abs(props.transaction.total_amount - expectedTotalIfExcluded);
        return diffToAmount < diffToExpected;
    }
    return false;
});

// VAT handling - watch transaction changes to update the ref
const amountIncludesVat = ref(initialAmountIncludesVat.value);

// Watch transaction changes to update amountIncludesVat
watch(() => props.transaction.id, () => {
    amountIncludesVat.value = initialAmountIncludesVat.value;
});

// Calculate VAT amounts based on current input
const vatCalculations = computed(() => {
    if (!form.amount || !selectedVatProfile.value) {
        return {
            baseAmount: 0,
            vatAmount: 0,
            totalAmount: 0,
        };
    }

    const amount = form.amount;
    const vatRate = selectedVatProfile.value.percentage;

    if (amountIncludesVat.value) {
        const baseAmount = Math.round(amount / (1 + vatRate / 100));
        const vatAmount = amount - baseAmount;
        return {
            baseAmount,
            vatAmount,
            totalAmount: amount,
        };
    } else {
        const vatAmount = Math.round((amount * vatRate) / 100);
        const totalAmount = amount + vatAmount;
        return {
            baseAmount: amount,
            vatAmount,
            totalAmount,
        };
    }
});

// Get selected VAT profile
const selectedVatProfile = computed(() => {
    if (form.vat_profile_id) {
        return props.vatProfiles?.find(p => p.id === form.vat_profile_id) || props.defaultVatProfile;
    }
    return props.defaultVatProfile;
});

// Initialize form with transaction data
const form = useForm({
    bank_account_id: props.transaction.bank_account_id,
    category_id: props.transaction.category_id,
    type: 'income' as string,
    amount: props.transaction.amount,
    currency: props.transaction.currency,
    house_of_zeros: props.transaction.house_of_zeros,
    vat_profile_id: props.transaction.vat_profile_id || props.defaultVatProfile?.id || null,
    amount_includes_vat: initialAmountIncludesVat.value,
    transaction_date: props.transaction.transaction_date,
    description: props.transaction.description || '',
    notes: props.transaction.notes || '',
    status: props.transaction.status,
});

// Get default VAT profile from props
const defaultVatProfile = computed(() => props.defaultVatProfile);

// Get VAT profiles list
const vatProfiles = computed(() => props.vatProfiles || []);

// Reset form when modal opens/closes or transaction changes
watch(() => [props.open, props.transaction?.id], ([isOpen, transactionId]) => {
    if (isOpen && transactionId && props.transaction) {
        form.bank_account_id = props.transaction.bank_account_id;
        form.category_id = props.transaction.category_id;
        form.type = 'income';
        form.amount = props.transaction.amount;
        form.currency = props.transaction.currency || vesselCurrencyData.value.code || 'EUR';
        form.house_of_zeros = props.transaction.house_of_zeros || currentCurrencyDecimals.value;
        form.vat_profile_id = props.transaction.vat_profile_id || props.defaultVatProfile?.id || null;
        // Update amountIncludesVat from the transaction
        amountIncludesVat.value = props.transaction.amount_includes_vat ?? initialAmountIncludesVat.value;
        form.amount_includes_vat = amountIncludesVat.value;
        form.transaction_date = props.transaction.transaction_date;
        form.description = props.transaction.description || '';
        form.notes = props.transaction.notes || '';
        form.status = props.transaction.status;
        form.clearErrors();
    }
});

// Watch bank account change to update currency
watch(() => form.bank_account_id, (newAccountId) => {
    if (newAccountId && selectedBankAccount.value) {
        form.currency = vesselCurrencyData.value.code || selectedBankAccount.value.currency || props.transaction.currency || 'EUR';
        form.house_of_zeros = currentCurrencyDecimals.value;
    }
});

// Watch VAT switch
watch(amountIncludesVat, (value) => {
    form.amount_includes_vat = value;
    if (selectedVatProfile.value) {
        form.vat_profile_id = selectedVatProfile.value.id;
    }
});

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

const submit = () => {
    form.amount_includes_vat = amountIncludesVat.value;

    if (!form.currency) {
        form.currency = vesselCurrencyData.value.code || selectedBankAccount.value?.currency || props.transaction.currency || 'EUR';
    }

    if (!form.house_of_zeros) {
        form.house_of_zeros = currentCurrencyDecimals.value;
    }

    if (!form.vat_profile_id && defaultVatProfile.value) {
        form.vat_profile_id = defaultVatProfile.value.id;
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
                <DialogTitle class="text-green-600 dark:text-green-400">Update Transaction #{{ transaction.transaction_number }}</DialogTitle>
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
                        <option v-for="category in incomeCategories" :key="category.id" :value="category.id">
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

                <!-- VAT Section -->
                <div v-if="selectedVatProfile && form.amount" class="space-y-4 p-4 border rounded-lg bg-muted/50 dark:bg-muted/30">
                    <div class="flex items-center justify-between">
                        <Label for="amount_includes_vat" class="font-medium cursor-pointer" @click="amountIncludesVat = !amountIncludesVat">
                            Amount already includes VAT
                        </Label>
                        <Switch
                            id="amount_includes_vat"
                            :checked="amountIncludesVat"
                            @update:checked="(val) => amountIncludesVat = val"
                        />
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-muted-foreground">VAT Profile:</span>
                            <span class="font-medium">{{ selectedVatProfile.name }} ({{ selectedVatProfile.percentage }}%)</span>
                        </div>

                        <!-- VAT Breakdown -->
                        <div class="space-y-2 pt-2 border-t">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-muted-foreground">Base Amount:</span>
                                <MoneyDisplay
                                    :value="vatCalculations.baseAmount"
                                    :currency="currentCurrency"
                                    :decimals="currentCurrencyDecimals"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-muted-foreground">VAT Amount ({{ selectedVatProfile.percentage }}%):</span>
                                <MoneyDisplay
                                    :value="vatCalculations.vatAmount"
                                    :currency="currentCurrency"
                                    :decimals="currentCurrencyDecimals"
                                    size="sm"
                                    variant="neutral"
                                />
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t font-semibold">
                                <span class="text-sm">Total Amount:</span>
                                <MoneyDisplay
                                    :value="vatCalculations.totalAmount"
                                    :currency="currentCurrency"
                                    :decimals="currentCurrencyDecimals"
                                    size="sm"
                                    variant="positive"
                                />
                            </div>
                        </div>

                        <p class="text-xs text-muted-foreground italic">
                            <span v-if="amountIncludesVat">
                                VAT ({{ selectedVatProfile.percentage }}%) is included in the amount above.
                            </span>
                            <span v-else>
                                VAT ({{ selectedVatProfile.percentage }}%) will be added to the amount above.
                            </span>
                        </p>
                    </div>
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

