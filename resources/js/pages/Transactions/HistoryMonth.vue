<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/ui/Pagination.vue';
import TransactionShowModal from '@/components/modals/Transaction/show.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import { usePermissions } from '@/composables/usePermissions';
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
    price_per_unit?: number | null;
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
    created_at_datetime?: string; // Base datetime format (Y-m-d H:i:s)
    status: string;
    status_label: string;
    category: {
        id: number;
        name: string;
        type: string;
        color: string;
    } | null;
    files?: {
        id: number;
        src: string;
        name: string;
        size: number;
        type: string;
        size_human: string;
    }[];
}

interface Props {
    transactions: {
        data: Transaction[];
        links: any[];
        current_page?: number;
        last_page?: number;
        per_page?: number;
        total?: number;
        from?: number;
        to?: number;
        meta?: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number;
            to: number;
        };
    };
    month: number;
    year: number;
    monthLabel: string;
    defaultCurrency?: string;
}

const props = defineProps<Props>();
const { canView, hasPermission } = usePermissions();

// Check if user has permission to access transaction history
onMounted(() => {
    if (!hasPermission('reports.access')) {
        router.visit(`/panel/${getCurrentVesselId()}/dashboard`, {
            replace: true,
        });
    }
});

// Computed property for transactions pagination (same pattern as Suppliers/CrewMembers)
const paginatedTransactions = computed(() => props.transactions);

// Modal state
const showShowModal = ref(false);
const selectedTransaction = ref<Transaction | null>(null);

// Get currency data from shared props
const page = usePage();
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

    // Sort transactions within each date group
    sortedDates.forEach(date => {
        groups[date].sort((a, b) => {
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

// Get type background color
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

// Open transaction details modal
const openShowModal = (transaction: Transaction) => {
    selectedTransaction.value = transaction;
    showShowModal.value = true;
};

const closeModals = () => {
    showShowModal.value = false;
    selectedTransaction.value = null;
};

</script>

<template>
    <Head :title="`Transactions - ${monthLabel} ${year}`" />

    <VesselLayout v-if="hasPermission('reports.access')" :breadcrumbs="[
        { title: 'Transactions', href: transactions.index.url({ vessel: getCurrentVesselId() }) },
        { title: 'History', href: `/panel/${getCurrentVesselId()}/transactions/history` },
        { title: `${monthLabel} ${year}`, href: `/panel/${getCurrentVesselId()}/transactions/history/${year}/${month}` }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">
                            Transactions - {{ monthLabel }} {{ year }}
                        </h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">
                            View all transactions for {{ monthLabel }} {{ year }}
                        </p>
                    </div>
                    <Link
                        :href="`/panel/${getCurrentVesselId()}/transactions/history`"
                        class="inline-flex items-center px-4 py-2 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground font-medium transition-colors"
                    >
                        <Icon name="arrow-left" class="w-4 h-4 mr-2" />
                        Back to History
                    </Link>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card overflow-hidden">
                <div v-if="!props.transactions || !props.transactions.data || !Array.isArray(props.transactions.data) || props.transactions.data.length === 0" class="px-6 py-12 text-center text-muted-foreground dark:text-muted-foreground">
                    No transactions found for {{ monthLabel }} {{ year }}
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
                                'px-6 py-4 transition-all relative cursor-pointer hover:opacity-80',
                                getTypeBgColor(transaction.type)
                            ]"
                            @click="openShowModal(transaction)"
                        >
                            <div class="flex items-center justify-between gap-4">
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
                                            <span
                                                v-if="(transaction.amount_per_unit ?? transaction.price_per_unit) != null && transaction.quantity != null && ((transaction.amount_per_unit ?? transaction.price_per_unit) ?? 0) > 0 && transaction.quantity > 0"
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
                                            <p v-if="transaction.created_at_datetime" class="text-xs text-muted-foreground dark:text-muted-foreground mt-0.5">
                                                {{ transaction.created_at_datetime }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Amount -->
                                <div class="text-right ml-4">
                                    <template v-if="(transaction.amount_per_unit ?? transaction.price_per_unit) != null && transaction.quantity != null && ((transaction.amount_per_unit ?? transaction.price_per_unit) ?? 0) > 0 && transaction.quantity > 0">
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
                                    <template v-else>
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
                                        <div v-if="transaction.vat_amount > 0" class="text-xs text-muted-foreground dark:text-muted-foreground mt-0.5">
                                            incl. VAT
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <Pagination
                v-if="paginatedTransactions?.links && (paginatedTransactions.last_page ?? paginatedTransactions.meta?.last_page ?? 1) > 1"
                :links="paginatedTransactions.links"
                :meta="paginatedTransactions"
            />
        </div>

        <!-- Transaction Show Modal -->
        <TransactionShowModal
            v-if="selectedTransaction"
            :open="showShowModal"
            :transaction="selectedTransaction"
            @close="closeModals"
        />
    </VesselLayout>
    <VesselLayout v-else :breadcrumbs="[]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-12 text-center">
                <p class="text-muted-foreground dark:text-muted-foreground">You do not have permission to view transaction history.</p>
            </div>
        </div>
    </VesselLayout>
</template>

