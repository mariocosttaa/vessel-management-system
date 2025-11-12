<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { useI18n } from '@/composables/useI18n';
import PdfLoadingModal from '@/components/modals/PdfLoadingModal.vue';
import ColorSelectionModal from '@/components/modals/Transaction/ColorSelectionModal.vue';
import Icon from '@/components/Icon.vue';
import transactions from '@/routes/panel/transactions';

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel IDs (alphanumeric strings) or numeric IDs
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
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
const { t } = useI18n();

// PDF download state
const showPdfModal = ref(false);
const showColorModal = ref(false);
const isDownloading = ref(false);
let downloadTimeout: ReturnType<typeof setTimeout> | null = null;

// Color preference (set by color selection modal)
let colorPreference = false;

// Check if user has permission to access transaction history
onMounted(() => {
    if (!hasPermission('reports.access')) {
        router.visit(`/panel/${getCurrentVesselId()}/dashboard`, {
            replace: true,
        });
    }
});

// Open color selection modal before download
const openColorModal = () => {
    showColorModal.value = true;
};

// Handle color selection confirmation
const handleColorConfirm = (enableColors: boolean) => {
    colorPreference = enableColors;
    showColorModal.value = false;

    // Check if it's a month download or all transactions download
    if (currentDownloadMonth.value !== null && currentDownloadYear.value !== null) {
        startDownloadMonth();
    } else {
        startDownload();
    }
};

// Start download after color selection
const startDownload = () => {
    showPdfModal.value = true;
    isDownloading.value = true;

    // Wait 5 seconds before starting download
    downloadTimeout = setTimeout(() => {
        if (!isDownloading.value) return; // Canceled

        const vesselId = getCurrentVesselId();
        const params = new URLSearchParams();
        if (colorPreference) {
            params.append('enable_colors', '1');
        }
        const queryString = params.toString();
        const url = `/panel/${vesselId}/transactions/history/download-pdf${queryString ? '?' + queryString : ''}`;

        // Create a temporary link to trigger download
        const link = document.createElement('a');
        link.href = url;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Close modal after a short delay
        setTimeout(() => {
            showPdfModal.value = false;
            isDownloading.value = false;
            downloadTimeout = null;
        }, 500);
    }, 5000);
};

// Download PDF for all transactions
const downloadPdf = () => {
    openColorModal();
};

// Store current download month/year
const currentDownloadMonth = ref<number | null>(null);
const currentDownloadYear = ref<number | null>(null);

// Download PDF for specific month/year
const downloadPdfMonth = (month: number, year: number, event: Event) => {
    // Prevent navigation when clicking download button
    event.stopPropagation();

    // Store month/year for later use
    currentDownloadMonth.value = month;
    currentDownloadYear.value = year;

    openColorModal();
};

// Start download for specific month after color selection
const startDownloadMonth = () => {
    if (currentDownloadMonth.value === null || currentDownloadYear.value === null) return;

    showPdfModal.value = true;
    isDownloading.value = true;

    // Wait 5 seconds before starting download
    downloadTimeout = setTimeout(() => {
        if (!isDownloading.value) return; // Canceled

        const vesselId = getCurrentVesselId();
        const params = new URLSearchParams();
        if (colorPreference) {
            params.append('enable_colors', '1');
        }
        const queryString = params.toString();
        const url = `/panel/${vesselId}/transactions/history/${currentDownloadYear.value}/${currentDownloadMonth.value}/download-pdf${queryString ? '?' + queryString : ''}`;

        // Create a temporary link to trigger download
        const link = document.createElement('a');
        link.href = url;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Close modal after a short delay
        setTimeout(() => {
            showPdfModal.value = false;
            isDownloading.value = false;
            downloadTimeout = null;
            currentDownloadMonth.value = null;
            currentDownloadYear.value = null;
        }, 500);
    }, 5000);
};

const closePdfModal = () => {
    if (!isDownloading.value) {
        showPdfModal.value = false;
    }
};

const handlePdfDownloadCancel = () => {
    if (downloadTimeout) {
        clearTimeout(downloadTimeout);
        downloadTimeout = null;
    }
    showPdfModal.value = false;
    isDownloading.value = false;
};

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
    <Head :title="t('Transaction History')" />

    <VesselLayout v-if="hasPermission('reports.access')" :breadcrumbs="[
        { title: t('Transactions'), href: transactions.index.url({ vessel: getCurrentVesselId() }) },
        { title: t('History'), href: `/panel/${getCurrentVesselId()}/transactions/history` }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">{{ t('Transaction History') }}</h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">{{ t('Select a month and year to view transactions') }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Download Button -->
                        <button
                            v-if="monthYearCombinations.length > 0"
                            @click="downloadPdf"
                            class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
                        >
                            <Icon name="download" class="w-4 h-4 mr-2" />
                            {{ t('Download PDF') }}
                        </button>
                    </div>
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
                        <div
                            v-for="item in yearGroup.months"
                            :key="`${item.year}-${item.month}`"
                            class="relative rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card transition-all hover:shadow-lg hover:border-primary/50"
                        >
                            <button
                                @click="viewMonthYear(item.month, item.year)"
                                class="w-full p-6 text-left cursor-pointer"
                            >
                                <div class="flex flex-col items-center justify-center text-center">
                                    <div class="text-2xl text-card-foreground dark:text-card-foreground mb-2">
                                        {{ item.month_label }}
                                    </div>
                                    <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                        {{ item.count }} {{ item.count === 1 ? t('transaction') : t('transactions') }}
                                    </div>
                                </div>
                            </button>
                            <!-- Download Button -->
                            <button
                                @click="downloadPdfMonth(item.month, item.year, $event)"
                                class="absolute top-2 right-2 p-2 rounded-lg bg-primary/10 hover:bg-primary/20 text-primary transition-colors"
                                :title="t('Download PDF')"
                            >
                                <Icon name="download" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Data Message -->
            <div v-else class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-12 text-center">
                <p class="text-muted-foreground dark:text-muted-foreground">{{ t('No transaction history available') }}</p>
            </div>
        </div>

        <!-- Color Selection Modal -->
        <ColorSelectionModal
            :open="showColorModal"
            @close="() => { showColorModal = false; currentDownloadMonth = null; currentDownloadYear = null; }"
            @confirm="handleColorConfirm"
        />

        <!-- PDF Loading Modal -->
        <PdfLoadingModal
            :open="showPdfModal"
            :countdown="5"
            @close="closePdfModal"
            @cancel="handlePdfDownloadCancel"
        />
    </VesselLayout>
    <VesselLayout v-else :breadcrumbs="[]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-12 text-center">
                <p class="text-muted-foreground dark:text-muted-foreground">{{ t('You do not have permission to view transaction history.') }}</p>
            </div>
        </div>
    </VesselLayout>
</template>
