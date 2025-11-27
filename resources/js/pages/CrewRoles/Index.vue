<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import Icon from '@/components/Icon.vue';
import DataTable from '@/components/ui/DataTable.vue';
import { Select } from '@/components/ui/select';
import Pagination from '@/components/ui/Pagination.vue';
import CrewRoleCreateModal from '@/components/modals/CrewRole/create.vue';
import CrewRoleUpdateModal from '@/components/modals/CrewRole/update.vue';
import CrewRoleShowModal from '@/components/modals/CrewRole/show.vue';
import PermissionGate from '@/components/PermissionGate.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useI18n } from '@/composables/useI18n';
import { usePage } from '@inertiajs/vue3';

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel IDs (alphanumeric strings) or numeric IDs
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

interface CrewPosition {
    id: number;
    name: string;
    vessel_id?: number | null;
    is_global: boolean;
    scope_label: string;
    is_administrative: boolean;
    crew_members_count?: number;
    created_at: string;
}

interface Props {
    crewPositions: {
        data: CrewPosition[];
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        scope?: string;
        role_type?: string;
        sort?: string;
        direction?: string;
    };
}

const props = defineProps<Props>();

// Permissions
const { can, canView } = usePermissions();
const { t } = useI18n();

// Check if user has permission to view crew roles
onMounted(() => {
    if (!canView('crew-roles')) {
        const vesselId = getCurrentVesselId();
        if (vesselId) {
            router.visit(`/panel/${vesselId}/dashboard`, {
                replace: true,
            });
        }
    }
});

// Computed property for crew positions data
const crewPositionsData = computed(() => props.crewPositions?.data || []);
const paginatedCrewPositions = computed(() => props.crewPositions);

const search = ref(props.filters.search || '');
const scopeFilter = ref(props.filters.scope || '');
const roleTypeFilter = ref(props.filters.role_type || 'administrative');
const sortField = ref(props.filters.sort || 'name');
const sortDirection = ref(props.filters.direction || 'asc');

// Computed properties to ensure string values are passed to DataTable
const sortFieldValue = computed(() => sortField.value);
const sortDirectionValue = computed(() => sortDirection.value);

// Convert to Select component options format
const scopeOptions = computed(() => {
    return [
        { value: '', label: t('All Scopes') },
        { value: 'global', label: t('Default') },
        { value: 'vessel', label: t('Created') }
    ];
});

// Modal state
const isCreateModalOpen = ref(false);
const isUpdateModalOpen = ref(false);
const isShowModalOpen = ref(false);
const editingCrewPosition = ref<CrewPosition | null>(null);
const viewingCrewPosition = ref<CrewPosition | null>(null);

// Confirmation dialog state
const showDeleteDialog = ref(false);
const crewPositionToDelete = ref<CrewPosition | null>(null);
const isDeleting = ref(false);

// Table configuration
const columns = computed(() => [
    { key: 'name', label: t('Role Name'), sortable: true },
    { key: 'scope_label', label: t('Scope'), sortable: false },
    { key: 'crew_members_count', label: t('Crew Members'), sortable: false },
    { key: 'created_at', label: t('Created'), sortable: true },
]);

// Actions configuration based on permissions
const actions = computed(() => {
    return (item: CrewPosition) => {
        const availableActions = [];

        if (can('view', 'crew-roles')) {
            availableActions.push({
                label: t('View Details'),
                icon: 'eye',
                onClick: (position: CrewPosition) => openShowModal(position),
            });
        }

        // Only allow editing of vessel-specific roles (not global/default roles)
        if (can('edit', 'crew-roles') && !item.is_global) {
            availableActions.push({
                label: t('Edit Role'),
                icon: 'edit',
                onClick: (position: CrewPosition) => openEditModal(position),
            });
        }

        // Only allow deletion of vessel-specific roles (not global/default roles)
        if (can('delete', 'crew-roles') && !item.is_global) {
            availableActions.push({
                label: t('Delete Role'),
                icon: 'trash-2',
                variant: 'destructive' as const,
                onClick: (position: CrewPosition) => deleteCrewPosition(position),
            });
        }

        return availableActions;
    };
});

// Watch for changes and update URL
watch([search, scopeFilter, roleTypeFilter, sortField, sortDirection], () => {
    const filters: Record<string, any> = {};

    if (search.value) filters.search = search.value;
    if (scopeFilter.value) filters.scope = scopeFilter.value;
    if (roleTypeFilter.value && roleTypeFilter.value !== 'administrative') filters.role_type = roleTypeFilter.value;
    if (sortField.value !== 'name') filters.sort = sortField.value;
    if (sortDirection.value !== 'asc') filters.direction = sortDirection.value;

    const vesselId = getCurrentVesselId();
    if (!vesselId) return;

    router.get(`/panel/${vesselId}/crew-roles`, filters, {
        preserveState: true,
        replace: true,
    });
}, { debounce: 300 });

const handleSort = (field: string) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

// Modal functions
const openCreateModal = () => {
    isCreateModalOpen.value = true;
};

const openEditModal = (position: CrewPosition) => {
    editingCrewPosition.value = position;
    isUpdateModalOpen.value = true;
};

const openShowModal = (position: CrewPosition) => {
    viewingCrewPosition.value = position;
    isShowModalOpen.value = true;
};

const handleModalSaved = () => {
    // Refresh the page to show updated data
    router.reload();
};

// Delete functions
const deleteCrewPosition = (position: CrewPosition) => {
    crewPositionToDelete.value = position;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!crewPositionToDelete.value) return;

    isDeleting.value = true;
    const vesselId = getCurrentVesselId();
    if (!vesselId) return;

    router.delete(`/panel/${vesselId}/crew-roles/${crewPositionToDelete.value.id}`, {
        onSuccess: () => {
            showDeleteDialog.value = false;
            crewPositionToDelete.value = null;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
};

const cancelDelete = () => {
    showDeleteDialog.value = false;
    crewPositionToDelete.value = null;
    isDeleting.value = false;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};
</script>

<template>
    <Head :title="t('Members Position')" />

    <VesselLayout :breadcrumbs="[{ title: t('Members Position'), href: `/panel/${getCurrentVesselId() || ''}/crew-roles` }]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">{{ t('Members Position') }}</h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">{{ t('Manage crew positions and roles') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <PermissionGate permission="crew-roles.create">
                            <button
                                @click="openCreateModal"
                                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                            >
                                <Icon name="plus" class="h-4 w-4" />
                                {{ t('Add Role') }}
                            </button>
                        </PermissionGate>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                <div class="mb-4 border-b border-border dark:border-border">
                    <div class="flex gap-1">
                        <button
                            @click="roleTypeFilter = 'administrative'"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px',
                                roleTypeFilter === 'administrative'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-card-foreground hover:border-border'
                            ]"
                        >
                            {{ t('Administrative Roles') }}
                        </button>
                        <button
                            @click="roleTypeFilter = 'normal'"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px',
                                roleTypeFilter === 'normal'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-card-foreground hover:border-border'
                            ]"
                        >
                            {{ t('Normal Roles') }}
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <Icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="t('Search roles...')"
                                class="w-full pl-10 pr-4 py-2 text-sm border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-colors"
                            />
                        </div>
                    </div>

                    <!-- Scope Filter -->
                    <div class="min-w-[150px]">
                        <Select
                            v-model="scopeFilter"
                            :options="scopeOptions"
                            :placeholder="t('All Scopes')"
                        />
                    </div>
                </div>
            </div>

            <!-- Data Table Card -->
                <DataTable
                    :data="crewPositionsData"
                    :columns="columns"
                    :actions="actions"
                    :sort-field="sortFieldValue"
                    :sort-direction="sortDirectionValue"
                    :on-sort="handleSort"
                >
                    <template #cell-name="{ item }">
                        <div class="font-medium">{{ item.name }}</div>
                    </template>
                    <template #cell-scope_label="{ item }">
                        <span
                            :class="[
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                item.is_global
                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200'
                                    : 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200',
                            ]"
                        >
                            {{ item.is_global ? t('Default') : t('Created') }}
                        </span>
                    </template>
                    <template #cell-crew_members_count="{ item }">
                        <span class="text-muted-foreground">{{ item.crew_members_count || 0 }}</span>
                    </template>
                    <template #cell-created_at="{ item }">
                        <span class="text-muted-foreground">{{ formatDate(item.created_at) }}</span>
                    </template>
                </DataTable>

                <!-- Pagination -->
                <Pagination
                    v-if="paginatedCrewPositions?.links && paginatedCrewPositions?.meta"
                    :links="paginatedCrewPositions.links"
                    :meta="paginatedCrewPositions.meta"
                    class="mt-4"
                />
            </div>

        <!-- Create Modal -->
        <CrewRoleCreateModal
            :open="isCreateModalOpen"
            @update:open="isCreateModalOpen = $event"
            @saved="handleModalSaved"
        />

        <!-- Update Modal -->
        <CrewRoleUpdateModal
            :open="isUpdateModalOpen"
            :crew-position="editingCrewPosition"
            @update:open="isUpdateModalOpen = $event"
            @saved="handleModalSaved"
        />

        <!-- Show Modal -->
        <CrewRoleShowModal
            :open="isShowModalOpen"
            :crew-position="viewingCrewPosition"
            @update:open="isShowModalOpen = $event"
        />

        <!-- Confirmation Dialog -->
        <ConfirmationDialog
            :open="showDeleteDialog"
            :title="t('Delete Crew Role')"
            :description="t('This action cannot be undone.')"
            :message="crewPositionToDelete ? `${t('Are you sure you want to delete the crew role')} '${crewPositionToDelete.name}'? ${t('This will permanently remove the role')}.` : ''"
            :confirm-text="t('Delete Role')"
            :cancel-text="t('Cancel')"
            variant="destructive"
            type="danger"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
            @update:open="showDeleteDialog = $event"
        />
    </VesselLayout>
</template>

