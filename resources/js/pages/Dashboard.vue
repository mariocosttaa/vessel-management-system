<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import Icon from '@/components/Icon.vue';
import { usePermissions } from '@/composables/usePermissions';
import {
    TrendingUp,
    TrendingDown,
    DollarSign,
    Ship,
    Anchor,
    Users,
    Receipt,
    BarChart3,
    ArrowRight,
    Calendar,
    Activity,
    Loader2,
    Clock
} from 'lucide-vue-next';

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

interface Props {
    vessel: {
        id: number;
        name: string;
        registration_number: string;
        status: string;
    };
    currentMonth: {
        month: number;
        year: number;
        month_label: string;
        total_income: number;
        total_expenses: number;
        net_balance: number;
        transaction_count: number;
    };
    last6Months: Array<{
        month: number;
        year: number;
        month_label: string;
        income: number;
        expenses: number;
        net: number;
    }>;
    vesselAtSea: boolean;
    activeMarea: {
        id: number;
        marea_number: string;
        name: string;
        status: string;
        actual_departure_date: string;
        estimated_return_date: string;
    } | null;
    preparingMareas: Array<{
        id: number;
        marea_number: string;
        name: string;
        status: string;
        estimated_departure_date: string;
        estimated_return_date: string;
    }>;
    recentTransactions: Array<{
        id: number;
        transaction_number: string;
        type: string;
        type_label: string;
        amount: number;
        currency: string;
        transaction_date: string;
        formatted_transaction_date: string;
        description: string | null;
        category: {
            id: number;
            name: string;
            color: string;
        } | null;
    }>;
    vesselStats: {
        total_crew: number;
        total_transactions: number;
        total_mareas: number;
        active_mareas: number;
    };
    defaultCurrency?: string;
    permissions: Record<string, boolean>;
}

const props = defineProps<Props>();

const { hasPermission, canView } = usePermissions();

// Get currency data from shared props
const page = usePage();
const currencies = computed(() => {
    return (page.props as any)?.currencies || [];
});

// Get currency details
const getCurrencyData = (currencyCode: string) => {
    const currency = currencies.value.find((c: any) => c.code === currencyCode);
    return currency || { code: currencyCode, symbol: currencyCode, decimal_separator: 2 };
};

const defaultCurrency = computed(() => props.defaultCurrency || 'EUR');
const currencyData = computed(() => getCurrencyData(defaultCurrency.value));

// Calculate max value for chart scaling
const maxChartValue = computed(() => {
    const allValues = props.last6Months.flatMap(m => [m.income, m.expenses]);
    return Math.max(...allValues, 1);
});

// Get chart bar width percentage
const getBarWidth = (value: number) => {
    if (maxChartValue.value === 0) return 0;
    return Math.max((Math.abs(value) / maxChartValue.value) * 100, 2);
};

// Navigate to transaction
const viewTransaction = (transactionId: number) => {
    const vesselId = getCurrentVesselId();
    router.visit(`/panel/${vesselId}/transactions/${transactionId}`);
};

// Navigate to marea
const viewMarea = (mareaId: number) => {
    const vesselId = getCurrentVesselId();
    router.visit(`/panel/${vesselId}/mareas/${mareaId}`);
};

// Calculate preparation progress (mock calculation - you can enhance this)
const getPreparationProgress = (marea: any) => {
    // Simple progress calculation based on days until departure
    if (!marea.estimated_departure_date) return 50;

    const departureDate = new Date(marea.estimated_departure_date);
    const today = new Date();
    const daysUntilDeparture = Math.ceil((departureDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));

    // If departure is today or past, show 90% (almost ready)
    if (daysUntilDeparture <= 0) return 90;

    // If more than 30 days away, show 10%
    if (daysUntilDeparture > 30) return 10;

    // Linear progression from 10% to 90% over 30 days
    return Math.min(90, Math.max(10, 90 - (daysUntilDeparture * 2.67)));
};
</script>

<template>
    <Head title="Dashboard" />

    <VesselLayout :breadcrumbs="[{ title: 'Dashboard', href: `/panel/${getCurrentVesselId()}/dashboard` }]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
            <!-- Compact Header with Status -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">
                        {{ vessel.name }}
                    </h1>
                    <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                        {{ vessel.registration_number }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Active Marea Badge (if at sea) -->
                    <div
                        v-if="vesselAtSea && activeMarea"
                        @click="viewMarea(activeMarea.id)"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-md bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 text-sm font-medium cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                    >
                        <Ship class="w-4 h-4" />
                        <span>{{ activeMarea.marea_number }}</span>
                    </div>
                    <!-- Status Badge -->
                    <div
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium"
                        :class="vesselAtSea ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300' : 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300'"
                    >
                        <component :is="vesselAtSea ? Ship : Anchor" class="w-4 h-4" />
                        <span>{{ vesselAtSea ? 'At Sea' : 'In Port' }}</span>
                    </div>
                </div>
            </div>

            <!-- Preparing Mareas Section -->
            <div
                v-if="preparingMareas && preparingMareas.length > 0"
                class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4 space-y-3"
            >
                <div class="flex items-center gap-2 mb-2">
                    <Loader2 class="w-4 h-4 text-blue-600 dark:text-blue-400 animate-spin" />
                    <h2 class="text-sm font-semibold text-card-foreground dark:text-card-foreground">
                        Preparing Mareas ({{ preparingMareas.length }})
                    </h2>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="marea in preparingMareas"
                        :key="marea.id"
                        @click="viewMarea(marea.id)"
                        class="rounded-lg border border-border dark:border-border bg-muted/30 dark:bg-muted/20 p-4 cursor-pointer hover:bg-muted/50 dark:hover:bg-muted/30 transition-all group"
                    >
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="text-sm font-semibold text-card-foreground dark:text-card-foreground">
                                        {{ marea.marea_number }}
                                    </h3>
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-medium">
                                        Preparing
                                    </span>
                                </div>
                                <p class="text-xs text-muted-foreground dark:text-muted-foreground mb-2">
                                    {{ marea.name }}
                                </p>
                                <div class="flex items-center gap-4 text-xs text-muted-foreground dark:text-muted-foreground">
                                    <div v-if="marea.estimated_departure_date" class="flex items-center gap-1">
                                        <Clock class="w-3 h-3" />
                                        <span>Departure: {{ new Date(marea.estimated_departure_date).toLocaleDateString() }}</span>
                                    </div>
                                </div>
                            </div>
                            <ArrowRight class="w-4 h-4 text-muted-foreground dark:text-muted-foreground flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                        <!-- Progress Bar -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-muted-foreground dark:text-muted-foreground">Preparation Progress</span>
                                <span class="font-medium text-blue-600 dark:text-blue-400">{{ getPreparationProgress(marea) }}%</span>
                            </div>
                            <div class="w-full h-2 bg-muted dark:bg-muted/50 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-full transition-all duration-500 relative"
                                    :style="{ width: `${getPreparationProgress(marea)}%` }"
                                >
                                    <div class="absolute inset-0 bg-white/30 dark:bg-white/10 animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compact Financial Stats Cards -->
            <div
                v-if="hasPermission('transactions.view') || hasPermission('reports.access')"
                class="grid grid-cols-2 md:grid-cols-4 gap-3"
            >
                <!-- Income -->
                <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-8 h-8 rounded-md bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <TrendingUp class="w-4 h-4 text-green-600 dark:text-green-400" />
                        </div>
                        <span class="text-xs text-muted-foreground dark:text-muted-foreground">
                            {{ currentMonth.month_label }}
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground dark:text-muted-foreground mb-1">Income</p>
                    <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                        <MoneyDisplay
                            :value="currentMonth.total_income"
                            :currency="defaultCurrency"
                            :decimals="currencyData.decimal_separator"
                            variant="positive"
                            size="sm"
                        />
                    </p>
                </div>

                <!-- Expenses -->
                <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-8 h-8 rounded-md bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <TrendingDown class="w-4 h-4 text-red-600 dark:text-red-400" />
                        </div>
                        <span class="text-xs text-muted-foreground dark:text-muted-foreground">
                            {{ currentMonth.month_label }}
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground dark:text-muted-foreground mb-1">Expenses</p>
                    <p class="text-lg font-semibold text-red-600 dark:text-red-400">
                        <MoneyDisplay
                            :value="currentMonth.total_expenses"
                            :currency="defaultCurrency"
                            :decimals="currencyData.decimal_separator"
                            variant="negative"
                            size="sm"
                        />
                    </p>
                </div>

                <!-- Net Balance -->
                <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-8 h-8 rounded-md bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <DollarSign class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        </div>
                        <span class="text-xs text-muted-foreground dark:text-muted-foreground">
                            {{ currentMonth.month_label }}
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground dark:text-muted-foreground mb-1">Net Balance</p>
                    <p
                        class="text-lg font-semibold"
                        :class="currentMonth.net_balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                    >
                        <MoneyDisplay
                            :value="currentMonth.net_balance"
                            :currency="defaultCurrency"
                            :decimals="currencyData.decimal_separator"
                            :variant="currentMonth.net_balance >= 0 ? 'positive' : 'negative'"
                            size="sm"
                        />
                    </p>
                </div>

                <!-- Transaction Count -->
                <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-8 h-8 rounded-md bg-muted dark:bg-muted/50 flex items-center justify-center">
                            <Activity class="w-4 h-4 text-muted-foreground dark:text-muted-foreground" />
                        </div>
                        <span class="text-xs text-muted-foreground dark:text-muted-foreground">
                            {{ currentMonth.month_label }}
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground dark:text-muted-foreground mb-1">Transactions</p>
                    <p class="text-lg font-semibold text-card-foreground dark:text-card-foreground">
                        {{ currentMonth.transaction_count }}
                    </p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Chart Section -->
                <div
                    v-if="hasPermission('reports.access')"
                    class="lg:col-span-2 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-card-foreground dark:text-card-foreground">
                            6 Months Overview
                        </h2>
                        <Link
                            :href="`/panel/${getCurrentVesselId()}/financial-reports`"
                            class="text-xs text-primary hover:text-primary/80 font-medium flex items-center gap-1"
                        >
                            View Reports
                            <ArrowRight class="w-3 h-3" />
                        </Link>
                    </div>
                    <!-- Horizontal Bar Chart -->
                    <div class="space-y-3">
                        <div
                            v-for="month in last6Months"
                            :key="`${month.year}-${month.month}`"
                            class="space-y-1.5"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-muted-foreground dark:text-muted-foreground font-medium w-16">
                                    {{ month.month_label }}
                                </span>
                                <div class="flex items-center gap-2 flex-1 max-w-xs">
                                    <!-- Income Bar -->
                                    <div class="flex-1 relative h-5 bg-muted/30 dark:bg-muted/20 rounded overflow-hidden">
                                        <div
                                            class="h-full bg-green-500 dark:bg-green-600 rounded transition-all"
                                            :style="{ width: `${getBarWidth(month.income)}%` }"
                                        ></div>
                                        <div
                                            v-if="month.income > 0"
                                            class="absolute inset-0 flex items-center justify-center text-[10px] font-medium text-green-700 dark:text-green-300"
                                        >
                                            <MoneyDisplay
                                                v-if="getBarWidth(month.income) > 15"
                                                :value="month.income"
                                                :currency="defaultCurrency"
                                                :decimals="currencyData.decimal_separator"
                                                variant="positive"
                                                size="xs"
                                                :show-symbol="false"
                                            />
                                        </div>
                                    </div>
                                    <!-- Expenses Bar -->
                                    <div class="flex-1 relative h-5 bg-muted/30 dark:bg-muted/20 rounded overflow-hidden">
                                        <div
                                            class="h-full bg-red-500 dark:bg-red-600 rounded transition-all"
                                            :style="{ width: `${getBarWidth(month.expenses)}%` }"
                                        ></div>
                                        <div
                                            v-if="month.expenses > 0"
                                            class="absolute inset-0 flex items-center justify-center text-[10px] font-medium text-red-700 dark:text-red-300"
                                        >
                                            <MoneyDisplay
                                                v-if="getBarWidth(month.expenses) > 15"
                                                :value="month.expenses"
                                                :currency="defaultCurrency"
                                                :decimals="currencyData.decimal_separator"
                                                variant="negative"
                                                size="xs"
                                                :show-symbol="false"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mt-4 pt-3 border-t border-border dark:border-border">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded bg-green-500 dark:bg-green-600"></div>
                            <span class="text-xs text-muted-foreground dark:text-muted-foreground">Income</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded bg-red-500 dark:bg-red-600"></div>
                            <span class="text-xs text-muted-foreground dark:text-muted-foreground">Expenses</span>
                        </div>
                    </div>
                </div>

                <!-- Vessel Statistics -->
                <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                    <h2 class="text-sm font-semibold text-card-foreground dark:text-card-foreground mb-3">
                        Statistics
                    </h2>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div class="p-3 rounded-md border border-border dark:border-border bg-muted/30 dark:bg-muted/20">
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <Users class="w-3.5 h-3.5 text-muted-foreground dark:text-muted-foreground" />
                                <span class="text-[10px] text-muted-foreground dark:text-muted-foreground">Crew</span>
                            </div>
                            <p class="text-lg font-semibold text-card-foreground dark:text-card-foreground">
                                {{ vesselStats.total_crew }}
                            </p>
                        </div>
                        <div class="p-3 rounded-md border border-border dark:border-border bg-muted/30 dark:bg-muted/20">
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <Receipt class="w-3.5 h-3.5 text-muted-foreground dark:text-muted-foreground" />
                                <span class="text-[10px] text-muted-foreground dark:text-muted-foreground">Transactions</span>
                            </div>
                            <p class="text-lg font-semibold text-card-foreground dark:text-card-foreground">
                                {{ vesselStats.total_transactions }}
                            </p>
                        </div>
                        <div class="p-3 rounded-md border border-border dark:border-border bg-muted/30 dark:bg-muted/20">
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <Ship class="w-3.5 h-3.5 text-muted-foreground dark:text-muted-foreground" />
                                <span class="text-[10px] text-muted-foreground dark:text-muted-foreground">Mareas</span>
                            </div>
                            <p class="text-lg font-semibold text-card-foreground dark:text-card-foreground">
                                {{ vesselStats.total_mareas }}
                            </p>
                        </div>
                        <div class="p-3 rounded-md border border-border dark:border-border bg-muted/30 dark:bg-muted/20">
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <Calendar class="w-3.5 h-3.5 text-muted-foreground dark:text-muted-foreground" />
                                <span class="text-[10px] text-muted-foreground dark:text-muted-foreground">Active</span>
                            </div>
                            <p class="text-lg font-semibold text-card-foreground dark:text-card-foreground">
                                {{ vesselStats.active_mareas }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div
                v-if="canView('transactions') && recentTransactions.length > 0"
                class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4"
            >
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-card-foreground dark:text-card-foreground">
                        Recent Transactions
                    </h2>
                    <Link
                        :href="`/panel/${getCurrentVesselId()}/transactions`"
                        class="text-xs text-primary hover:text-primary/80 font-medium flex items-center gap-1"
                    >
                        View All
                        <ArrowRight class="w-3 h-3" />
                    </Link>
                </div>
                <div class="space-y-2">
                    <div
                        v-for="transaction in recentTransactions"
                        :key="transaction.id"
                        @click="viewTransaction(transaction.id)"
                        class="flex items-center justify-between p-2.5 rounded-md border border-border dark:border-border hover:bg-muted/50 dark:hover:bg-muted/20 cursor-pointer transition-colors"
                    >
                        <div class="flex items-center gap-2.5 flex-1 min-w-0">
                            <div
                                class="w-8 h-8 rounded-md flex items-center justify-center flex-shrink-0"
                                :class="transaction.type === 'income' ? 'bg-green-100 dark:bg-green-900/30' : transaction.type === 'expense' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-blue-100 dark:bg-blue-900/30'"
                            >
                                <Receipt
                                    class="w-4 h-4"
                                    :class="transaction.type === 'income' ? 'text-green-600 dark:text-green-400' : transaction.type === 'expense' ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400'"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-card-foreground dark:text-card-foreground truncate">
                                    {{ transaction.transaction_number }}
                                </p>
                                <p class="text-[10px] text-muted-foreground dark:text-muted-foreground truncate">
                                    {{ transaction.description || 'No description' }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right ml-3 flex-shrink-0">
                            <MoneyDisplay
                                :value="transaction.amount"
                                :currency="transaction.currency"
                                :decimals="getCurrencyData(transaction.currency).decimal_separator"
                                :variant="transaction.type === 'income' ? 'positive' : transaction.type === 'expense' ? 'negative' : 'neutral'"
                                size="xs"
                            />
                            <p class="text-[10px] text-muted-foreground dark:text-muted-foreground mt-0.5">
                                {{ transaction.formatted_transaction_date }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </VesselLayout>
</template>
