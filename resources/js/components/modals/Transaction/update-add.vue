<script setup lang="ts">
import { ref, watch, computed, onMounted, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
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
import Icon from '@/components/Icon.vue';
import { useNotifications } from '@/composables/useNotifications';
import { useMoney } from '@/composables/useMoney';
import { useI18n } from '@/composables/useI18n';
import { router } from '@inertiajs/vue3';
import transactions from '@/routes/panel/transactions';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { usePermissions } from '@/composables/usePermissions';

interface Transaction {
    id: number;
    transaction_number: string;
    type: string;
    amount: number;
    price_per_unit: number | null;
    quantity: number | null;
    vat_amount?: number;
    total_amount?: number;
    currency: string;
    house_of_zeros: number;
    transaction_date: string;
    description: string | null;
    notes: string | null;
    reference: string | null;
    status: string;
    category_id: number;
    vat_profile_id: number | null;
    amount_includes_vat?: boolean;
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
    const propsCurrency = props.defaultCurrency;
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
    return vesselCurrencyData.value.code || props.defaultCurrency || 'EUR';
});

// Get current currency decimals
const currentCurrencyDecimals = computed(() => {
    if (vesselCurrencyData.value.decimals) {
        return vesselCurrencyData.value.decimals;
    }
    return props.transaction?.house_of_zeros || 2;
});

// Filter categories for income only
const incomeCategories = computed(() => {
    return (props.categories || []).filter(cat => cat.type === 'income');
});

// Convert to Select component options format
const categoryOptions = computed(() => {
    const options = [{ value: null, label: t('Select a category') }];
    incomeCategories.value.forEach(category => {
        options.push({ value: category.id, label: t(category.name) });
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

// VAT handling - will be set when transaction loads
const amountIncludesVat = ref(false);

// Price per unit handling
const usePricePerUnit = ref(false);
const pricePerUnit = ref<number | null>(null);
const quantity = ref<number | null>(null);

// Initialize price per unit state from transaction
const initializePricePerUnit = () => {
    if (!props.transaction) return;

    // Read from amount_per_unit (preferred) or price_per_unit (fallback for backward compatibility)
    const amountPerUnit = (props.transaction as any).amount_per_unit ?? props.transaction.price_per_unit;
    if (amountPerUnit !== null && amountPerUnit !== undefined && amountPerUnit > 0 &&
        props.transaction.quantity !== null && props.transaction.quantity !== undefined && props.transaction.quantity > 0) {
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

// Watch transaction changes separately for amountIncludesVat
watch(() => props.transaction, (transaction) => {
    if (transaction) {
        initializePricePerUnit();
    }
}, { deep: true });

// Initialize price per unit on component mount if transaction has price_per_unit
onMounted(() => {
    if (props.open && props.transaction) {
        initializeFormFromTransaction();
    }
});

// Calculate VAT amounts based on current input
const vatCalculations = computed(() => {
    const amount = calculatedAmount.value; // Use calculated amount or form.amount
    if (!amount || !selectedVatProfile.value) {
        return {
            baseAmount: 0,
            vatAmount: 0,
            totalAmount: 0,
        };
    }

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

// Initialize form - will be populated from transaction when modal opens
// We'll initialize with empty/default values and populate from transaction in the watch
const form = useForm({
    category_id: null as number | null,
    type: 'income' as string,
    amount: null as number | null,
    amount_per_unit: null as number | null,
    quantity: null as number | null,
    currency: props.defaultCurrency || 'EUR',
    house_of_zeros: 2,
    vat_profile_id: null as number | null,
    files: [] as File[],
    amount_includes_vat: false,
    transaction_date: '' as string,
    description: '',
    notes: '',
    status: 'pending',
});

const selectedFiles = ref<File[]>([]);

// Get default VAT profile from props
const defaultVatProfile = computed(() => props.defaultVatProfile);

// Get VAT profiles list
const vatProfiles = computed(() => props.vatProfiles || []);

// Function to initialize form from transaction
const initializeFormFromTransaction = () => {
    if (!props.transaction) {
        console.warn('Cannot initialize form: transaction is not available');
        return;
    }

    if (!props.open) {
        return;
    }

    console.log('Initializing form from transaction:', {
        id: props.transaction.id,
        category_id: props.transaction.category_id,
        amount: props.transaction.amount,
        transaction_date: props.transaction.transaction_date,
        description: props.transaction.description
    });

    try {
        // Clear errors first
        form.clearErrors();

        // Set category_id - must be a valid positive integer
        const categoryId = props.transaction.category_id ? Number(props.transaction.category_id) : null;
        if (categoryId && categoryId > 0) {
            form.category_id = categoryId;
        } else {
            console.error('Invalid category_id from transaction:', props.transaction.category_id);
            form.category_id = null;
        }

        form.type = 'income';

        // Set amount - must be in cents (integer)
        if (props.transaction.amount !== null && props.transaction.amount !== undefined) {
            const amount = Number(props.transaction.amount);
            if (!isNaN(amount) && amount > 0) {
                form.amount = amount;
            } else {
                console.warn('Invalid amount from transaction:', props.transaction.amount);
                form.amount = null;
            }
        } else {
            form.amount = null;
        }

        // Set amount_per_unit and quantity
        const amountPerUnit = (props.transaction as any).amount_per_unit ?? props.transaction.price_per_unit ?? null;
        if (amountPerUnit !== null && amountPerUnit !== undefined) {
            form.amount_per_unit = Number(amountPerUnit);
        } else {
            form.amount_per_unit = null;
        }

        if (props.transaction.quantity !== null && props.transaction.quantity !== undefined) {
            form.quantity = Number(props.transaction.quantity);
        } else {
            form.quantity = null;
        }

        // Set currency and house_of_zeros
        form.currency = props.transaction.currency || vesselCurrencyData.value.code || props.defaultCurrency || 'EUR';
        form.house_of_zeros = props.transaction.house_of_zeros || currentCurrencyDecimals.value || 2;

        // Set VAT profile
        if (props.transaction.vat_profile_id) {
            form.vat_profile_id = Number(props.transaction.vat_profile_id);
        } else if (props.defaultVatProfile?.id) {
            form.vat_profile_id = props.defaultVatProfile.id;
        } else {
            form.vat_profile_id = null;
        }

        // Set amount_includes_vat
        if (props.transaction.amount_includes_vat !== undefined) {
            amountIncludesVat.value = Boolean(props.transaction.amount_includes_vat);
        } else {
            // Calculate from amounts if not explicitly set
            if (props.transaction.vat_amount && props.transaction.total_amount && props.transaction.amount) {
                const expectedTotalIfExcluded = props.transaction.amount + props.transaction.vat_amount;
                const diffToAmount = Math.abs(props.transaction.total_amount - props.transaction.amount);
                const diffToExpected = Math.abs(props.transaction.total_amount - expectedTotalIfExcluded);
                amountIncludesVat.value = diffToAmount < diffToExpected;
            } else {
                amountIncludesVat.value = false;
            }
        }
        form.amount_includes_vat = amountIncludesVat.value;

        // Normalize transaction_date to YYYY-MM-DD format
        if (props.transaction.transaction_date) {
            try {
                let dateStr = String(props.transaction.transaction_date).trim();

                // If it's already in YYYY-MM-DD format, use it directly
                if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
                    form.transaction_date = dateStr;
                } else {
                    // Try to parse and format it
                    const date = new Date(dateStr);
                    if (!isNaN(date.getTime())) {
                        // Format as YYYY-MM-DD (avoid timezone issues by using UTC methods or local methods consistently)
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        form.transaction_date = `${year}-${month}-${day}`;
                    } else {
                        // Fallback: try to extract date parts if it's in a different format
                        const dateMatch = dateStr.match(/(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/);
                        if (dateMatch) {
                            const [, year, month, day] = dateMatch;
                            form.transaction_date = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
                        } else {
                            // Last resort: use today's date
                            const today = new Date();
                            const year = today.getFullYear();
                            const month = String(today.getMonth() + 1).padStart(2, '0');
                            const day = String(today.getDate()).padStart(2, '0');
                            form.transaction_date = `${year}-${month}-${day}`;
                        }
                    }
                }
                console.log('Set transaction_date to:', form.transaction_date, 'from original:', props.transaction.transaction_date);
            } catch (e) {
                console.error('Error parsing transaction_date:', e, 'Original value:', props.transaction.transaction_date);
                // Fallback to today's date
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                form.transaction_date = `${year}-${month}-${day}`;
            }
        } else {
            // No date provided, use today's date
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            form.transaction_date = `${year}-${month}-${day}`;
        }

        // Set description and notes
        form.description = props.transaction.description || '';
        form.notes = props.transaction.notes || '';

        // Set status
        form.status = props.transaction.status || 'pending';

        // Clear files
        form.files = [];
        selectedFiles.value = [];

        // Initialize price per unit state (this sets usePricePerUnit, pricePerUnit, quantity refs)
        initializePricePerUnit();

        // Clear any previous errors
        form.clearErrors();

        // Log final form state
        console.log('Form initialized successfully:', {
            category_id: form.category_id,
            amount: form.amount,
            transaction_date: form.transaction_date,
            description: form.description,
            currency: form.currency,
            status: form.status,
            vat_profile_id: form.vat_profile_id,
            amount_includes_vat: form.amount_includes_vat
        });
    } catch (error) {
        console.error('Error initializing form:', error);
    }
};

// Watch for modal opening and transaction changes
watch([() => props.open, () => props.transaction?.id], ([isOpen, transactionId]) => {
    if (isOpen && transactionId && props.transaction) {
        console.log('Watch triggered - initializing form', { isOpen, transactionId });
        // Use nextTick to ensure DOM is ready
        nextTick(() => {
            initializeFormFromTransaction();
            // Double-check form values after initialization
            nextTick(() => {
                console.log('Form values after initialization:', {
                    category_id: form.category_id,
                    amount: form.amount,
                    transaction_date: form.transaction_date,
                    description: form.description
                });
            });
        });
    }
}, { immediate: true });

// Watch form.amount to ensure it's always a number
watch(() => form.amount, (newAmount) => {
    if (newAmount !== null && newAmount !== undefined && typeof newAmount !== 'number') {
        console.warn('Form amount is not a number, converting:', newAmount);
        form.amount = Number(newAmount) || 0;
    }
});

// Watch form.transaction_date to ensure it's properly set
watch(() => form.transaction_date, (newDate) => {
    if (!newDate && props.transaction?.transaction_date) {
        console.warn('Transaction date is empty, re-initializing from transaction');
        const dateStr = props.transaction.transaction_date;
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
            form.transaction_date = dateStr;
        } else {
            try {
                const date = new Date(dateStr);
                if (!isNaN(date.getTime())) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    form.transaction_date = `${year}-${month}-${day}`;
                }
            } catch (e) {
                console.error('Error re-initializing date:', e);
            }
        }
    }
});

// Watch selectedFiles and update form.files
watch(selectedFiles, (files) => {
    form.files = files;
}, { deep: true });

// Watch VAT switch
watch(amountIncludesVat, (value) => {
    form.amount_includes_vat = value;
    if (selectedVatProfile.value) {
        form.vat_profile_id = selectedVatProfile.value.id;
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

    router.delete(`/panel/${vesselId}/transactions/${props.transaction.id}/files/${fileId}`, {
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

// Handle dialog update
const handleDialogUpdate = (value: boolean) => {
    // Only emit close when dialog is being closed (not opened)
    if (!value) {
        emit('close');
    }
};

const submit = async () => {
    if (!props.transaction?.id) {
        addNotification({
            type: 'error',
            title: t('Error'),
            message: t('Transaction data is not available. Please try again.'),
        });
        return;
    }

    // Use nextTick to ensure form inputs have finished updating the form object
    await nextTick();

    // Read values directly from form object properties (reactive to v-model)
    // These should be the current values from the form inputs
    let categoryId = form.category_id ? Number(form.category_id) : null;
    let transactionDate = form.transaction_date ? String(form.transaction_date).trim() : '';
    let amountValue = form.amount ? Number(form.amount) : null;

    // Ensure amount_includes_vat is set from the reactive ref
    form.amount_includes_vat = amountIncludesVat.value;

    // Handle price per unit vs amount
    if (usePricePerUnit.value && pricePerUnit.value !== null && quantity.value !== null && quantity.value > 0) {
        form.amount_per_unit = Number(pricePerUnit.value);
        form.quantity = Math.round(Number(quantity.value));
        form.amount = calculatedAmount.value;
        amountValue = calculatedAmount.value;
        // Clear amount_per_unit and quantity if not using price per unit
    } else {
        form.amount_per_unit = null;
        form.quantity = null;
        // amountValue is already set from form.amount above
    }

    console.log('Pre-submission validation check:', {
        categoryId,
        transactionDate,
        amountValue,
        formCategoryId: form.category_id,
        formTransactionDate: form.transaction_date,
        formAmount: form.amount,
        usePricePerUnit: usePricePerUnit.value,
        pricePerUnit: pricePerUnit.value,
        quantity: quantity.value,
        amountIncludesVat: amountIncludesVat.value
    });

    // Validate category_id
    if (!categoryId || categoryId === 0) {
        console.error('Category validation failed:', categoryId, 'form.category_id:', form.category_id);
        form.setError('category_id', 'Please select a category.');
        addNotification({
            type: 'error',
            title: t('Validation Error'),
            message: t('Please select a category.'),
        });
        return;
    }

    // Validate transaction_date
    if (!transactionDate || transactionDate.trim() === '') {
        console.error('Transaction date validation failed:', transactionDate, 'form.transaction_date:', form.transaction_date);
        form.setError('transaction_date', 'Transaction date is required.');
        addNotification({
            type: 'error',
            title: t('Validation Error'),
            message: t('Transaction date is required.'),
        });
        return;
    }

    // Normalize transaction_date to YYYY-MM-DD format
    let normalizedDate = transactionDate.trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(normalizedDate)) {
        try {
            const date = new Date(normalizedDate);
            if (!isNaN(date.getTime())) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                normalizedDate = `${year}-${month}-${day}`;
            } else {
                throw new Error('Invalid date');
            }
        } catch (e) {
            console.error('Error normalizing date:', e, 'Original:', normalizedDate);
            form.setError('transaction_date', 'Transaction date is required.');
            return;
        }
    }

    // Validate amount is provided when not using price per unit
    if (!usePricePerUnit.value && (!amountValue || amountValue === 0)) {
        console.error('Amount validation failed:', amountValue, 'form.amount:', form.amount);
        form.setError('amount', 'Either amount or both amount_per_unit and quantity must be provided.');
        addNotification({
            type: 'error',
            title: t('Validation Error'),
            message: t('Either amount or both amount_per_unit and quantity must be provided.'),
        });
        return;
    }

    // Ensure all form fields are properly set with correct types
    // Set values directly on form object - these will be sent to backend
    form.category_id = Number(categoryId);
    form.type = 'income';
    form.transaction_date = normalizedDate;
    form.amount = amountValue ? Number(amountValue) : null;

    // Ensure currency and house_of_zeros are set
    if (!form.currency) {
        form.currency = vesselCurrencyData.value.code || props.transaction.currency || props.defaultCurrency || 'EUR';
    } else {
        form.currency = String(form.currency).toUpperCase();
    }

    if (!form.house_of_zeros) {
        form.house_of_zeros = currentCurrencyDecimals.value;
    }

    // Set VAT profile if not set
    if (!form.vat_profile_id && defaultVatProfile.value) {
        form.vat_profile_id = defaultVatProfile.value.id;
    }
    if (form.vat_profile_id) {
        form.vat_profile_id = Number(form.vat_profile_id);
    }

    // Ensure description and notes are strings (not null)
    form.description = form.description || '';
    form.notes = form.notes || '';
    form.status = form.status || 'pending';

    console.log('Form data before submission (after setting all values):', {
        transactionId: props.transaction.id,
        category_id: form.category_id,
        type: form.type,
        amount: form.amount,
        amount_per_unit: form.amount_per_unit,
        quantity: form.quantity,
        currency: form.currency,
        house_of_zeros: form.house_of_zeros,
        vat_profile_id: form.vat_profile_id,
        amount_includes_vat: form.amount_includes_vat,
        transaction_date: form.transaction_date,
        description: form.description,
        notes: form.notes,
        status: form.status,
        formData: form.data()
    });

    // Get files from selectedFiles (already synced with form.files via watcher)
    // Ensure form.files is set from selectedFiles
    if (selectedFiles.value && Array.isArray(selectedFiles.value) && selectedFiles.value.length > 0) {
        form.files = Array.from(selectedFiles.value);
    } else {
        form.files = [];
    }

    // Double-check form data is set correctly before submission
    const formDataBeforeSubmit = form.data();
    console.log('Form data after setting properties:', {
        category_id: form.category_id,
        transaction_date: form.transaction_date,
        amount: form.amount,
        formData: formDataBeforeSubmit,
        hasFiles: form.files.length > 0
    });

    // Only use forceFormData if we have files to upload
    // Otherwise, use regular JSON submission which handles data better
    const submitOptions: any = {
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
        onError: (errors: any) => {
            console.error('Form submission errors:', errors);
            console.error('Form data that was sent:', formDataBeforeSubmit);
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to update transaction. Please check the form for errors.'),
            });
        },
    };

    // Only use forceFormData if we have files to upload
    if (form.files && form.files.length > 0) {
        submitOptions.forceFormData = true;
    }

    form.put(transactions.update.url({ vessel: getCurrentVesselId(), transactionId: props.transaction.id }), submitOptions);
};
</script>

<template>
    <Dialog :open="open" @update:open="handleDialogUpdate">
        <DialogContent class="max-h-[90vh] overflow-y-auto" :style="{ maxWidth: '75vw', width: '100%' }">
            <DialogHeader>
                <DialogTitle class="text-green-600 dark:text-green-400">{{ t('Update Transaction') }} #{{ transaction.transaction_number }}</DialogTitle>
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

                <!-- VAT Section -->
                <div v-if="selectedVatProfile && calculatedAmount > 0" class="space-y-4 p-4 border rounded-lg bg-muted/50 dark:bg-muted/30">
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
                            <span class="font-medium">{{ selectedVatProfile.name }} ({{ selectedVatProfile.percentage }}%)</span>
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
                                <span class="text-sm text-muted-foreground">{{ t('VAT Amount') }} ({{ selectedVatProfile.percentage }}%):</span>
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
                                {{ t('VAT') }} ({{ selectedVatProfile.percentage }}%) {{ t('is included in the amount above.') }}
                            </span>
                            <span v-else>
                                {{ t('VAT') }} ({{ selectedVatProfile.percentage }}%) {{ t('will be added to the amount above.') }}
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

