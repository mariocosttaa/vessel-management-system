<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import Icon from '@/components/Icon.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useI18n } from '@/composables/useI18n';
import vatReports from '@/routes/panel/vat-reports';
import ColorSelectionModal from '@/components/modals/Movimentation/ColorSelectionModal.vue';
import PdfLoadingModal from '@/components/modals/PdfLoadingModal.vue';

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed ID (alphanumeric string) or numeric ID
    const vesselMatch = path.match(/\/panel\/([^/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
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

// Permission check
const { hasPermission, isAdmin, isSupervisor } = usePermissions();
const { t } = useI18n();

// Check if user has permission to access reports (only Administrators and Supervisors)
onMounted(() => {
    if (!hasPermission('reports.access') || (!isAdmin.value && !isSupervisor.value)) {
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

// PDF download state
const showColorModal = ref(false);
const showPdfModal = ref(false);
const isDownloading = ref(false);
const selectedMonth = ref<number | null>(null);
const selectedYear = ref<number | null>(null);
let colorPreference = false;

// Open color selection modal before download
const openColorModal = (month: number, year: number) => {
    selectedMonth.value = month;
    selectedYear.value = year;
    showColorModal.value = true;
};

// Handle color selection confirmation
const handleColorConfirm = (enableColors: boolean) => {
    colorPreference = enableColors;
    showColorModal.value = false;
    startDownload();
};

// Start download after color selection
const startDownload = () => {
    showPdfModal.value = true;
    isDownloading.value = true;
};

// Download PDF for specific month/year
const downloadPdfMonth = (month: number, year: number, event?: Event) => {
    if (event) {
        event.stopPropagation();
    }
    openColorModal(month, year);
};

const closePdfModal = () => {
    if (!isDownloading.value) {
        showPdfModal.value = false;
    }
};

const handlePdfDownloadCancel = () => {
    showPdfModal.value = false;
    isDownloading.value = false;
    selectedMonth.value = null;
    selectedYear.value = null;
};

const handlePdfReady = () => {
    if (!isDownloading.value || selectedMonth.value === null || selectedYear.value === null) return;

    const vesselId = getCurrentVesselId();
    const params = new URLSearchParams();
    if (colorPreference) {
        params.append('enable_colors', '1');
    }
    const queryString = params.toString();
    const url = vatReports.pdf.url({
        vessel: vesselId,
        year: selectedYear.value,
        month: selectedMonth.value
    }) + (queryString ? '?' + queryString : '');

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
        selectedMonth.value = null;
        selectedYear.value = null;
    }, 500);
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
    <Head :title="t('VAT Reports')" />

    <VesselLayout v-if="hasPermission('reports.access') && (isAdmin || isSupervisor)" :breadcrumbs="[
        { title: t('VAT Reports'), href: vatReports.index.url({ vessel: getCurrentVesselId() }) }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div>
                    <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">{{ t('VAT Reports') }}</h1>
                    <p class="text-muted-foreground dark:text-muted-foreground mt-1">{{ t('Select a month and year to view detailed VAT reports') }}</p>
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
                                    <div class="text-sm text-muted-foreground dark:text-muted-foreground mb-2">
                                        {{ item.count }} {{ item.count === 1 ? t('transaction') : t('transactions') }}
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
                            <!-- Download PDF Button -->
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
                <p class="text-muted-foreground dark:text-muted-foreground">{{ t('No VAT reports available') }}</p>
                <p class="text-sm text-muted-foreground dark:text-muted-foreground mt-2">{{ t('VAT is only calculated for income transactions') }}</p>
            </div>
        </div>

        <!-- Color Selection Modal -->
        <ColorSelectionModal
            :open="showColorModal"
            @close="showColorModal = false"
            @confirm="handleColorConfirm"
        />

        <!-- PDF Loading Modal -->
        <PdfLoadingModal
            :open="showPdfModal"
            :countdown="5"
            @close="closePdfModal"
            @cancel="handlePdfDownloadCancel"
            @ready="handlePdfReady"
        />
    </VesselLayout>
    <VesselLayout v-else :breadcrumbs="[]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-12 text-center">
                <p class="text-muted-foreground dark:text-muted-foreground">{{ t('You do not have permission to view VAT reports.') }}</p>
            </div>
        </div>
    </VesselLayout>
</template>

