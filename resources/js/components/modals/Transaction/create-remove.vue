<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DateInput } from '@/components/ui/date-input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import { Switch } from '@/components/ui/switch';
import MultiFileUpload from '@/components/Forms/MultiFileUpload.vue';
import { useNotifications } from '@/composables/useNotifications';
import transactions from '@/routes/panel/transactions';

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
    categories: TransactionCategory[];
    suppliers: Supplier[];
    crewMembers: CrewMember[];
    vatRates?: any[]; // Keep for backward compatibility (not used)
    vatProfiles?: any[]; // Keep for backward compatibility (not used)
    defaultVatProfile?: any; // Keep for backward compatibility (not used)
    defaultCurrency?: string; // Default currency from vessel_settings (passed from controller)
    mareaId?: number | null; // Optional marea ID to link transaction to marea
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const { addNotification } = useNotifications();
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

// Currency priority: vessel_settings (defaultCurrency prop) > EUR
// Vessel settings currency should always take precedence
const currentCurrency = computed(() => {
    // Always prioritize vessel_settings currency (from defaultCurrency prop)
    return vesselCurrencyData.value.code || 'EUR';
});

// Get current currency decimals
// Priority: vessel_settings currency decimals > 2
const currentCurrencyDecimals = computed(() => {
    // Always prioritize vessel_settings currency decimals
    return vesselCurrencyData.value.decimals || 2;
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

// Price per unit handling
const usePricePerUnit = ref(false);
const pricePerUnit = ref<number | null>(null);
const quantity = ref<number | null>(null);

// Calculate total amount from price_per_unit * quantity
const calculatedAmount = computed(() => {
    if (usePricePerUnit.value && pricePerUnit.value !== null && quantity.value !== null && quantity.value > 0) {
        // pricePerUnit is in cents (integer), quantity is an integer
        const qty = Math.round(quantity.value);
        return Math.round(pricePerUnit.value * qty);
    }
    return form.amount || 0;
});

// VAT is not used for expenses/removals

const form = useForm({
    category_id: null as number | null,
    type: 'expense' as string,
    amount: null as number | null,
    amount_per_unit: null as number | null,
    quantity: null as number | null,
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
    marea_id: props.mareaId ?? null as number | null,
    status: 'completed',
    files: [] as File[],
});

const selectedFiles = ref<File[]>([]);
const fileUploadRef = ref<InstanceType<typeof MultiFileUpload> | null>(null);

// Reset form when modal opens
watch(() => props.open, (isOpen, wasOpen) => {
    // Only reset when modal opens (transitioning from false to true)
    if (isOpen && wasOpen === false) {
        // Reset all form fields to default values
        form.reset();
        form.type = 'expense';
        form.currency = vesselCurrencyData.value.code || 'EUR';
        form.house_of_zeros = currentCurrencyDecimals.value;
        form.transaction_date = new Date().toISOString().split('T')[0];
        form.status = 'completed';
        form.amount_includes_vat = false;
        form.vat_rate_id = null;
        form.vat_profile_id = null;
        form.supplier_id = null;
        form.crew_member_id = null;
        form.marea_id = props.mareaId ?? null;
        form.category_id = null;
        form.amount = null;
        form.amount_per_unit = null;
        form.quantity = null;
        form.description = '';
        form.notes = '';
        form.files = [];

        // Reset reactive refs
        usePricePerUnit.value = false;
        pricePerUnit.value = null;
        quantity.value = null;
        selectedFiles.value = [];

        // Clear file upload component after a brief delay to ensure component is mounted
        nextTick(() => {
            if (fileUploadRef.value && typeof fileUploadRef.value.clearFiles === 'function') {
                fileUploadRef.value.clearFiles();
            }
        });

        // Clear all errors
        form.clearErrors();
    }
});

// Don't watch selectedFiles - update form.files directly in submit
// This prevents infinite loops from reactive updates

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

// VAT is not used for expenses/removals

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

const handleDialogUpdate = (value: boolean) => {
    // Only emit close when dialog is being closed (not opened)
    if (!value) {
        emit('close');
    }
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

    // Get files for submission - prioritize component ref, then selectedFiles, then empty array
    let filesToSubmit: File[] = [];

    // Method 1: Get files directly from the MultiFileUpload component (most reliable)
    if (fileUploadRef.value && typeof fileUploadRef.value.getFiles === 'function') {
        try {
            const componentFiles = fileUploadRef.value.getFiles();
            if (componentFiles && Array.isArray(componentFiles) && componentFiles.length > 0) {
                filesToSubmit = Array.from(componentFiles);
            }
        } catch (e) {
            // If getFiles fails, fall through to selectedFiles
        }
    }

    // Method 2: Fallback to selectedFiles if component ref didn't work or returned empty
    if (filesToSubmit.length === 0 && selectedFiles.value && Array.isArray(selectedFiles.value) && selectedFiles.value.length > 0) {
        filesToSubmit = Array.from(selectedFiles.value);
    }

    // Set form.files for submission (can be empty array if no files)
    form.files = filesToSubmit;

    // Ensure currency is always set from vessel settings
    // Priority: vessel settings currency > EUR
    if (!form.currency) {
        form.currency = vesselCurrencyData.value.code || 'EUR';
    }

    // Ensure house_of_zeros is set
    if (!form.house_of_zeros) {
        form.house_of_zeros = currentCurrencyDecimals.value;
    }

    // Always ensure currency is set before submission (force update)
    // Priority: vessel_settings currency > EUR
    form.currency = vesselCurrencyData.value.code || 'EUR';
    form.house_of_zeros = currentCurrencyDecimals.value;

    // Set marea_id if provided
    if (props.mareaId) {
        form.marea_id = props.mareaId;
    }

    form.post(transactions.store.url({ vessel: getCurrentVesselId() }), {
        forceFormData: true, // Required for file uploads
        preserveScroll: true,
        onSuccess: (page) => {
            // Reset all form fields to default values
            form.reset();
            form.type = 'expense';
            form.currency = vesselCurrencyData.value.code || 'EUR';
            form.house_of_zeros = currentCurrencyDecimals.value;
            form.transaction_date = new Date().toISOString().split('T')[0];
            form.status = 'completed';
            form.amount_includes_vat = false;
            form.vat_rate_id = null;
            form.vat_profile_id = null;
            form.supplier_id = null;
            form.crew_member_id = null;
            form.marea_id = props.mareaId ?? null;
            form.category_id = null;
            form.amount = null;
            form.amount_per_unit = null;
            form.quantity = null;
            form.description = '';
            form.notes = '';
            form.files = [];

            // Reset reactive refs
            usePricePerUnit.value = false;
            pricePerUnit.value = null;
            quantity.value = null;
            selectedFiles.value = [];

            // Clear file upload component
            if (fileUploadRef.value && typeof fileUploadRef.value.clearFiles === 'function') {
                fileUploadRef.value.clearFiles();
            }

            // Clear all errors
            form.clearErrors();

            // Close modal - parent will handle reload
            emit('close');

            // Small delay before emitting success to ensure modal closes first
            requestAnimationFrame(() => {
                emit('success');
            });
        },
        onError: (errors) => {
            // Errors are already displayed via form.errors in the template
            addNotification({
                type: 'error',
                title: 'Error',
                message: 'Failed to remove funds. Please check the form for errors.',
            });
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="handleDialogUpdate">
        <DialogContent class="max-h-[90vh] overflow-y-auto" :style="{ maxWidth: '75vw', width: '100%' }">
            <DialogHeader>
                <DialogTitle class="text-red-600 dark:text-red-400">Remove Transaction</DialogTitle>
                <DialogDescription class="sr-only">
                    Add a new expense transaction to the vessel
                </DialogDescription>
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

                <!-- Price Per Unit Checkbox -->
                <div class="flex items-center justify-between p-4 border rounded-lg bg-muted/50 dark:bg-muted/30">
                    <Label for="use_price_per_unit" class="font-medium cursor-pointer" @click="usePricePerUnit = !usePricePerUnit">
                        Use Price Per Unit and Quantity
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
                                label="Price Per Unit"
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
                            <Label for="quantity">Quantity <span class="text-destructive">*</span></Label>
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
                        <span class="text-sm font-medium">Total Amount:</span>
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
                            label="Amount"
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

                    </div>

                    <!-- Right Column: Files Section -->
                    <div class="lg:col-span-1 space-y-4 border-l-0 lg:border-l lg:pl-6 pt-0 lg:pt-0">
                        <!-- File Upload -->
                        <div class="space-y-2">
                            <Label class="text-base font-semibold"></Label>
                            <MultiFileUpload
                                ref="fileUploadRef"
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
                <input type="hidden" v-model="form.status" />

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <Button type="button" variant="outline" @click="emit('close')">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Removing...' : 'Remove Transaction' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

