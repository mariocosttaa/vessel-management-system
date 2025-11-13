<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import Icon from '@/components/Icon.vue';
import { Select } from '@/components/ui/select';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useNotifications } from '@/composables/useNotifications';
import { useI18n } from '@/composables/useI18n';

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match /panel/{vesselId}/ where vesselId can be alphanumeric (hashed) or numeric
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

interface RecycleBinItem {
    id: number;
    type: string;
    type_label: string;
    name: string;
    description: string | null;
    deleted_at: string | null;
    category?: string | null;
    amount?: number;
    currency?: string;
    transaction_count?: number;
    status?: string;
}

interface Props {
    items: RecycleBinItem[];
    filters: {
        type: string;
        search: string;
    };
    counts: {
        transactions: number;
        suppliers: number;
        recurring_transactions: number;
        mareas: number;
        maintenances: number;
    };
}

const props = defineProps<Props>();
const { hasPermission, canDelete } = usePermissions();
const { addNotification } = useNotifications();
const { t } = useI18n();

// Check if user has permission to view recycle bin
onMounted(() => {
    if (!hasPermission('recycle_bin.view')) {
        router.visit(`/panel/${getCurrentVesselId()}/dashboard`, {
            replace: true,
        });
    }
});

// Permission checks
const canRestore = (resource: string) => hasPermission(`${resource}.restore`);
const canDeleteRecycleBin = (resource: string) => hasPermission(`${resource}.delete`);

// Filters
const typeFilter = ref(props.filters.type || 'all');
const search = ref(props.filters.search || '');

// Convert to Select component options format
const typeOptions = computed(() => {
    return [
        { value: 'all', label: t('All Types') },
        { value: 'transaction', label: t('Transactions') },
        { value: 'supplier', label: t('Suppliers') },
        { value: 'recurring_transaction', label: t('Recurring Transactions') },
        { value: 'marea', label: t('Mareas') },
        { value: 'maintenance', label: t('Maintenances') }
    ];
});

// Confirmation dialogs
const showRestoreDialog = ref(false);
const showDeleteDialog = ref(false);
const showEmptyDialog = ref(false);
const itemToRestore = ref<RecycleBinItem | null>(null);
const itemToDelete = ref<RecycleBinItem | null>(null);
const isProcessing = ref(false);

// Filtered items
const filteredItems = computed(() => {
    let items = props.items;

    // Filter by type
    if (typeFilter.value !== 'all') {
        items = items.filter(item => item.type === typeFilter.value);
    }

    // Filter by search
    if (search.value) {
        const searchLower = search.value.toLowerCase();
        items = items.filter(item =>
            item.name.toLowerCase().includes(searchLower) ||
            (item.description && item.description.toLowerCase().includes(searchLower))
        );
    }

    return items;
});

// Apply filters
const applyFilters = () => {
    router.get(`/panel/${getCurrentVesselId()}/recycle-bin`, {
        type: typeFilter.value,
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Clear filters
const clearFilters = () => {
    typeFilter.value = 'all';
    search.value = '';
    router.get(`/panel/${getCurrentVesselId()}/recycle-bin`, {}, {
        preserveState: true,
        replace: true,
    });
};

// Restore item
const handleRestore = (item: RecycleBinItem) => {
    itemToRestore.value = item;
    showRestoreDialog.value = true;
};

const confirmRestore = () => {
    if (!itemToRestore.value) return;

    isProcessing.value = true;
    const item = itemToRestore.value;

    router.post(`/panel/${getCurrentVesselId()}/recycle-bin/${item.type}/${item.id}/restore`, {}, {
        onSuccess: () => {
            showRestoreDialog.value = false;
            itemToRestore.value = null;
            isProcessing.value = false;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: `${item.type_label} '${item.name}' ${t('has been restored successfully')}.`,
            });
        },
        onError: () => {
            isProcessing.value = false;
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to restore item. Please try again.'),
            });
        },
    });
};

// Delete item permanently
const handleDelete = (item: RecycleBinItem) => {
    itemToDelete.value = item;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!itemToDelete.value) return;

    isProcessing.value = true;
    const item = itemToDelete.value;

    router.delete(`/panel/${getCurrentVesselId()}/recycle-bin/${item.type}/${item.id}`, {
        onSuccess: () => {
            showDeleteDialog.value = false;
            itemToDelete.value = null;
            isProcessing.value = false;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: `${item.type_label} '${item.name}' ${t('has been permanently deleted')}.`,
            });
        },
        onError: () => {
            isProcessing.value = false;
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to delete item. Please try again.'),
            });
        },
    });
};

// Empty recycle bin
const handleEmpty = () => {
    showEmptyDialog.value = true;
};

const confirmEmpty = () => {
    isProcessing.value = true;

    router.post(`/panel/${getCurrentVesselId()}/recycle-bin/empty`, {}, {
        onSuccess: () => {
            showEmptyDialog.value = false;
            isProcessing.value = false;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Recycle bin has been emptied successfully.'),
            });
        },
        onError: () => {
            isProcessing.value = false;
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to empty recycle bin. Please try again.'),
            });
        },
    });
};

// Format date
const formatDate = (dateString: string | null) => {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Get type badge color
const getTypeColor = (type: string) => {
    switch (type) {
        case 'transaction':
            return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300';
        case 'supplier':
            return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300';
        case 'recurring_transaction':
            return 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300';
        case 'marea':
            return 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300';
        case 'maintenance':
            return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

// Total count
const totalCount = computed(() => {
    return props.counts.transactions + props.counts.suppliers + props.counts.recurring_transactions + props.counts.mareas + props.counts.maintenances;
});
</script>

<template>
    <Head :title="t('Recycle Bin')" />

    <VesselLayout :breadcrumbs="[{ title: t('Recycle Bin'), href: `/panel/${getCurrentVesselId()}/recycle-bin` }]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">{{ t('Recycle Bin') }}</h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">
                            {{ t('Manage deleted items. Restore or permanently delete them.') }}
                        </p>
                    </div>
                    <div v-if="totalCount > 0 && canDeleteRecycleBin('recycle_bin')" class="flex gap-3">
                        <button
                            @click="handleEmpty"
                            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <Icon name="trash-2" class="w-4 h-4 mr-2" />
                            {{ t('Empty Recycle Bin') }}
                        </button>
                    </div>
                </div>

                <!-- Summary -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800">
                        <div class="text-sm font-medium text-blue-800 dark:text-blue-300 mb-1">{{ t('Transactions') }}</div>
                        <div class="text-2xl font-bold text-blue-900 dark:text-blue-200">{{ counts.transactions }}</div>
                    </div>
                    <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800">
                        <div class="text-sm font-medium text-green-800 dark:text-green-300 mb-1">{{ t('Suppliers') }}</div>
                        <div class="text-2xl font-bold text-green-900 dark:text-green-200">{{ counts.suppliers }}</div>
                    </div>
                    <div class="p-4 rounded-lg bg-purple-50 dark:bg-purple-900/10 border border-purple-200 dark:border-purple-800">
                        <div class="text-sm font-medium text-purple-800 dark:text-purple-300 mb-1">{{ t('Recurring Transactions') }}</div>
                        <div class="text-2xl font-bold text-purple-900 dark:text-purple-200">{{ counts.recurring_transactions }}</div>
                    </div>
                    <div class="p-4 rounded-lg bg-orange-50 dark:bg-orange-900/10 border border-orange-200 dark:border-orange-800">
                        <div class="text-sm font-medium text-orange-800 dark:text-orange-300 mb-1">{{ t('Mareas') }}</div>
                        <div class="text-2xl font-bold text-orange-900 dark:text-orange-200">{{ counts.mareas }}</div>
                    </div>
                    <div class="p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/10 border border-yellow-200 dark:border-yellow-800">
                        <div class="text-sm font-medium text-yellow-800 dark:text-yellow-300 mb-1">{{ t('Maintenances') }}</div>
                        <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-200">{{ counts.maintenances }}</div>
                    </div>
                    <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-900/10 border border-gray-200 dark:border-gray-800">
                        <div class="text-sm font-medium text-gray-800 dark:text-gray-300 mb-1">{{ t('Total') }}</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-200">{{ totalCount }}</div>
                    </div>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Type Filter -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                            {{ t('Type') }}
                        </label>
                        <Select
                            v-model="typeFilter"
                            :options="typeOptions"
                            :placeholder="t('All Types')"
                            @update:model-value="applyFilters"
                        />
                    </div>

                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                            {{ t('Search') }}
                        </label>
                        <input
                            v-model="search"
                            @input="applyFilters"
                            type="text"
                            :placeholder="t('Search deleted items...')"
                            class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                        />
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="flex gap-3 mt-4">
                    <button
                        @click="applyFilters"
                        class="px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
                    >
                        {{ t('Apply Filters') }}
                    </button>
                    <button
                        @click="clearFilters"
                        class="px-4 py-2 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground font-medium transition-colors"
                    >
                        {{ t('Clear Filters') }}
                    </button>
                </div>
            </div>

            <!-- Items List -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card overflow-hidden">
                <div v-if="filteredItems.length === 0" class="px-6 py-12 text-center text-muted-foreground dark:text-muted-foreground">
                    <Icon name="trash-2" class="w-12 h-12 mx-auto mb-4 opacity-50" />
                    <p class="text-lg font-medium mb-2">{{ t('No deleted items found') }}</p>
                    <p class="text-sm">{{ t('The recycle bin is empty or no items match your filters.') }}</p>
                </div>

                <div v-else class="divide-y divide-border dark:divide-border">
                    <div
                        v-for="item in filteredItems"
                        :key="`${item.type}-${item.id}`"
                        class="px-6 py-4 transition-all hover:bg-muted/30 dark:hover:bg-muted/20"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center space-x-4 flex-1 min-w-0">
                                <!-- Type Badge -->
                                <span
                                    :class="[
                                        'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                                        getTypeColor(item.type)
                                    ]"
                                >
                                    {{ item.type_label }}
                                </span>

                                <!-- Item Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                            {{ item.name }}
                                        </span>
                                        <span v-if="item.category" class="text-xs text-muted-foreground dark:text-muted-foreground">
                                            • {{ item.category }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex items-center gap-4 text-xs text-muted-foreground dark:text-muted-foreground">
                                        <span v-if="item.description" class="truncate">
                                            {{ item.description }}
                                        </span>
                                        <span>{{ t('Deleted') }}: {{ formatDate(item.deleted_at) }}</span>
                                        <span v-if="item.transaction_count !== undefined && item.transaction_count > 0" class="text-orange-600 dark:text-orange-400">
                                            {{ item.transaction_count }} {{ item.transaction_count === 1 ? t('transaction') : t('transactions') }}
                                        </span>
                                    </div>
                                    <div v-if="item.amount !== undefined && item.currency" class="mt-1">
                                        <MoneyDisplay
                                            :value="item.amount"
                                            :currency="item.currency"
                                            :variant="item.type === 'transaction' ? 'neutral' : 'neutral'"
                                            size="sm"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button
                                    v-if="canRestore('recycle_bin')"
                                    @click="handleRestore(item)"
                                    class="inline-flex items-center px-3 py-1.5 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
                                >
                                    <Icon name="rotate-ccw" class="w-4 h-4 mr-1" />
                                    {{ t('Restore') }}
                                </button>
                                <button
                                    v-if="canDeleteRecycleBin('recycle_bin')"
                                    @click="handleDelete(item)"
                                    class="inline-flex items-center px-3 py-1.5 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors"
                                >
                                    <Icon name="trash-2" class="w-4 h-4 mr-1" />
                                    {{ t('Delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restore Confirmation Dialog -->
        <ConfirmationDialog
            v-model:open="showRestoreDialog"
            :title="t('Restore Item')"
            :description="t('This will restore the item to its original location.')"
            :message="itemToRestore ? `${t('Are you sure you want to restore')} ${itemToRestore.type_label.toLowerCase()} '${itemToRestore.name}'?` : ''"
            :confirm-text="t('Restore')"
            :cancel-text="t('Cancel')"
            variant="default"
            type="info"
            :loading="isProcessing"
            @confirm="confirmRestore"
            @cancel="showRestoreDialog = false; itemToRestore = null;"
        />

        <!-- Delete Confirmation Dialog -->
        <ConfirmationDialog
            v-model:open="showDeleteDialog"
            :title="t('Permanently Delete Item')"
            :description="t('This action cannot be undone. The item will be permanently removed from the system.')"
            :message="itemToDelete ? `${t('Are you sure you want to permanently delete')} ${itemToDelete.type_label.toLowerCase()} '${itemToDelete.name}'? ${t('This action cannot be undone.')}` : ''"
            :confirm-text="t('Delete Permanently')"
            :cancel-text="t('Cancel')"
            variant="destructive"
            type="danger"
            :loading="isProcessing"
            @confirm="confirmDelete"
            @cancel="showDeleteDialog = false; itemToDelete = null;"
        />

        <!-- Empty Recycle Bin Confirmation Dialog -->
        <ConfirmationDialog
            v-model:open="showEmptyDialog"
            :title="t('Empty Recycle Bin')"
            :description="t('This action cannot be undone. All items in the recycle bin will be permanently deleted.')"
            :message="`${t('Are you sure you want to empty the recycle bin? This will permanently delete')} ${totalCount} ${totalCount === 1 ? t('item') : t('items')}. ${t('This action cannot be undone.')}`"
            :confirm-text="t('Empty Recycle Bin')"
            :cancel-text="t('Cancel')"
            variant="destructive"
            type="danger"
            :loading="isProcessing"
            @confirm="confirmEmpty"
            @cancel="showEmptyDialog = false"
        />
    </VesselLayout>
</template>

