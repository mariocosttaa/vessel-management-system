<script setup lang="ts">
import { watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DateInput } from '@/components/ui/date-input';
import { Select } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue';
import { useNotifications } from '@/composables/useNotifications';
import { useI18n } from '@/composables/useI18n';
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
    categories: TransactionCategory[];
    bankAccounts: BankAccount[];
    suppliers: Supplier[];
    crewMembers: CrewMember[];
    vatRates?: any[]; // Keep for backward compatibility (not used)
    vatProfiles?: any[]; // Keep for backward compatibility (not used)
    defaultVatProfile?: any; // Keep for backward compatibility (not used)
    defaultCurrency?: string; // Default currency from vessel_settings (passed from controller)
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const { addNotification } = useNotifications();
const { t } = useI18n();
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

// Filter categories for expense only
const expenseCategories = computed(() => {
    return props.categories.filter(cat => cat.type === 'expense');
});

// Convert to Select component options format
const bankAccountOptions = computed(() => {
    const options = [{ value: null, label: t('Select a bank account') }];
    props.bankAccounts.forEach(account => {
        options.push({ value: account.id, label: `${account.name} (${account.bank_name})` });
    });
    return options;
});

const categoryOptions = computed(() => {
    const options = [{ value: null, label: t('Select a category') }];
    expenseCategories.value.forEach(category => {
        options.push({ value: category.id, label: t(category.name) });
    });
    return options;
});

const supplierOptions = computed(() => {
    const options = [{ value: null, label: t('Select a supplier') }];
    (props.suppliers || []).forEach(supplier => {
        options.push({ value: supplier.id, label: supplier.company_name });
    });
    return options;
});

const crewMemberOptions = computed(() => {
    const options = [{ value: null, label: t('Select a crew member') }];
    (props.crewMembers || []).forEach(member => {
        options.push({ value: member.id, label: `${member.name} (${member.email})` });
    });
    return options;
});

// Show supplier field for expenses
const showSupplierField = computed(() => {
    return true; // Always show for expenses
});

// Show crew member field for salary expenses
const showCrewMemberField = computed(() => {
    if (!form.category_id) return false;
    const category = props.categories.find(cat => cat.id === form.category_id);
    // Check for salary categories in English (Salaries, Crew Salaries, Wages)
    return category && (category.name === 'Salaries' || category.name === 'Crew Salaries' || category.name === 'Wages');
});

// VAT is not used for expenses/removals

const form = useForm({
    bank_account_id: null as number | null,
    category_id: null as number | null,
    type: 'expense' as string,
    amount: null as number | null,
    currency: vesselCurrencyData.value.code,
    house_of_zeros: vesselCurrencyData.value.decimals,
    vat_rate_id: null as number | null,
    vat_profile_id: null as number | null,
    amount_includes_vat: false,
    transaction_date: new Date().toISOString().split('T')[0],
    description: '',
    notes: '',
    supplier_id: null as number | null,
    crew_member_id: null as number | null,
    status: 'completed',
});

// Reset form when modal opens/closes
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.type = 'expense';
        form.bank_account_id = defaultBankAccount.value;
        // Priority: vessel_settings currency > bank account currency > EUR
        form.currency = vesselCurrencyData.value.code || selectedBankAccount.value?.currency || 'EUR';
        form.house_of_zeros = currentCurrencyDecimals.value;
        form.transaction_date = new Date().toISOString().split('T')[0];
        form.status = 'completed';
        // Remove VAT completely from expenses
        form.amount_includes_vat = false;
        form.vat_rate_id = null;
        form.vat_profile_id = null;
        form.supplier_id = null;
        form.crew_member_id = null;
        form.category_id = null;
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

// VAT is not used for expenses/removals

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

    // Ensure currency is always set from vessel settings or bank account
    // Priority: selected bank account currency > vessel settings currency > EUR
    if (!form.currency) {
        form.currency = selectedBankAccount.value?.currency || vesselCurrencyData.value.code || 'EUR';
    }

    // Ensure house_of_zeros is set
    if (!form.house_of_zeros) {
        form.house_of_zeros = currentCurrencyDecimals.value;
    }

    // Always ensure currency is set before submission (force update)
    // Priority: vessel_settings currency > bank account currency > EUR
    form.currency = vesselCurrencyData.value.code || selectedBankAccount.value?.currency || 'EUR';
    form.house_of_zeros = currentCurrencyDecimals.value;

    form.post(transactions.store.url({ vessel: getCurrentVesselId() }), {
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Funds have been removed successfully.'),
            });
            emit('success');
            emit('close');
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to remove funds. Please check the form for errors.'),
            });
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="text-red-600 dark:text-red-400">{{ t('Remove Transaction') }}</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Bank Account -->
                <div class="space-y-2">
                    <Label for="bank_account_id">{{ t('Bank Account') }} <span v-if="bankAccounts.length > 0" class="text-destructive">*</span></Label>
                    <Select
                        id="bank_account_id"
                        v-model="form.bank_account_id"
                        :options="bankAccountOptions"
                        :placeholder="t('Select a bank account')"
                        searchable
                        :error="!!form.errors.bank_account_id"
                    />
                    <InputError :message="form.errors.bank_account_id" />
                    <p v-if="bankAccounts.length === 0" class="text-xs text-muted-foreground">
                        {{ t('Please create a bank account first before removing funds.') }}
                    </p>
                </div>

                <!-- Category -->
                <div class="space-y-2">
                    <Label for="category_id">{{ t('Category') }} <span class="text-destructive">*</span></Label>
                    <Select
                        id="category_id"
                        v-model="form.category_id"
                        :options="categoryOptions"
                        :placeholder="t('Select a category')"
                        searchable
                        :error="!!form.errors.category_id"
                    />
                    <InputError :message="form.errors.category_id" />
                </div>

                <!-- Amount and Date -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Amount -->
                    <div class="space-y-2">
                        <MoneyInputWithLabel
                            v-model="form.amount"
                            :label="t('Amount')"
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
                        <Label for="transaction_date">{{ t('Transaction Date') }} <span class="text-destructive">*</span></Label>
                        <DateInput
                            id="transaction_date"
                            v-model="form.transaction_date"
                            :max="new Date().toISOString().split('T')[0]"
                            :class="{ 'border-destructive dark:border-destructive': form.errors.transaction_date }"
                        />
                        <InputError :message="form.errors.transaction_date" />
                    </div>
                </div>

                <!-- Supplier (for expenses) -->
                <div v-if="showSupplierField" class="space-y-2">
                    <Label for="supplier_id">{{ t('Supplier') }}</Label>
                    <Select
                        id="supplier_id"
                        v-model="form.supplier_id"
                        :options="supplierOptions"
                        :placeholder="t('Select a supplier')"
                        searchable
                        :error="!!form.errors.supplier_id"
                    />
                    <InputError :message="form.errors.supplier_id" />
                </div>

                <!-- Crew Member (for salary expenses) -->
                <div v-if="showCrewMemberField" class="space-y-2">
                    <Label for="crew_member_id">{{ t('Crew Member') }} <span class="text-destructive">*</span></Label>
                    <Select
                        id="crew_member_id"
                        v-model="form.crew_member_id"
                        :options="crewMemberOptions"
                        :placeholder="t('Select a crew member')"
                        searchable
                        :error="!!form.errors.crew_member_id"
                    />
                    <InputError :message="form.errors.crew_member_id" />
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="description">{{ t('Description') }}</Label>
                    <Input
                        id="description"
                        v-model="form.description"
                        type="text"
                        :placeholder="t('Enter transaction description')"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.description }"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <!-- Notes -->
                <div class="space-y-2">
                    <Label for="notes">{{ t('Notes') }}</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        :placeholder="t('Additional notes about this transaction')"
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
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="form.processing || bankAccounts.length === 0">
                        {{ form.processing ? t('Removing...') : t('Remove Transaction') }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

