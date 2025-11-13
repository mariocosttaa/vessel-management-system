<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Icon from '@/components/Icon.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DateInput } from '@/components/ui/date-input';
import { Select } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/composables/usePermissions';
import { useNotifications } from '@/composables/useNotifications';
import { useI18n } from '@/composables/useI18n';
import mareas from '@/routes/panel/mareas';
import { Ship, Calendar, Users, Package, DollarSign, TrendingUp, TrendingDown, Plus, X, Trash2, Wallet, ChevronDown, ChevronUp } from 'lucide-vue-next';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import CreateAddModal from '@/components/modals/Movimentation/create-add.vue';
import CreateRemoveModal from '@/components/modals/Movimentation/create-remove.vue';
import UpdateAddModal from '@/components/modals/Movimentation/update-add.vue';
import UpdateRemoveModal from '@/components/modals/Movimentation/update-remove.vue';
import EditCalculationModal from '@/components/modals/Marea/EditCalculationModal.vue';
import EditMareaModal from '@/components/modals/Marea/edit.vue';
import TransactionShowModal from '@/components/modals/Movimentation/show.vue';
import transactions from '@/routes/panel/movimentations';
import MoneyInput from '@/components/Forms/MoneyInput.vue';

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel IDs (alphanumeric strings) or numeric IDs
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

interface Marea {
    id: number;
    marea_number: string;
    name: string | null;
    description: string | null;
    status: string;
    estimated_departure_date: string | null;
    estimated_return_date: string | null;
    actual_departure_date: string | null;
    actual_return_date: string | null;
    closed_at: string | null;
    distribution_profile_id: number | null;
    distribution_profile: {
        id: number;
        name: string;
    } | null;
    use_calculation: boolean;
    currency: string;
    house_of_zeros: number;
    total_income: number;
    total_expenses: number;
    net_result: number;
    formatted_total_income: string;
    formatted_total_expenses: string;
    formatted_net_result: string;
    distribution: {
        total_income: number;
        total_expenses: number;
        net_result: number;
        final_result: number;
        formatted_final_result?: string;
        uses_overrides?: boolean;
        items: Record<number, {
            item: any;
            value: number;
            formatted_value?: string;
        }>;
    };
    distribution_items?: Array<any>;
    distribution_profile_items?: Array<any>;
    crew_members: Array<{
        id: number;
        name: string;
        email: string;
        notes: string | null;
    }>;
    quantity_returns: Array<{
        id: number;
        name: string;
        quantity: number;
        notes: string | null;
    }>;
    transactions: Array<{
        id: number;
        transaction_number: string;
        type: string;
        amount: number;
        amount_per_unit: number | null;
        price_per_unit?: number | null; // Backward compatibility
        quantity: number | null;
        vat_amount: number;
        total_amount: number;
        currency: string;
        transaction_date: string | null;
        description: string | null;
        category: {
            id: number;
            name: string;
            type: string;
            color: string;
        } | null;
        supplier: {
            id: number;
            company_name: string;
        } | null;
        crew_member_id: number | null;
        crew_member: {
            id: number;
            name: string;
            email: string;
        } | null;
    }>;
    created_at: string | null;
    created_by: {
        id: number;
        name: string;
    } | null;
}

interface Props {
    marea: Marea;
    transactionCount?: number;
    defaultCurrency?: string;
    categories?: Array<{
        id: number;
        name: string;
        type: string;
        color: string;
    }>;
    suppliers?: Array<{
        id: number;
        company_name: string;
        description?: string;
    }>;
    crewMembers?: Array<{
        id: number;
        name: string;
        email: string;
    }>;
    vatProfiles?: Array<{
        id: number;
        name: string;
        percentage: number;
        country_id?: number | null;
    }>;
    defaultVatProfile?: {
        id: number;
        name: string;
        percentage: number;
        country_id?: number | null;
    } | null;
    distributionProfiles?: Array<{
        id: number;
        name: string;
        description?: string | null;
        is_default?: boolean;
    }>;
    salaryCategory?: {
        id: number;
        name: string;
        type: string;
        color: string;
    } | null;
    crewSalaryData?: Record<number, {
        id: number;
        compensation_type: string;
        fixed_amount: number | null;
        percentage: number | null;
        currency: string;
        calculated_amount: number | null;
    }>;
}

const props = defineProps<Props>();
const { canEdit, canDelete } = usePermissions();
const { addNotification } = useNotifications();
const { t } = useI18n();
const page = usePage();

// Get currency data from shared props
const currencies = computed(() => {
    return (page.props as any)?.currencies || [];
});

// Get currency details for a transaction
const getCurrencyData = (currencyCode: string) => {
    const currency = currencies.value.find((c: any) => c.code === currencyCode);
    return currency || { code: currencyCode, symbol: currencyCode, decimal_separator: 2 };
};

// Open transaction show modal
const openTransactionModal = async (transaction: any) => {
    selectedTransaction.value = transaction;
    loadingTransaction.value = true;
    showTransactionModal.value = true;

    // Fetch full transaction details from API
    try {
        const vesselId = getCurrentVesselId();
        const response = await fetch(`/panel/${vesselId}/api/movimentations/${transaction.id}/details`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            const data = await response.json();
            if (data.transaction) {
                selectedTransaction.value = data.transaction;
            }
        } else {
            console.error('Failed to load transaction details:', response.statusText);
            // Continue with the transaction data we have
        }
    } catch (error) {
        console.error('Failed to load transaction details:', error);
        // Continue with the transaction data we have
    } finally {
        loadingTransaction.value = false;
    }
};

// Close transaction modal
const closeTransactionModal = () => {
    showTransactionModal.value = false;
    selectedTransaction.value = null;
};

// Open update modal for transaction
const openUpdateModal = async (transaction: any) => {
    transactionToEdit.value = transaction;

    // Fetch full transaction details from API
    try {
        const vesselId = getCurrentVesselId();
        const response = await fetch(`/panel/${vesselId}/api/movimentations/${transaction.id}/details`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            const data = await response.json();
            if (data.transaction) {
                transactionToEdit.value = data.transaction;
            }
        }
    } catch (error) {
        console.error('Failed to load transaction details:', error);
    }

    // Determine which update modal to show based on transaction type
    if (transaction.type === 'income') {
        showUpdateAddModal.value = true;
    } else if (transaction.type === 'expense') {
        showUpdateRemoveModal.value = true;
    }
};

// Close update modals
const closeUpdateModals = () => {
    showUpdateAddModal.value = false;
    showUpdateRemoveModal.value = false;
    transactionToEdit.value = null;
};

// Handle update success
const handleUpdateSuccess = () => {
    closeUpdateModals();
    router.reload();
};

// Handle delete transaction
const openDeleteTransactionDialog = (transactionId: number) => {
    transactionToDelete.value = transactionId;
    showDeleteTransactionDialog.value = true;
};

const confirmDeleteTransaction = () => {
    if (!transactionToDelete.value) return;

    isProcessing.value = true;
    const vesselId = getCurrentVesselId();
    const url = `/panel/${vesselId}/mareas/${props.marea.id}/remove-movimentation/${transactionToDelete.value}`;
    router.delete(url, {
        onSuccess: () => {
            isProcessing.value = false;
            showDeleteTransactionDialog.value = false;
            transactionToDelete.value = null;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Transaction has been removed from the marea.'),
            });
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
};

const cancelDeleteTransaction = () => {
    showDeleteTransactionDialog.value = false;
    transactionToDelete.value = null;
};

// Confirmation dialogs
const showMarkAtSeaDialog = ref(false);
const showMarkReturnedDialog = ref(false);
const showCloseDialog = ref(false);
const showCancelDialog = ref(false);
const showDeleteDialog = ref(false);
const isProcessing = ref(false);

// Modals for adding items
const showAddTransactionDialog = ref(false);
const showCreateIncomeDialog = ref(false);
const showCreateExpenseDialog = ref(false);
const showAddCrewDialog = ref(false);
const showAddQuantityReturnDialog = ref(false);
const showEditCalculationDialog = ref(false);
const showEditMareaDialog = ref(false);
const showSalaryPaymentDialog = ref(false);
const showTransactionModal = ref(false);
const selectedTransaction = ref<any>(null);
const loadingTransaction = ref(false);
const showUpdateAddModal = ref(false);
const showUpdateRemoveModal = ref(false);
const transactionToEdit = ref<any>(null);
const showDeleteTransactionDialog = ref(false);
const transactionToDelete = ref<number | null>(null);

// Distribution section collapsed state - default to collapsed, especially when calculation is not active
const isDistributionExpanded = ref(false);

// Crew Members section collapsed state - default to collapsed
const isCrewMembersExpanded = ref(false);

// Fishing Quantity section collapsed state - default to collapsed
const isFishingQuantityExpanded = ref(false);

// Data for modals
const availableTransactions = ref<Array<any>>([]);
const availableCrewMembers = ref<Array<any>>([]);
const loadingTransactions = ref(false);
const loadingCrew = ref(false);

// Forms
const addTransactionForm = useForm({
    transaction_id: null as number | null,
});

// Selected transaction (for UI reactivity)
const selectedTransactionId = ref<number | null>(null);

const addCrewForm = useForm({
    user_id: null as string | null,
    notes: '' as string,
});

const addQuantityReturnForm = useForm({
    name: '' as string,
    quantity: 0 as number,
    notes: '' as string,
});

const salaryPaymentForm = useForm({
    crew_member_id: null as number | null,
    amount: null as number | null,
    transaction_date: new Date().toISOString().split('T')[0] as string,
    description: '' as string,
    notes: '' as string,
});

const loadingSalaryData = ref(false);

// Forms for status actions
const markAtSeaForm = useForm({
    date: new Date().toISOString().split('T')[0],
});

const markReturnedForm = useForm({
    date: new Date().toISOString().split('T')[0],
});

// Get status badge color
const getStatusColor = (status: string) => {
    switch (status) {
        case 'preparing':
            return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300';
        case 'at_sea':
            return 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300';
        case 'returned':
            return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300';
        case 'closed':
            return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300';
        case 'cancelled':
            return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

// Format date
const formatDate = (dateString: string | null) => {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Format datetime
const formatDateTime = (dateString: string | null) => {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Status actions
const handleMarkAtSea = () => {
    isProcessing.value = true;
    markAtSeaForm.post(mareas.markAtSea.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }), {
        onSuccess: () => {
            showMarkAtSeaDialog.value = false;
            isProcessing.value = false;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Marea has been marked as at sea.'),
            });
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
};

const handleMarkReturned = () => {
    isProcessing.value = true;
    markReturnedForm.post(mareas.markReturned.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }), {
        onSuccess: () => {
            showMarkReturnedDialog.value = false;
            isProcessing.value = false;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Marea has been marked as returned.'),
            });
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
};

const handleClose = () => {
    isProcessing.value = true;
    router.post(mareas.close.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }), {}, {
        onSuccess: () => {
            showCloseDialog.value = false;
            isProcessing.value = false;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Marea has been closed.'),
            });
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
};

const handleCancel = () => {
    isProcessing.value = true;
    router.post(mareas.cancel.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }), {}, {
        onSuccess: () => {
            showCancelDialog.value = false;
            isProcessing.value = false;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Marea has been cancelled.'),
            });
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
};

const handleDelete = () => {
    isProcessing.value = true;
    router.delete(mareas.destroy.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            isProcessing.value = false;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Marea has been deleted successfully.'),
            });
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
};

// Get default currency (assuming EUR for now)
const defaultCurrency = computed(() => props.defaultCurrency || 'EUR');

// Group transactions by type
const incomeTransactions = computed(() =>
    props.marea.transactions.filter(t => t.type === 'income')
);

const expenseTransactions = computed(() =>
    props.marea.transactions.filter(t => t.type === 'expense')
);

// Salary transactions (expense transactions with crew_member_id)
// Any expense with a crew_member_id is considered a salary payment
const salaryTransactions = computed(() =>
    props.marea.transactions.filter(t =>
        t.type === 'expense' &&
        t.crew_member_id !== null
    )
);

// Non-salary expense transactions (exclude transactions with crew_member_id)
const nonSalaryExpenseTransactions = computed(() =>
    props.marea.transactions.filter(t =>
        t.type === 'expense' &&
        t.crew_member_id === null
    )
);

// Filter categories by type
const incomeCategories = computed(() => {
    return props.categories?.filter(cat => cat.type === 'income') || [];
});

const expenseCategories = computed(() => {
    return props.categories?.filter(cat => cat.type === 'expense') || [];
});

// Sort distribution items by order_index for display
const sortedDistributionItems = computed(() => {
    if (!props.marea.distribution?.items) return [];
    const items = Object.values(props.marea.distribution.items);
    return items.sort((a: any, b: any) => {
        const orderA = a.item?.order_index || 0;
        const orderB = b.item?.order_index || 0;
        return orderA - orderB;
    });
});

// Load available transactions
const loadAvailableTransactions = async () => {
    loadingTransactions.value = true;
    try {
        const response = await fetch(mareas.availableTransactions.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }));
        const data = await response.json();
        availableTransactions.value = data.transactions || [];
    } catch (error) {
        console.error('Failed to load available transactions:', error);
        addNotification({
            type: 'error',
            title: t('Error'),
            message: t('Failed to load available transactions.'),
        });
    } finally {
        loadingTransactions.value = false;
    }
};

// Load available crew members
const loadAvailableCrew = async () => {
    loadingCrew.value = true;
    try {
        const response = await fetch(mareas.availableCrew.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }));
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        availableCrewMembers.value = data.crew_members || [];
        if (data.error) {
            console.error('Error from server:', data.error);
            addNotification({
                type: 'error',
                title: t('Error'),
                message: data.error,
            });
        }
    } catch (error) {
        console.error('Failed to load available crew:', error);
        addNotification({
            type: 'error',
            title: t('Error'),
            message: t('Failed to load available crew members.'),
        });
    } finally {
        loadingCrew.value = false;
    }
};

// Watch modals to load data when opened
watch(showAddTransactionDialog, (open) => {
    if (open) {
        loadAvailableTransactions();
        // Reset selection when dialog opens
        selectedTransactionId.value = null;
        addTransactionForm.transaction_id = null;
    }
});

watch(showAddCrewDialog, (open) => {
    if (open) {
        loadAvailableCrew();
    }
});

// Handle transaction selection
const selectTransaction = (transactionId: number) => {
    selectedTransactionId.value = transactionId;
    addTransactionForm.transaction_id = transactionId;
};

// Handle add transaction
const handleAddTransaction = () => {
    if (!selectedTransactionId.value && !addTransactionForm.transaction_id) {
        addNotification({
            type: 'error',
            title: t('Error'),
            message: t('Please select a transaction.'),
        });
        return;
    }

    // Ensure form has the selected transaction ID
    if (selectedTransactionId.value) {
        addTransactionForm.transaction_id = selectedTransactionId.value;
    }

    isProcessing.value = true;
    addTransactionForm.post(mareas.addTransaction.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }), {
        onSuccess: () => {
            showAddTransactionDialog.value = false;
            isProcessing.value = false;
            addTransactionForm.reset();
            selectedTransactionId.value = null;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Transaction has been added to the marea.'),
            });
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
};

const handleEditCalculationSuccess = () => {
    router.reload();
};

// Handle edit marea success
const handleEditMareaSuccess = () => {
    showEditMareaDialog.value = false;
    router.reload();
};

// Handle remove transaction (deprecated - use openDeleteTransactionDialog instead)
const handleRemoveTransaction = (transactionId: number) => {
    openDeleteTransactionDialog(transactionId);
};

// Handle add crew
const handleAddCrew = () => {
    if (!addCrewForm.user_id) {
        addNotification({
            type: 'error',
            title: t('Error'),
            message: t('Please select a crew member.'),
        });
        return;
    }

    isProcessing.value = true;
    addCrewForm.post(mareas.addCrew.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }), {
        onSuccess: () => {
            showAddCrewDialog.value = false;
            isProcessing.value = false;
            addCrewForm.reset();
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Crew member has been added to the marea.'),
            });
            // Reload the page to show the updated crew list
            router.reload();
        },
        onError: (errors) => {
            isProcessing.value = false;
            console.error('Failed to add crew member:', errors);
            if (errors.message) {
                addNotification({
                    type: 'error',
                    title: t('Error'),
                    message: errors.message,
                });
            }
        },
    });
};

// Handle remove crew
const handleRemoveCrew = (userId: number) => {
    isProcessing.value = true;
    router.delete(mareas.removeCrew.url({
        vessel: getCurrentVesselId(),
        mareaId: props.marea.id,
        crewMember: userId
    }), {
        onSuccess: () => {
            isProcessing.value = false;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Crew member has been removed from the marea.'),
            });
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
};

// Handle add quantity return
const handleAddQuantityReturn = () => {
    if (!addQuantityReturnForm.name || !addQuantityReturnForm.quantity) {
        addNotification({
            type: 'error',
            title: t('Error'),
            message: t('Please fill in all required fields.'),
        });
        return;
    }

    isProcessing.value = true;
    addQuantityReturnForm.post(mareas.addQuantityReturn.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }), {
        onSuccess: () => {
            showAddQuantityReturnDialog.value = false;
            isProcessing.value = false;
            addQuantityReturnForm.reset();
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Product return has been added to the marea.'),
            });
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
};

// Handle create transaction success
const handleCreateTransactionSuccess = () => {
    showCreateIncomeDialog.value = false;
    showCreateExpenseDialog.value = false;
    // Reload the page to show the new transaction
    router.reload();
    addNotification({
        type: 'success',
        title: t('Success'),
        message: t('Transaction has been created and linked to the marea.'),
    });
};

// Handle remove quantity return
const handleRemoveQuantityReturn = (quantityReturnId: number) => {
    isProcessing.value = true;
    router.delete(mareas.removeQuantityReturn.url({
        vessel: getCurrentVesselId(),
        mareaId: props.marea.id,
        quantityReturn: quantityReturnId
    }), {
        onSuccess: () => {
            isProcessing.value = false;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Product return has been removed from the marea.'),
            });
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
};

// Handle toggle calculation
const toggleCalculationForm = useForm({
    use_calculation: props.marea.use_calculation,
});

const toggleCalculation = () => {
    isProcessing.value = true;
    toggleCalculationForm.use_calculation = !props.marea.use_calculation;
    toggleCalculationForm.put(mareas.update.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }), {
        onSuccess: () => {
            isProcessing.value = false;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: `${t('Calculation')} ${!props.marea.use_calculation ? t('enabled') : t('disabled')} ${t('for this marea.')}`,
            });
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
};

// Profile selection - instant update
const updatingProfile = ref(false);
const selectedProfileId = ref<number | null>(props.marea.distribution_profile_id);
const updateProfileForm = useForm({
    distribution_profile_id: props.marea.distribution_profile_id as number | null,
});

// Convert to Select component options format
const distributionProfileOptions = computed(() => {
    if (!props.distributionProfiles) return [];
    const options = [{ value: null, label: t('No Profile (Optional)') }];
    props.distributionProfiles.forEach(profile => {
        const label = profile.is_default ? `${profile.name} (${t('Default')})` : profile.name;
        options.push({ value: profile.id, label });
    });
    return options;
});

const availableCrewMemberOptions = computed(() => {
    const options = [{ value: null, label: t('Select a crew member') }];
    availableCrewMembers.value.forEach(member => {
        options.push({ value: member.id, label: `${member.name} (${member.email})` });
    });
    return options;
});

const mareaCrewMemberOptions = computed(() => {
    const options = [{ value: null, label: t('Select a crew member') }];
    (props.marea.crew_members || []).forEach(member => {
        const label = member.email ? `${member.name} (${member.email})` : member.name;
        options.push({ value: member.id, label });
    });
    return options;
});

const updateProfile = (profileId: string | number | null) => {
    if (updatingProfile.value) return;

    // Convert empty string to null
    const profileIdValue = (profileId === '' || profileId === null || profileId === undefined || profileId === 'null') ? null : Number(profileId);

    updatingProfile.value = true;
    selectedProfileId.value = profileIdValue;
    updateProfileForm.distribution_profile_id = profileIdValue;

    updateProfileForm.put(mareas.update.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }), {
        preserveScroll: true,
        onSuccess: () => {
            updatingProfile.value = false;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: profileIdValue ? t('Distribution profile updated successfully.') : t('Distribution profile removed successfully.'),
            });
        },
        onError: () => {
            updatingProfile.value = false;
            selectedProfileId.value = props.marea.distribution_profile_id; // Revert on error
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to update distribution profile.'),
            });
        },
    });
};

// Store selected crew member's salary info for display
const selectedCrewSalaryInfo = ref<{
    compensation_type: string | null;
    fixed_amount: number | null;
    percentage: number | null;
    calculated_amount: number | null;
    currency: string | null;
} | null>(null);

// Load salary data when crew member is selected
const loadCrewSalaryData = async (crewMemberId: number | null) => {
    if (!crewMemberId) {
        salaryPaymentForm.amount = null;
        selectedCrewSalaryInfo.value = null;
        return;
    }

    // Check if we have salary data in props
    if (props.crewSalaryData && props.crewSalaryData[crewMemberId]) {
        const salaryData = props.crewSalaryData[crewMemberId];
        selectedCrewSalaryInfo.value = {
            compensation_type: salaryData.compensation_type,
            fixed_amount: salaryData.fixed_amount,
            percentage: salaryData.percentage,
            calculated_amount: salaryData.calculated_amount,
            currency: salaryData.currency,
        };

        if (salaryData.calculated_amount) {
            salaryPaymentForm.amount = salaryData.calculated_amount;
        } else if (salaryData.fixed_amount) {
            salaryPaymentForm.amount = salaryData.fixed_amount;
        } else {
            salaryPaymentForm.amount = null;
        }
        return;
    }

    // If not in props, fetch from API
    loadingSalaryData.value = true;
    try {
        const response = await fetch(
            `/panel/${getCurrentVesselId()}/mareas/${props.marea.id}/crew-salary-data?crew_member_id=${crewMemberId}`
        );
        const data = await response.json();

        selectedCrewSalaryInfo.value = {
            compensation_type: data.compensation_type || null,
            fixed_amount: data.fixed_amount || null,
            percentage: data.percentage || null,
            calculated_amount: data.calculated_amount || null,
            currency: data.currency || null,
        };

        if (data.calculated_amount) {
            salaryPaymentForm.amount = data.calculated_amount;
        } else if (data.fixed_amount) {
            salaryPaymentForm.amount = data.fixed_amount;
        } else {
            salaryPaymentForm.amount = null;
        }
    } catch (error) {
        console.error('Failed to load salary data:', error);
        salaryPaymentForm.amount = null;
        selectedCrewSalaryInfo.value = null;
    } finally {
        loadingSalaryData.value = false;
    }
};

// Watch crew member selection
watch(() => salaryPaymentForm.crew_member_id, (newValue) => {
    if (newValue) {
        loadCrewSalaryData(newValue);
        // Auto-fill description
        const crewMember = props.marea.crew_members.find(m => m.id === newValue);
        if (crewMember) {
            salaryPaymentForm.description = `${t('Salary payment for')} ${crewMember.name}`;
        }
    } else {
        salaryPaymentForm.amount = null;
        salaryPaymentForm.description = '';
        selectedCrewSalaryInfo.value = null;
    }
});

// Watch marea total income to recalculate percentage-based salaries
watch(() => props.marea.total_income, () => {
    if (salaryPaymentForm.crew_member_id) {
        loadCrewSalaryData(salaryPaymentForm.crew_member_id);
    }
});

// Handle salary payment
const handleSalaryPayment = () => {
    if (!salaryPaymentForm.crew_member_id || !salaryPaymentForm.amount) {
        addNotification({
            type: 'error',
            title: t('Error'),
            message: t('Please select a crew member and enter an amount.'),
        });
        return;
    }

    isProcessing.value = true;
    salaryPaymentForm.post(`/panel/${getCurrentVesselId()}/mareas/${props.marea.id}/salary-payment`, {
        onSuccess: () => {
            showSalaryPaymentDialog.value = false;
            isProcessing.value = false;
            salaryPaymentForm.reset();
            salaryPaymentForm.transaction_date = new Date().toISOString().split('T')[0];
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Salary payment has been created successfully.'),
            });
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
};

// Watch salary payment dialog to reset form when opened
watch(showSalaryPaymentDialog, (open) => {
    if (open) {
        salaryPaymentForm.reset();
        salaryPaymentForm.transaction_date = new Date().toISOString().split('T')[0];
        selectedCrewSalaryInfo.value = null;
    }
});

// Delete marea functions
const isDeleting = ref(false);

const handleDeleteMarea = () => {
    showDeleteDialog.value = true;
};

const confirmDeleteMarea = () => {
    isDeleting.value = true;

    router.delete(mareas.destroy.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }), {
        onSuccess: () => {
            router.visit(mareas.index.url({ vessel: getCurrentVesselId() }));
        },
        onError: () => {
            isDeleting.value = false;
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to delete marea. Please try again.'),
            });
        },
    });
};

const cancelDeleteMarea = () => {
    showDeleteDialog.value = false;
    isDeleting.value = false;
};
</script>

<template>
    <Head :title="`Marea ${marea.marea_number}`" />

    <VesselLayout :breadcrumbs="[
        { title: t('Mareas'), href: mareas.index.url({ vessel: getCurrentVesselId() }) },
        { title: marea.marea_number, href: mareas.show.url({ vessel: getCurrentVesselId(), mareaId: marea.id }) }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">
                                {{ marea.marea_number }}
                            </h1>
                            <span
                                :class="[
                                    'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                                    getStatusColor(marea.status)
                                ]"
                            >
                                {{ marea.status === 'preparing' ? t('Preparing') :
                                   marea.status === 'at_sea' ? t('At Sea') :
                                   marea.status === 'returned' ? t('Returned') :
                                   marea.status === 'closed' ? t('Closed') :
                                   marea.status === 'cancelled' ? t('Cancelled') : marea.status }}
                            </span>
                        </div>
                        <p v-if="marea.name" class="text-lg text-muted-foreground dark:text-muted-foreground mb-2">
                            {{ marea.name }}
                        </p>
                        <p v-if="marea.description" class="text-sm text-muted-foreground dark:text-muted-foreground">
                            {{ marea.description }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            v-if="marea.status === 'preparing' && canEdit('mareas')"
                            @click="showMarkAtSeaDialog = true"
                            class="inline-flex items-center px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <Ship class="w-4 h-4 mr-2" />
                            {{ t('Mark At Sea') }}
                        </button>
                        <button
                            v-if="marea.status === 'at_sea' && canEdit('mareas')"
                            @click="showMarkReturnedDialog = true"
                            class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <Ship class="w-4 h-4 mr-2" />
                            {{ t('Mark Returned') }}
                        </button>
                        <button
                            v-if="(marea.status === 'returned' || marea.status === 'at_sea') && canEdit('mareas')"
                            @click="showCloseDialog = true"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <Icon name="check" class="w-4 h-4 mr-2" />
                            {{ t('Close Marea') }}
                        </button>
                        <button
                            v-if="marea.status !== 'closed' && marea.status !== 'cancelled' && canEdit('mareas')"
                            @click="showCancelDialog = true"
                            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <Icon name="x" class="w-4 h-4 mr-2" />
                            {{ t('Cancel') }}
                        </button>
                        <button
                            v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                            @click="showEditMareaDialog = true"
                            class="inline-flex items-center px-4 py-2 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground font-medium transition-colors"
                        >
                            <Icon name="edit" class="w-4 h-4 mr-2" />
                            {{ t('Edit') }}
                        </button>
                        <button
                            v-if="canDelete('mareas')"
                            @click="handleDeleteMarea"
                            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <Icon name="trash-2" class="w-4 h-4 mr-2" />
                            {{ t('Delete') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Financial Summary Card - Moved to top for quick overview -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">{{ t('Financial Summary') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center p-4 rounded-lg bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800">
                        <div class="flex items-center justify-center mb-2">
                            <TrendingUp class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" />
                            <span class="text-sm font-medium text-green-800 dark:text-green-300">{{ t('Total Income') }}</span>
                        </div>
                        <MoneyDisplay
                            :value="marea.total_income"
                            :currency="defaultCurrency"
                            variant="positive"
                            size="lg"
                            class="font-bold"
                        />
                    </div>
                    <div class="text-center p-4 rounded-lg bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800">
                        <div class="flex items-center justify-center mb-2">
                            <TrendingDown class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" />
                            <span class="text-sm font-medium text-red-800 dark:text-red-300">{{ t('Total Expenses') }}</span>
                        </div>
                        <MoneyDisplay
                            :value="marea.total_expenses"
                            :currency="defaultCurrency"
                            variant="negative"
                            size="lg"
                            class="font-bold"
                        />
                    </div>
                    <div class="text-center p-4 rounded-lg bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center justify-center mb-2">
                            <DollarSign class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" />
                            <span class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                {{ marea.use_calculation && marea.distribution?.final_result !== undefined ? t('Distribution Result') : t('Net Result') }}
                            </span>
                        </div>
                        <MoneyDisplay
                            :value="marea.use_calculation && marea.distribution?.final_result !== undefined ? marea.distribution.final_result : marea.net_result"
                            :currency="defaultCurrency"
                            :variant="(marea.use_calculation && marea.distribution?.final_result !== undefined ? marea.distribution.final_result : marea.net_result) >= 0 ? 'positive' : 'negative'"
                            size="lg"
                            class="font-bold"
                        />
                        <p v-if="marea.use_calculation && marea.distribution?.final_result !== undefined" class="text-xs text-muted-foreground mt-1">
                            {{ t('Based on distribution calculation') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Timeline Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4 flex items-center">
                    <Calendar class="w-5 h-5 mr-2" />
                    {{ t('Timeline') }}
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-2 h-2 rounded-full bg-blue-500 mt-2"></div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Estimated Departure') }}
                            </div>
                            <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ formatDate(marea.estimated_departure_date) }}
                            </div>
                        </div>
                    </div>
                    <div v-if="marea.actual_departure_date" class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-2 h-2 rounded-full bg-cyan-500 mt-2"></div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Actual Departure') }}
                            </div>
                            <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ formatDate(marea.actual_departure_date) }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-2 h-2 rounded-full bg-yellow-500 mt-2"></div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Estimated Return') }}
                            </div>
                            <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ formatDate(marea.estimated_return_date) }}
                            </div>
                        </div>
                    </div>
                    <div v-if="marea.actual_return_date" class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-2 h-2 rounded-full bg-green-500 mt-2"></div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Actual Return') }}
                            </div>
                            <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ formatDate(marea.actual_return_date) }}
                            </div>
                        </div>
                    </div>
                    <div v-if="marea.closed_at" class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-2 h-2 rounded-full bg-gray-500 mt-2"></div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Closed At') }}
                            </div>
                            <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ formatDateTime(marea.closed_at) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribution Calculation Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <Collapsible v-model:open="isDistributionExpanded" :default-open="false">
                    <CollapsibleTrigger class="w-full">
                        <div class="flex items-center justify-between w-full cursor-pointer hover:opacity-80 transition-opacity">
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground">
                                    {{ t('Distribution Calculation') }}
                                </h2>
                                <span v-if="marea.distribution_profile" class="text-sm text-muted-foreground dark:text-muted-foreground">
                                    ({{ marea.distribution_profile.name }})
                                </span>
                                <span v-if="marea.distribution?.uses_overrides" class="text-xs text-primary dark:text-primary px-2 py-1 rounded bg-primary/10 dark:bg-primary/20">
                                    {{ t('Custom Override') }}
                                </span>
                                <span v-if="!marea.use_calculation" class="text-xs text-muted-foreground dark:text-muted-foreground px-2 py-1 rounded bg-muted/50">
                                    {{ t('Inactive') }}
                                </span>
                            </div>
                            <ChevronDown
                                :class="[
                                    'w-5 h-5 text-muted-foreground dark:text-muted-foreground transition-transform duration-200',
                                    isDistributionExpanded ? 'transform rotate-180' : ''
                                ]"
                            />
                        </div>
                    </CollapsibleTrigger>

                    <CollapsibleContent>
                        <div class="mt-4">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <!-- Instant Profile Selector -->
                                    <div v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'" class="flex items-center gap-3 mb-4">
                                        <Label for="distribution-profile" class="text-sm font-medium text-card-foreground dark:text-card-foreground whitespace-nowrap">
                                            {{ t('Distribution Profile:') }}
                                        </Label>
                                        <div class="flex items-center gap-2 flex-1 max-w-md">
                                            <Select
                                                id="distribution-profile"
                                                :model-value="selectedProfileId"
                                                @update:model-value="updateProfile"
                                                :options="distributionProfileOptions"
                                                :placeholder="t('No Profile (Optional)')"
                                                :disabled="updatingProfile || isProcessing"
                                                searchable
                                            />
                                            <Icon
                                                v-if="updatingProfile"
                                                name="loader-circle"
                                                class="w-4 h-4 animate-spin text-muted-foreground"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 ml-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            :checked="marea.use_calculation"
                                            @change="toggleCalculation"
                                            class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                                            :disabled="isProcessing || marea.status === 'closed' || marea.status === 'cancelled'"
                                        />
                                        <span class="text-sm text-card-foreground dark:text-card-foreground">{{ t('Use Calculation') }}</span>
                                    </label>
                                    <button
                                        v-if="marea.use_calculation && marea.distribution_profile_id && canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                                        @click="showEditCalculationDialog = true"
                                        class="inline-flex items-center px-3 py-1.5 text-sm bg-secondary text-secondary-foreground rounded-lg hover:bg-secondary/90 transition-colors"
                                    >
                                        <Icon name="edit" class="w-4 h-4 mr-1" />
                                        {{ t('Edit Calculation') }}
                                    </button>
                                </div>
                            </div>

                <!-- Warning message for closed/cancelled mareas -->
                <div v-if="(marea.status === 'closed' || marea.status === 'cancelled') && marea.distribution?.items && Object.keys(marea.distribution.items).length > 0" class="mb-4 py-3 px-4 rounded-lg border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20">
                    <p class="text-xs text-yellow-700 dark:text-yellow-400">
                        <strong>{{ marea.status === 'closed' ? t('Closed marea:') : t('Cancelled marea:') }}</strong>
                        {{ t('Distribution calculations are displayed for reference only and cannot be modified.') }}
                    </p>
                </div>

                <div v-if="!marea.use_calculation" class="text-center py-8 text-muted-foreground dark:text-muted-foreground border border-dashed border-border dark:border-border rounded-lg">
                    <p v-if="marea.status === 'closed' || marea.status === 'cancelled'" class="text-sm mb-2">
                        {{ t('Calculation is disabled for this marea.') }}
                    </p>
                    <p v-else class="text-sm">
                        {{ t('Calculation is disabled for this marea. Enable it to see distribution results.') }}
                    </p>
                </div>

                <div v-else-if="!marea.distribution_profile_id" class="text-center py-8 text-muted-foreground dark:text-muted-foreground border border-dashed border-border dark:border-border rounded-lg">
                    <Icon name="layers" class="w-12 h-12 mx-auto mb-3 opacity-50" />
                    <p class="text-base font-medium mb-2">{{ t('No Distribution Profile Selected') }}</p>
                    <p v-if="marea.status === 'closed' || marea.status === 'cancelled'" class="text-sm">
                        {{ t('No distribution profile was selected for this marea.') }}
                    </p>
                    <p v-else class="text-sm mb-4">{{ t('Select a distribution profile above to enable calculation.') }}</p>
                </div>

                <div v-else-if="marea.distribution?.items && Object.keys(marea.distribution.items).length > 0" class="space-y-4">
                    <!-- Visual Flow Display -->
                    <div
                        v-for="(item, index) in sortedDistributionItems"
                        :key="index"
                        class="relative"
                    >
                        <!-- Connecting Line -->
                        <div v-if="index > 0" class="absolute left-8 top-0 w-0.5 h-6 bg-border dark:bg-border -translate-y-full z-0"></div>

                        <!-- Step Card -->
                        <div class="flex items-start gap-4">
                            <!-- Step Number and Operation -->
                            <div class="flex flex-col items-center gap-2 flex-shrink-0">
                                <div class="w-14 h-14 rounded-full border-2 border-border dark:border-border bg-card dark:bg-card flex items-center justify-center relative z-10">
                                    <span class="text-xs font-bold text-muted-foreground">#{{ item.item?.order_index || index + 1 }}</span>
                                </div>
                                <div
                                    :class="[
                                        'w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-lg',
                                        item.item?.operation === 'set' ? 'bg-blue-500' :
                                        item.item?.operation === 'add' ? 'bg-green-500' :
                                        item.item?.operation === 'subtract' ? 'bg-red-500' :
                                        item.item?.operation === 'multiply' ? 'bg-purple-500' :
                                        item.item?.operation === 'divide' ? 'bg-orange-500' : 'bg-gray-500'
                                    ]"
                                >
                                    {{ item.item?.operation === 'set' ? '=' :
                                       item.item?.operation === 'add' ? '+' :
                                       item.item?.operation === 'subtract' ? '-' :
                                       item.item?.operation === 'multiply' ? '×' :
                                       item.item?.operation === 'divide' ? '÷' : '?' }}
                                </div>
                            </div>

                            <!-- Step Content Card -->
                            <div class="flex-1 border border-border dark:border-border rounded-lg p-4 bg-card dark:bg-card hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-card-foreground dark:text-card-foreground mb-1">
                                            {{ item.item?.name || t('Unnamed Step') }}
                                        </h4>
                                        <p v-if="item.item?.description" class="text-sm text-muted-foreground dark:text-muted-foreground line-clamp-2 mb-2">
                                            {{ item.item.description }}
                                        </p>
                                        <div class="flex items-center gap-3 text-xs text-muted-foreground dark:text-muted-foreground">
                                            <span class="capitalize">{{ item.item?.value_type?.replace(/_/g, ' ') || '—' }}</span>
                                            <span v-if="item.item?.value_amount">• {{ item.item.value_amount }}{{ item.item.value_type?.includes('percentage') ? '%' : '' }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right ml-4">
                                        <div v-if="item.formatted_value" class="text-lg font-bold text-card-foreground dark:text-card-foreground">
                                            {{ item.formatted_value }}
                                        </div>
                                        <MoneyDisplay
                                            v-else
                                            :value="item.value"
                                            :currency="marea.currency || defaultCurrency"
                                            variant="neutral"
                                            size="lg"
                                            class="font-bold"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Final Result Card -->
                    <div class="flex items-center justify-between p-6 rounded-lg bg-primary/10 dark:bg-primary/20 border-2 border-primary mt-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold">
                                ✓
                            </div>
                            <div>
                                <div class="text-lg font-semibold text-card-foreground dark:text-card-foreground">
                                    {{ t('Final Result') }}
                                </div>
                                <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                    {{ t('Calculated distribution result') }}
                                </div>
                            </div>
                        </div>
                        <div v-if="marea.distribution.formatted_final_result" class="text-2xl font-bold" :class="marea.distribution.final_result >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                            {{ marea.distribution.formatted_final_result }}
                        </div>
                        <MoneyDisplay
                            v-else
                            :value="marea.distribution.final_result"
                            :currency="marea.currency || defaultCurrency"
                            :variant="marea.distribution.final_result >= 0 ? 'positive' : 'negative'"
                            size="xl"
                            class="font-bold text-2xl"
                        />
                    </div>
                </div>
                <div v-else class="text-center py-8 text-muted-foreground dark:text-muted-foreground border border-dashed border-border dark:border-border rounded-lg">
                    <p class="text-sm">
                        {{ t('No distribution items configured.') }} {{ marea.distribution_profile ? t('The profile has no items.') : t('Please select a distribution profile.') }}
                    </p>
                    <p v-if="marea.status === 'closed' || marea.status === 'cancelled'" class="text-xs mt-2 text-muted-foreground dark:text-muted-foreground">
                        {{ t('Distribution cannot be modified for') }} {{ marea.status === 'closed' ? t('closed') : t('cancelled') }} {{ t('mareas.') }}
                    </p>
                </div>
                        </div>
                    </CollapsibleContent>
                </Collapsible>
            </div>

            <!-- Salary Payments Card -->
            <div v-if="marea.crew_members.length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground flex items-center">
                        <Wallet class="w-5 h-5 mr-2" />
                        {{ t('Salary Payments') }}
                    </h2>
                    <button
                        v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                        @click="showSalaryPaymentDialog = true"
                        class="inline-flex items-center px-3 py-1.5 text-sm bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors"
                    >
                        <Plus class="w-4 h-4 mr-1" />
                        {{ t('Pay Salary') }}
                    </button>
                </div>
                <div v-if="salaryTransactions.length === 0" class="text-center py-8 text-muted-foreground dark:text-muted-foreground">
                    <p v-if="marea.status === 'closed' || marea.status === 'cancelled'" class="text-sm">
                        {{ t('No salary payments recorded for this marea') }}
                    </p>
                    <p v-else-if="marea.crew_members.length === 0" class="text-sm">
                        {{ t('Add crew members to enable salary payments') }}
                    </p>
                    <p v-else class="text-sm">
                        {{ t('No salary payments recorded for this marea') }}
                    </p>
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="transaction in salaryTransactions"
                        :key="transaction.id"
                        class="flex items-center justify-between p-3 rounded-lg border border-border dark:border-border hover:bg-muted/30 dark:hover:bg-muted/20 group"
                    >
                        <div
                            class="flex items-center gap-3 flex-1 cursor-pointer flex-wrap"
                            @click="openTransactionModal(transaction)"
                        >
                            <span class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ transaction.transaction_number }}
                            </span>
                            <span
                                v-if="transaction.crew_member"
                                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300"
                            >
                                {{ transaction.crew_member.name }}
                            </span>
                            <span class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ transaction.description || t('Salary payment') }}
                            </span>
                            <!-- Quantity and Price Per Unit (inline, prominent) -->
                            <span
                                v-if="(transaction.amount_per_unit ?? transaction.price_per_unit) != null && transaction.quantity != null && (transaction.amount_per_unit ?? transaction.price_per_unit) > 0 && transaction.quantity > 0"
                                class="inline-flex items-center gap-1 text-xs text-muted-foreground dark:text-muted-foreground"
                            >
                                <span class="font-medium">{{ Math.round(transaction.quantity) }}</span>
                                <span>×</span>
                                <MoneyDisplay
                                    :value="transaction.amount_per_unit ?? transaction.price_per_unit"
                                    :currency="transaction.currency"
                                    :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                    variant="neutral"
                                    size="xs"
                                    class="inline"
                                />
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex flex-col items-end">
                                <!-- When amount_per_unit and quantity exist -->
                                <template v-if="(transaction.amount_per_unit ?? transaction.price_per_unit) != null && transaction.quantity != null && (transaction.amount_per_unit ?? transaction.price_per_unit)! > 0 && transaction.quantity > 0">
                                    <!-- Show VAT only if it exists -->
                                    <template v-if="transaction.vat_amount > 0">
                                        <div class="text-xs text-muted-foreground dark:text-muted-foreground mb-0.5">
                                            {{ t('VAT') }}:
                                            <MoneyDisplay
                                                :value="transaction.vat_amount"
                                                :currency="transaction.currency"
                                                :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                                variant="neutral"
                                                size="xs"
                                                class="text-xs font-normal"
                                            />
                                        </div>
                                    </template>
                                    <!-- Total Amount (always show) -->
                                    <div class="text-sm font-semibold">
                                        <MoneyDisplay
                                            :value="transaction.vat_amount > 0 ? transaction.total_amount : transaction.amount"
                                            :currency="transaction.currency"
                                            :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                            variant="negative"
                                            size="sm"
                                            class="font-semibold"
                                        />
                                    </div>
                                </template>
                                <!-- When no price_per_unit/quantity -->
                                <template v-else>
                                    <div class="text-sm font-semibold">
                                        <MoneyDisplay
                                            :value="transaction.vat_amount > 0 ? transaction.total_amount : transaction.amount"
                                            :currency="transaction.currency"
                                            :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                            variant="negative"
                                            size="sm"
                                            class="font-semibold"
                                        />
                                    </div>
                                    <!-- VAT indicator (only if VAT exists) -->
                                    <div v-if="transaction.vat_amount > 0" class="text-xs text-muted-foreground dark:text-muted-foreground mt-0.5">
                                        {{ t('incl. VAT') }}
                                    </div>
                                </template>
                            </div>
                            <button
                                v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                                @click.stop="handleRemoveTransaction(transaction.id)"
                                class="opacity-0 group-hover:opacity-100 p-1 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-all"
                                :disabled="isProcessing"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground">{{ t('Transactions') }}</h2>
                    <div class="flex gap-2">
                        <button
                            v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                            @click="showCreateIncomeDialog = true"
                            class="inline-flex items-center px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                        >
                            <Plus class="w-4 h-4 mr-1" />
                            {{ t('Add Income') }}
                        </button>
                        <button
                            v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                            @click="showCreateExpenseDialog = true"
                            class="inline-flex items-center px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                        >
                            <Plus class="w-4 h-4 mr-1" />
                            {{ t('Add Expense') }}
                        </button>
                        <button
                            v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                            @click="showAddTransactionDialog = true"
                            class="inline-flex items-center px-3 py-1.5 text-sm bg-secondary text-secondary-foreground rounded-lg hover:bg-secondary/90 transition-colors"
                        >
                            <Plus class="w-4 h-4 mr-1" />
                            {{ t('Link Transaction') }}
                        </button>
                        <button
                            @click="router.visit(`/panel/${getCurrentVesselId()}/transactions?marea_id=${marea.id}`)"
                            class="text-sm text-primary hover:text-primary/80 font-medium"
                        >
                            {{ t('View All →') }}
                        </button>
                    </div>
                </div>
                <div v-if="incomeTransactions.length === 0 && nonSalaryExpenseTransactions.length === 0" class="text-center py-8 text-muted-foreground dark:text-muted-foreground">
                    <p v-if="marea.status === 'closed' || marea.status === 'cancelled'" class="text-sm">
                        {{ t('No transactions linked to this marea') }}
                    </p>
                    <p v-else class="text-sm">
                        {{ t('No transactions linked to this marea. Add income or expense transactions to get started.') }}
                    </p>
                </div>
                <div v-else class="space-y-4">
                    <!-- Income Transactions -->
                    <div v-if="incomeTransactions.length > 0">
                        <h3 class="text-sm font-semibold text-green-700 dark:text-green-400 mb-2">{{ t('Income') }}</h3>
                        <div class="space-y-3">
                            <div
                                v-for="transaction in incomeTransactions"
                                :key="transaction.id"
                                class="flex items-center justify-between p-3 rounded-lg border border-border dark:border-border hover:bg-muted/30 dark:hover:bg-muted/20 group"
                            >
                                <div
                                    class="flex items-center gap-3 flex-1 cursor-pointer flex-wrap"
                                    @click="openTransactionModal(transaction)"
                                >
                                    <span class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                        {{ transaction.transaction_number }}
                                    </span>
                                    <span
                                        v-if="transaction.category"
                                        class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium"
                                        :style="transaction.category.color ? {
                                            backgroundColor: transaction.category.color + '20',
                                            color: transaction.category.color
                                        } : {}"
                                    >
                                        {{ t(transaction.category.name) }}
                                    </span>
                                    <span class="text-sm text-muted-foreground dark:text-muted-foreground">
                                        {{ transaction.description || t('No description') }}
                                    </span>
                                    <!-- Quantity and Price Per Unit (inline, prominent) -->
                                    <span
                                        v-if="(transaction.amount_per_unit ?? transaction.price_per_unit) != null && transaction.quantity != null && (transaction.amount_per_unit ?? transaction.price_per_unit)! > 0 && transaction.quantity > 0"
                                        class="inline-flex items-center gap-1 text-xs text-muted-foreground dark:text-muted-foreground"
                                    >
                                        <span class="font-medium">{{ Math.round(transaction.quantity) }}</span>
                                        <span>×</span>
                                        <MoneyDisplay
                                            :value="transaction.amount_per_unit ?? transaction.price_per_unit"
                                            :currency="transaction.currency"
                                            :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                            variant="neutral"
                                            size="xs"
                                            class="inline"
                                        />
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex flex-col items-end">
                                        <!-- When amount_per_unit and quantity exist -->
                                        <template v-if="(transaction.amount_per_unit ?? transaction.price_per_unit) != null && transaction.quantity != null && (transaction.amount_per_unit ?? transaction.price_per_unit) > 0 && transaction.quantity > 0">
                                            <!-- Show VAT only if it exists -->
                                            <template v-if="transaction.vat_amount > 0">
                                                <div class="text-xs text-muted-foreground dark:text-muted-foreground mb-0.5">
                                                    {{ t('VAT') }}:
                                                    <MoneyDisplay
                                                        :value="transaction.vat_amount"
                                                        :currency="transaction.currency"
                                                        :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                                        variant="neutral"
                                                        size="xs"
                                                        class="text-xs font-normal"
                                                    />
                                                </div>
                                            </template>
                                            <!-- Total Amount (always show) -->
                                            <div class="text-sm font-semibold">
                                                <MoneyDisplay
                                                    :value="transaction.vat_amount > 0 ? transaction.total_amount : transaction.amount"
                                                    :currency="transaction.currency"
                                                    :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                                    variant="positive"
                                                    size="sm"
                                                    class="font-semibold"
                                                />
                                            </div>
                                        </template>
                                        <!-- When no amount_per_unit/quantity -->
                                        <template v-else>
                                            <div class="text-sm font-semibold">
                                                <MoneyDisplay
                                                    :value="transaction.vat_amount > 0 ? transaction.total_amount : transaction.amount"
                                                    :currency="transaction.currency"
                                                    :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                                    variant="positive"
                                                    size="sm"
                                                    class="font-semibold"
                                                />
                                            </div>
                                            <!-- VAT indicator (only if VAT exists) -->
                                            <div v-if="transaction.vat_amount > 0" class="text-xs text-muted-foreground dark:text-muted-foreground mt-0.5">
                                                {{ t('incl. VAT') }}
                                            </div>
                                        </template>
                                    </div>
                                    <!-- Action Buttons (View, Edit, Delete) -->
                                    <div
                                        @click.stop
                                        class="flex items-center gap-1 flex-shrink-0 ml-2"
                                    >
                                        <!-- View Button -->
                                        <button
                                            @click.stop="openTransactionModal(transaction)"
                                            class="flex items-center justify-center w-7 h-7 rounded-full hover:bg-primary/10 dark:hover:bg-primary/20 transition-colors text-muted-foreground hover:text-primary dark:text-muted-foreground dark:hover:text-primary"
                                            :title="t('View transaction details')"
                                        >
                                            <Icon name="eye" class="w-4 h-4" />
                                        </button>

                                        <!-- Edit Button -->
                                        <button
                                            v-if="canEdit('transactions') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                                            @click.stop="openUpdateModal(transaction)"
                                            class="flex items-center justify-center w-7 h-7 rounded-full hover:bg-primary/10 dark:hover:bg-primary/20 transition-colors text-muted-foreground hover:text-primary dark:text-muted-foreground dark:hover:text-primary"
                                            :title="t('Edit transaction')"
                                        >
                                            <Icon name="edit" class="w-4 h-4" />
                                        </button>

                                        <!-- Delete Button -->
                                        <button
                                            v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                                            @click.stop="openDeleteTransactionDialog(transaction.id)"
                                            class="flex items-center justify-center w-7 h-7 rounded-full hover:bg-destructive/10 dark:hover:bg-destructive/20 transition-colors text-muted-foreground hover:text-destructive dark:text-muted-foreground dark:hover:text-destructive"
                                            :title="t('Remove transaction from marea')"
                                            :disabled="isProcessing"
                                        >
                                            <Icon name="x" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Divider between income and expenses -->
                    <div v-if="incomeTransactions.length > 0 && nonSalaryExpenseTransactions.length > 0" class="flex items-center my-4">
                        <div class="flex-1 border-t border-border dark:border-border"></div>
                        <span class="px-4 text-xs font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Expenses') }}</span>
                        <div class="flex-1 border-t border-border dark:border-border"></div>
                    </div>

                    <!-- Expense Transactions (non-salary) -->
                    <div v-if="nonSalaryExpenseTransactions.length > 0">
                        <h3 v-if="incomeTransactions.length === 0" class="text-sm font-semibold text-red-700 dark:text-red-400 mb-2">{{ t('Expenses') }}</h3>
                        <div class="space-y-3">
                            <div
                                v-for="transaction in nonSalaryExpenseTransactions"
                                :key="transaction.id"
                                class="flex items-center justify-between p-3 rounded-lg border border-border dark:border-border hover:bg-muted/30 dark:hover:bg-muted/20 group"
                            >
                                <div
                                    class="flex items-center gap-3 flex-1 cursor-pointer flex-wrap"
                                    @click="openTransactionModal(transaction)"
                                >
                                    <span class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                        {{ transaction.transaction_number }}
                                    </span>
                                    <span
                                        v-if="transaction.category"
                                        class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium"
                                        :style="transaction.category.color ? {
                                            backgroundColor: transaction.category.color + '20',
                                            color: transaction.category.color
                                        } : {}"
                                    >
                                        {{ t(transaction.category.name) }}
                                    </span>
                                    <span class="text-sm text-muted-foreground dark:text-muted-foreground">
                                        {{ transaction.description || t('No description') }}
                                    </span>
                                    <!-- Quantity and Price Per Unit (inline, prominent) -->
                                    <span
                                        v-if="(transaction.amount_per_unit ?? transaction.price_per_unit) != null && transaction.quantity != null && (transaction.amount_per_unit ?? transaction.price_per_unit)! > 0 && transaction.quantity > 0"
                                        class="inline-flex items-center gap-1 text-xs text-muted-foreground dark:text-muted-foreground"
                                    >
                                        <span class="font-medium">{{ Math.round(transaction.quantity) }}</span>
                                        <span>×</span>
                                        <MoneyDisplay
                                            :value="transaction.amount_per_unit ?? transaction.price_per_unit"
                                            :currency="transaction.currency"
                                            :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                            variant="neutral"
                                            size="xs"
                                            class="inline"
                                        />
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex flex-col items-end">
                                        <!-- When amount_per_unit and quantity exist -->
                                        <template v-if="(transaction.amount_per_unit ?? transaction.price_per_unit) != null && transaction.quantity != null && (transaction.amount_per_unit ?? transaction.price_per_unit) > 0 && transaction.quantity > 0">
                                            <!-- Show VAT only if it exists -->
                                            <template v-if="transaction.vat_amount > 0">
                                                <div class="text-xs text-muted-foreground dark:text-muted-foreground mb-0.5">
                                                    {{ t('VAT') }}:
                                                    <MoneyDisplay
                                                        :value="transaction.vat_amount"
                                                        :currency="transaction.currency"
                                                        :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                                        variant="neutral"
                                                        size="xs"
                                                        class="text-xs font-normal"
                                                    />
                                                </div>
                                            </template>
                                            <!-- Total Amount (always show) -->
                                            <div class="text-sm font-semibold">
                                                <MoneyDisplay
                                                    :value="transaction.vat_amount > 0 ? transaction.total_amount : transaction.amount"
                                                    :currency="transaction.currency"
                                                    :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                                    variant="negative"
                                                    size="sm"
                                                    class="font-semibold"
                                                />
                                            </div>
                                        </template>
                                        <!-- When no amount_per_unit/quantity -->
                                        <template v-else>
                                            <div class="text-sm font-semibold">
                                                <MoneyDisplay
                                                    :value="transaction.vat_amount > 0 ? transaction.total_amount : transaction.amount"
                                                    :currency="transaction.currency"
                                                    :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                                    variant="negative"
                                                    size="sm"
                                                    class="font-semibold"
                                                />
                                            </div>
                                            <!-- VAT indicator (only if VAT exists) -->
                                            <div v-if="transaction.vat_amount > 0" class="text-xs text-muted-foreground dark:text-muted-foreground mt-0.5">
                                                {{ t('incl. VAT') }}
                                            </div>
                                        </template>
                                    </div>
                                    <!-- Action Buttons (View, Edit, Delete) -->
                                    <div
                                        @click.stop
                                        class="flex items-center gap-1 flex-shrink-0 ml-2"
                                    >
                                        <!-- View Button -->
                                        <button
                                            @click.stop="openTransactionModal(transaction)"
                                            class="flex items-center justify-center w-7 h-7 rounded-full hover:bg-primary/10 dark:hover:bg-primary/20 transition-colors text-muted-foreground hover:text-primary dark:text-muted-foreground dark:hover:text-primary"
                                            :title="t('View transaction details')"
                                        >
                                            <Icon name="eye" class="w-4 h-4" />
                                        </button>

                                        <!-- Edit Button -->
                                        <button
                                            v-if="canEdit('transactions') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                                            @click.stop="openUpdateModal(transaction)"
                                            class="flex items-center justify-center w-7 h-7 rounded-full hover:bg-primary/10 dark:hover:bg-primary/20 transition-colors text-muted-foreground hover:text-primary dark:text-muted-foreground dark:hover:text-primary"
                                            :title="t('Edit transaction')"
                                        >
                                            <Icon name="edit" class="w-4 h-4" />
                                        </button>

                                        <!-- Delete Button -->
                                        <button
                                            v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                                            @click.stop="openDeleteTransactionDialog(transaction.id)"
                                            class="flex items-center justify-center w-7 h-7 rounded-full hover:bg-destructive/10 dark:hover:bg-destructive/20 transition-colors text-muted-foreground hover:text-destructive dark:text-muted-foreground dark:hover:text-destructive"
                                            :title="t('Remove transaction from marea')"
                                            :disabled="isProcessing"
                                        >
                                            <Icon name="x" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Crew Members Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <Collapsible v-model:open="isCrewMembersExpanded" :default-open="false">
                    <CollapsibleTrigger class="w-full">
                        <div class="flex items-center justify-between w-full cursor-pointer hover:opacity-80 transition-opacity">
                            <div class="flex items-center gap-2">
                                <Users class="w-5 h-5 text-card-foreground dark:text-card-foreground" />
                                <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground">
                                    {{ t('Crew Members') }}
                                </h2>
                                <span v-if="marea.crew_members.length > 0" class="text-xs text-muted-foreground dark:text-muted-foreground px-2 py-1 rounded bg-muted/50">
                                    {{ marea.crew_members.length }}
                                </span>
                                <span v-else class="text-xs text-muted-foreground dark:text-muted-foreground px-2 py-1 rounded bg-muted/50">
                                    {{ t('Empty') }}
                                </span>
                            </div>
                            <ChevronDown
                                :class="[
                                    'w-5 h-5 text-muted-foreground dark:text-muted-foreground transition-transform duration-200',
                                    isCrewMembersExpanded ? 'transform rotate-180' : ''
                                ]"
                            />
                        </div>
                    </CollapsibleTrigger>

                    <CollapsibleContent>
                        <div class="mt-4">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex-1"></div>
                                <button
                                    v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                                    @click="showAddCrewDialog = true"
                                    class="inline-flex items-center px-3 py-1.5 text-sm bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors"
                                >
                                    <Plus class="w-4 h-4 mr-1" />
                                    {{ t('Add Crew Member') }}
                                </button>
                            </div>

                            <div v-if="marea.crew_members.length === 0" class="text-center py-8 text-muted-foreground dark:text-muted-foreground border border-dashed border-border dark:border-border rounded-lg">
                                {{ t('No crew members assigned to this marea') }}
                            </div>

                            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="member in marea.crew_members"
                                    :key="member.id"
                                    class="p-4 rounded-lg border border-border dark:border-border group relative"
                                >
                                    <button
                                        v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                                        @click="handleRemoveCrew(member.id)"
                                        class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 p-1 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-all"
                                        :disabled="isProcessing"
                                    >
                                        <X class="w-4 h-4" />
                                    </button>
                                    <div class="font-medium text-card-foreground dark:text-card-foreground">
                                        {{ member.name }}
                                    </div>
                                    <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                        {{ member.email }}
                                    </div>
                                    <div v-if="member.notes" class="text-xs text-muted-foreground dark:text-muted-foreground mt-2">
                                        {{ member.notes }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CollapsibleContent>
                </Collapsible>
            </div>

            <!-- Fishing Quantity Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <Collapsible v-model:open="isFishingQuantityExpanded" :default-open="false">
                    <CollapsibleTrigger class="w-full">
                        <div class="flex items-center justify-between w-full cursor-pointer hover:opacity-80 transition-opacity">
                            <div class="flex items-center gap-2">
                                <Package class="w-5 h-5 text-card-foreground dark:text-card-foreground" />
                                <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground">
                                    {{ t('Fishing Quantity') }}
                                </h2>
                                <span v-if="marea.quantity_returns.length > 0" class="text-xs text-muted-foreground dark:text-muted-foreground px-2 py-1 rounded bg-muted/50">
                                    {{ marea.quantity_returns.length }}
                                </span>
                                <span v-else class="text-xs text-muted-foreground dark:text-muted-foreground px-2 py-1 rounded bg-muted/50">
                                    {{ t('Empty') }}
                                </span>
                            </div>
                            <ChevronDown
                                :class="[
                                    'w-5 h-5 text-muted-foreground dark:text-muted-foreground transition-transform duration-200',
                                    isFishingQuantityExpanded ? 'transform rotate-180' : ''
                                ]"
                            />
                        </div>
                    </CollapsibleTrigger>

                    <CollapsibleContent>
                        <div class="mt-4">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex-1"></div>
                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="canEdit('mareas') && marea.status === 'returned'"
                                        @click="showAddQuantityReturnDialog = true"
                                        class="inline-flex items-center px-3 py-1.5 text-sm bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors"
                                    >
                                        <Plus class="w-4 h-4 mr-1" />
                                        {{ t('Add Product') }}
                                    </button>
                                    <div v-else-if="marea.status === 'closed'" class="text-xs text-muted-foreground dark:text-muted-foreground italic">
                                        {{ t('Products cannot be added to closed mareas') }}
                                    </div>
                                </div>
                            </div>

                            <div v-if="marea.quantity_returns.length === 0" class="text-center py-8 text-muted-foreground dark:text-muted-foreground border border-dashed border-border dark:border-border rounded-lg">
                                <p v-if="marea.status === 'closed'" class="text-sm mb-2">
                                    {{ t('No products returned for this marea') }}
                                </p>
                                <p v-else-if="marea.status === 'returned'" class="text-sm">
                                    {{ t('No products returned for this marea') }}
                                </p>
                                <p v-else class="text-sm">
                                    {{ t('Products can be added when the marea is returned') }}
                                </p>
                            </div>

                            <div v-else class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b border-border dark:border-border">
                                            <th class="text-left py-2 px-4 text-sm font-medium text-card-foreground dark:text-card-foreground">{{ t('Name') }}</th>
                                            <th class="text-right py-2 px-4 text-sm font-medium text-card-foreground dark:text-card-foreground">{{ t('Quantity') }}</th>
                                            <th v-if="canEdit('mareas') && marea.status !== 'closed'" class="text-right py-2 px-4 text-sm font-medium text-card-foreground dark:text-card-foreground">{{ t('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="qr in marea.quantity_returns"
                                            :key="qr.id"
                                            class="border-b border-border dark:border-border"
                                        >
                                            <td class="py-2 px-4 text-sm text-card-foreground dark:text-card-foreground">
                                                {{ qr.name }}
                                                <div v-if="qr.notes" class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                                                    {{ qr.notes }}
                                                </div>
                                            </td>
                                            <td class="py-2 px-4 text-sm text-right text-card-foreground dark:text-card-foreground">
                                                {{ qr.quantity }}
                                            </td>
                                            <td v-if="canEdit('mareas') && marea.status !== 'closed'" class="py-2 px-4 text-right">
                                                <button
                                                    @click="handleRemoveQuantityReturn(qr.id)"
                                                    class="p-1 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-all"
                                                    :disabled="isProcessing"
                                                >
                                                    <X class="w-4 h-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </CollapsibleContent>
                </Collapsible>
            </div>
        </div>

        <!-- Confirmation Dialogs -->
        <ConfirmationDialog
            v-model:open="showMarkAtSeaDialog"
            :title="t('Mark Marea as At Sea')"
            :description="t('This will mark the marea as currently at sea.')"
            :message="t('Are you sure you want to mark this marea as at sea?')"
            :confirm-text="t('Mark At Sea')"
            :cancel-text="t('Cancel')"
            variant="default"
            type="info"
            :loading="isProcessing"
            @confirm="handleMarkAtSea"
            @cancel="showMarkAtSeaDialog = false"
        >
            <template #default>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                        {{ t('Departure Date') }}
                    </label>
                    <DateInput
                        v-model="markAtSeaForm.date"
                    />
                </div>
            </template>
        </ConfirmationDialog>

        <ConfirmationDialog
            v-model:open="showMarkReturnedDialog"
            :title="t('Mark Marea as Returned')"
            :description="t('This will mark the marea as returned.')"
            :message="t('Are you sure you want to mark this marea as returned?')"
            :confirm-text="t('Mark Returned')"
            :cancel-text="t('Cancel')"
            variant="default"
            type="info"
            :loading="isProcessing"
            @confirm="handleMarkReturned"
            @cancel="showMarkReturnedDialog = false"
        >
            <template #default>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                        {{ t('Return Date') }}
                    </label>
                    <DateInput
                        v-model="markReturnedForm.date"
                    />
                </div>
            </template>
        </ConfirmationDialog>

        <ConfirmationDialog
            v-model:open="showCloseDialog"
            :title="t('Close Marea')"
            :description="t('This action will close the marea permanently.')"
            :message="t('Are you sure you want to close this marea? This action cannot be undone.')"
            :confirm-text="t('Close Marea')"
            :cancel-text="t('Cancel')"
            variant="default"
            type="warning"
            :loading="isProcessing"
            @confirm="handleClose"
            @cancel="showCloseDialog = false"
        />

        <ConfirmationDialog
            v-model:open="showCancelDialog"
            :title="t('Cancel Marea')"
            :description="t('This action will cancel the marea.')"
            :message="t('Are you sure you want to cancel this marea? This action cannot be undone.')"
            :confirm-text="t('Cancel Marea')"
            :cancel-text="t('No, Keep It')"
            variant="destructive"
            type="danger"
            :loading="isProcessing"
            @confirm="handleCancel"
            @cancel="showCancelDialog = false"
        />

        <ConfirmationDialog
            v-model:open="showDeleteDialog"
            :title="t('Delete Marea')"
            :description="t('This action cannot be undone.')"
            :message="t('Are you sure you want to delete marea') + ` '${marea.marea_number}'? ` + t('This will permanently remove the marea and all') + ` ${props.transactionCount || 0} ` + t('transaction(s) associated with it.')"
            :confirm-text="t('Delete Marea')"
            :cancel-text="t('Cancel')"
            variant="destructive"
            type="danger"
            :loading="isDeleting"
            @confirm="confirmDeleteMarea"
            @cancel="cancelDeleteMarea"
        />

        <!-- Add Transaction Modal -->
        <Dialog :open="showAddTransactionDialog" @update:open="showAddTransactionDialog = $event">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ t('Add Transaction to Marea') }}</DialogTitle>
                    <DialogDescription>{{ t('Select a transaction to link to this marea.') }}</DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <div v-if="loadingTransactions" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                    </div>
                    <div v-else-if="availableTransactions.length === 0" class="text-center py-8 text-muted-foreground">
                        {{ t('No available transactions to add.') }}
                    </div>
                    <div v-else class="space-y-2 max-h-96 overflow-y-auto">
                        <div
                            v-for="transaction in availableTransactions"
                            :key="transaction.id"
                            @click.stop="selectTransaction(transaction.id)"
                            :class="[
                                'p-3 rounded-lg border cursor-pointer transition-colors',
                                selectedTransactionId === transaction.id
                                    ? 'border-primary bg-primary/10 dark:bg-primary/20'
                                    : 'border-border hover:bg-muted/30 dark:hover:bg-muted/20'
                            ]"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="font-medium text-card-foreground">
                                        {{ transaction.transaction_number }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ transaction.description || t('No description') }}
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-1">
                                        {{ transaction.transaction_date }}
                                    </div>
                                </div>
                                <MoneyDisplay
                                    :value="transaction.total_amount"
                                    :currency="transaction.currency"
                                    :variant="transaction.type === 'income' ? 'positive' : 'negative'"
                                    size="sm"
                                    class="font-semibold"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showAddTransactionDialog = false" :disabled="isProcessing">
                        {{ t('Cancel') }}
                    </Button>
                    <Button @click="handleAddTransaction" :disabled="isProcessing || !selectedTransactionId">
                        {{ t('Add Transaction') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Add Crew Modal -->
        <Dialog :open="showAddCrewDialog" @update:open="showAddCrewDialog = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('Add Crew Member to Marea') }}</DialogTitle>
                    <DialogDescription>{{ t('Select a crew member to add to this marea.') }}</DialogDescription>
                </DialogHeader>
                <div class="py-4 space-y-4">
                    <div v-if="loadingCrew" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                    </div>
                    <div v-else>
                        <div>
                            <Label for="crew_member">{{ t('Crew Member') }}</Label>
                            <Select
                                id="crew_member"
                                v-model="addCrewForm.user_id"
                                :options="availableCrewMemberOptions"
                                :placeholder="t('Select a crew member')"
                                :disabled="addCrewForm.processing || availableCrewMembers.length === 0"
                                searchable
                                :error="!!addCrewForm.errors.user_id"
                            />
                            <InputError :message="addCrewForm.errors.user_id" class="mt-1" />
                            <p v-if="availableCrewMembers.length === 0" class="mt-1 text-xs text-muted-foreground dark:text-muted-foreground">
                                {{ t('No available crew members to assign') }}
                            </p>
                        </div>
                        <div>
                            <Label for="crew_notes">{{ t('Notes') }} ({{ t('Optional') }})</Label>
                            <textarea
                                id="crew_notes"
                                v-model="addCrewForm.notes"
                                :placeholder="t('Additional notes about this crew member')"
                                rows="3"
                                class="flex min-h-[80px] w-full rounded-md border border-input dark:border-input bg-background dark:bg-background px-3 py-2 text-sm text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            />
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showAddCrewDialog = false" :disabled="isProcessing">
                        {{ t('Cancel') }}
                    </Button>
                    <Button @click="handleAddCrew" :disabled="isProcessing || !addCrewForm.user_id">
                        {{ t('Add Crew') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Add Fishing Quantity Modal -->
        <Dialog :open="showAddQuantityReturnDialog" @update:open="showAddQuantityReturnDialog = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('Add Product Return') }}</DialogTitle>
                    <DialogDescription>{{ t('Add fishing quantity for this marea.') }}</DialogDescription>
                </DialogHeader>
                <div class="py-4 space-y-4">
                    <div>
                        <Label for="product_name">{{ t('Product Name') }} *</Label>
                        <Input
                            v-model="addQuantityReturnForm.name"
                            :placeholder="t('Enter product name')"
                            :error="addQuantityReturnForm.errors.name"
                        />
                    </div>
                    <div>
                        <Label for="quantity">{{ t('Quantity') }} *</Label>
                        <Input
                            v-model.number="addQuantityReturnForm.quantity"
                            type="number"
                            step="0.01"
                            min="0"
                            :placeholder="t('Enter quantity')"
                            :error="addQuantityReturnForm.errors.quantity"
                        />
                    </div>
                    <div>
                        <Label for="product_notes">{{ t('Notes') }} ({{ t('Optional') }})</Label>
                        <textarea
                            id="product_notes"
                            v-model="addQuantityReturnForm.notes"
                            :placeholder="t('Additional notes about this product')"
                            rows="3"
                            class="flex min-h-[80px] w-full rounded-md border border-input dark:border-input bg-background dark:bg-background px-3 py-2 text-sm text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showAddQuantityReturnDialog = false" :disabled="isProcessing">
                        {{ t('Cancel') }}
                    </Button>
                    <Button @click="handleAddQuantityReturn" :disabled="isProcessing">
                        {{ t('Add Product') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Create Income Transaction Modal -->
        <CreateAddModal
            :open="showCreateIncomeDialog"
            :categories="props.categories || []"
            :vat-profiles="props.vatProfiles || []"
            :default-vat-profile="props.defaultVatProfile || null"
            :default-currency="props.defaultCurrency || 'EUR'"
            :marea-id="marea.id"
            @close="showCreateIncomeDialog = false"
            @success="handleCreateTransactionSuccess"
        />

        <!-- Create Expense Transaction Modal -->
        <CreateRemoveModal
            :open="showCreateExpenseDialog"
            :categories="props.categories || []"
            :suppliers="props.suppliers || []"
            :crew-members="props.crewMembers || []"
            :default-currency="props.defaultCurrency || 'EUR'"
            :marea-id="marea.id"
            @close="showCreateExpenseDialog = false"
            @success="handleCreateTransactionSuccess"
        />

        <!-- Edit Calculation Modal -->
        <EditCalculationModal
            v-if="marea.distribution_profile"
            :open="showEditCalculationDialog"
            :marea-id="marea.id"
            :distribution-items="marea.distribution_items || []"
            :distribution-profile-items="marea.distribution_profile_items || []"
            :vessel-id="getCurrentVesselId()"
            @update:open="showEditCalculationDialog = $event"
            @close="showEditCalculationDialog = false"
            @success="handleEditCalculationSuccess"
        />

        <!-- Edit Marea Modal -->
        <EditMareaModal
            :open="showEditMareaDialog"
            :marea="{
                id: marea.id,
                marea_number: marea.marea_number,
                name: marea.name,
                description: marea.description,
                estimated_departure_date: marea.estimated_departure_date,
                estimated_return_date: marea.estimated_return_date,
                status: marea.status,
            }"
            :vessel-id="getCurrentVesselId()"
            @update:open="showEditMareaDialog = $event"
            @close="showEditMareaDialog = false"
            @success="handleEditMareaSuccess"
        />

        <!-- Salary Payment Modal -->
        <Dialog :open="showSalaryPaymentDialog" @update:open="showSalaryPaymentDialog = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('Pay Salary to Crew Member') }}</DialogTitle>
                    <DialogDescription>{{ t('Select a crew member to pay salary to.') }}</DialogDescription>
                </DialogHeader>
                <div class="py-4 space-y-4">
                    <div>
                        <Label for="crew_member_salary">{{ t('Crew Member') }} *</Label>
                        <Select
                            id="crew_member_salary"
                            v-model="salaryPaymentForm.crew_member_id"
                            :options="mareaCrewMemberOptions"
                            :placeholder="t('Select a crew member')"
                            :disabled="isProcessing || loadingSalaryData"
                            searchable
                            :error="!!salaryPaymentForm.errors.crew_member_id"
                        />
                        <p v-if="salaryPaymentForm.errors.crew_member_id" class="text-sm text-red-600 dark:text-red-400 mt-1">
                            {{ salaryPaymentForm.errors.crew_member_id }}
                        </p>
                    </div>

                    <!-- Salary Configuration Info -->
                    <div v-if="selectedCrewSalaryInfo && selectedCrewSalaryInfo.compensation_type" class="p-4 rounded-lg border border-border dark:border-border bg-muted/30 dark:bg-muted/20">
                        <div class="flex items-start gap-3">
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-card-foreground dark:text-card-foreground mb-2">
                                    {{ t('Salary Configuration') }}
                                </h4>
                                <div v-if="selectedCrewSalaryInfo.compensation_type === 'fixed'" class="space-y-1">
                                    <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                                        <span class="font-medium">{{ t('Type') }}:</span> {{ t('Fixed Amount') }}
                                    </p>
                                    <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                                        <span class="font-medium">{{ t('Fixed Salary') }}:</span>
                                        <MoneyDisplay
                                            v-if="selectedCrewSalaryInfo.fixed_amount"
                                            :value="selectedCrewSalaryInfo.fixed_amount"
                                            :currency="selectedCrewSalaryInfo.currency || defaultCurrency"
                                            variant="neutral"
                                            size="sm"
                                            class="inline-block ml-1"
                                        />
                                        <span v-else class="ml-1">{{ t('Not specified') }}</span>
                                    </p>
                                </div>
                                <div v-else-if="selectedCrewSalaryInfo.compensation_type === 'percentage'" class="space-y-1">
                                    <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                                        <span class="font-medium">{{ t('Type') }}:</span> {{ t('Percentage of Revenue') }}
                                    </p>
                                    <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                                        <span class="font-medium">{{ t('Percentage') }}:</span> {{ selectedCrewSalaryInfo.percentage }}% {{ t('of total income') }}
                                    </p>
                                    <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                                        <span class="font-medium">{{ t('Total Income') }}:</span>
                                        <MoneyDisplay
                                            :value="marea.total_income"
                                            :currency="defaultCurrency"
                                            variant="neutral"
                                            size="sm"
                                            class="inline-block ml-1"
                                        />
                                    </p>
                                    <p class="text-sm font-semibold text-card-foreground dark:text-card-foreground mt-2">
                                        <span class="font-medium">{{ t('Calculated Amount') }}:</span>
                                        <MoneyDisplay
                                            v-if="selectedCrewSalaryInfo.calculated_amount"
                                            :value="selectedCrewSalaryInfo.calculated_amount"
                                            :currency="selectedCrewSalaryInfo.currency || defaultCurrency"
                                            variant="positive"
                                            size="sm"
                                            class="inline-block ml-1"
                                        />
                                        <span v-else class="ml-1 text-muted-foreground">{{ t('Not calculated') }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="salaryPaymentForm.crew_member_id && !loadingSalaryData" class="p-4 rounded-lg border border-border/50 dark:border-border/50 bg-muted/20 dark:bg-muted/10">
                        <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                            {{ t('This crew member doesn\'t have a salary configuration. You can enter the amount manually.') }}
                        </p>
                    </div>

                    <div>
                        <Label for="salary_amount">{{ t('Amount') }} *</Label>
                        <MoneyInput
                            id="salary_amount"
                            v-model="salaryPaymentForm.amount"
                            :currency="defaultCurrency"
                            :decimals="marea.house_of_zeros"
                            return-type="int"
                            :disabled="isProcessing || loadingSalaryData"
                            :error="!!salaryPaymentForm.errors.amount"
                            :placeholder="t('Enter amount')"
                        />
                        <p v-if="salaryPaymentForm.errors.amount" class="text-sm text-red-600 dark:text-red-400 mt-1">
                            {{ salaryPaymentForm.errors.amount }}
                        </p>
                        <p v-if="loadingSalaryData" class="text-xs text-muted-foreground mt-1">
                            {{ t('Loading salary configuration...') }}
                        </p>
                    </div>
                    <div>
                        <Label for="salary_date">{{ t('Transaction Date') }} *</Label>
                        <DateInput
                            id="salary_date"
                            v-model="salaryPaymentForm.transaction_date"
                            :class="{ 'border-destructive dark:border-destructive': salaryPaymentForm.errors.transaction_date }"
                            :disabled="isProcessing"
                        />
                    </div>
                    <div>
                        <Label for="salary_description">{{ t('Description') }}</Label>
                        <Input
                            id="salary_description"
                            v-model="salaryPaymentForm.description"
                            :placeholder="t('Enter description')"
                            :error="salaryPaymentForm.errors.description"
                            :disabled="isProcessing"
                        />
                    </div>
                    <div>
                        <Label for="salary_notes">{{ t('Notes') }} ({{ t('Optional') }})</Label>
                        <textarea
                            id="salary_notes"
                            v-model="salaryPaymentForm.notes"
                            :placeholder="t('Additional notes')"
                            rows="3"
                            class="flex min-h-[80px] w-full rounded-md border border-input dark:border-input bg-background dark:bg-background px-3 py-2 text-sm text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="isProcessing"
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showSalaryPaymentDialog = false" :disabled="isProcessing">
                        {{ t('Cancel') }}
                    </Button>
                    <Button @click="handleSalaryPayment" :disabled="isProcessing || !salaryPaymentForm.crew_member_id || !salaryPaymentForm.amount">
                        {{ t('Pay Salary') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Transaction Show Modal -->
        <TransactionShowModal
            v-if="selectedTransaction"
            :open="showTransactionModal"
            :transaction="selectedTransaction"
            @close="closeTransactionModal"
        />

        <!-- Update Transaction Modals -->
        <UpdateAddModal
            v-if="transactionToEdit && transactionToEdit.type === 'income'"
            :open="showUpdateAddModal"
            :transaction="transactionToEdit"
            :categories="incomeCategories"
            :vat-profiles="vatProfiles"
            :default-vat-profile="defaultVatProfile"
            :default-currency="props.defaultCurrency"
            @close="closeUpdateModals"
            @success="handleUpdateSuccess"
        />

        <UpdateRemoveModal
            v-if="transactionToEdit && transactionToEdit.type === 'expense'"
            :open="showUpdateRemoveModal"
            :transaction="transactionToEdit"
            :categories="expenseCategories"
            :suppliers="suppliers"
            :crew-members="crewMembers"
            :default-currency="props.defaultCurrency"
            @close="closeUpdateModals"
            @success="handleUpdateSuccess"
        />

        <!-- Delete Transaction Confirmation Dialog -->
        <ConfirmationDialog
            v-model:open="showDeleteTransactionDialog"
            :title="t('Remove Transaction from Marea')"
            :description="t('This will remove the transaction from this marea. The transaction itself will not be deleted.')"
            :message="t('Are you sure you want to remove this transaction from the marea?')"
            :confirm-text="t('Remove Transaction')"
            :cancel-text="t('Cancel')"
            variant="destructive"
            type="warning"
            :loading="isProcessing"
            @confirm="confirmDeleteTransaction"
            @cancel="cancelDeleteTransaction"
        />
    </VesselLayout>
</template>


