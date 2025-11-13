<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/ui/Pagination.vue';
import { DateInput } from '@/components/ui/date-input';
import { Select } from '@/components/ui/select';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useNotifications } from '@/composables/useNotifications';
import { useI18n } from '@/composables/useI18n';
import mareas from '@/routes/panel/mareas';
import MareaCreateModal from '@/components/modals/Marea/create.vue';

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel IDs (alphanumeric strings) or numeric IDs
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

interface Marea {
    id: number;
    marea_number: string;
    name: string | null;
    description: string | null;
    status: string;
    estimated_departure_date: string | null;
    estimated_return_date: string | null;
    actual_departure_date: string | null;
    actual_return_date: string | null;
    total_income: number;
    total_expenses: number;
    net_result: number;
    created_at: string | null;
    transaction_count?: number;
}

interface Props {
    mareas: {
        data: Marea[];
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
        sort?: string;
        direction?: string;
    };
    statuses: Record<string, string>;
    defaultCurrency?: string;
}

const props = defineProps<Props>();
const { canCreate, canEdit, canDelete } = usePermissions();
const { addNotification } = useNotifications();
const { t } = useI18n();

// Confirmation dialog state
const showDeleteDialog = ref(false);
const mareaToDelete = ref<Marea | null>(null);
const isDeleting = ref(false);

// Create modal state
const showCreateModal = ref(false);

// Dropdown state
const openDropdownId = ref<number | null>(null);

// Dropdown methods
const toggleActionsDropdown = (mareaId: number) => {
    openDropdownId.value = openDropdownId.value === mareaId ? null : mareaId;
};

// Sorting - default to created_at descending (newest first)
const sortField = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');

// Search and filters
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

// Convert to Select component options format
const statusOptions = computed(() => {
    const options = [{ value: '', label: t('All Statuses') }];
    Object.entries(props.statuses).forEach(([value, label]) => {
        options.push({ value, label: label as string });
    });
    return options;
});
const dateFromFilter = ref(props.filters.date_from || '');
const dateToFilter = ref(props.filters.date_to || '');

const filters = computed(() => {
    const filterObj: Record<string, any> = {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        date_from: dateFromFilter.value || undefined,
        date_to: dateToFilter.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    };

    // Remove undefined values
    Object.keys(filterObj).forEach(key => {
        if (filterObj[key] === undefined) {
            delete filterObj[key];
        }
    });

    return filterObj;
});

const applyFilters = () => {
    router.get(mareas.index.url({ vessel: getCurrentVesselId() }), filters.value, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    statusFilter.value = '';
    dateFromFilter.value = '';
    dateToFilter.value = '';
    sortField.value = 'created_at';
    sortDirection.value = 'desc';

    router.get(mareas.index.url({ vessel: getCurrentVesselId() }), {}, {
        preserveState: true,
        replace: true,
    });
};

// Sorting
const handleSort = (field: string) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }

    router.get(mareas.index.url({ vessel: getCurrentVesselId() }), {
        ...filters.value,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Delete functions
const deleteMarea = (marea: Marea) => {
    mareaToDelete.value = marea;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!mareaToDelete.value) return;

    const mareaNumber = mareaToDelete.value.marea_number;
    isDeleting.value = true;

    router.delete(mareas.destroy.url({ vessel: getCurrentVesselId(), mareaId: mareaToDelete.value.id }), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            mareaToDelete.value = null;
            isDeleting.value = false;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: `${t('Marea')} '${mareaNumber}' ${t('has been deleted successfully')}.`,
            });
        },
        onError: () => {
            isDeleting.value = false;
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to delete marea. Please try again.'),
            });
        },
    });
};

const cancelDelete = () => {
    showDeleteDialog.value = false;
    mareaToDelete.value = null;
    isDeleting.value = false;
};

// Get status badge color
const getStatusColor = (status: string) => {
    switch (status) {
        case 'preparing':
            return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300';
        case 'at_sea':
            return 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300';
        case 'returned':
            return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300';
        case 'closed':
            return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300';
        case 'cancelled':
            return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

// Format date
const formatDate = (dateString: string | null) => {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Get default currency from props (vessel settings)
const defaultCurrency = computed(() => props.defaultCurrency || 'EUR');

// Translate statuses
const translatedStatuses = computed(() => {
    const translated: Record<string, string> = {};
    Object.entries(props.statuses).forEach(([key, value]) => {
        // Try to translate the status value, fallback to the original value
        translated[key] = t(value as string) || value as string;
    });
    return translated;
});
</script>

<template>
    <Head :title="t('Mareas')" />

    <VesselLayout :breadcrumbs="[{ title: t('Mareas'), href: mareas.index.url({ vessel: getCurrentVesselId() }) }]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">{{ t('Mareas') }}</h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">{{ t('Manage expeditions and trips for your vessel') }}</p>
                    </div>
                    <div v-if="canCreate('mareas')" class="flex gap-3">
                        <button
                            @click="showCreateModal = true"
                            class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
                        >
                            <Icon name="plus" class="w-4 h-4 mr-2" />
                            {{ t('New Marea') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <Icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="t('Search mareas...')"
                                @keyup.enter="applyFilters"
                                class="w-full pl-10 pr-4 py-2 text-sm border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-colors"
                            />
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="min-w-[140px]">
                        <Select
                            v-model="statusFilter"
                            :options="statusOptions"
                            :placeholder="t('All Statuses')"
                            searchable
                        />
                    </div>

                    <!-- Date From -->
                    <div class="min-w-[140px]">
                        <DateInput v-model="dateFromFilter" />
                    </div>

                    <!-- Date To -->
                    <div class="min-w-[140px]">
                        <DateInput v-model="dateToFilter" />
                    </div>

                    <!-- Apply Filters Button -->
                    <button
                        @click="applyFilters"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg transition-colors"
                    >
                        <Icon name="check" class="h-4 w-4" />
                        {{ t('Apply') }}
                    </button>

                    <!-- Clear Filters Button -->
                    <button
                        @click="clearFilters"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border border-input dark:border-input rounded-lg bg-background dark:bg-background hover:bg-muted/50 text-muted-foreground hover:text-foreground transition-colors"
                    >
                        <Icon name="x" class="h-4 w-4" />
                        {{ t('Clear') }}
                    </button>
                </div>
            </div>

            <!-- Mareas List -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card overflow-hidden">
                <div v-if="!props.mareas || !props.mareas.data || !Array.isArray(props.mareas.data) || props.mareas.data.length === 0"
                     class="px-6 py-12 text-center text-muted-foreground dark:text-muted-foreground">
                    {{ t('No mareas found') }}
                </div>

                <div v-else class="divide-y divide-border dark:divide-border">
                    <div
                        v-for="marea in props.mareas.data"
                        :key="marea.id"
                        class="px-6 py-4 transition-all hover:bg-muted/30 dark:hover:bg-muted/20 cursor-pointer"
                        @click="router.visit(mareas.show.url({ vessel: getCurrentVesselId(), mareaId: marea.id }))"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center space-x-4 flex-1 min-w-0">
                                <!-- Marea Number and Name -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                            {{ marea.marea_number }}
                                        </span>
                                        <span
                                            v-if="marea.name"
                                            class="text-sm text-muted-foreground dark:text-muted-foreground"
                                        >
                                            {{ marea.name }}
                                        </span>
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2 py-1 rounded-md text-xs font-medium',
                                                getStatusColor(marea.status)
                                            ]"
                                        >
                                            {{ translatedStatuses[marea.status] || t(marea.status) || marea.status }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex items-center gap-4 text-xs text-muted-foreground dark:text-muted-foreground">
                                        <span v-if="marea.estimated_departure_date">
                                            {{ t('Est. Departure') }}: {{ formatDate(marea.estimated_departure_date) }}
                                        </span>
                                        <span v-if="marea.actual_departure_date">
                                            {{ t('Actual Departure') }}: {{ formatDate(marea.actual_departure_date) }}
                                        </span>
                                        <span v-if="marea.actual_return_date">
                                            {{ t('Return') }}: {{ formatDate(marea.actual_return_date) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Summary -->
                            <div class="flex items-center gap-6 ml-4">
                                <div class="text-right">
                                    <div class="text-xs text-muted-foreground dark:text-muted-foreground mb-1">{{ t('Income') }}</div>
                                    <MoneyDisplay
                                        :value="marea.total_income"
                                        :currency="defaultCurrency"
                                        variant="positive"
                                        size="sm"
                                        class="font-semibold"
                                    />
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-muted-foreground dark:text-muted-foreground mb-1">{{ t('Expenses') }}</div>
                                    <MoneyDisplay
                                        :value="marea.total_expenses"
                                        :currency="defaultCurrency"
                                        variant="negative"
                                        size="sm"
                                        class="font-semibold"
                                    />
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-muted-foreground dark:text-muted-foreground mb-1">{{ t('Net Result') }}</div>
                                    <MoneyDisplay
                                        :value="marea.net_result"
                                        :currency="defaultCurrency"
                                        :variant="marea.net_result >= 0 ? 'positive' : 'negative'"
                                        size="sm"
                                        class="font-semibold"
                                    />
                                </div>
                            </div>

                            <!-- Actions Dropdown -->
                            <div
                                v-if="canEdit('mareas') || canDelete('mareas')"
                                @click.stop
                                class="relative dropdown-container flex-shrink-0"
                            >
                                <button
                                    @click.stop="toggleActionsDropdown(marea.id)"
                                    class="flex items-center justify-center w-8 h-8 rounded-full hover:bg-background dark:hover:bg-background transition-colors"
                                >
                                    <Icon name="menu" class="w-4 h-4 text-muted-foreground dark:text-muted-foreground" />
                                </button>

                                <!-- Actions Dropdown Menu -->
                                <div
                                    v-if="openDropdownId === marea.id"
                                    @click.stop
                                    class="absolute right-0 mt-2 w-48 bg-card dark:bg-card border border-border dark:border-border rounded-lg shadow-lg z-50"
                                >
                                    <div class="py-1">
                                        <button
                                            @click.stop="router.visit(mareas.show.url({ vessel: getCurrentVesselId(), mareaId: marea.id }))"
                                            class="flex items-center w-full px-4 py-2 text-sm text-card-foreground dark:text-card-foreground hover:bg-muted dark:hover:bg-muted transition-colors"
                                        >
                                            <Icon name="eye" class="w-4 h-4 mr-3" />
                                            {{ t('View Details') }}
                                        </button>
                                        <button
                                            v-if="canEdit('mareas') && marea.status !== 'closed' && marea.status !== 'cancelled'"
                                            @click.stop="router.visit(mareas.edit.url({ vessel: getCurrentVesselId(), mareaId: marea.id }))"
                                            class="flex items-center w-full px-4 py-2 text-sm text-card-foreground dark:text-card-foreground hover:bg-muted dark:hover:bg-muted transition-colors"
                                        >
                                            <Icon name="edit" class="w-4 h-4 mr-3" />
                                            {{ t('Edit Marea') }}
                                        </button>
                                        <button
                                            v-if="canDelete('mareas')"
                                            @click.stop="deleteMarea(marea)"
                                            class="flex items-center w-full px-4 py-2 text-sm text-destructive dark:text-destructive hover:bg-muted dark:hover:bg-muted transition-colors"
                                        >
                                            <Icon name="trash-2" class="w-4 h-4 mr-3" />
                                            {{ t('Delete Marea') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="mareas.meta && mareas.meta.last_page > 1" class="flex justify-center">
                <Pagination :links="mareas.links" />
            </div>
        </div>

        <!-- Confirmation Dialog -->
        <ConfirmationDialog
            v-model:open="showDeleteDialog"
            :title="t('Delete Marea')"
            :description="t('This action cannot be undone.')"
            :message="mareaToDelete ? `${t('Are you sure you want to delete marea')} '${mareaToDelete.marea_number}'? ${t('This will permanently remove the marea and all')} ${mareaToDelete.transaction_count || 0} ${t('transaction(s) associated with it')}.` : ''"
            :confirm-text="t('Delete Marea')"
            :cancel-text="t('Cancel')"
            variant="destructive"
            type="danger"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />

        <!-- Create Marea Modal -->
        <MareaCreateModal
            v-if="getCurrentVesselId()"
            :open="showCreateModal"
            :vessel-id="getCurrentVesselId()!"
            @update:open="showCreateModal = $event"
            @saved="router.reload()"
        />
    </VesselLayout>
</template>

