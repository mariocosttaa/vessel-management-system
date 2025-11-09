<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import Icon from '@/components/Icon.vue';
import DataTable from '@/components/ui/DataTable.vue';
import Pagination from '@/components/ui/Pagination.vue';
import CreateAddModal from '@/components/modals/Transaction/create-add.vue';
import CreateRemoveModal from '@/components/modals/Transaction/create-remove.vue';
import UpdateAddModal from '@/components/modals/Transaction/update-add.vue';
import UpdateRemoveModal from '@/components/modals/Transaction/update-remove.vue';
import TransactionShowModal from '@/components/modals/Transaction/show.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useNotifications } from '@/composables/useNotifications';
import { Plus, Minus, ArrowUpCircle, ArrowDownCircle, ArrowLeftRight } from 'lucide-vue-next';
import transactions from '@/routes/panel/transactions';

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

interface Transaction {
    id: number;
    transaction_number: string;
    type: string;
    type_label: string;
    amount: number;
    formatted_amount: string;
    vat_amount: number;
    formatted_vat_amount: string;
    total_amount: number;
    formatted_total_amount: string;
    currency: string;
    house_of_zeros: number;
    transaction_date: string;
    formatted_transaction_date: string;
    description: string | null;
    reference: string | null;
    status: string;
    status_label: string;
    category_id: number;
    supplier_id: number | null;
    crew_member_id: number | null;
    vat_profile_id: number | null;
    amount_includes_vat?: boolean;
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
    crew_member: {
        id: number;
        name: string;
        email: string;
    } | null;
    files?: {
        id: number;
        src: string;
        name: string;
        size: number;
        type: string;
        size_human: string;
    }[];
    created_at?: string; // ISO 8601 format for sorting
    created_at_formatted?: string;
    updated_at?: string; // ISO 8601 format for sorting
    updated_at_formatted?: string;
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

interface VatProfile {
    id: number;
    name: string;
    percentage: number;
    country_id?: number | null;
}

interface Props {
    transactions: {
        data: Transaction[];
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        type?: string;
        status?: string;
        category_id?: number;
        date_from?: string;
        date_to?: string;
        sort?: string;
        direction?: string;
    };
    categories: TransactionCategory[];
    suppliers: Supplier[];
    crewMembers: CrewMember[];
    vatProfiles?: VatProfile[];
    defaultVatProfile?: VatProfile | null;
    defaultCurrency?: string; // Default currency from vessel_settings (passed from controller)
    transactionTypes: Record<string, string>;
    statuses: Record<string, string>;
}

const props = defineProps<Props>();
const { canCreate, canEdit, canDelete } = usePermissions();
const { addNotification } = useNotifications();

// Modal states
const showCreateAddModal = ref(false);
const showCreateRemoveModal = ref(false);
const showUpdateAddModal = ref(false);
const showUpdateRemoveModal = ref(false);
const showShowModal = ref(false);
const selectedTransaction = ref<Transaction | null>(null);

// Confirmation dialog state
const showDeleteDialog = ref(false);
const transactionToDelete = ref<Transaction | null>(null);
const isDeleting = ref(false);

// Dropdown state
const openDropdownId = ref<number | null>(null);

// Dropdown methods
const toggleActionsDropdown = (transactionId: number) => {
    openDropdownId.value = openDropdownId.value === transactionId ? null : transactionId;
};

const handleActionClick = (action: any, transaction: Transaction) => {
    action.onClick(transaction);
    openDropdownId.value = null;
};

// Click outside handler
const handleClickOutside = (event: Event) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.dropdown-container')) {
        openDropdownId.value = null;
    }
};

// Sorting - default to created_at descending (newest first)
const sortField = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');

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

// Group transactions by date
const groupedTransactions = computed(() => {
    if (!props.transactions || !props.transactions.data || !Array.isArray(props.transactions.data)) {
        return [];
    }

    if (props.transactions.data.length === 0) {
        return [];
    }

    const groups: Record<string, Transaction[]> = {};

    props.transactions.data.forEach(transaction => {
        const dateKey = transaction.transaction_date;
        if (!dateKey) {
            return;
        }
        if (!groups[dateKey]) {
            groups[dateKey] = [];
        }
        groups[dateKey].push(transaction);
    });

    // Sort dates descending (newest first)
    const sortedDates = Object.keys(groups).sort((a, b) => {
        return new Date(b).getTime() - new Date(a).getTime();
    });

    // Sort transactions within each date group by created_at (newest first)
    // Since backend already sorts by created_at, this maintains that order within groups
    sortedDates.forEach(date => {
        groups[date].sort((a, b) => {
            // Sort by created_at if available, otherwise by transaction_number
            if (a.created_at && b.created_at) {
                return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
            }
            // Fallback to transaction_number if created_at is not available
            return b.transaction_number.localeCompare(a.transaction_number);
        });
    });

    return sortedDates.map(date => ({
        date,
        formattedDate: formatDate(date),
        transactions: groups[date],
    }));
});

// Format date for display
const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    if (date.toDateString() === today.toDateString()) {
        return 'Today';
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    } else {
        return date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
};

// Get type icon
const getTypeIcon = (type: string) => {
    switch (type) {
        case 'income':
            return ArrowUpCircle;
        case 'expense':
            return ArrowDownCircle;
        case 'transfer':
            return ArrowLeftRight;
        default:
            return ArrowLeftRight;
    }
};

// Get type color
const getTypeColor = (type: string) => {
    switch (type) {
        case 'income':
            return 'text-green-600 dark:text-green-400';
        case 'expense':
            return 'text-red-600 dark:text-red-400';
        case 'transfer':
            return 'text-blue-600 dark:text-blue-400';
        default:
            return 'text-muted-foreground';
    }
};

// Get type background color (subtle border on left side)
const getTypeBgColor = (type: string) => {
    switch (type) {
        case 'income':
            return 'border-l-4 border-green-500 dark:border-green-400 bg-green-50/30 dark:bg-green-900/10';
        case 'expense':
            return 'border-l-4 border-red-500 dark:border-red-400 bg-red-50/30 dark:bg-red-900/10';
        case 'transfer':
            return 'border-l-4 border-blue-500 dark:border-blue-400 bg-blue-50/30 dark:bg-blue-900/10';
        default:
            return 'border-l-4 border-border bg-muted/30';
    }
};

// Get status badge color
const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300';
        case 'pending':
            return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300';
        case 'cancelled':
            return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const actions = computed(() => {
    const actionItems = [];

    if (canEdit('transactions')) {
        actionItems.push({
            label: 'View Details',
            icon: 'eye',
            onClick: (item: Transaction) => openShowModal(item),
        });
        actionItems.push({
            label: 'Edit Transaction',
            icon: 'edit',
            onClick: (item: Transaction) => openUpdateModal(item),
        });
    }

    if (canDelete('transactions')) {
        actionItems.push({
            label: 'Delete Transaction',
            icon: 'trash-2',
            onClick: (item: Transaction) => deleteTransaction(item),
            variant: 'destructive' as const,
        });
    }

    return actionItems;
});

// Modal handlers
const openCreateAddModal = () => {
    showCreateAddModal.value = true;
};

const openCreateRemoveModal = () => {
    showCreateRemoveModal.value = true;
};

const openUpdateModal = (transaction: Transaction) => {
    selectedTransaction.value = transaction;
    // Determine which update modal to show based on transaction type
    if (transaction.type === 'income') {
        showUpdateAddModal.value = true;
    } else if (transaction.type === 'expense') {
        showUpdateRemoveModal.value = true;
    } else {
        // For transfer transactions, we can use update-add as default
        // Or create a separate update-transfer modal if needed
        showUpdateAddModal.value = true;
    }
};

const openShowModal = (transaction: Transaction) => {
    selectedTransaction.value = transaction;
    showShowModal.value = true;
};

const closeModals = async () => {
    showCreateAddModal.value = false;
    showCreateRemoveModal.value = false;
    showUpdateAddModal.value = false;
    showUpdateRemoveModal.value = false;
    showShowModal.value = false;
    await nextTick();
    selectedTransaction.value = null;
};

const handleCreateSuccess = () => {
    // Close modals immediately and ensure they stay closed
    showCreateAddModal.value = false;
    showCreateRemoveModal.value = false;
    selectedTransaction.value = null;

    // Reload transactions data after modal has closed
    // Use a small delay to ensure modal close animation completes
    setTimeout(() => {
        router.reload({
            only: ['transactions'],
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                // Ensure modals remain closed after reload
                showCreateAddModal.value = false;
                showCreateRemoveModal.value = false;
                selectedTransaction.value = null;
            }
        });
    }, 100);
};


// Filter categories by type
const incomeCategories = computed(() => {
    return props.categories.filter(cat => cat.type === 'income');
});

const expenseCategories = computed(() => {
    return props.categories.filter(cat => cat.type === 'expense');
});

// CRUD operations
const deleteTransaction = (transaction: Transaction) => {
    transactionToDelete.value = transaction;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!transactionToDelete.value) return;

    const transactionNumber = transactionToDelete.value.transaction_number;
    isDeleting.value = true;

    router.delete(transactions.destroy.url({ vessel: getCurrentVesselId(), transaction: transactionToDelete.value.id }), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            transactionToDelete.value = null;
            isDeleting.value = false;
            addNotification({
                type: 'success',
                title: 'Success',
                message: `Transaction '${transactionNumber}' has been deleted successfully.`,
            });
        },
        onError: () => {
            isDeleting.value = false;
            addNotification({
                type: 'error',
                title: 'Error',
                message: 'Failed to delete transaction. Please try again.',
            });
        },
    });
};

const cancelDelete = () => {
    showDeleteDialog.value = false;
    transactionToDelete.value = null;
    isDeleting.value = false;
};

// Sorting
const handleSort = (field: string) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }

    router.get(transactions.index.url({ vessel: getCurrentVesselId() }), {
        ...filters.value,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Search and filters
const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || '');
const statusFilter = ref(props.filters.status || '');
const categoryFilter = ref(props.filters.category_id || '');
const dateFromFilter = ref(props.filters.date_from || '');
const dateToFilter = ref(props.filters.date_to || '');

const filters = computed(() => {
    const filterObj: Record<string, any> = {
        search: search.value || undefined,
        type: typeFilter.value || undefined,
        status: statusFilter.value || undefined,
        category_id: categoryFilter.value ? Number(categoryFilter.value) : undefined,
        date_from: dateFromFilter.value || undefined,
        date_to: dateToFilter.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    };

    // Remove undefined values
    Object.keys(filterObj).forEach(key => {
        if (filterObj[key] === undefined) {
            delete filterObj[key];
        }
    });

    return filterObj;
});

const applyFilters = () => {
    router.get(transactions.index.url({ vessel: getCurrentVesselId() }), filters.value, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    typeFilter.value = '';
    statusFilter.value = '';
    categoryFilter.value = '';
    dateFromFilter.value = '';
    dateToFilter.value = '';
    sortField.value = 'created_at';
    sortDirection.value = 'desc';

    router.get(transactions.index.url({ vessel: getCurrentVesselId() }), {}, {
        preserveState: true,
        replace: true,
    });
};

// Handle click outside for dropdown
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <Head title="Transactions" />

    <VesselLayout :breadcrumbs="[{ title: 'Transactions', href: transactions.index.url({ vessel: getCurrentVesselId() }) }]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">Transactions</h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">Manage financial transactions for your vessel</p>
                    </div>
                    <div v-if="canCreate('transactions')" class="flex gap-3">
                        <button
                            @click="openCreateAddModal"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <Icon name="plus" class="w-4 h-4 mr-2" />
                            Add
                        </button>
                        <button
                            @click="openCreateRemoveModal"
                            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <Icon name="minus" class="w-4 h-4 mr-2" />
                            Remove
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-7 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                            Search
                        </label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search transactions..."
                            class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                        />
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                            Type
                        </label>
                        <select
                            v-model="typeFilter"
                            class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                        >
                            <option value="">All Types</option>
                            <option v-for="(label, value) in transactionTypes" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                            Status
                        </label>
                        <select
                            v-model="statusFilter"
                            class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                        >
                            <option value="">All Statuses</option>
                            <option v-for="(label, value) in statuses" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                            Category
                        </label>
                        <select
                            v-model="categoryFilter"
                            class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                        >
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                            Date From
                        </label>
                        <input
                            v-model="dateFromFilter"
                            type="date"
                            class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                        />
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                            Date To
                        </label>
                        <input
                            v-model="dateToFilter"
                            type="date"
                            class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                        />
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="flex gap-3 mt-4">
                    <button
                        @click="applyFilters"
                        class="px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
                    >
                        Apply Filters
                    </button>
                    <button
                        @click="clearFilters"
                        class="px-4 py-2 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground font-medium transition-colors"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Transactions Table with Date Grouping -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card overflow-hidden">
                <div v-if="!props.transactions || !props.transactions.data || !Array.isArray(props.transactions.data) || props.transactions.data.length === 0" class="px-6 py-12 text-center text-muted-foreground dark:text-muted-foreground">
                    No transactions found
                </div>

                <div v-else-if="groupedTransactions && groupedTransactions.length > 0" class="divide-y divide-border dark:divide-border">
                    <!-- Date Group -->
                    <div v-for="group in groupedTransactions" :key="group.date" class="divide-y divide-border dark:divide-border">
                        <!-- Date Header -->
                        <div class="bg-muted/30 dark:bg-muted/20 px-6 py-3 border-b border-border dark:border-border">
                            <h3 class="text-sm font-semibold text-card-foreground dark:text-card-foreground">
                                {{ group.formattedDate }}
                            </h3>
                        </div>

                        <!-- Transactions for this date -->
                        <div
                            v-for="transaction in group.transactions"
                            :key="transaction.id"
                            :class="[
                                'px-6 py-4 transition-all relative',
                                getTypeBgColor(transaction.type)
                            ]"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <!-- Clickable row area (excluding dropdown) -->
                                <div
                                    @click="openShowModal(transaction)"
                                    class="flex items-center justify-between flex-1 min-w-0 cursor-pointer hover:opacity-80"
                                >
                                    <div class="flex items-center space-x-4 flex-1 min-w-0">
                                        <!-- Type Icon -->
                                        <div :class="getTypeColor(transaction.type)">
                                            <component :is="getTypeIcon(transaction.type)" class="w-5 h-5" />
                                        </div>

                                        <!-- Transaction Details -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-3">
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
                                                <span
                                                    :class="[
                                                        'inline-flex items-center px-2 py-1 rounded-md text-xs font-medium',
                                                        getStatusColor(transaction.status)
                                                    ]"
                                                >
                                                    {{ transaction.status_label }}
                                                </span>
                                            </div>
                                            <div class="mt-1">
                                                <p class="text-sm text-card-foreground dark:text-card-foreground truncate">
                                                    {{ transaction.description || 'No description' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Amount (with right padding for dropdown) -->
                                    <div class="flex items-center ml-4 mr-10">
                                        <!-- Amount with VAT -->
                                        <div class="text-right">
                                            <div class="flex flex-col items-end gap-0.5">
                                                <!-- Base Amount + VAT (on same line) -->
                                                <div class="flex items-baseline gap-2">
                                                    <!-- Base Amount -->
                                                    <MoneyDisplay
                                                        :value="transaction.amount"
                                                        :currency="transaction.currency"
                                                        :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                                        :variant="transaction.type === 'income' ? 'positive' : transaction.type === 'expense' ? 'negative' : 'neutral'"
                                                        size="sm"
                                                        class="font-semibold"
                                                    />
                                                    <!-- VAT Amount (only show if VAT > 0, smaller text) -->
                                                    <span v-if="transaction.vat_amount > 0" class="text-xs text-muted-foreground dark:text-muted-foreground flex items-baseline gap-1">
                                                        <span class="opacity-60">+</span>
                                                        <MoneyDisplay
                                                            :value="transaction.vat_amount"
                                                            :currency="transaction.currency"
                                                            :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                                            variant="neutral"
                                                            size="sm"
                                                            class="text-xs font-normal opacity-80"
                                                        />
                                                    </span>
                                                </div>
                                                <!-- Total Amount (below, smaller, only show if VAT exists) -->
                                                <div v-if="transaction.vat_amount > 0" class="text-xs text-muted-foreground dark:text-muted-foreground opacity-75">
                                                    Total:
                                                    <MoneyDisplay
                                                        :value="transaction.total_amount"
                                                        :currency="transaction.currency"
                                                        :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                                        variant="neutral"
                                                        size="sm"
                                                        class="text-xs font-medium"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions Dropdown (outside clickable area, positioned to the right) -->
                                <div
                                    v-if="actions.length > 0"
                                    @click.stop
                                    @mouseenter.stop
                                    @mouseleave.stop
                                    @mousedown.stop
                                    @mouseup.stop
                                    class="relative dropdown-container flex-shrink-0"
                                >
                                    <button
                                        @click.stop="toggleActionsDropdown(transaction.id)"
                                        @mouseenter.stop
                                        @mouseleave.stop
                                        class="flex items-center justify-center w-8 h-8 rounded-full hover:bg-background dark:hover:bg-background transition-colors"
                                    >
                                        <Icon name="menu" class="w-4 h-4 text-muted-foreground dark:text-muted-foreground" />
                                    </button>

                                    <!-- Actions Dropdown Menu -->
                                    <div
                                        v-if="openDropdownId === transaction.id"
                                        @click.stop
                                        @mouseenter.stop
                                        @mouseleave.stop
                                        @mousedown.stop
                                        @mouseup.stop
                                        class="absolute right-0 mt-2 w-48 bg-card dark:bg-card border border-border dark:border-border rounded-lg shadow-lg z-50"
                                    >
                                        <div class="py-1">
                                            <button
                                                v-for="action in actions"
                                                :key="action.label"
                                                @click.stop="handleActionClick(action, transaction)"
                                                @mouseenter.stop
                                                @mouseleave.stop
                                                :class="[
                                                    'flex items-center w-full px-4 py-2 text-sm transition-colors',
                                                    action.variant === 'destructive'
                                                        ? 'text-destructive dark:text-destructive hover:bg-muted dark:hover:bg-muted'
                                                        : 'text-card-foreground dark:text-card-foreground hover:bg-muted dark:hover:bg-muted'
                                                ]"
                                            >
                                                <Icon :name="action.icon" class="w-4 h-4 mr-3" />
                                                {{ action.label }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="transactions.meta && transactions.meta.last_page > 1" class="flex justify-center">
                <Pagination :links="transactions.links" />
            </div>
        </div>

        <!-- Modals -->
        <CreateAddModal
            :open="showCreateAddModal"
            :categories="incomeCategories"
            :vat-profiles="vatProfiles"
            :default-vat-profile="defaultVatProfile"
            :default-currency="props.defaultCurrency"
            @close="closeModals"
            @success="handleCreateSuccess"
        />

        <CreateRemoveModal
            :open="showCreateRemoveModal"
            :categories="expenseCategories"
            :suppliers="suppliers"
            :crew-members="crewMembers"
            :vat-profiles="vatProfiles"
            :default-vat-profile="defaultVatProfile"
            :default-currency="props.defaultCurrency"
            @close="closeModals"
            @success="handleCreateSuccess"
        />

        <UpdateAddModal
            v-if="selectedTransaction && selectedTransaction.type === 'income'"
            :open="showUpdateAddModal"
            :transaction="selectedTransaction"
            :categories="incomeCategories"
            :vat-profiles="vatProfiles"
            :default-vat-profile="defaultVatProfile"
            :default-currency="props.defaultCurrency"
            @close="closeModals"
            @success="() => { closeModals(); router.reload(); }"
        />

        <UpdateRemoveModal
            v-if="selectedTransaction && selectedTransaction.type === 'expense'"
            :open="showUpdateRemoveModal"
            :transaction="selectedTransaction"
            :categories="expenseCategories"
            :suppliers="suppliers"
            :crew-members="crewMembers"
            :default-currency="props.defaultCurrency"
            @close="closeModals"
            @success="() => { closeModals(); router.reload(); }"
        />

        <TransactionShowModal
            v-if="selectedTransaction"
            :open="showShowModal"
            :transaction="selectedTransaction"
            @close="closeModals"
        />

        <!-- Confirmation Dialog -->
        <ConfirmationDialog
            v-model:open="showDeleteDialog"
            title="Delete Transaction"
            description="This action cannot be undone."
            :message="`Are you sure you want to delete transaction '${transactionToDelete?.transaction_number}'? This will permanently remove the transaction and all its data.`"
            confirm-text="Delete Transaction"
            cancel-text="Cancel"
            variant="destructive"
            type="danger"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </VesselLayout>
</template>

