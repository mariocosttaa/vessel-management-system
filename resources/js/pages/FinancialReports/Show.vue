<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import Icon from '@/components/Icon.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import { usePermissions } from '@/composables/usePermissions';
import financialReports from '@/routes/panel/financial-reports';
import { TrendingUp, TrendingDown, Minus } from 'lucide-vue-next';

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

interface CategoryBreakdown {
    category_id: number;
    category_name: string;
    category_type: string | null;
    category_color: string | null;
    income: number;
    expenses: number;
    count: number;
}

interface DailyBreakdown {
    date: string;
    formatted_date: string;
    income: number;
    expenses: number;
    net: number;
    count: number;
}

interface Marea {
    id: number;
    marea_number: string;
    name: string | null;
    status: string;
    actual_departure_date: string | null;
    actual_return_date: string | null;
    estimated_departure_date: string | null;
    estimated_return_date: string | null;
    total_income: number;
    total_expenses: number;
    net_result: number;
    transaction_count: number;
    quantity_returns: Array<{
        name: string;
        quantity: number;
    }>;
}

interface Props {
    month: number;
    year: number;
    monthLabel: string;
    defaultCurrency?: string;
    summary: {
        total_income: number;
        total_expenses: number;
        net_balance: number;
        transaction_count: number;
        income_change: number;
        expenses_change: number;
        net_change: number;
    };
    categoryBreakdown: CategoryBreakdown[];
    dailyBreakdown: DailyBreakdown[];
    mareas: Marea[];
}

const props = defineProps<Props>();

// Permission check
const { hasPermission } = usePermissions();

// Check if user has permission to access reports
onMounted(() => {
    if (!hasPermission('reports.access')) {
        router.visit(`/panel/${getCurrentVesselId()}/dashboard`, {
            replace: true,
        });
    }
});

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

const currency = computed(() => props.defaultCurrency || 'EUR');
const currencyData = computed(() => getCurrencyData(currency.value));

// Template accessors
const month = computed(() => props.month);
const year = computed(() => props.year);
const monthLabel = computed(() => props.monthLabel);
const summary = computed(() => props.summary);
const categoryBreakdown = computed(() => props.categoryBreakdown);
const dailyBreakdown = computed(() => props.dailyBreakdown);
const mareas = computed(() => props.mareas);

// Calculate chart max values for scaling
const maxIncome = computed(() => {
    if (dailyBreakdown.value.length === 0) return props.summary.total_income || 1;
    return Math.max(...dailyBreakdown.value.map((d: DailyBreakdown) => d.income), props.summary.total_income);
});

const maxExpenses = computed(() => {
    if (dailyBreakdown.value.length === 0) return props.summary.total_expenses || 1;
    return Math.max(...dailyBreakdown.value.map((d: DailyBreakdown) => d.expenses), props.summary.total_expenses);
});

const maxCategoryValue = computed(() => {
    if (categoryBreakdown.value.length === 0) return 1;
    return Math.max(...categoryBreakdown.value.map((c: CategoryBreakdown) => Math.max(c.income, c.expenses)), 1);
});

// Chart bar height calculation
const getBarHeight = (value: number, maxValue: number) => {
    if (maxValue === 0) return 0;
    return Math.max((value / maxValue) * 100, 2); // Minimum 2% for visibility
};

// Format percentage change
const formatChange = (change: number) => {
    if (change === 0) return '0%';
    const sign = change > 0 ? '+' : '';
    return `${sign}${change.toFixed(1)}%`;
};

// Get change icon
const getChangeIcon = (change: number) => {
    if (change > 0) return TrendingUp;
    if (change < 0) return TrendingDown;
    return Minus;
};

// Get change color
const getChangeColor = (change: number) => {
    if (change > 0) return 'text-green-600 dark:text-green-400';
    if (change < 0) return 'text-red-600 dark:text-red-400';
    return 'text-muted-foreground';
};

</script>

<template>
    <Head :title="`Financial Report - ${monthLabel} ${year}`" />

    <VesselLayout v-if="hasPermission('reports.access')" :breadcrumbs="[
        { title: 'Financial Reports', href: financialReports.index.url({ vessel: getCurrentVesselId() }) },
        { title: `${monthLabel} ${year}`, href: financialReports.show.url({ vessel: getCurrentVesselId(), year: year, month: month }) }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">
                            Financial Report - {{ monthLabel }} {{ year }}
                        </h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">
                            Comprehensive financial overview for {{ monthLabel }} {{ year }}
                        </p>
                    </div>
                    <Link
                        :href="financialReports.index.url({ vessel: getCurrentVesselId() })"
                        class="inline-flex items-center px-4 py-2 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground font-medium transition-colors"
                    >
                        <Icon name="arrow-left" class="w-4 h-4 mr-2" />
                        Back to Reports
                    </Link>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Total Income -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Total Income</h3>
                        <component
                            :is="getChangeIcon(summary.income_change)"
                            :class="['w-4 h-4', getChangeColor(summary.income_change)]"
                        />
                    </div>
                    <div class="flex items-baseline gap-2">
                        <MoneyDisplay
                            :value="summary.total_income"
                            :currency="currency"
                            :decimals="currencyData.decimal_separator"
                            variant="positive"
                            size="xl"
                        />
                        <span
                            :class="['text-sm font-medium', getChangeColor(summary.income_change)]"
                        >
                            {{ formatChange(summary.income_change) }}
                        </span>
                    </div>
                </div>

                <!-- Total Expenses -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Total Expenses</h3>
                        <component
                            :is="getChangeIcon(summary.expenses_change)"
                            :class="['w-4 h-4', getChangeColor(summary.expenses_change)]"
                        />
                    </div>
                    <div class="flex items-baseline gap-2">
                        <MoneyDisplay
                            :value="summary.total_expenses"
                            :currency="currency"
                            :decimals="currencyData.decimal_separator"
                            variant="negative"
                            size="xl"
                        />
                        <span
                            :class="['text-sm font-medium', getChangeColor(summary.expenses_change)]"
                        >
                            {{ formatChange(summary.expenses_change) }}
                        </span>
                    </div>
                </div>

                <!-- Net Balance -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Net Balance</h3>
                        <component
                            :is="getChangeIcon(summary.net_change)"
                            :class="['w-4 h-4', getChangeColor(summary.net_change)]"
                        />
                    </div>
                    <div class="flex items-baseline gap-2">
                        <MoneyDisplay
                            :value="summary.net_balance"
                            :currency="currency"
                            :decimals="currencyData.decimal_separator"
                            :variant="summary.net_balance >= 0 ? 'positive' : 'negative'"
                            size="xl"
                        />
                        <span
                            :class="['text-sm font-medium', getChangeColor(summary.net_change)]"
                        >
                            {{ formatChange(summary.net_change) }}
                        </span>
                    </div>
                </div>

                <!-- Transaction Count -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <h3 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground mb-2">Transactions</h3>
                    <div class="text-3xl font-bold text-card-foreground dark:text-card-foreground">
                        {{ summary.transaction_count }}
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Daily Breakdown Chart -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">Daily Breakdown</h3>
                    <div class="space-y-4">
                        <div
                            v-for="day in dailyBreakdown"
                            :key="day.date"
                            class="flex items-center gap-4"
                        >
                            <div class="w-20 text-xs text-muted-foreground dark:text-muted-foreground">
                                {{ day.formatted_date }}
                            </div>
                            <div class="flex-1 space-y-1">
                                <!-- Income Bar -->
                                <div v-if="day.income > 0" class="flex items-center gap-2">
                                    <div class="w-16 text-xs text-muted-foreground dark:text-muted-foreground">Income</div>
                                    <div class="flex-1 relative h-4 bg-muted dark:bg-muted/50 rounded overflow-hidden">
                                        <div
                                            class="h-full bg-green-500 dark:bg-green-600 transition-all"
                                            :style="{ width: `${getBarHeight(day.income, maxIncome)}%` }"
                                        ></div>
                                    </div>
                                    <div class="w-20 text-xs text-right text-green-600 dark:text-green-400">
                                        <MoneyDisplay
                                            :value="day.income"
                                            :currency="currency"
                                            :decimals="currencyData.decimal_separator"
                                            variant="positive"
                                            size="xs"
                                            :show-symbol="false"
                                        />
                                    </div>
                                </div>
                                <!-- Expenses Bar -->
                                <div v-if="day.expenses > 0" class="flex items-center gap-2">
                                    <div class="w-16 text-xs text-muted-foreground dark:text-muted-foreground">Expenses</div>
                                    <div class="flex-1 relative h-4 bg-muted dark:bg-muted/50 rounded overflow-hidden">
                                        <div
                                            class="h-full bg-red-500 dark:bg-red-600 transition-all"
                                            :style="{ width: `${getBarHeight(day.expenses, maxExpenses)}%` }"
                                        ></div>
                                    </div>
                                    <div class="w-20 text-xs text-right text-red-600 dark:text-red-400">
                                        <MoneyDisplay
                                            :value="day.expenses"
                                            :currency="currency"
                                            :decimals="currencyData.decimal_separator"
                                            variant="negative"
                                            size="xs"
                                            :show-symbol="false"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="dailyBreakdown.length === 0" class="text-center text-muted-foreground dark:text-muted-foreground py-8">
                            No data available for this period
                        </div>
                    </div>
                </div>

                <!-- Category Breakdown Chart -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">Category Breakdown</h3>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <div
                            v-for="category in categoryBreakdown"
                            :key="category.category_id"
                            class="space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div
                                        v-if="category.category_color"
                                        class="w-3 h-3 rounded-full"
                                        :style="{ backgroundColor: category.category_color }"
                                    ></div>
                                    <span class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                        {{ category.category_name }}
                                    </span>
                                </div>
                                <span class="text-xs text-muted-foreground dark:text-muted-foreground">
                                    {{ category.count }} transactions
                                </span>
                            </div>
                            <!-- Income Bar -->
                            <div v-if="category.income > 0" class="flex items-center gap-2">
                                <div class="w-16 text-xs text-muted-foreground dark:text-muted-foreground">Income</div>
                                <div class="flex-1 relative h-3 bg-muted dark:bg-muted/50 rounded overflow-hidden">
                                    <div
                                        class="h-full bg-green-500 dark:bg-green-600 transition-all"
                                        :style="{ width: `${getBarHeight(category.income, maxCategoryValue)}%` }"
                                    ></div>
                                </div>
                                <div class="w-20 text-xs text-right text-green-600 dark:text-green-400">
                                    <MoneyDisplay
                                        :value="category.income"
                                        :currency="currency"
                                        :decimals="currencyData.decimal_separator"
                                        variant="positive"
                                        size="xs"
                                        :show-symbol="false"
                                    />
                                </div>
                            </div>
                            <!-- Expenses Bar -->
                            <div v-if="category.expenses > 0" class="flex items-center gap-2">
                                <div class="w-16 text-xs text-muted-foreground dark:text-muted-foreground">Expenses</div>
                                <div class="flex-1 relative h-3 bg-muted dark:bg-muted/50 rounded overflow-hidden">
                                    <div
                                        class="h-full bg-red-500 dark:bg-red-600 transition-all"
                                        :style="{ width: `${getBarHeight(category.expenses, maxCategoryValue)}%` }"
                                    ></div>
                                </div>
                                <div class="w-20 text-xs text-right text-red-600 dark:text-red-400">
                                    <MoneyDisplay
                                        :value="category.expenses"
                                        :currency="currency"
                                        :decimals="currencyData.decimal_separator"
                                        variant="negative"
                                        size="xs"
                                        :show-symbol="false"
                                    />
                                </div>
                            </div>
                        </div>
                        <div v-if="categoryBreakdown.length === 0" class="text-center text-muted-foreground dark:text-muted-foreground py-8">
                            No categories found
                        </div>
                    </div>
                </div>
            </div>

            <!-- Marea Information -->
            <div v-if="mareas.length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">Mareas (Fishing Trips)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="marea in mareas"
                        :key="marea.id"
                        class="rounded-lg border border-border dark:border-border bg-muted/30 dark:bg-muted/20 p-4"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-card-foreground dark:text-card-foreground">
                                {{ marea.marea_number }}
                            </h4>
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium"
                                :class="{
                                    'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300': marea.status === 'preparing',
                                    'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300': marea.status === 'closed',
                                    'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300': marea.status === 'at_sea' || marea.status === 'returned',
                                    'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300': marea.status === 'cancelled',
                                }"
                            >
                                {{ marea.status }}
                            </span>
                        </div>
                        <div v-if="marea.name" class="text-sm text-muted-foreground dark:text-muted-foreground mb-3">
                            {{ marea.name }}
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground dark:text-muted-foreground">Income:</span>
                                <MoneyDisplay
                                    :value="marea.total_income"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    variant="positive"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground dark:text-muted-foreground">Expenses:</span>
                                <MoneyDisplay
                                    :value="marea.total_expenses"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    variant="negative"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between font-semibold">
                                <span class="text-card-foreground dark:text-card-foreground">Net:</span>
                                <MoneyDisplay
                                    :value="marea.net_result"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    :variant="marea.net_result >= 0 ? 'positive' : 'negative'"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between text-xs text-muted-foreground dark:text-muted-foreground mt-2">
                                <span>Transactions:</span>
                                <span>{{ marea.transaction_count }}</span>
                            </div>
                            <!-- Quantity Returns -->
                            <div v-if="marea.quantity_returns.length > 0" class="mt-3 pt-3 border-t border-border dark:border-border">
                                <div class="text-xs font-medium text-muted-foreground dark:text-muted-foreground mb-2">Quantity Returns:</div>
                                <div
                                    v-for="qr in marea.quantity_returns"
                                    :key="qr.name"
                                    class="flex justify-between text-xs"
                                >
                                    <span class="text-muted-foreground dark:text-muted-foreground">{{ qr.name }}:</span>
                                    <span class="text-card-foreground dark:text-card-foreground font-medium">{{ qr.quantity }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </VesselLayout>
    <VesselLayout v-else :breadcrumbs="[]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-12 text-center">
                <p class="text-muted-foreground dark:text-muted-foreground">You do not have permission to view financial reports.</p>
            </div>
        </div>
    </VesselLayout>
</template>

