<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { ref, computed, watch, nextTick } from 'vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/ui/Pagination.vue';
import { DateInput } from '@/components/ui/date-input';
import { Select } from '@/components/ui/select';
import CreateAddModal from '@/components/modals/Transaction/create-add.vue';
import CreateRemoveModal from '@/components/modals/Transaction/create-remove.vue';
import UpdateAddModal from '@/components/modals/Transaction/update-add.vue';
import UpdateRemoveModal from '@/components/modals/Transaction/update-remove.vue';
import TransactionShowModal from '@/components/modals/Transaction/show.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useNotifications } from '@/composables/useNotifications';
import { ArrowUpCircle, ArrowDownCircle, ArrowLeftRight } from 'lucide-vue-next';
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
    amount_per_unit: number | null;
    price_per_unit?: number | null; // Backward compatibility
    quantity: number | null;
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


// Modal handlers
const openCreateAddModal = () => {
    showCreateAddModal.value = true;
};

const openCreateRemoveModal = () => {
    showCreateRemoveModal.value = true;
};

const openUpdateModal = (transaction: Transaction) => {
    if (!transaction) {
        console.error('No transaction provided to openUpdateModal');
        return;
    }

    // Set the transaction first
    selectedTransaction.value = transaction;

    // Use nextTick to ensure Vue has updated the DOM and component is created
    nextTick(() => {
        // Determine which update modal to show based on transaction type
        if (transaction.type === 'income') {
            showUpdateAddModal.value = true;
        } else if (transaction.type === 'expense') {
            showUpdateRemoveModal.value = true;
        } else {
            // For transfer transactions, we can use update-add as default
            showUpdateAddModal.value = true;
        }
    });
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

const handleUpdateSuccess = () => {
    // Close modals immediately and ensure they stay closed
    showUpdateAddModal.value = false;
    showUpdateRemoveModal.value = false;
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
                showUpdateAddModal.value = false;
                showUpdateRemoveModal.value = false;
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

    router.delete(transactions.destroy.url({ vessel: getCurrentVesselId(), transactionId: transactionToDelete.value.id }), {
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

// Convert filters to Select component options format
const typeOptions = computed(() => {
    const options = [{ value: '', label: 'All Types' }];
    Object.entries(props.transactionTypes).forEach(([value, label]) => {
        options.push({ value, label: label as string });
    });
    return options;
});

const statusOptions = computed(() => {
    const options = [{ value: '', label: 'All Statuses' }];
    Object.entries(props.statuses).forEach(([value, label]) => {
        options.push({ value, label: label as string });
    });
    return options;
});

const categoryOptions = computed(() => {
    const options = [{ value: '', label: 'All Categories' }];
    props.categories.forEach(category => {
        options.push({ value: category.id, label: category.name });
    });
    return options;
});
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
                    <div class="flex gap-3">
                        <Link
                            :href="`/panel/${getCurrentVesselId()}/transactions/history`"
                            class="inline-flex items-center px-4 py-2 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground font-medium transition-colors"
                        >
                            <Icon name="calendar" class="w-4 h-4 mr-2" />
                            History
                        </Link>
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
            </div>

            <!-- Filters Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <Icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search transactions..."
                                @keyup.enter="applyFilters"
                                class="w-full pl-10 pr-4 py-2 text-sm border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-colors"
                            />
                        </div>
                    </div>

                    <!-- Type Filter -->
                    <div class="min-w-[130px]">
                        <Select
                            v-model="typeFilter"
                            :options="typeOptions"
                            placeholder="All Types"
                            searchable
                        />
                    </div>

                    <!-- Status Filter -->
                    <div class="min-w-[130px]">
                        <Select
                            v-model="statusFilter"
                            :options="statusOptions"
                            placeholder="All Statuses"
                            searchable
                        />
                    </div>

                    <!-- Category Filter -->
                    <div class="min-w-[140px]">
                        <Select
                            v-model="categoryFilter"
                            :options="categoryOptions"
                            placeholder="All Categories"
                            searchable
                        />
                    </div>

                    <!-- Date From -->
                    <div class="min-w-[140px]">
                        <DateInput v-model="dateFromFilter" />
                    </div>

                    <!-- Date To -->
                    <div class="min-w-[140px]">
                        <DateInput v-model="dateToFilter" />
                    </div>

                    <!-- Apply Filters Button -->
                    <button
                        @click="applyFilters"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg transition-colors"
                    >
                        <Icon name="check" class="h-4 w-4" />
                        Apply
                    </button>

                    <!-- Clear Filters Button -->
                    <button
                        @click="clearFilters"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border border-input dark:border-input rounded-lg bg-background dark:bg-background hover:bg-muted/50 text-muted-foreground hover:text-foreground transition-colors"
                    >
                        <Icon name="x" class="h-4 w-4" />
                        Clear
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
                                            <div class="flex items-center space-x-3 flex-wrap gap-2">
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
                                            <div class="mt-1">
                                                <p class="text-sm text-card-foreground dark:text-card-foreground truncate">
                                                    {{ transaction.description || 'No description' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Amount and Action Buttons -->
                                    <div class="flex items-center gap-2 ml-4">
                                        <div class="text-right">
                                            <!-- When amount_per_unit and quantity exist -->
                                            <template v-if="(transaction.amount_per_unit ?? transaction.price_per_unit) != null && transaction.quantity != null && (transaction.amount_per_unit ?? transaction.price_per_unit)! > 0 && transaction.quantity > 0">
                                                <!-- Show VAT only if it exists, otherwise just show total -->
                                                <template v-if="transaction.vat_amount > 0">
                                                    <div class="text-xs text-muted-foreground dark:text-muted-foreground mb-0.5">
                                                        VAT:
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
                                                        :variant="transaction.type === 'income' ? 'positive' : transaction.type === 'expense' ? 'negative' : 'neutral'"
                                                        size="sm"
                                                        class="font-semibold"
                                                    />
                                                </div>
                                            </template>
                                            <!-- When no amount_per_unit/quantity -->
                                            <template v-else>
                                                <!-- Total Amount with optional VAT indicator -->
                                                <div class="text-sm font-semibold">
                                                    <MoneyDisplay
                                                        :value="transaction.vat_amount > 0 ? transaction.total_amount : transaction.amount"
                                                        :currency="transaction.currency"
                                                        :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                                        :variant="transaction.type === 'income' ? 'positive' : transaction.type === 'expense' ? 'negative' : 'neutral'"
                                                        size="sm"
                                                        class="font-semibold"
                                                    />
                                                </div>
                                                <!-- VAT indicator (only if VAT exists) -->
                                                <div v-if="transaction.vat_amount > 0" class="text-xs text-muted-foreground dark:text-muted-foreground mt-0.5">
                                                    incl. VAT
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Action Buttons (View, Edit, and Delete) -->
                                        <div
                                            @click.stop
                                            class="flex items-center gap-1 flex-shrink-0"
                                        >
                                            <!-- View Button -->
                                            <button
                                                @click.stop="openShowModal(transaction)"
                                                class="flex items-center justify-center w-7 h-7 rounded-full hover:bg-primary/10 dark:hover:bg-primary/20 transition-colors text-muted-foreground hover:text-primary dark:text-muted-foreground dark:hover:text-primary"
                                                title="View transaction details"
                                            >
                                                <Icon name="eye" class="w-4 h-4" />
                                            </button>

                                            <!-- Edit Button -->
                                            <button
                                                v-if="canEdit('transactions')"
                                                @click.stop="openUpdateModal(transaction)"
                                                class="flex items-center justify-center w-7 h-7 rounded-full hover:bg-primary/10 dark:hover:bg-primary/20 transition-colors text-muted-foreground hover:text-primary dark:text-muted-foreground dark:hover:text-primary"
                                                title="Edit transaction"
                                            >
                                                <Icon name="edit" class="w-4 h-4" />
                                            </button>

                                            <!-- Delete Button -->
                                            <button
                                                v-if="canDelete('transactions')"
                                                @click.stop="deleteTransaction(transaction)"
                                                class="flex items-center justify-center w-7 h-7 rounded-full hover:bg-destructive/10 dark:hover:bg-destructive/20 transition-colors text-muted-foreground hover:text-destructive dark:text-muted-foreground dark:hover:text-destructive"
                                                title="Delete transaction"
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
            </div>

            <!-- Pagination -->
            <Pagination
                v-if="transactions?.links && transactions.links.length > 3"
                :links="transactions.links"
                :meta="transactions"
            />
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
            @success="handleUpdateSuccess"
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
            @success="handleUpdateSuccess"
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

