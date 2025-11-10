<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import transactions from '@/routes/panel/transactions';

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
}

interface Props {
    monthYearCombinations: MonthYearCombination[];
}

const props = defineProps<Props>();

// Permission check
const { hasPermission } = usePermissions();

// Check if user has permission to access transaction history
onMounted(() => {
    if (!hasPermission('reports.access')) {
        router.visit(`/panel/${getCurrentVesselId()}/dashboard`, {
            replace: true,
        });
    }
});

// Navigate to month/year page
const viewMonthYear = (month: number, year: number) => {
    const vesselId = getCurrentVesselId();
    const url = transactions.history.month.url({
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
    <Head title="Transaction History" />

    <VesselLayout v-if="hasPermission('reports.access')" :breadcrumbs="[
        { title: 'Transactions', href: transactions.index.url({ vessel: getCurrentVesselId() }) },
        { title: 'History', href: `/panel/${getCurrentVesselId()}/transactions/history` }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div>
                    <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">Transaction History</h1>
                    <p class="text-muted-foreground dark:text-muted-foreground mt-1">Select a month and year to view transactions</p>
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
                                <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                    {{ item.count }} {{ item.count === 1 ? 'transaction' : 'transactions' }}
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- No Data Message -->
            <div v-else class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-12 text-center">
                <p class="text-muted-foreground dark:text-muted-foreground">No transaction history available</p>
            </div>
        </div>
    </VesselLayout>
    <VesselLayout v-else :breadcrumbs="[]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-12 text-center">
                <p class="text-muted-foreground dark:text-muted-foreground">You do not have permission to view transaction history.</p>
            </div>
        </div>
    </VesselLayout>
</template>
