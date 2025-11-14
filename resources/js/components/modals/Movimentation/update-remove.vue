<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue';
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
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import { Switch } from '@/components/ui/switch';
import MultiFileUpload from '@/components/Forms/MultiFileUpload.vue';
import Icon from '@/components/Icon.vue';
import { useNotifications } from '@/composables/useNotifications';
import { useI18n } from '@/composables/useI18n';
import { router } from '@inertiajs/vue3';
import transactions from '@/routes/panel/movimentations';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { usePermissions } from '@/composables/usePermissions';

interface Transaction {
    id: number;
    transaction_number: string;
    type: string;
    amount: number;
    price_per_unit: number | null;
    quantity: number | null;
    currency: string;
    house_of_zeros: number;
    transaction_date: string;
    description: string | null;
    notes: string | null;
    reference: string | null;
    status: string;
    category_id: number;
    supplier_id: number | null;
    crew_member_id: number | null;
    files?: {
        id: number;
        src: string;
        name: string;
        size: number;
        type: string;
        size_human: string;
    }[];
}

interface TransactionCategory {
    id: number;
    name: string;
    type: string;
    color: string;
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
const { t } = useI18n();
const { canEdit } = usePermissions();
const page = usePage();

// File deletion state
const showDeleteFileDialog = ref(false);
const fileToDelete = ref<{ id: number; name: string } | null>(null);
const isDeletingFile = ref(false);

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

// Currency priority: transaction currency (what was saved) > vessel_settings > EUR
const currentCurrency = computed(() => {
    // Always prioritize transaction currency for update modals
    if (props.transaction?.currency) {
        return props.transaction.currency;
    }
    return vesselCurrencyData.value.code || 'EUR';
});

// Get current currency decimals
const currentCurrencyDecimals = computed(() => {
    if (vesselCurrencyData.value.decimals) {
        return vesselCurrencyData.value.decimals;
    }
    return props.transaction.house_of_zeros || 2;
});

// Filter categories for expense only
const expenseCategories = computed(() => {
    return props.categories.filter(cat => cat.type === 'expense');
});

// Convert to Select component options format
const categoryOptions = computed(() => {
    const options = [{ value: null, label: t('Select a category') }];
    expenseCategories.value.forEach(category => {
        options.push({ value: category.id, label: category.name });
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

const statusOptions = computed(() => {
    return [
        { value: 'pending', label: t('Pending') },
        { value: 'completed', label: t('Completed') },
        { value: 'cancelled', label: t('Cancelled') }
    ];
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

// Price per unit handling
const usePricePerUnit = ref(false);
const pricePerUnit = ref<number | null>(null);
const quantity = ref<number | null>(null);

// Initialize price per unit state from transaction
const initializePricePerUnit = () => {
    // Read from amount_per_unit (preferred) or price_per_unit (fallback for backward compatibility)
    const amountPerUnit = (props.transaction as any).amount_per_unit ?? props.transaction.price_per_unit;
    if (amountPerUnit !== null && amountPerUnit !== undefined && props.transaction.quantity !== null && props.transaction.quantity !== undefined) {
        usePricePerUnit.value = true;
        pricePerUnit.value = amountPerUnit;
        quantity.value = props.transaction.quantity;
    } else {
        usePricePerUnit.value = false;
        pricePerUnit.value = null;
        quantity.value = null;
    }
};

// Calculate total amount from price_per_unit * quantity
const calculatedAmount = computed(() => {
    if (usePricePerUnit.value && pricePerUnit.value !== null && quantity.value !== null && quantity.value > 0) {
        // pricePerUnit is in cents (integer), quantity is an integer
        const qty = Math.round(quantity.value);
        return Math.round(pricePerUnit.value * qty);
    }
    return form.amount || 0;
});

// Initialize form with transaction data
const form = useForm({
    category_id: props.transaction.category_id,
    type: 'expense' as string,
    amount: props.transaction.amount,
    amount_per_unit: (props.transaction as any).amount_per_unit ?? props.transaction.price_per_unit,
    quantity: props.transaction.quantity,
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
    files: [] as File[],
});

const selectedFiles = ref<File[]>([]);

// Reset form when modal opens/closes or transaction changes
watch(() => [props.open, props.transaction?.id], ([isOpen, transactionId]) => {
    if (isOpen && transactionId && props.transaction) {
        form.category_id = props.transaction.category_id;
        form.type = 'expense';
        form.amount = props.transaction.amount;
        form.amount_per_unit = (props.transaction as any).amount_per_unit ?? props.transaction.price_per_unit;
        form.quantity = props.transaction.quantity;
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
        form.files = [];
        selectedFiles.value = [];

        // Initialize price per unit state
        initializePricePerUnit();

        form.clearErrors();
    }
});

// Watch selectedFiles and update form.files
watch(selectedFiles, (files) => {
    form.files = files;
}, { deep: true });

// Watch type change to reset category
watch(() => form.category_id, () => {
    if (!showCrewMemberField.value) {
        form.crew_member_id = null;
    }
});

// Watch price per unit and quantity to update form.amount
watch([pricePerUnit, quantity, usePricePerUnit], () => {
    if (usePricePerUnit.value && pricePerUnit.value !== null && quantity.value !== null && quantity.value > 0) {
        form.amount_per_unit = pricePerUnit.value;
        form.quantity = Math.round(quantity.value); // Ensure quantity is integer
        form.amount = calculatedAmount.value;
    } else {
        form.amount_per_unit = null;
        form.quantity = null;
        // Keep form.amount as is if price per unit is not used
    }
});

// Initialize price per unit on component mount if transaction has price_per_unit
onMounted(() => {
    if (props.open && props.transaction) {
        initializePricePerUnit();
    }
});

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel IDs (alphanumeric strings) or numeric IDs
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

// File management functions
const getFileIcon = (type: string): string => {
    const extension = type.toLowerCase();
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
        return 'image';
    }
    if (extension === 'pdf') {
        return 'file-text';
    }
    if (['doc', 'docx'].includes(extension)) {
        return 'file-text';
    }
    if (['xls', 'xlsx'].includes(extension)) {
        return 'file-text';
    }
    if (['txt', 'csv'].includes(extension)) {
        return 'file-text';
    }
    return 'file';
};

const openFile = (src: string) => {
    // Ensure the URL starts with / for relative paths
    const url = src.startsWith('/') ? src : `/${src}`;
    window.open(url, '_blank');
};

const deleteFile = (file: { id: number; name: string }) => {
    fileToDelete.value = file;
    showDeleteFileDialog.value = true;
};

const confirmDeleteFile = () => {
    if (!fileToDelete.value) return;

    const vesselId = getCurrentVesselId();
    const fileId = fileToDelete.value.id;
    const fileName = fileToDelete.value.name;
    isDeletingFile.value = true;

    router.delete(`/panel/${vesselId}/movimentations/${props.transaction.id}/files/${fileId}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteFileDialog.value = false;
            fileToDelete.value = null;
            isDeletingFile.value = false;
            // Emit success to refresh transaction data in parent
            emit('success');
        },
        onError: () => {
            isDeletingFile.value = false;
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to delete file. Please try again.'),
            });
        },
    });
};

const cancelDeleteFile = () => {
    showDeleteFileDialog.value = false;
    fileToDelete.value = null;
    isDeletingFile.value = false;
};

const submit = () => {
    // No VAT for expenses/removals
    form.amount_includes_vat = false;
    form.vat_profile_id = null;
    form.vat_rate_id = null;

    // Calculate amount from price_per_unit * quantity if using price per unit
    if (usePricePerUnit.value && pricePerUnit.value !== null && quantity.value !== null && quantity.value > 0) {
        form.amount_per_unit = pricePerUnit.value;
        form.quantity = Math.round(quantity.value); // Ensure quantity is integer
        form.amount = calculatedAmount.value;
    } else {
        // If not using price per unit, ensure price_per_unit and quantity are null
        form.amount_per_unit = null;
        form.quantity = null;
    }

    if (!form.currency) {
        form.currency = vesselCurrencyData.value.code || props.transaction.currency || 'EUR';
    }

    if (!form.house_of_zeros) {
        form.house_of_zeros = currentCurrencyDecimals.value;
    }

    form.currency = vesselCurrencyData.value.code || props.transaction.currency || 'EUR';
    form.house_of_zeros = currentCurrencyDecimals.value;

    form.put(transactions.update.url({ vessel: getCurrentVesselId(), transaction: props.transaction.id }), {
        forceFormData: true, // Required for file uploads
        preserveScroll: true,
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: t('Success'),
                message: `${t('Transaction')} '${props.transaction.transaction_number}' ${t('has been updated successfully.')}`,
            });
            emit('success');
            emit('close');
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
        <DialogContent class="max-h-[90vh] overflow-y-auto" :style="{ maxWidth: '75vw', width: '100%' }">
            <DialogHeader>
                <DialogTitle class="text-red-600 dark:text-red-400">{{ t('Update Transaction') }} #{{ transaction.transaction_number }}</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Two Column Layout: Left (Form Fields) | Right (Files) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column: Form Fields -->
                    <div class="lg:col-span-2 space-y-6">
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

                <!-- Price Per Unit Checkbox -->
                <div class="flex items-center justify-between p-4 border rounded-lg bg-muted/50 dark:bg-muted/30">
                    <Label for="use_price_per_unit" class="font-medium cursor-pointer" @click="usePricePerUnit = !usePricePerUnit">
                        {{ t('Use Price Per Unit and Quantity') }}
                    </Label>
                    <Switch
                        id="use_price_per_unit"
                        :checked="usePricePerUnit"
                        @update:checked="(val) => usePricePerUnit = val"
                    />
                </div>

                <!-- Price Per Unit and Quantity Fields (shown when checkbox is checked) -->
                <div v-if="usePricePerUnit" class="space-y-4 p-4 border rounded-lg bg-muted/50 dark:bg-muted/30">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Price Per Unit -->
                        <div class="space-y-2">
                            <MoneyInputWithLabel
                                v-model="pricePerUnit"
                                :label="t('Price Per Unit')"
                                :currency="currentCurrency"
                                placeholder="0,00"
                                :error="form.errors.amount_per_unit"
                                :show-currency="true"
                                return-type="int"
                                :decimals="currentCurrencyDecimals"
                                required
                            />
                            <InputError :message="form.errors.amount_per_unit" />
                        </div>

                        <!-- Quantity -->
                        <div class="space-y-2">
                            <Label for="quantity">{{ t('Quantity') }} <span class="text-destructive">*</span></Label>
                            <Input
                                id="quantity"
                                v-model.number="quantity"
                                type="number"
                                step="1"
                                min="1"
                                placeholder="0"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.quantity }"
                                required
                            />
                            <InputError :message="form.errors.quantity" />
                        </div>
                    </div>

                    <!-- Calculated Total -->
                    <div v-if="pricePerUnit !== null && quantity !== null && quantity > 0" class="flex justify-between items-center pt-2 border-t">
                        <span class="text-sm font-medium">{{ t('Total Amount') }}:</span>
                        <MoneyDisplay
                            :value="calculatedAmount"
                            :currency="currentCurrency"
                            :decimals="currentCurrencyDecimals"
                            size="sm"
                            variant="negative"
                            class="font-semibold"
                        />
                    </div>
                </div>

                <!-- Amount and Date -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Amount (shown when not using price per unit) -->
                    <div v-if="!usePricePerUnit" class="space-y-2">
                        <MoneyInputWithLabel
                            v-model="form.amount"
                            :label="t('Amount')"
                            :currency="currentCurrency"
                            placeholder="0,00"
                            :error="form.errors.amount"
                            :show-currency="true"
                            return-type="int"
                            :decimals="currentCurrencyDecimals"
                            :required="!usePricePerUnit"
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

                <!-- Status -->
                <div class="space-y-2">
                    <Label for="status">{{ t('Status') }}</Label>
                    <Select
                        id="status"
                        v-model="form.status"
                        :options="statusOptions"
                        :placeholder="t('Select status')"
                        :error="!!form.errors.status"
                    />
                    <InputError :message="form.errors.status" />
                </div>

                    </div>

                    <!-- Right Column: Files Section -->
                    <div class="lg:col-span-1 space-y-4 border-l-0 lg:border-l lg:pl-6 pt-0 lg:pt-0">
                        <!-- Existing Files -->
                        <div v-if="transaction.files && transaction.files.length > 0" class="space-y-3">
                            <Label class="text-base font-semibold">{{ t('Existing Files') }}</Label>
                            <div class="space-y-2 max-h-[300px] overflow-y-auto">
                                <div
                                    v-for="file in transaction.files"
                                    :key="file.id"
                                    class="flex flex-col gap-2 p-3 rounded-lg border border-border bg-card hover:bg-muted/50 transition-colors"
                                >
                                    <div class="flex items-start gap-2">
                                        <Icon
                                            :name="getFileIcon(file.type)"
                                            class="w-5 h-5 text-muted-foreground flex-shrink-0 mt-0.5"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-foreground truncate">
                                                {{ file.name }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ file.size_human }} · {{ file.type.toUpperCase() }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 pt-1">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="openFile(file.src)"
                                            class="flex-1 text-xs"
                                        >
                                            <Icon name="external-link" class="w-3 h-3 mr-1" />
                                            {{ t('View') }}
                                        </Button>
                                        <Button
                                            v-if="canEdit('transactions')"
                                            type="button"
                                            variant="destructive"
                                            size="sm"
                                            @click="deleteFile({ id: file.id, name: file.name })"
                                            class="flex-shrink-0"
                                            :title="t('Delete file')"
                                        >
                                            <Icon name="trash-2" class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="space-y-2">
                            <Label class="text-base font-semibold">{{ t('Add New Files') }}</Label>
                            <MultiFileUpload
                                v-model="selectedFiles"
                                :error="form.errors.files"
                                @error="(error) => form.setError('files', error)"
                            />
                        </div>
                    </div>
                </div>

                <!-- Hidden fields -->
                <input type="hidden" v-model="form.currency" />
                <input type="hidden" v-model="form.house_of_zeros" />
                <input type="hidden" v-model="form.type" />

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

    <!-- File Deletion Confirmation Dialog -->
    <ConfirmationDialog
        v-model:open="showDeleteFileDialog"
        :title="t('Delete File')"
        :description="t('This action cannot be undone.')"
        :message="t('Are you sure you want to delete the file') + ` '${fileToDelete?.name}'? ` + t('This will permanently remove the file from this transaction.')"
        :confirm-text="t('Delete File')"
        :cancel-text="t('Cancel')"
        variant="destructive"
        type="danger"
        :loading="isDeletingFile"
        @confirm="confirmDeleteFile"
        @cancel="cancelDeleteFile"
    />
</template>

