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
import vatReports from '@/routes/panel/vat-reports';
import { TrendingUp, TrendingDown, Minus, Receipt } from 'lucide-vue-next';

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel IDs (alphanumeric strings) or numeric IDs
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

interface VatProfileBreakdown {
    vat_profile_id: number;
    vat_profile_name: string;
    vat_profile_percentage: number;
    vat_profile_code: string | null;
    country: {
        id: number;
        name: string;
        code: string;
    } | null;
    total_base_amount: number;
    total_vat_amount: number;
    total_amount_with_vat: number;
    transaction_count: number;
    transactions: Array<{
        id: number;
        transaction_number: string;
        transaction_date: string | null;
        description: string | null;
        base_amount: number;
        vat_amount: number;
        total_amount: number;
        category: {
            id: number;
            name: string;
            color: string | null;
        } | null;
    }>;
}

interface CategoryBreakdown {
    category_id: number;
    category_name: string;
    category_color: string | null;
    total_base_amount: number;
    total_vat_amount: number;
    total_amount_with_vat: number;
    transaction_count: number;
}

interface DailyBreakdown {
    date: string;
    formatted_date: string;
    base_amount: number;
    vat_amount: number;
    total_amount: number;
    count: number;
}

interface MareaBreakdown {
    marea_id: number;
    marea_number: string;
    marea_name: string | null;
    total_base_amount: number;
    total_vat_amount: number;
    total_amount_with_vat: number;
    transaction_count: number;
}

interface Transaction {
    id: number;
    transaction_number: string;
    transaction_date: string | null;
    formatted_transaction_date: string | null;
    description: string | null;
    reference: string | null;
    base_amount: number;
    vat_amount: number;
    total_amount: number;
    currency: string;
    category: {
        id: number;
        name: string;
        color: string | null;
    } | null;
    vat_profile: {
        id: number;
        name: string;
        percentage: number;
        code: string | null;
    } | null;
    marea: {
        id: number;
        marea_number: string;
        name: string | null;
    } | null;
}

interface Props {
    month: number;
    year: number;
    monthLabel: string;
    defaultCurrency?: string;
    summary: {
        total_vat: number;
        total_base_amount: number;
        total_amount_with_vat: number;
        transaction_count: number;
        vat_change: number;
        base_change: number;
    };
    vatProfileBreakdown: VatProfileBreakdown[];
    categoryBreakdown: CategoryBreakdown[];
    dailyBreakdown: DailyBreakdown[];
    mareaBreakdown: MareaBreakdown[];
    transactions: Transaction[];
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
const vatProfileBreakdown = computed(() => props.vatProfileBreakdown);
const categoryBreakdown = computed(() => props.categoryBreakdown);
const dailyBreakdown = computed(() => props.dailyBreakdown);
const mareaBreakdown = computed(() => props.mareaBreakdown);
const transactions = computed(() => props.transactions);

// Prepare data for LineChart (daily breakdown)
const dailyChartData = computed(() => {
    return dailyBreakdown.value.map((day: DailyBreakdown) => ({
        date: day.formatted_date,
        vat: day.vat_amount, // Already in cents
        base: day.base_amount, // Already in cents
    }));
});

// Prepare data for BarChart (VAT profile breakdown)
const vatProfileChartData = computed(() => {
    return vatProfileBreakdown.value.map((profile: VatProfileBreakdown) => ({
        profile: `${profile.vat_profile_name} (${profile.vat_profile_percentage}%)`,
        vat: profile.total_vat_amount, // Already in cents
        base: profile.total_base_amount, // Already in cents
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
    <Head :title="`${t('VAT Report')} - ${monthLabel} ${year}`" />

    <VesselLayout v-if="hasPermission('reports.access')" :breadcrumbs="[
        { title: t('VAT Reports'), href: vatReports.index.url({ vessel: getCurrentVesselId() }) },
        { title: `${monthLabel} ${year}`, href: vatReports.show.url({ vessel: getCurrentVesselId(), year: year, month: month }) }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">
                            {{ t('VAT Report') }} - {{ monthLabel }} {{ year }}
                        </h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">
                            {{ t('Comprehensive VAT overview for') }} {{ monthLabel }} {{ year }}
                        </p>
                    </div>
                    <Link
                        :href="vatReports.index.url({ vessel: getCurrentVesselId() })"
                        class="inline-flex items-center px-4 py-2 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground font-medium transition-colors"
                    >
                        <Icon name="arrow-left" class="w-4 h-4 mr-2" />
                        {{ t('Back to Reports') }}
                    </Link>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Total VAT -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Total VAT') }}</h3>
                        <component
                            :is="getChangeIcon(summary.vat_change)"
                            :class="['w-4 h-4', getChangeColor(summary.vat_change)]"
                        />
                    </div>
                    <div class="flex items-baseline gap-2">
                        <MoneyDisplay
                            :value="summary.total_vat"
                            :currency="currency"
                            :decimals="currencyData.decimal_separator"
                            variant="positive"
                            size="xl"
                        />
                        <span
                            :class="['text-sm font-medium', getChangeColor(summary.vat_change)]"
                        >
                            {{ formatChange(summary.vat_change) }}
                        </span>
                    </div>
                </div>

                <!-- Base Amount -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Base Amount') }}</h3>
                        <component
                            :is="getChangeIcon(summary.base_change)"
                            :class="['w-4 h-4', getChangeColor(summary.base_change)]"
                        />
                    </div>
                    <div class="flex items-baseline gap-2">
                        <MoneyDisplay
                            :value="summary.total_base_amount"
                            :currency="currency"
                            :decimals="currencyData.decimal_separator"
                            variant="neutral"
                            size="xl"
                        />
                        <span
                            :class="['text-sm font-medium', getChangeColor(summary.base_change)]"
                        >
                            {{ formatChange(summary.base_change) }}
                        </span>
                    </div>
                </div>

                <!-- Total with VAT -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <h3 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground mb-2">{{ t('Total with VAT') }}</h3>
                    <MoneyDisplay
                        :value="summary.total_amount_with_vat"
                        :currency="currency"
                        :decimals="currencyData.decimal_separator"
                        variant="positive"
                        size="xl"
                    />
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
                    <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">{{ t('Daily VAT Breakdown') }}</h3>
                    <div v-if="dailyChartData.length > 0">
                        <LineChart
                            :data="dailyChartData"
                            index="date"
                            :categories="['vat', 'base']"
                            :colors="['hsl(142 76% 36%)', 'hsl(217 91% 60%)']"
                            :height="300"
                            :y-formatter="currencyFormatter"
                        />
                    </div>
                    <div v-else class="text-center text-muted-foreground dark:text-muted-foreground py-8">
                        {{ t('No data available for this period') }}
                    </div>
                </div>

                <!-- VAT Profile Breakdown Bar Chart -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">{{ t('VAT by Profile') }}</h3>
                    <div v-if="vatProfileChartData.length > 0">
                        <BarChart
                            :data="vatProfileChartData"
                            index="profile"
                            :categories="['vat', 'base']"
                            :colors="['hsl(142 76% 36%)', 'hsl(217 91% 60%)']"
                            :height="Math.max(300, vatProfileChartData.length * 50)"
                            :y-formatter="currencyFormatter"
                        />
                    </div>
                    <div v-else class="text-center text-muted-foreground dark:text-muted-foreground py-8">
                        {{ t('No VAT profiles found') }}
                    </div>
                </div>
            </div>

            <!-- VAT Breakdown Summary -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- VAT by Profile -->
                <div v-if="vatProfileBreakdown.length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                    <h3 class="text-sm font-semibold text-card-foreground dark:text-card-foreground mb-3">{{ t('VAT by Profile') }}</h3>
                    <div class="space-y-3">
                        <div
                            v-for="profile in vatProfileBreakdown"
                            :key="`vat-profile-${profile.vat_profile_id}`"
                            class="space-y-1"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <span class="font-medium text-card-foreground dark:text-card-foreground truncate">
                                        {{ profile.vat_profile_name }}
                                    </span>
                                    <span class="text-xs text-muted-foreground dark:text-muted-foreground">
                                        ({{ profile.vat_profile_percentage }}%)
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                    <span class="text-xs font-semibold text-card-foreground dark:text-card-foreground">
                                        <MoneyDisplay
                                            :value="profile.total_vat_amount"
                                            :currency="currency"
                                            :decimals="currencyData.decimal_separator"
                                            variant="positive"
                                            size="xs"
                                            :show-symbol="false"
                                        />
                                    </span>
                                    <span class="text-xs text-muted-foreground dark:text-muted-foreground">
                                        ({{ summary.total_vat > 0 ? ((profile.total_vat_amount / summary.total_vat) * 100).toFixed(1) : '0' }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="relative h-2 bg-muted dark:bg-muted/50 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-green-500 dark:bg-green-600 transition-all rounded-full"
                                    :style="{ width: `${summary.total_vat > 0 ? ((profile.total_vat_amount / summary.total_vat) * 100) : 0}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VAT by Category -->
                <div v-if="categoryBreakdown.length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                    <h3 class="text-sm font-semibold text-card-foreground dark:text-card-foreground mb-3">{{ t('VAT by Category') }}</h3>
                    <div class="space-y-3">
                        <div
                            v-for="cat in categoryBreakdown"
                            :key="`vat-category-${cat.category_id}`"
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
                                            :value="cat.total_vat_amount"
                                            :currency="currency"
                                            :decimals="currencyData.decimal_separator"
                                            variant="positive"
                                            size="xs"
                                            :show-symbol="false"
                                        />
                                    </span>
                                    <span class="text-xs text-muted-foreground dark:text-muted-foreground">
                                        ({{ summary.total_vat > 0 ? ((cat.total_vat_amount / summary.total_vat) * 100).toFixed(1) : '0' }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="relative h-2 bg-muted dark:bg-muted/50 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-green-500 dark:bg-green-600 transition-all rounded-full"
                                    :style="{ width: `${summary.total_vat > 0 ? ((cat.total_vat_amount / summary.total_vat) * 100) : 0}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Marea Breakdown -->
            <div v-if="mareaBreakdown.length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">{{ t('VAT by Marea') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="marea in mareaBreakdown"
                        :key="marea.marea_id"
                        class="rounded-lg border border-border dark:border-border bg-muted/30 dark:bg-muted/20 p-4"
                    >
                        <h4 class="font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ marea.marea_number }}
                        </h4>
                        <div v-if="marea.marea_name" class="text-sm text-muted-foreground dark:text-muted-foreground mb-3">
                            {{ marea.marea_name }}
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground dark:text-muted-foreground">{{ t('VAT') }}:</span>
                                <MoneyDisplay
                                    :value="marea.total_vat_amount"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    variant="positive"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground dark:text-muted-foreground">{{ t('Base') }}:</span>
                                <MoneyDisplay
                                    :value="marea.total_base_amount"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    variant="neutral"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground dark:text-muted-foreground">{{ t('Total') }}:</span>
                                <MoneyDisplay
                                    :value="marea.total_amount_with_vat"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    variant="positive"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between text-xs text-muted-foreground dark:text-muted-foreground mt-2 pt-2 border-t border-border dark:border-border">
                                <span>{{ t('Transactions') }}:</span>
                                <span>{{ marea.transaction_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions List -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">{{ t('All Transactions with VAT') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border dark:border-border">
                                <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Date') }}</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Transaction') }}</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Description') }}</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Category') }}</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('VAT Profile') }}</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Base Amount') }}</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('VAT') }}</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="transaction in transactions"
                                :key="transaction.id"
                                class="border-b border-border dark:border-border hover:bg-muted/30 dark:hover:bg-muted/20 transition-colors"
                            >
                                <td class="py-3 px-4 text-sm text-card-foreground dark:text-card-foreground">
                                    {{ transaction.formatted_transaction_date || transaction.transaction_date || '—' }}
                                </td>
                                <td class="py-3 px-4 text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    {{ transaction.transaction_number }}
                                </td>
                                <td class="py-3 px-4 text-sm text-card-foreground dark:text-card-foreground">
                                    {{ transaction.description || '—' }}
                                </td>
                                <td class="py-3 px-4">
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
                                    <span v-else class="text-xs text-muted-foreground dark:text-muted-foreground">—</span>
                                </td>
                                <td class="py-3 px-4 text-sm text-card-foreground dark:text-card-foreground">
                                    <div v-if="transaction.vat_profile" class="flex flex-col">
                                        <span class="font-medium">{{ transaction.vat_profile.name }}</span>
                                        <span class="text-xs text-muted-foreground dark:text-muted-foreground">
                                            {{ transaction.vat_profile.percentage }}%
                                            <span v-if="transaction.vat_profile.code"> ({{ transaction.vat_profile.code }})</span>
                                        </span>
                                    </div>
                                    <span v-else class="text-muted-foreground dark:text-muted-foreground">—</span>
                                </td>
                                <td class="py-3 px-4 text-right text-sm">
                                    <MoneyDisplay
                                        :value="transaction.base_amount"
                                        :currency="transaction.currency || currency"
                                        :decimals="currencyData.decimal_separator"
                                        variant="neutral"
                                        size="sm"
                                    />
                                </td>
                                <td class="py-3 px-4 text-right text-sm">
                                    <MoneyDisplay
                                        :value="transaction.vat_amount"
                                        :currency="transaction.currency || currency"
                                        :decimals="currencyData.decimal_separator"
                                        variant="positive"
                                        size="sm"
                                    />
                                </td>
                                <td class="py-3 px-4 text-right text-sm font-semibold">
                                    <MoneyDisplay
                                        :value="transaction.total_amount"
                                        :currency="transaction.currency || currency"
                                        :decimals="currencyData.decimal_separator"
                                        variant="positive"
                                        size="sm"
                                    />
                                </td>
                            </tr>
                            <tr v-if="transactions.length === 0">
                                <td colspan="8" class="py-8 text-center text-muted-foreground dark:text-muted-foreground">
                                    {{ t('No transactions with VAT found for this period') }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="transactions.length > 0" class="bg-muted/30 dark:bg-muted/20">
                            <tr>
                                <td colspan="5" class="py-3 px-4 text-sm font-semibold text-card-foreground dark:text-card-foreground">
                                    {{ t('Total') }}
                                </td>
                                <td class="py-3 px-4 text-right text-sm font-semibold">
                                    <MoneyDisplay
                                        :value="summary.total_base_amount"
                                        :currency="currency"
                                        :decimals="currencyData.decimal_separator"
                                        variant="neutral"
                                        size="sm"
                                    />
                                </td>
                                <td class="py-3 px-4 text-right text-sm font-semibold">
                                    <MoneyDisplay
                                        :value="summary.total_vat"
                                        :currency="currency"
                                        :decimals="currencyData.decimal_separator"
                                        variant="positive"
                                        size="sm"
                                    />
                                </td>
                                <td class="py-3 px-4 text-right text-sm font-semibold">
                                    <MoneyDisplay
                                        :value="summary.total_amount_with_vat"
                                        :currency="currency"
                                        :decimals="currencyData.decimal_separator"
                                        variant="positive"
                                        size="sm"
                                    />
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </VesselLayout>
    <VesselLayout v-else :breadcrumbs="[]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-12 text-center">
                <p class="text-muted-foreground dark:text-muted-foreground">{{ t('You do not have permission to view VAT reports.') }}</p>
            </div>
        </div>
    </VesselLayout>
</template>

