<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import Icon from '@/components/Icon.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import { LineChart } from '@/components/ui/chart-line';
import { BarChart } from '@/components/ui/chart-bar';
import { usePermissions } from '@/composables/usePermissions';
import { useI18n } from '@/composables/useI18n';
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
const { t } = useI18n();

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

// Prepare data for LineChart (daily breakdown)
const dailyChartData = computed(() => {
    return dailyBreakdown.value.map((day: DailyBreakdown) => ({
        date: day.formatted_date,
        income: day.income, // Already in cents
        expenses: day.expenses, // Already in cents
    }));
});

// Prepare data for BarChart (category breakdown)
const categoryChartData = computed(() => {
    return categoryBreakdown.value.map((cat: CategoryBreakdown) => ({
        category: cat.category_name,
        income: cat.income, // Already in cents
        expenses: cat.expenses, // Already in cents
    }));
});


// Currency formatter for charts (values are in cents)
const currencyFormatter = (value: number | Date, index?: number) => {
    if (typeof value === 'number') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency.value,
            minimumFractionDigits: currencyData.value.decimal_separator,
            maximumFractionDigits: currencyData.value.decimal_separator,
        }).format(value / 100);
    }
    return String(value);
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
    <Head :title="`${t('Financial Report')} - ${monthLabel} ${year}`" />

    <VesselLayout v-if="hasPermission('reports.access')" :breadcrumbs="[
        { title: t('Financial Reports'), href: financialReports.index.url({ vessel: getCurrentVesselId() }) },
        { title: `${monthLabel} ${year}`, href: financialReports.show.url({ vessel: getCurrentVesselId(), year: year, month: month }) }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">
                            {{ t('Financial Report') }} - {{ monthLabel }} {{ year }}
                        </h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">
                            {{ t('Comprehensive financial overview for') }} {{ monthLabel }} {{ year }}
                        </p>
                    </div>
                    <Link
                        :href="financialReports.index.url({ vessel: getCurrentVesselId() })"
                        class="inline-flex items-center px-4 py-2 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground font-medium transition-colors"
                    >
                        <Icon name="arrow-left" class="w-4 h-4 mr-2" />
                        {{ t('Back to Reports') }}
                    </Link>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Total Income -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Total Income') }}</h3>
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
                        <h3 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Total Expenses') }}</h3>
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
                        <h3 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Net Balance') }}</h3>
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
                    <h3 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground mb-2">{{ t('Transactions') }}</h3>
                    <div class="text-3xl font-bold text-card-foreground dark:text-card-foreground">
                        {{ summary.transaction_count }}
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Daily Breakdown Line Chart -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">{{ t('Daily Breakdown') }}</h3>
                    <div v-if="dailyChartData.length > 0">
                        <LineChart
                            :data="dailyChartData"
                            index="date"
                            :categories="['income', 'expenses']"
                            :colors="['hsl(142 76% 36%)', 'hsl(0 84% 60%)']"
                            :height="300"
                            :y-formatter="currencyFormatter"
                        />
                    </div>
                    <div v-else class="text-center text-muted-foreground dark:text-muted-foreground py-8">
                        {{ t('No data available for this period') }}
                    </div>
                </div>

                <!-- Category Breakdown Bar Chart -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">{{ t('Category Breakdown') }}</h3>
                    <div v-if="categoryChartData.length > 0">
                        <BarChart
                            :data="categoryChartData"
                            index="category"
                            :categories="['income', 'expenses']"
                            :colors="['hsl(142 76% 36%)', 'hsl(0 84% 60%)']"
                            :height="Math.max(300, categoryChartData.length * 40)"
                            :y-formatter="currencyFormatter"
                        />
                    </div>
                    <div v-else class="text-center text-muted-foreground dark:text-muted-foreground py-8">
                        {{ t('No categories found') }}
                    </div>
                </div>
            </div>

            <!-- Category Breakdown Summary -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Expenses by Category -->
                <div v-if="categoryBreakdown.filter((c: CategoryBreakdown) => c.expenses > 0).length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                    <h3 class="text-sm font-semibold text-card-foreground dark:text-card-foreground mb-3">{{ t('Expenses by Category') }}</h3>
                    <div class="space-y-3">
                        <div
                            v-for="cat in categoryBreakdown.filter((c: CategoryBreakdown) => c.expenses > 0)"
                            :key="`expense-${cat.category_id}`"
                            class="space-y-1"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <div
                                        v-if="cat.category_color"
                                        class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                        :style="{ backgroundColor: cat.category_color }"
                                    ></div>
                                    <span class="font-medium text-card-foreground dark:text-card-foreground truncate">
                                        {{ t(cat.category_name) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                    <span class="text-xs font-semibold text-card-foreground dark:text-card-foreground">
                                        <MoneyDisplay
                                            :value="cat.expenses"
                                            :currency="currency"
                                            :decimals="currencyData.decimal_separator"
                                            variant="negative"
                                            size="xs"
                                            :show-symbol="false"
                                        />
                                    </span>
                                    <span class="text-xs text-muted-foreground dark:text-muted-foreground">
                                        ({{ summary.total_expenses > 0 ? ((cat.expenses / summary.total_expenses) * 100).toFixed(1) : '0' }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="relative h-2 bg-muted dark:bg-muted/50 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-red-500 dark:bg-red-600 transition-all rounded-full"
                                    :style="{ width: `${summary.total_expenses > 0 ? ((cat.expenses / summary.total_expenses) * 100) : 0}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Income by Category -->
                <div v-if="categoryBreakdown.filter((c: CategoryBreakdown) => c.income > 0).length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                    <h3 class="text-sm font-semibold text-card-foreground dark:text-card-foreground mb-3">{{ t('Income by Category') }}</h3>
                    <div class="space-y-3">
                        <div
                            v-for="cat in categoryBreakdown.filter((c: CategoryBreakdown) => c.income > 0)"
                            :key="`income-${cat.category_id}`"
                            class="space-y-1"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <div
                                        v-if="cat.category_color"
                                        class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                        :style="{ backgroundColor: cat.category_color }"
                                    ></div>
                                    <span class="font-medium text-card-foreground dark:text-card-foreground truncate">
                                        {{ t(cat.category_name) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                    <span class="text-xs font-semibold text-card-foreground dark:text-card-foreground">
                                        <MoneyDisplay
                                            :value="cat.income"
                                            :currency="currency"
                                            :decimals="currencyData.decimal_separator"
                                            variant="positive"
                                            size="xs"
                                            :show-symbol="false"
                                        />
                                    </span>
                                    <span class="text-xs text-muted-foreground dark:text-muted-foreground">
                                        ({{ summary.total_income > 0 ? ((cat.income / summary.total_income) * 100).toFixed(1) : '0' }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="relative h-2 bg-muted dark:bg-muted/50 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-green-500 dark:bg-green-600 transition-all rounded-full"
                                    :style="{ width: `${summary.total_income > 0 ? ((cat.income / summary.total_income) * 100) : 0}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Marea Information -->
            <div v-if="mareas.length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">{{ t('Mareas (Fishing Trips)') }}</h3>
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
                                <span class="text-muted-foreground dark:text-muted-foreground">{{ t('Income') }}:</span>
                                <MoneyDisplay
                                    :value="marea.total_income"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    variant="positive"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground dark:text-muted-foreground">{{ t('Expenses') }}:</span>
                                <MoneyDisplay
                                    :value="marea.total_expenses"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    variant="negative"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between font-semibold">
                                <span class="text-card-foreground dark:text-card-foreground">{{ t('Net') }}:</span>
                                <MoneyDisplay
                                    :value="marea.net_result"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    :variant="marea.net_result >= 0 ? 'positive' : 'negative'"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between text-xs text-muted-foreground dark:text-muted-foreground mt-2">
                                <span>{{ t('Transactions') }}:</span>
                                <span>{{ marea.transaction_count }}</span>
                            </div>
                            <!-- Quantity Returns -->
                            <div v-if="marea.quantity_returns.length > 0" class="mt-3 pt-3 border-t border-border dark:border-border">
                                <div class="text-xs font-medium text-muted-foreground dark:text-muted-foreground mb-2">{{ t('Quantity Returns') }}:</div>
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
                <p class="text-muted-foreground dark:text-muted-foreground">{{ t('You do not have permission to view financial reports.') }}</p>
            </div>
        </div>
    </VesselLayout>
</template>


