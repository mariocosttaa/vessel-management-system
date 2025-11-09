<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Icon from '@/components/Icon.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/composables/usePermissions';
import { useNotifications } from '@/composables/useNotifications';
import mareas from '@/routes/panel/mareas';
import { Ship, Calendar, Users, Package, DollarSign, TrendingUp, TrendingDown, Plus, X, Trash2 } from 'lucide-vue-next';
import CreateAddModal from '@/components/modals/Transaction/create-add.vue';
import CreateRemoveModal from '@/components/modals/Transaction/create-remove.vue';
import EditCalculationModal from '@/components/modals/Marea/EditCalculationModal.vue';
import transactions from '@/routes/panel/transactions';

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
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
    }>;
    created_at: string | null;
    created_by: {
        id: number;
        name: string;
    } | null;
}

interface Props {
    marea: Marea;
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
}

const props = defineProps<Props>();
const { canEdit, canDelete } = usePermissions();
const { addNotification } = useNotifications();

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
    user_id: null as number | null,
    notes: '' as string,
});

const addQuantityReturnForm = useForm({
    name: '' as string,
    quantity: 0 as number,
    notes: '' as string,
});

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
                title: 'Success',
                message: 'Marea has been marked as at sea.',
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
                title: 'Success',
                message: 'Marea has been marked as returned.',
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
                title: 'Success',
                message: 'Marea has been closed.',
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
                title: 'Success',
                message: 'Marea has been cancelled.',
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
                title: 'Success',
                message: 'Marea has been deleted successfully.',
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
            title: 'Error',
            message: 'Failed to load available transactions.',
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
        const data = await response.json();
        availableCrewMembers.value = data.crew_members || [];
    } catch (error) {
        console.error('Failed to load available crew:', error);
        addNotification({
            type: 'error',
            title: 'Error',
            message: 'Failed to load available crew members.',
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
            title: 'Error',
            message: 'Please select a transaction.',
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
                title: 'Success',
                message: 'Transaction has been added to the marea.',
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

// Handle remove transaction
const handleRemoveTransaction = (transactionId: number) => {
    isProcessing.value = true;
    router.delete(mareas.removeTransaction.url({
        vessel: getCurrentVesselId(),
        mareaId: props.marea.id,
        transaction: transactionId
    }), {
        onSuccess: () => {
            isProcessing.value = false;
            addNotification({
                type: 'success',
                title: 'Success',
                message: 'Transaction has been removed from the marea.',
            });
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
};

// Handle add crew
const handleAddCrew = () => {
    if (!addCrewForm.user_id) {
        addNotification({
            type: 'error',
            title: 'Error',
            message: 'Please select a crew member.',
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
                title: 'Success',
                message: 'Crew member has been added to the marea.',
            });
        },
        onError: () => {
            isProcessing.value = false;
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
                title: 'Success',
                message: 'Crew member has been removed from the marea.',
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
            title: 'Error',
            message: 'Please fill in all required fields.',
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
                title: 'Success',
                message: 'Product return has been added to the marea.',
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
        title: 'Success',
        message: 'Transaction has been created and linked to the marea.',
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
                title: 'Success',
                message: 'Product return has been removed from the marea.',
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
                title: 'Success',
                message: `Calculation ${!props.marea.use_calculation ? 'enabled' : 'disabled'} for this marea.`,
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

const updateProfile = (eventOrValue: Event | string | number | null) => {
    if (updatingProfile.value) return;

    // Extract value from event if it's an event
    let profileId: string | number | null;
    if (eventOrValue instanceof Event) {
        profileId = (eventOrValue.target as HTMLSelectElement).value;
    } else {
        profileId = eventOrValue;
    }

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
                title: 'Success',
                message: profileIdValue ? 'Distribution profile updated successfully.' : 'Distribution profile removed successfully.',
            });
        },
        onError: () => {
            updatingProfile.value = false;
            selectedProfileId.value = props.marea.distribution_profile_id; // Revert on error
            addNotification({
                type: 'error',
                title: 'Error',
                message: 'Failed to update distribution profile.',
            });
        },
    });
};
</script>

<template>
    <Head :title="`Marea ${marea.marea_number}`" />

    <VesselLayout :breadcrumbs="[
        { title: 'Mareas', href: mareas.index.url({ vessel: getCurrentVesselId() }) },
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
                                {{ marea.status === 'preparing' ? 'Preparing' :
                                   marea.status === 'at_sea' ? 'At Sea' :
                                   marea.status === 'returned' ? 'Returned' :
                                   marea.status === 'closed' ? 'Closed' :
                                   marea.status === 'cancelled' ? 'Cancelled' : marea.status }}
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
                            Mark At Sea
                        </button>
                        <button
                            v-if="marea.status === 'at_sea' && canEdit('mareas')"
                            @click="showMarkReturnedDialog = true"
                            class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <Ship class="w-4 h-4 mr-2" />
                            Mark Returned
                        </button>
                        <button
                            v-if="(marea.status === 'returned' || marea.status === 'at_sea') && canEdit('mareas')"
                            @click="showCloseDialog = true"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <Icon name="check" class="w-4 h-4 mr-2" />
                            Close Marea
                        </button>
                        <button
                            v-if="marea.status !== 'closed' && marea.status !== 'cancelled' && canEdit('mareas')"
                            @click="showCancelDialog = true"
                            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <Icon name="x" class="w-4 h-4 mr-2" />
                            Cancel
                        </button>
                        <button
                            v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                            @click="router.visit(mareas.edit.url({ vessel: getCurrentVesselId(), mareaId: marea.id }))"
                            class="inline-flex items-center px-4 py-2 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground font-medium transition-colors"
                        >
                            <Icon name="edit" class="w-4 h-4 mr-2" />
                            Edit
                        </button>
                    </div>
                </div>
            </div>

            <!-- Timeline Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">Timeline</h2>
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-2 h-2 rounded-full bg-blue-500 mt-2"></div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                Estimated Departure
                            </div>
                            <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ formatDate(marea.estimated_departure_date) }}
                            </div>
                        </div>
                    </div>
                    <div v-if="marea.actual_departure_date" class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-2 h-2 rounded-full bg-cyan-500 mt-2"></div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                Actual Departure
                            </div>
                            <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ formatDate(marea.actual_departure_date) }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-2 h-2 rounded-full bg-yellow-500 mt-2"></div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                Estimated Return
                            </div>
                            <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ formatDate(marea.estimated_return_date) }}
                            </div>
                        </div>
                    </div>
                    <div v-if="marea.actual_return_date" class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-2 h-2 rounded-full bg-green-500 mt-2"></div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                Actual Return
                            </div>
                            <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ formatDate(marea.actual_return_date) }}
                            </div>
                        </div>
                    </div>
                    <div v-if="marea.closed_at" class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-2 h-2 rounded-full bg-gray-500 mt-2"></div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                Closed At
                            </div>
                            <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ formatDateTime(marea.closed_at) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Summary Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">Financial Summary</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center p-4 rounded-lg bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800">
                        <div class="flex items-center justify-center mb-2">
                            <TrendingUp class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" />
                            <span class="text-sm font-medium text-green-800 dark:text-green-300">Total Income</span>
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
                            <span class="text-sm font-medium text-red-800 dark:text-red-300">Total Expenses</span>
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
                            <span class="text-sm font-medium text-blue-800 dark:text-blue-300">Net Result</span>
                        </div>
                        <MoneyDisplay
                            :value="marea.net_result"
                            :currency="defaultCurrency"
                            :variant="marea.net_result >= 0 ? 'positive' : 'negative'"
                            size="lg"
                            class="font-bold"
                        />
                    </div>
                </div>
            </div>

            <!-- Distribution Calculation Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            Distribution Calculation
                            <span v-if="marea.distribution_profile" class="text-sm text-muted-foreground dark:text-muted-foreground ml-2">
                                ({{ marea.distribution_profile.name }})
                            </span>
                            <span v-if="marea.distribution?.uses_overrides" class="text-xs text-primary dark:text-primary ml-2 px-2 py-1 rounded bg-primary/10 dark:bg-primary/20">
                                Custom Override
                            </span>
                        </h2>

                        <!-- Instant Profile Selector -->
                        <div v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'" class="flex items-center gap-3 mb-4">
                            <Label for="distribution-profile" class="text-sm font-medium text-card-foreground dark:text-card-foreground whitespace-nowrap">
                                Distribution Profile:
                            </Label>
                            <div class="flex items-center gap-2 flex-1 max-w-md">
                                <select
                                    id="distribution-profile"
                                    :value="selectedProfileId"
                                    @change="updateProfile($event.target.value)"
                                    :disabled="updatingProfile || isProcessing"
                                    class="flex h-10 w-full rounded-md border border-input dark:border-input bg-background dark:bg-background px-3 py-2 text-sm text-foreground dark:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option :value="null">No Profile (Optional)</option>
                                    <option
                                        v-for="profile in (distributionProfiles || [])"
                                        :key="profile.id"
                                        :value="profile.id"
                                    >
                                        {{ profile.name }}{{ profile.is_default ? ' (Default)' : '' }}
                                    </option>
                                </select>
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
                            <span class="text-sm text-card-foreground dark:text-card-foreground">Use Calculation</span>
                        </label>
                        <button
                            v-if="marea.use_calculation && marea.distribution_profile_id && canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                            @click="showEditCalculationDialog = true"
                            class="inline-flex items-center px-3 py-1.5 text-sm bg-secondary text-secondary-foreground rounded-lg hover:bg-secondary/90 transition-colors"
                        >
                            <Icon name="edit" class="w-4 h-4 mr-1" />
                            Edit Calculation
                        </button>
                    </div>
                </div>

                <div v-if="!marea.use_calculation" class="text-center py-8 text-muted-foreground dark:text-muted-foreground border border-dashed border-border dark:border-border rounded-lg">
                    Calculation is disabled for this marea. Enable it to see distribution results.
                </div>

                <div v-else-if="!marea.distribution_profile_id" class="text-center py-8 text-muted-foreground dark:text-muted-foreground border border-dashed border-border dark:border-border rounded-lg">
                    <Icon name="layers" class="w-12 h-12 mx-auto mb-3 opacity-50" />
                    <p class="text-base font-medium mb-2">No Distribution Profile Selected</p>
                    <p class="text-sm mb-4">Select a distribution profile above to enable calculation.</p>
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
                                            {{ item.item?.name || 'Unnamed Step' }}
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
                                    Final Result
                                </div>
                                <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                    Calculated distribution result
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
                <div v-else class="text-center py-8 text-muted-foreground dark:text-muted-foreground">
                    No distribution items configured. {{ marea.distribution_profile ? 'The profile has no items.' : 'Please select a distribution profile.' }}
                </div>
            </div>

            <!-- Transactions Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground">Transactions</h2>
                    <div class="flex gap-2">
                        <button
                            v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                            @click="showCreateIncomeDialog = true"
                            class="inline-flex items-center px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                        >
                            <Plus class="w-4 h-4 mr-1" />
                            Add Income
                        </button>
                        <button
                            v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                            @click="showCreateExpenseDialog = true"
                            class="inline-flex items-center px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                        >
                            <Plus class="w-4 h-4 mr-1" />
                            Add Expense
                        </button>
                        <button
                            v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                            @click="showAddTransactionDialog = true"
                            class="inline-flex items-center px-3 py-1.5 text-sm bg-secondary text-secondary-foreground rounded-lg hover:bg-secondary/90 transition-colors"
                        >
                            <Plus class="w-4 h-4 mr-1" />
                            Link Transaction
                        </button>
                        <button
                            @click="router.visit(`/panel/${getCurrentVesselId()}/transactions?marea_id=${marea.id}`)"
                            class="text-sm text-primary hover:text-primary/80 font-medium"
                        >
                            View All →
                        </button>
                    </div>
                </div>
                <div v-if="marea.transactions.length === 0" class="text-center py-8 text-muted-foreground dark:text-muted-foreground">
                    No transactions linked to this marea
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="transaction in marea.transactions.slice(0, 10)"
                        :key="transaction.id"
                        class="flex items-center justify-between p-3 rounded-lg border border-border dark:border-border hover:bg-muted/30 dark:hover:bg-muted/20 group"
                    >
                        <div
                            class="flex items-center gap-3 flex-1 cursor-pointer"
                            @click="router.visit(`/panel/${getCurrentVesselId()}/transactions/${transaction.id}`)"
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
                                {{ transaction.category.name }}
                            </span>
                            <span class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ transaction.description || 'No description' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <MoneyDisplay
                                :value="transaction.total_amount"
                                :currency="transaction.currency"
                                :variant="transaction.type === 'income' ? 'positive' : 'negative'"
                                size="sm"
                                class="font-semibold"
                            />
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

            <!-- Crew Members Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground flex items-center">
                        <Users class="w-5 h-5 mr-2" />
                        Crew Members
                    </h2>
                    <button
                        v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                        @click="showAddCrewDialog = true"
                        class="inline-flex items-center px-3 py-1.5 text-sm bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors"
                    >
                        <Plus class="w-4 h-4 mr-1" />
                        Add Crew Member
                    </button>
                </div>
                <div v-if="marea.crew_members.length === 0" class="text-center py-8 text-muted-foreground dark:text-muted-foreground">
                    No crew members assigned to this marea
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

            <!-- Quantity Returns Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground flex items-center">
                        <Package class="w-5 h-5 mr-2" />
                        Products Returned
                    </h2>
                    <button
                        v-if="canEdit('mareas') && (marea.status === 'returned' || marea.status === 'closed')"
                        @click="showAddQuantityReturnDialog = true"
                        class="inline-flex items-center px-3 py-1.5 text-sm bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors"
                    >
                        <Plus class="w-4 h-4 mr-1" />
                        Add Product
                    </button>
                </div>
                <div v-if="marea.quantity_returns.length === 0" class="text-center py-8 text-muted-foreground dark:text-muted-foreground">
                    No products returned for this marea
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border dark:border-border">
                                <th class="text-left py-2 px-4 text-sm font-medium text-card-foreground dark:text-card-foreground">Name</th>
                                <th class="text-right py-2 px-4 text-sm font-medium text-card-foreground dark:text-card-foreground">Quantity</th>
                                <th v-if="canEdit('mareas') && marea.status !== 'closed'" class="text-right py-2 px-4 text-sm font-medium text-card-foreground dark:text-card-foreground">Actions</th>
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
        </div>

        <!-- Confirmation Dialogs -->
        <ConfirmationDialog
            v-model:open="showMarkAtSeaDialog"
            title="Mark Marea as At Sea"
            description="This will mark the marea as currently at sea."
            message="Are you sure you want to mark this marea as at sea?"
            confirm-text="Mark At Sea"
            cancel-text="Cancel"
            variant="default"
            type="info"
            :loading="isProcessing"
            @confirm="handleMarkAtSea"
            @cancel="showMarkAtSeaDialog = false"
        >
            <template #default>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                        Departure Date
                    </label>
                    <input
                        v-model="markAtSeaForm.date"
                        type="date"
                        class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground"
                    />
                </div>
            </template>
        </ConfirmationDialog>

        <ConfirmationDialog
            v-model:open="showMarkReturnedDialog"
            title="Mark Marea as Returned"
            description="This will mark the marea as returned."
            message="Are you sure you want to mark this marea as returned?"
            confirm-text="Mark Returned"
            cancel-text="Cancel"
            variant="default"
            type="info"
            :loading="isProcessing"
            @confirm="handleMarkReturned"
            @cancel="showMarkReturnedDialog = false"
        >
            <template #default>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                        Return Date
                    </label>
                    <input
                        v-model="markReturnedForm.date"
                        type="date"
                        class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground"
                    />
                </div>
            </template>
        </ConfirmationDialog>

        <ConfirmationDialog
            v-model:open="showCloseDialog"
            title="Close Marea"
            description="This action will close the marea permanently."
            message="Are you sure you want to close this marea? This action cannot be undone."
            confirm-text="Close Marea"
            cancel-text="Cancel"
            variant="default"
            type="warning"
            :loading="isProcessing"
            @confirm="handleClose"
            @cancel="showCloseDialog = false"
        />

        <ConfirmationDialog
            v-model:open="showCancelDialog"
            title="Cancel Marea"
            description="This action will cancel the marea."
            message="Are you sure you want to cancel this marea? This action cannot be undone."
            confirm-text="Cancel Marea"
            cancel-text="No, Keep It"
            variant="destructive"
            type="danger"
            :loading="isProcessing"
            @confirm="handleCancel"
            @cancel="showCancelDialog = false"
        />

        <ConfirmationDialog
            v-model:open="showDeleteDialog"
            title="Delete Marea"
            description="This action cannot be undone."
            :message="`Are you sure you want to delete marea '${marea.marea_number}'? This will permanently remove the marea and all its data.`"
            confirm-text="Delete Marea"
            cancel-text="Cancel"
            variant="destructive"
            type="danger"
            :loading="isProcessing"
            @confirm="handleDelete"
            @cancel="showDeleteDialog = false"
        />

        <!-- Add Transaction Modal -->
        <Dialog :open="showAddTransactionDialog" @update:open="showAddTransactionDialog = $event">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Add Transaction to Marea</DialogTitle>
                    <DialogDescription>Select a transaction to link to this marea.</DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <div v-if="loadingTransactions" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                    </div>
                    <div v-else-if="availableTransactions.length === 0" class="text-center py-8 text-muted-foreground">
                        No available transactions to add.
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
                                        {{ transaction.description || 'No description' }}
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
                        Cancel
                    </Button>
                    <Button @click="handleAddTransaction" :disabled="isProcessing || !selectedTransactionId">
                        Add Transaction
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Add Crew Modal -->
        <Dialog :open="showAddCrewDialog" @update:open="showAddCrewDialog = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Add Crew Member to Marea</DialogTitle>
                    <DialogDescription>Select a crew member to assign to this marea.</DialogDescription>
                </DialogHeader>
                <div class="py-4 space-y-4">
                    <div v-if="loadingCrew" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                    </div>
                    <div v-else>
                        <div>
                            <Label for="crew_member">Crew Member</Label>
                            <Select
                                v-model="addCrewForm.user_id"
                                :options="availableCrewMembers.map(m => ({ value: m.id, label: `${m.name} (${m.email})` }))"
                                placeholder="Select a crew member"
                                searchable
                                :error="addCrewForm.errors.user_id"
                            />
                        </div>
                        <div>
                            <Label for="crew_notes">Notes (Optional)</Label>
                            <textarea
                                id="crew_notes"
                                v-model="addCrewForm.notes"
                                placeholder="Additional notes about this crew member"
                                rows="3"
                                class="flex min-h-[80px] w-full rounded-md border border-input dark:border-input bg-background dark:bg-background px-3 py-2 text-sm text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            />
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showAddCrewDialog = false" :disabled="isProcessing">
                        Cancel
                    </Button>
                    <Button @click="handleAddCrew" :disabled="isProcessing || !addCrewForm.user_id">
                        Add Crew Member
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Add Quantity Return Modal -->
        <Dialog :open="showAddQuantityReturnDialog" @update:open="showAddQuantityReturnDialog = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Add Product Return</DialogTitle>
                    <DialogDescription>Add a product that was returned from this marea.</DialogDescription>
                </DialogHeader>
                <div class="py-4 space-y-4">
                    <div>
                        <Label for="product_name">Product Name *</Label>
                        <Input
                            v-model="addQuantityReturnForm.name"
                            placeholder="e.g., Tuna, Sardines"
                            :error="addQuantityReturnForm.errors.name"
                        />
                    </div>
                    <div>
                        <Label for="quantity">Quantity *</Label>
                        <Input
                            v-model.number="addQuantityReturnForm.quantity"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            :error="addQuantityReturnForm.errors.quantity"
                        />
                    </div>
                    <div>
                        <Label for="product_notes">Notes (Optional)</Label>
                        <textarea
                            id="product_notes"
                            v-model="addQuantityReturnForm.notes"
                            placeholder="Additional notes about this product"
                            rows="3"
                            class="flex min-h-[80px] w-full rounded-md border border-input dark:border-input bg-background dark:bg-background px-3 py-2 text-sm text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showAddQuantityReturnDialog = false" :disabled="isProcessing">
                        Cancel
                    </Button>
                    <Button @click="handleAddQuantityReturn" :disabled="isProcessing">
                        Add Product
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
    </VesselLayout>
</template>


