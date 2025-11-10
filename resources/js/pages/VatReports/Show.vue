<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import vatReports from '@/routes/panel/vat-reports';
import { TrendingUp, TrendingDown, Minus, Receipt } from 'lucide-vue-next';

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
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

// Calculate chart max values for scaling
const maxVat = computed(() => {
    if (dailyBreakdown.value.length === 0) return props.summary.total_vat || 1;
    return Math.max(...dailyBreakdown.value.map((d: DailyBreakdown) => d.vat_amount), props.summary.total_vat);
});

const maxBaseAmount = computed(() => {
    if (dailyBreakdown.value.length === 0) return props.summary.total_base_amount || 1;
    return Math.max(...dailyBreakdown.value.map((d: DailyBreakdown) => d.base_amount), props.summary.total_base_amount);
});

const maxVatProfileValue = computed(() => {
    if (vatProfileBreakdown.value.length === 0) return 1;
    return Math.max(...vatProfileBreakdown.value.map((v: VatProfileBreakdown) => v.total_vat_amount), 1);
});

const maxCategoryValue = computed(() => {
    if (categoryBreakdown.value.length === 0) return 1;
    return Math.max(...categoryBreakdown.value.map((c: CategoryBreakdown) => c.total_vat_amount), 1);
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
    <Head :title="`VAT Report - ${monthLabel} ${year}`" />

    <VesselLayout :breadcrumbs="[
        { title: 'VAT Reports', href: vatReports.index.url({ vessel: getCurrentVesselId() }) },
        { title: `${monthLabel} ${year}`, href: vatReports.show.url({ vessel: getCurrentVesselId(), year: year, month: month }) }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">
                            VAT Report - {{ monthLabel }} {{ year }}
                        </h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">
                            Comprehensive VAT overview for {{ monthLabel }} {{ year }}
                        </p>
                    </div>
                    <Link
                        :href="vatReports.index.url({ vessel: getCurrentVesselId() })"
                        class="inline-flex items-center px-4 py-2 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground font-medium transition-colors"
                    >
                        <Icon name="arrow-left" class="w-4 h-4 mr-2" />
                        Back to Reports
                    </Link>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Total VAT -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Total VAT</h3>
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
                        <h3 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Base Amount</h3>
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
                    <h3 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground mb-2">Total with VAT</h3>
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
                    <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">Daily VAT Breakdown</h3>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <div
                            v-for="day in dailyBreakdown"
                            :key="day.date"
                            class="flex items-center gap-4"
                        >
                            <div class="w-20 text-xs text-muted-foreground dark:text-muted-foreground">
                                {{ day.formatted_date }}
                            </div>
                            <div class="flex-1 space-y-2">
                                <!-- Base Amount Bar -->
                                <div v-if="day.base_amount > 0" class="flex items-center gap-2">
                                    <div class="w-16 text-xs text-muted-foreground dark:text-muted-foreground">Base</div>
                                    <div class="flex-1 relative h-3 bg-muted dark:bg-muted/50 rounded overflow-hidden">
                                        <div
                                            class="h-full bg-blue-500 dark:bg-blue-600 transition-all"
                                            :style="{ width: `${getBarHeight(day.base_amount, maxBaseAmount)}%` }"
                                        ></div>
                                    </div>
                                    <div class="w-20 text-xs text-right text-blue-600 dark:text-blue-400">
                                        <MoneyDisplay
                                            :value="day.base_amount"
                                            :currency="currency"
                                            :decimals="currencyData.decimal_separator"
                                            variant="neutral"
                                            size="xs"
                                            :show-symbol="false"
                                        />
                                    </div>
                                </div>
                                <!-- VAT Bar -->
                                <div v-if="day.vat_amount > 0" class="flex items-center gap-2">
                                    <div class="w-16 text-xs text-muted-foreground dark:text-muted-foreground">VAT</div>
                                    <div class="flex-1 relative h-4 bg-muted dark:bg-muted/50 rounded overflow-hidden">
                                        <div
                                            class="h-full bg-green-500 dark:bg-green-600 transition-all"
                                            :style="{ width: `${getBarHeight(day.vat_amount, maxVat)}%` }"
                                        ></div>
                                    </div>
                                    <div class="w-20 text-xs text-right text-green-600 dark:text-green-400 font-medium">
                                        <MoneyDisplay
                                            :value="day.vat_amount"
                                            :currency="currency"
                                            :decimals="currencyData.decimal_separator"
                                            variant="positive"
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

                <!-- VAT Profile Breakdown Chart -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">VAT by Profile</h3>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <div
                            v-for="profile in vatProfileBreakdown"
                            :key="profile.vat_profile_id"
                            class="space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                        {{ profile.vat_profile_name }}
                                    </span>
                                    <span class="text-xs text-muted-foreground dark:text-muted-foreground">
                                        ({{ profile.vat_profile_percentage }}%)
                                    </span>
                                    <span v-if="profile.vat_profile_code" class="text-xs text-muted-foreground dark:text-muted-foreground">
                                        {{ profile.vat_profile_code }}
                                    </span>
                                </div>
                                <span class="text-xs text-muted-foreground dark:text-muted-foreground">
                                    {{ profile.transaction_count }} transactions
                                </span>
                            </div>
                            <!-- VAT Bar -->
                            <div class="flex items-center gap-2">
                                <div class="w-16 text-xs text-muted-foreground dark:text-muted-foreground">VAT</div>
                                <div class="flex-1 relative h-4 bg-muted dark:bg-muted/50 rounded overflow-hidden">
                                    <div
                                        class="h-full bg-green-500 dark:bg-green-600 transition-all"
                                        :style="{ width: `${getBarHeight(profile.total_vat_amount, maxVatProfileValue)}%` }"
                                    ></div>
                                </div>
                                <div class="w-24 text-xs text-right text-green-600 dark:text-green-400 font-medium">
                                    <MoneyDisplay
                                        :value="profile.total_vat_amount"
                                        :currency="currency"
                                        :decimals="currencyData.decimal_separator"
                                        variant="positive"
                                        size="xs"
                                        :show-symbol="false"
                                    />
                                </div>
                            </div>
                            <!-- Base Amount (smaller) -->
                            <div class="flex items-center gap-2 pl-16">
                                <div class="flex-1 relative h-2 bg-muted dark:bg-muted/50 rounded overflow-hidden">
                                    <div
                                        class="h-full bg-blue-500/50 dark:bg-blue-600/50 transition-all"
                                        :style="{ width: `${getBarHeight(profile.total_base_amount, maxBaseAmount)}%` }"
                                    ></div>
                                </div>
                                <div class="w-24 text-xs text-right text-muted-foreground dark:text-muted-foreground">
                                    Base: <MoneyDisplay
                                        :value="profile.total_base_amount"
                                        :currency="currency"
                                        :decimals="currencyData.decimal_separator"
                                        variant="neutral"
                                        size="xs"
                                        :show-symbol="false"
                                    />
                                </div>
                            </div>
                        </div>
                        <div v-if="vatProfileBreakdown.length === 0" class="text-center text-muted-foreground dark:text-muted-foreground py-8">
                            No VAT profiles found
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Breakdown -->
            <div v-if="categoryBreakdown.length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">VAT by Category</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="category in categoryBreakdown"
                        :key="category.category_id"
                        class="rounded-lg border border-border dark:border-border bg-muted/30 dark:bg-muted/20 p-4"
                    >
                        <div class="flex items-center gap-2 mb-3">
                            <div
                                v-if="category.category_color"
                                class="w-3 h-3 rounded-full"
                                :style="{ backgroundColor: category.category_color }"
                            ></div>
                            <h4 class="font-semibold text-card-foreground dark:text-card-foreground">
                                {{ category.category_name }}
                            </h4>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground dark:text-muted-foreground">VAT:</span>
                                <MoneyDisplay
                                    :value="category.total_vat_amount"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    variant="positive"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground dark:text-muted-foreground">Base:</span>
                                <MoneyDisplay
                                    :value="category.total_base_amount"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    variant="neutral"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground dark:text-muted-foreground">Total:</span>
                                <MoneyDisplay
                                    :value="category.total_amount_with_vat"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    variant="positive"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between text-xs text-muted-foreground dark:text-muted-foreground mt-2 pt-2 border-t border-border dark:border-border">
                                <span>Transactions:</span>
                                <span>{{ category.transaction_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Marea Breakdown -->
            <div v-if="mareaBreakdown.length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">VAT by Marea</h3>
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
                                <span class="text-muted-foreground dark:text-muted-foreground">VAT:</span>
                                <MoneyDisplay
                                    :value="marea.total_vat_amount"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    variant="positive"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground dark:text-muted-foreground">Base:</span>
                                <MoneyDisplay
                                    :value="marea.total_base_amount"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    variant="neutral"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground dark:text-muted-foreground">Total:</span>
                                <MoneyDisplay
                                    :value="marea.total_amount_with_vat"
                                    :currency="currency"
                                    :decimals="currencyData.decimal_separator"
                                    variant="positive"
                                    size="sm"
                                />
                            </div>
                            <div class="flex justify-between text-xs text-muted-foreground dark:text-muted-foreground mt-2 pt-2 border-t border-border dark:border-border">
                                <span>Transactions:</span>
                                <span>{{ marea.transaction_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions List -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">All Transactions with VAT</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border dark:border-border">
                                <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">Date</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">Transaction</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">Description</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">Category</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">VAT Profile</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">Base Amount</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">VAT</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-muted-foreground dark:text-muted-foreground">Total</th>
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
                                        {{ transaction.category.name }}
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
                                    No transactions with VAT found for this period
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="transactions.length > 0" class="bg-muted/30 dark:bg-muted/20">
                            <tr>
                                <td colspan="5" class="py-3 px-4 text-sm font-semibold text-card-foreground dark:text-card-foreground">
                                    Total
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
</template>

