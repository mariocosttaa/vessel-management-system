<script setup lang="ts">
import { ref, watch, computed, onMounted, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DateInput } from '@/components/ui/date-input';
import { Select } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import InputError from '@/components/InputError.vue';
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import MultiFileUpload from '@/components/Forms/MultiFileUpload.vue';
import { useNotifications } from '@/composables/useNotifications';
import { useMoney } from '@/composables/useMoney';
import { useI18n } from '@/composables/useI18n';
import transactions from '@/routes/panel/transactions';

interface TransactionCategory {
    id: number;
    name: string;
    type: string;
    color: string;
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
    vatRates?: any[]; // Keep for backward compatibility
    vatProfiles?: VatProfile[];
    defaultVatProfile?: VatProfile | null;
    defaultCurrency?: string; // Default currency from vessel_settings (passed from controller)
    mareaId?: number | null; // Optional marea ID to link transaction to marea
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const { addNotification } = useNotifications();
const { calculateVat, calculateTotal } = useMoney();
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

// Filter categories for income only
const incomeCategories = computed(() => {
    return props.categories.filter(cat => cat.type === 'income');
});

// Convert categories to Select component options format
const categoryOptions = computed(() => {
    const options = [{ value: null, label: t('Select a category') }];
    incomeCategories.value.forEach(category => {
        options.push({ value: category.id, label: t(category.name) });
    });
    return options;
});

// VAT handling
const amountIncludesVat = ref(false);

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

// Calculate VAT amounts based on current input
const vatCalculations = computed(() => {
    const amount = calculatedAmount.value; // Use calculated amount or form.amount
    if (!amount || !defaultVatProfile.value) {
        return {
            baseAmount: 0,
            vatAmount: 0,
            totalAmount: 0,
        };
    }

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
    category_id: null as number | null,
    type: 'income' as string,
    amount: null as number | null,
    amount_per_unit: null as number | null,
    quantity: null as number | null,
    currency: vesselCurrency.value,
    house_of_zeros: 2,
    vat_rate_id: null as number | null, // Keep for backward compatibility
    vat_profile_id: null as number | null,
    amount_includes_vat: false,
    transaction_date: new Date().toISOString().split('T')[0],
    description: '',
    notes: '',
    status: 'completed',
    marea_id: props.mareaId ?? null as number | null,
    files: [] as File[],
});

const selectedFiles = ref<File[]>([]);
const fileUploadRef = ref<InstanceType<typeof MultiFileUpload> | null>(null);

// Get default VAT profile from props
const defaultVatProfile = computed(() => props.defaultVatProfile);

// Get VAT profiles list
const vatProfiles = computed(() => props.vatProfiles || []);

// Reset form when modal opens
watch(() => props.open, (isOpen, wasOpen) => {
    // Only reset when modal opens (transitioning from false to true)
    if (isOpen && wasOpen === false) {
        // Reset all form fields to default values
        form.reset();
        form.type = 'income';
        form.currency = vesselCurrencyData.value.code || 'EUR';
        form.house_of_zeros = currentCurrencyDecimals.value;
        form.transaction_date = new Date().toISOString().split('T')[0];
        form.status = 'completed';
        form.amount_includes_vat = false;
        form.vat_rate_id = null;
        form.vat_profile_id = defaultVatProfile.value?.id || null;
        form.category_id = null;
        form.amount = null;
        form.description = '';
        form.notes = '';
        form.marea_id = props.mareaId ?? null;
        form.files = [];

        // Reset reactive refs
        amountIncludesVat.value = false;
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

// Watch selectedFiles to ensure it stays in sync with MultiFileUpload
// But only log/debug, don't modify form.files here to avoid loops
watch(selectedFiles, (newFiles) => {
    // This watcher ensures selectedFiles is reactive
    // The actual form.files will be set in submit()
}, { deep: false }); // Use shallow watch to avoid deep watching File objects

// Watch VAT checkbox
watch(amountIncludesVat, (value) => {
    form.amount_includes_vat = value;
    // Always use default VAT profile if available
    if (defaultVatProfile.value) {
        form.vat_profile_id = defaultVatProfile.value.id;
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

// Watch form.amount when not using price per unit
watch(() => form.amount, (newAmount) => {
    if (!usePricePerUnit.value && newAmount !== null) {
        // Only update if not using price per unit
    }
});

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel IDs (alphanumeric strings) or numeric IDs
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

const handleDialogUpdate = (value: boolean) => {
    // Only emit close when dialog is being closed (not opened)
    if (!value) {
        emit('close');
    }
};

const submit = () => {
    // Set amount_includes_vat flag
    form.amount_includes_vat = amountIncludesVat.value;

    // Calculate amount from amount_per_unit * quantity if using price per unit
    if (usePricePerUnit.value && pricePerUnit.value !== null && quantity.value !== null && quantity.value > 0) {
        form.amount_per_unit = pricePerUnit.value;
        form.quantity = Math.round(quantity.value); // Ensure quantity is integer
        form.amount = calculatedAmount.value;
    } else {
        // If not using price per unit, ensure amount_per_unit and quantity are null
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

    // Set default VAT profile if not set
    if (!form.vat_profile_id && defaultVatProfile.value) {
        form.vat_profile_id = defaultVatProfile.value.id;
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
            form.type = 'income';
            form.currency = vesselCurrencyData.value.code || 'EUR';
            form.house_of_zeros = currentCurrencyDecimals.value;
            form.transaction_date = new Date().toISOString().split('T')[0];
            form.status = 'completed';
            form.amount_includes_vat = false;
            form.vat_rate_id = null;
            form.vat_profile_id = defaultVatProfile.value?.id || null;
            form.category_id = null;
            form.amount = null;
            form.amount_per_unit = null;
            form.quantity = null;
            form.description = '';
            form.notes = '';
            form.marea_id = props.mareaId ?? null;
            form.files = [];

            // Reset reactive refs
            amountIncludesVat.value = false;
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
                title: t('Error'),
                message: t('Failed to add funds. Please check the form for errors.'),
            });
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="handleDialogUpdate">
        <DialogContent class="max-h-[90vh] overflow-y-auto" :style="{ maxWidth: '75vw', width: '100%' }">
            <DialogHeader>
                <DialogTitle class="text-green-600 dark:text-green-400">{{ t('Add Transaction') }}</DialogTitle>
                <DialogDescription class="sr-only">
                    {{ t('Add a new income transaction to the vessel') }}
                </DialogDescription>
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
                            variant="positive"
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

                <!-- VAT Section (only shown if default VAT profile exists and amount > 0) -->
                <div v-if="defaultVatProfile && calculatedAmount > 0" class="space-y-4 p-4 border rounded-lg bg-muted/50 dark:bg-muted/30">
                    <div class="flex items-center justify-between">
                        <Label for="amount_includes_vat" class="font-medium cursor-pointer" @click="amountIncludesVat = !amountIncludesVat">
                            {{ t('Amount already includes VAT') }}
                        </Label>
                        <Switch
                            id="amount_includes_vat"
                            :checked="amountIncludesVat"
                            @update:checked="(val) => amountIncludesVat = val"
                        />
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-muted-foreground">{{ t('VAT Profile') }}:</span>
                            <span class="font-medium">{{ defaultVatProfile.name }} ({{ defaultVatProfile.percentage }}%)</span>
                        </div>

                        <!-- VAT Breakdown -->
                        <div class="space-y-2 pt-2 border-t">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-muted-foreground">{{ t('Base Amount') }}:</span>
                                <MoneyDisplay
                                    :value="vatCalculations.baseAmount"
                                    :currency="currentCurrency"
                                    :decimals="currentCurrencyDecimals"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-muted-foreground">{{ t('VAT Amount') }} ({{ defaultVatProfile.percentage }}%):</span>
                                <MoneyDisplay
                                    :value="vatCalculations.vatAmount"
                                    :currency="currentCurrency"
                                    :decimals="currentCurrencyDecimals"
                                    size="sm"
                                    variant="neutral"
                                />
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t font-semibold">
                                <span class="text-sm">{{ t('Total Amount') }}:</span>
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
                                {{ t('VAT') }} ({{ defaultVatProfile.percentage }}%) {{ t('is included in the amount above.') }}
                            </span>
                            <span v-else>
                                {{ t('VAT') }} ({{ defaultVatProfile.percentage }}%) {{ t('will be added to the amount above.') }}
                            </span>
                        </p>
                    </div>
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
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? t('Adding...') : t('Add Funds') }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

