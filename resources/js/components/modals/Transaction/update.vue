<script setup lang="ts">
import { ref, watch, computed } from 'vue';
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

interface Transaction {
    id: number;
    transaction_number: string;
    type: string;
    type_label: string;
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
    vat_profile_id: number | null;
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
    suppliers: Supplier[];
    crewMembers: CrewMember[];
    vatProfiles?: VatProfile[];
    defaultVatProfile?: VatProfile | null;
    defaultCurrency?: string; // Default currency from vessel_settings (passed from controller)
    transactionTypes: Record<string, string>;
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
// Get vessel currency from shared props - use currency from currency table
// Priority: props.defaultCurrency (from controller) > transaction.currency > current_vessel.currency_code > EUR
// The controller passes defaultCurrency from vessel_settings, which is the most reliable source
const vesselCurrency = computed(() => {
    // First try to get from props (passed from TransactionController edit method)
    const propsCurrency = (props as any).defaultCurrency;
    if (propsCurrency) {
        return propsCurrency;
    }
    // Fallback to transaction currency (if updating existing transaction)
    if (props.transaction?.currency) {
        return props.transaction.currency;
    }
    // Fallback to current_vessel (might be null, but try anyway)
    const currency = (page.props.auth as any)?.current_vessel?.currency_code;
    return currency || 'EUR';
});

// Get selected bank account currency
const selectedBankAccount = computed(() => {
    if (!form.bank_account_id) return null;
    return props.bankAccounts.find(acc => acc.id === form.bank_account_id);
});

const currentCurrency = computed(() => {
    return selectedBankAccount.value?.currency || props.transaction.currency || vesselCurrency.value;
});

// Filter categories by transaction type
const filteredCategories = computed(() => {
    if (!form.type) return [];
    return props.categories.filter(cat => cat.type === form.type);
});

// Show supplier field for expenses
const showSupplierField = computed(() => {
    return form.type === 'expense';
});

// Show crew member field for salary expenses
const showCrewMemberField = computed(() => {
    if (form.type !== 'expense' || !form.category_id) return false;
    const category = props.categories.find(cat => cat.id === form.category_id);
    // Check for salary categories in English (Salaries, Crew Salaries, Wages)
    return category && (category.name === 'Salaries' || category.name === 'Crew Salaries' || category.name === 'Wages');
});

// Get VAT profiles list
const vatProfiles = computed(() => props.vatProfiles || []);

// Convert to Select component options format
const transactionTypeOptions = computed(() => {
    const options = [{ value: '', label: t('Select transaction type') }];
    Object.entries(props.transactionTypes).forEach(([value, label]) => {
        options.push({ value, label: label as string });
    });
    return options;
});

const bankAccountOptions = computed(() => {
    const options = [{ value: '', label: t('Select a bank account') }];
    props.bankAccounts.forEach(account => {
        options.push({ value: account.id, label: `${account.name} (${account.bank_name})` });
    });
    return options;
});

const categoryOptions = computed(() => {
    const options = [{ value: '', label: t('Select a category') }];
    filteredCategories.value.forEach(category => {
        options.push({ value: category.id, label: t(category.name) });
    });
    return options;
});

const supplierOptions = computed(() => {
    const options = [{ value: '', label: t('None') }];
    (props.suppliers || []).forEach(supplier => {
        options.push({ value: supplier.id, label: supplier.company_name });
    });
    return options;
});

const crewMemberOptions = computed(() => {
    const options = [{ value: '', label: t('Select a crew member') }];
    (props.crewMembers || []).forEach(member => {
        options.push({ value: member.id, label: `${member.name} (${member.email})` });
    });
    return options;
});

const vatProfileOptions = computed(() => {
    const options = [{ value: null, label: t('Select a VAT profile') }];
    vatProfiles.value.forEach(profile => {
        options.push({ value: profile.id, label: `${profile.name} - ${profile.percentage}%` });
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

const defaultVatProfile = computed(() => props.defaultVatProfile);

const form = useForm({
    bank_account_id: props.transaction.bank_account_id,
    category_id: props.transaction.category_id,
    type: props.transaction.type,
    amount: props.transaction.amount,
    currency: props.transaction.currency,
    house_of_zeros: props.transaction.house_of_zeros,
    vat_profile_id: props.transaction.vat_profile_id || (props.transaction.type === 'income' && defaultVatProfile.value ? defaultVatProfile.value.id : null),
    transaction_date: props.transaction.transaction_date,
    description: props.transaction.description || '',
    notes: props.transaction.notes || '',
    reference: props.transaction.reference || '',
    supplier_id: props.transaction.supplier_id || null,
    crew_member_id: props.transaction.crew_member_id || null,
    status: props.transaction.status,
});

// Reset form when modal opens/closes
watch(() => props.open, (isOpen) => {
    if (isOpen && props.transaction) {
        form.reset();
        form.bank_account_id = props.transaction.bank_account_id;
        form.category_id = props.transaction.category_id;
        form.type = props.transaction.type;
        form.amount = props.transaction.amount;
        form.currency = props.transaction.currency;
        form.house_of_zeros = props.transaction.house_of_zeros;
        form.vat_profile_id = props.transaction.vat_profile_id || (props.transaction.type === 'income' && defaultVatProfile.value ? defaultVatProfile.value.id : null);
        // For expense transactions, ensure vat_profile_id is null
        if (form.type === 'expense') {
            form.vat_profile_id = null;
        }
        form.transaction_date = props.transaction.transaction_date;
        form.description = props.transaction.description || '';
        form.notes = props.transaction.notes || '';
        form.reference = props.transaction.reference || '';
        form.supplier_id = props.transaction.supplier_id;
        form.crew_member_id = props.transaction.crew_member_id;
        form.status = props.transaction.status;
        form.clearErrors();
    }
});

// Watch type change to reset category if needed
watch(() => form.type, () => {
    // Don't reset if current category matches the new type
    const currentCategory = props.categories.find(cat => cat.id === form.category_id);
    if (currentCategory && currentCategory.type !== form.type) {
        form.category_id = null;
    }
});

// Watch bank account change to update currency
watch(() => form.bank_account_id, (newAccountId) => {
    if (newAccountId && selectedBankAccount.value) {
        form.currency = selectedBankAccount.value.currency || vesselCurrency.value;
    }
});

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel IDs (alphanumeric strings) or numeric IDs
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

const submit = () => {
    form.put(transactions.update.url({ vessel: getCurrentVesselId(), transaction: props.transaction.id }), {
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: t('Success'),
                message: `${t('Transaction')} '${props.transaction.transaction_number}' ${t('has been updated successfully.')}`,
            });
            emit('success');
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to update transaction. Please check the form for errors.'),
            });
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{{ t('Edit Transaction') }} #{{ transaction.transaction_number }}</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Transaction Type and Bank Account -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Transaction Type -->
                    <div class="space-y-2">
                        <Label for="type">{{ t('Transaction Type') }} <span class="text-destructive">*</span></Label>
                        <Select
                            id="type"
                            v-model="form.type"
                            :options="transactionTypeOptions"
                            :placeholder="t('Select transaction type')"
                            :error="!!form.errors.type"
                        />
                        <InputError :message="form.errors.type" />
                    </div>

                    <!-- Bank Account -->
                    <div class="space-y-2">
                        <Label for="bank_account_id">{{ t('Bank Account') }} <span class="text-destructive">*</span></Label>
                        <Select
                            id="bank_account_id"
                            v-model="form.bank_account_id"
                            :options="bankAccountOptions"
                            :placeholder="t('Select a bank account')"
                            searchable
                            :error="!!form.errors.bank_account_id"
                        />
                        <InputError :message="form.errors.bank_account_id" />
                    </div>
                </div>

                <!-- Category -->
                <div class="space-y-2">
                    <Label for="category_id">{{ t('Category') }} <span class="text-destructive">*</span></Label>
                    <Select
                        id="category_id"
                        v-model="form.category_id"
                        :options="categoryOptions"
                        :placeholder="t('Select a category')"
                        :disabled="!form.type"
                        searchable
                        :error="!!form.errors.category_id"
                    />
                    <InputError :message="form.errors.category_id" />
                    <p v-if="!form.type" class="text-xs text-muted-foreground">{{ t('Please select a transaction type first') }}</p>
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
                            :decimals="form.house_of_zeros"
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
                        :placeholder="t('None')"
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

                <!-- VAT Profile (only for income transactions) -->
                <div v-if="form.type === 'income'" class="space-y-2">
                    <Label for="vat_profile_id">{{ t('VAT Profile') }}</Label>
                    <Select
                        id="vat_profile_id"
                        v-model="form.vat_profile_id"
                        :options="vatProfileOptions"
                        :placeholder="t('Select a VAT profile')"
                        searchable
                        :error="!!form.errors.vat_profile_id"
                    />
                    <InputError :message="form.errors.vat_profile_id" />
                    <p v-if="defaultVatProfile" class="text-xs text-muted-foreground">
                        {{ t('Default') }}: {{ defaultVatProfile.name }} - {{ defaultVatProfile.percentage }}%
                    </p>
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

                <!-- Reference -->
                <div class="space-y-2">
                    <Label for="reference">{{ t('Reference') }}</Label>
                    <Input
                        id="reference"
                        v-model="form.reference"
                        type="text"
                        :placeholder="t('Enter reference number')"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.reference }"
                    />
                    <InputError :message="form.errors.reference" />
                </div>

                <!-- Status -->
                <div class="space-y-2">
                    <Label for="status">{{ t('Status') }}</Label>
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

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <Button type="button" variant="outline" @click="emit('close')">
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? t('Updating...') : t('Update Transaction') }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

