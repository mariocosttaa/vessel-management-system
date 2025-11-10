<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import vatReports from '@/routes/panel/vat-reports';

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

interface MonthYearCombination {
    month: number;
    year: number;
    month_label: string;
    count: number;
    total_vat: number;
}

interface Props {
    monthYearCombinations: MonthYearCombination[];
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

// Default currency (usually EUR, but can be from vessel settings)
const defaultCurrency = computed(() => {
    return (page.props as any)?.defaultCurrency || 'EUR';
});

const currencyData = computed(() => getCurrencyData(defaultCurrency.value));

// Navigate to month/year page
const viewMonthYear = (month: number, year: number) => {
    const vesselId = getCurrentVesselId();
    const url = vatReports.show.url({
        vessel: vesselId,
        year: year,
        month: month
    });
    router.visit(url, {
        preserveState: false,
        preserveScroll: false,
    });
};

// Group month/year combinations by year
const groupedByYear = computed(() => {
    const groups: Record<number, MonthYearCombination[]> = {};

    props.monthYearCombinations.forEach(item => {
        if (!groups[item.year]) {
            groups[item.year] = [];
        }
        groups[item.year].push(item);
    });

    // Sort months within each year (descending)
    Object.keys(groups).forEach(year => {
        groups[parseInt(year)].sort((a, b) => b.month - a.month);
    });

    // Convert to array and sort years descending
    return Object.keys(groups)
        .map(year => ({
            year: parseInt(year),
            months: groups[parseInt(year)],
        }))
        .sort((a, b) => b.year - a.year);
});

</script>

<template>
    <Head title="VAT Reports" />

    <VesselLayout :breadcrumbs="[
        { title: 'VAT Reports', href: vatReports.index.url({ vessel: getCurrentVesselId() }) }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div>
                    <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">VAT Reports</h1>
                    <p class="text-muted-foreground dark:text-muted-foreground mt-1">Select a month and year to view detailed VAT reports</p>
                </div>
            </div>

            <!-- Month/Year Cards -->
            <div v-if="monthYearCombinations.length > 0" class="space-y-6">
                <div v-for="yearGroup in groupedByYear" :key="yearGroup.year" class="space-y-4">
                    <!-- Year Header -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground">{{ yearGroup.year }}</h2>
                    </div>

                    <!-- Month Cards Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                        <button
                            v-for="item in yearGroup.months"
                            :key="`${item.year}-${item.month}`"
                            @click="viewMonthYear(item.month, item.year)"
                            class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6 text-left transition-all hover:shadow-lg hover:border-primary/50 cursor-pointer"
                        >
                            <div class="flex flex-col items-center justify-center text-center">
                                <div class="text-2xl text-card-foreground dark:text-card-foreground mb-2">
                                    {{ item.month_label }}
                                </div>
                                <div class="text-sm text-muted-foreground dark:text-muted-foreground mb-2">
                                    {{ item.count }} {{ item.count === 1 ? 'transaction' : 'transactions' }}
                                </div>
                                <div class="text-lg font-semibold text-primary dark:text-primary">
                                    <MoneyDisplay
                                        :value="item.total_vat"
                                        :currency="defaultCurrency"
                                        :decimals="currencyData.decimal_separator"
                                        variant="positive"
                                        size="sm"
                                    />
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- No Data Message -->
            <div v-else class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-12 text-center">
                <p class="text-muted-foreground dark:text-muted-foreground">No VAT reports available</p>
                <p class="text-sm text-muted-foreground dark:text-muted-foreground mt-2">VAT is only calculated for income transactions</p>
            </div>
        </div>
    </VesselLayout>
</template>

