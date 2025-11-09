<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue';
import MultiFileUpload from '@/components/Forms/MultiFileUpload.vue';
import Icon from '@/components/Icon.vue';
import { useNotifications } from '@/composables/useNotifications';
import { router } from '@inertiajs/vue3';
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
    files: [] as File[],
});

const selectedFiles = ref<File[]>([]);

// Reset form when modal opens/closes or transaction changes
watch(() => [props.open, props.transaction?.id], ([isOpen, transactionId]) => {
    if (isOpen && transactionId && props.transaction) {
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
        form.files = [];
        selectedFiles.value = [];
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

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
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

const deleteFile = (fileId: number) => {
    if (!confirm('Are you sure you want to delete this file?')) {
        return;
    }

    const vesselId = getCurrentVesselId();
    router.delete(`/panel/${vesselId}/transactions/${props.transaction.id}/files/${fileId}`, {
        preserveScroll: true,
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: 'Success',
                message: 'File deleted successfully.',
            });
            // Reload the page to refresh the transaction data
            router.reload({ only: ['transactions'] });
        },
        onError: () => {
            addNotification({
                type: 'error',
                title: 'Error',
                message: 'Failed to delete file.',
            });
        },
    });
};

const submit = () => {
    // No VAT for expenses/removals
    form.amount_includes_vat = false;
    form.vat_profile_id = null;
    form.vat_rate_id = null;

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
        <DialogContent class="max-h-[90vh] overflow-y-auto" :style="{ maxWidth: '75vw', width: '100%' }">
            <DialogHeader>
                <DialogTitle class="text-red-600 dark:text-red-400">Update Transaction #{{ transaction.transaction_number }}</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Two Column Layout: Left (Form Fields) | Right (Files) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column: Form Fields -->
                    <div class="lg:col-span-2 space-y-6">
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

                    </div>

                    <!-- Right Column: Files Section -->
                    <div class="lg:col-span-1 space-y-4 border-l-0 lg:border-l lg:pl-6 pt-0 lg:pt-0">
                        <!-- Existing Files -->
                        <div v-if="transaction.files && transaction.files.length > 0" class="space-y-3">
                            <Label class="text-base font-semibold">Existing Files</Label>
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
                                            View
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="sm"
                                            @click="deleteFile(file.id)"
                                            class="flex-shrink-0"
                                            title="Delete file"
                                        >
                                            <Icon name="trash-2" class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="space-y-2">
                            <Label class="text-base font-semibold">Add New Files</Label>
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

