<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DateInput } from '@/components/ui/date-input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import InputError from '@/components/InputError.vue';
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import { useNotifications } from '@/composables/useNotifications';
import { useMoney } from '@/composables/useMoney';
import transactions from '@/routes/panel/transactions';

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
    categories: TransactionCategory[];
    bankAccounts: BankAccount[];
    vatRates?: any[]; // Keep for backward compatibility
    vatProfiles?: VatProfile[];
    defaultVatProfile?: VatProfile | null;
    defaultCurrency?: string; // Default currency from vessel_settings (passed from controller)
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const { addNotification } = useNotifications();
const { calculateVat, calculateTotal } = useMoney();
const page = usePage();

// Get vessel currency from shared props - use currency from currency table
// Priority: props.defaultCurrency (from controller) > current_vessel.currency_code > EUR
// The controller passes defaultCurrency from vessel_settings, which is the most reliable source
const vesselCurrency = computed(() => {
    // First try to get from props (passed from TransactionController index method)
    const propsCurrency = (props as any).defaultCurrency;
    if (propsCurrency) {
        return propsCurrency;
    }
    // Fallback to current_vessel (might be null, but try anyway)
    const currency = (page.props.auth as any)?.current_vessel?.currency_code;
    // Get currency from currency table if available, otherwise fallback to EUR
    // This currency comes from vessel_settings.currency_code (priority) or vessel.currency_code (fallback)
    return currency || 'EUR';
});

// Get currency symbol and decimals from currency table
const vesselCurrencyData = computed(() => {
    const currencyCode = vesselCurrency.value;
    // Try to get currency data from shared props
    const currencies = (page.props as any)?.currencies || [];
    const currency = currencies.find((c: any) => c.code === currencyCode);
    if (currency) {
        return {
            code: currency.code,
            symbol: currency.symbol || currencyCode,
            decimals: currency.decimal_separator || 2,
        };
    }
    // Fallback defaults
    return {
        code: currencyCode,
        symbol: currencyCode === 'EUR' ? '€' : currencyCode,
        decimals: 2,
    };
});

// Auto-select bank account if only one exists
const defaultBankAccount = computed(() => {
    if (props.bankAccounts.length === 1) {
        return props.bankAccounts[0].id;
    }
    return null;
});

// Get selected bank account currency
const selectedBankAccount = computed(() => {
    if (!form.bank_account_id) return null;
    return props.bankAccounts.find(acc => acc.id === form.bank_account_id);
});

// Currency priority: vessel_settings (defaultCurrency prop) > bank account currency > EUR
// Vessel settings currency should always take precedence over bank account currency
const currentCurrency = computed(() => {
    // Always prioritize vessel_settings currency (from defaultCurrency prop)
    // Only use bank account currency if vessel_settings currency is not available
    return vesselCurrencyData.value.code || selectedBankAccount.value?.currency || 'EUR';
});

// Get current currency decimals
// Priority: vessel_settings currency decimals > bank account currency decimals > 2
const currentCurrencyDecimals = computed(() => {
    // Always prioritize vessel_settings currency decimals
    if (vesselCurrencyData.value.decimals) {
        return vesselCurrencyData.value.decimals;
    }
    // Fallback to bank account currency decimals if vessel currency not available
    if (selectedBankAccount.value?.currency) {
        const currencies = (page.props as any)?.currencies || [];
        const currency = currencies.find((c: any) => c.code === selectedBankAccount.value?.currency);
        return currency?.decimal_separator || 2;
    }
    return 2;
});

// Filter categories for income only
const incomeCategories = computed(() => {
    return props.categories.filter(cat => cat.type === 'income');
});

// VAT handling
const amountIncludesVat = ref(false);

// Calculate VAT amounts based on current input
const vatCalculations = computed(() => {
    if (!form.amount || !defaultVatProfile.value) {
        return {
            baseAmount: 0,
            vatAmount: 0,
            totalAmount: 0,
        };
    }

    const amount = form.amount; // Amount is already in cents (integer)
    const vatRate = defaultVatProfile.value.percentage;

    if (amountIncludesVat.value) {
        // Amount INCLUDES VAT - extract VAT from total
        // Formula: base = total / (1 + vat_rate/100)
        // Example: If total = 11400 cents (114.00 EUR) with 14% VAT
        // base = 11400 / 1.14 = 10000 cents (100.00 EUR)
        // vat = 11400 - 10000 = 1400 cents (14.00 EUR)
        const baseAmount = Math.round(amount / (1 + vatRate / 100));
        const vatAmount = amount - baseAmount;
        return {
            baseAmount, // Base amount without VAT
            vatAmount,  // VAT amount extracted from total
            totalAmount: amount, // Total amount (what user entered, includes VAT)
        };
    } else {
        // Amount EXCLUDES VAT - add VAT on top
        // Formula: vat = base * (vat_rate/100)
        // Example: If base = 10000 cents (100.00 EUR) with 14% VAT
        // vat = 10000 * 0.14 = 1400 cents (14.00 EUR)
        // total = 10000 + 1400 = 11400 cents (114.00 EUR)
        const vatAmount = Math.round((amount * vatRate) / 100);
        const totalAmount = amount + vatAmount;
        return {
            baseAmount: amount, // Base amount (what user entered, excludes VAT)
            vatAmount,  // VAT amount calculated on top
            totalAmount, // Total amount (base + VAT)
        };
    }
});

const form = useForm({
    bank_account_id: null as number | null,
    category_id: null as number | null,
    type: 'income' as string,
    amount: null as number | null,
    currency: vesselCurrency.value,
    house_of_zeros: 2,
    vat_rate_id: null as number | null, // Keep for backward compatibility
    vat_profile_id: null as number | null,
    amount_includes_vat: false,
    transaction_date: new Date().toISOString().split('T')[0],
    description: '',
    notes: '',
    status: 'completed',
});

// Get default VAT profile from props
const defaultVatProfile = computed(() => props.defaultVatProfile);

// Get VAT profiles list
const vatProfiles = computed(() => props.vatProfiles || []);

// Reset form when modal opens/closes
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.type = 'income';
        form.bank_account_id = defaultBankAccount.value;
        // Priority: vessel_settings currency > bank account currency > EUR
        form.currency = vesselCurrencyData.value.code || selectedBankAccount.value?.currency || 'EUR';
        form.house_of_zeros = currentCurrencyDecimals.value;
        form.transaction_date = new Date().toISOString().split('T')[0];
        form.status = 'completed';
        amountIncludesVat.value = false;
        form.amount_includes_vat = false;
        form.vat_rate_id = null;
        // Always set default VAT profile if available (user cannot change it)
        form.vat_profile_id = defaultVatProfile.value?.id || null;
        form.category_id = null;
        form.clearErrors();
    }
});

// Watch bank account change to update currency
watch(() => form.bank_account_id, (newAccountId) => {
    if (newAccountId && selectedBankAccount.value) {
        // Priority: vessel_settings currency > bank account currency > EUR
        // Vessel settings currency should always take precedence
        form.currency = vesselCurrencyData.value.code || selectedBankAccount.value.currency || 'EUR';
        form.house_of_zeros = currentCurrencyDecimals.value;
    } else if (!newAccountId) {
        // If no bank account selected, use vessel settings currency
        form.currency = vesselCurrencyData.value.code || 'EUR';
        form.house_of_zeros = vesselCurrencyData.value.decimals;
    }
});

// Watch VAT checkbox
watch(amountIncludesVat, (value) => {
    form.amount_includes_vat = value;
    // Always use default VAT profile if available
    if (defaultVatProfile.value) {
        form.vat_profile_id = defaultVatProfile.value.id;
    }
});

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

const submit = () => {
    // Set amount_includes_vat flag
    form.amount_includes_vat = amountIncludesVat.value;

    // Ensure currency is always set from vessel settings or bank account
    // Priority: selected bank account currency > vessel settings currency > EUR
    if (!form.currency) {
        form.currency = selectedBankAccount.value?.currency || vesselCurrencyData.value.code || 'EUR';
    }

    // Ensure house_of_zeros is set
    if (!form.house_of_zeros) {
        form.house_of_zeros = currentCurrencyDecimals.value;
    }

    // Set default VAT profile if not set
    if (!form.vat_profile_id && defaultVatProfile.value) {
        form.vat_profile_id = defaultVatProfile.value.id;
    }

    // Always ensure currency is set before submission (force update)
    // Priority: vessel_settings currency > bank account currency > EUR
    form.currency = vesselCurrencyData.value.code || selectedBankAccount.value?.currency || 'EUR';
    form.house_of_zeros = currentCurrencyDecimals.value;

    form.post(transactions.store.url({ vessel: getCurrentVesselId() }), {
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: 'Success',
                message: 'Funds have been added successfully.',
            });
            emit('success');
            emit('close');
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
            addNotification({
                type: 'error',
                title: 'Error',
                message: 'Failed to add funds. Please check the form for errors.',
            });
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="text-green-600 dark:text-green-400">Add Transaction</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Bank Account -->
                <div class="space-y-2">
                    <Label for="bank_account_id">Bank Account <span v-if="bankAccounts.length > 0" class="text-destructive">*</span></Label>
                    <select
                        id="bank_account_id"
                        v-model="form.bank_account_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.bank_account_id }"
                        :required="bankAccounts.length > 0"
                    >
                        <option :value="null">Select a bank account</option>
                        <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                            {{ account.name }} ({{ account.bank_name }})
                        </option>
                    </select>
                    <InputError :message="form.errors.bank_account_id" />
                    <p v-if="bankAccounts.length === 0" class="text-xs text-muted-foreground">
                        Please create a bank account first before adding funds.
                    </p>
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
                        <DateInput
                            id="transaction_date"
                            v-model="form.transaction_date"
                            :max="new Date().toISOString().split('T')[0]"
                            :class="{ 'border-destructive dark:border-destructive': form.errors.transaction_date }"
                        />
                        <InputError :message="form.errors.transaction_date" />
                    </div>
                </div>

                <!-- VAT Section (only shown if default VAT profile exists) -->
                <div v-if="defaultVatProfile && form.amount" class="space-y-4 p-4 border rounded-lg bg-muted/50 dark:bg-muted/30">
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
                            <span class="font-medium">{{ defaultVatProfile.name }} ({{ defaultVatProfile.percentage }}%)</span>
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
                                <span class="text-sm text-muted-foreground">VAT Amount ({{ defaultVatProfile.percentage }}%):</span>
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
                                VAT ({{ defaultVatProfile.percentage }}%) is included in the amount above.
                            </span>
                            <span v-else>
                                VAT ({{ defaultVatProfile.percentage }}%) will be added to the amount above.
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

                <!-- Hidden fields -->
                <input type="hidden" v-model="form.currency" />
                <input type="hidden" v-model="form.house_of_zeros" />
                <input type="hidden" v-model="form.type" />
                <input type="hidden" v-model="form.status" />

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <Button type="button" variant="outline" @click="emit('close')">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing || bankAccounts.length === 0">
                        {{ form.processing ? 'Adding...' : 'Add Funds' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

