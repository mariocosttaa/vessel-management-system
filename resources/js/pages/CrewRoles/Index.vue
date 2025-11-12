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
import PermissionsInfoModal from '@/components/modals/CrewRole/PermissionsInfoModal.vue';
import PermissionGate from '@/components/PermissionGate.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useI18n } from '@/composables/useI18n';
import { usePage } from '@inertiajs/vue3';

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

interface CrewPosition {
    id: number;
    name: string;
    description?: string;
    vessel_id?: number | null;
    is_global: boolean;
    scope_label: string;
    crew_members_count?: number;
    created_at: string;
    vessel_role_access_id?: number | null;
    vessel_role_access?: {
        id: number;
        name: string;
        display_name: string;
        description: string;
    } | null;
}

interface VesselRoleAccess {
    id: number;
    name: string;
    display_name: string;
    description: string;
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
        sort?: string;
        direction?: string;
    };
    vesselRoleAccesses?: VesselRoleAccess[];
    permissionsConfig?: Record<string, any>;
}

const props = defineProps<Props>();

// Permissions
const { can, canView } = usePermissions();
const { t } = useI18n();

// Check if user has permission to view crew roles
onMounted(() => {
    if (!canView('crew-roles')) {
        router.visit(`/panel/${getCurrentVesselId()}/dashboard`, {
            replace: true,
        });
    }
});

// Computed property for crew positions data
const crewPositionsData = computed(() => props.crewPositions?.data || []);
const paginatedCrewPositions = computed(() => props.crewPositions);

const search = ref(props.filters.search || '');
const scopeFilter = ref(props.filters.scope || '');
const sortField = ref(props.filters.sort || 'name');
const sortDirection = ref(props.filters.direction || 'asc');

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
const isPermissionsInfoModalOpen = ref(false);
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
    { key: 'vessel_role_access', label: t('Permission Level'), sortable: false },
    { key: 'crew_members_count', label: t('Crew Members'), sortable: false },
    { key: 'description', label: t('Description'), sortable: false },
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
watch([search, scopeFilter, sortField, sortDirection], () => {
    const filters: Record<string, any> = {};

    if (search.value) filters.search = search.value;
    if (scopeFilter.value) filters.scope = scopeFilter.value;
    if (sortField.value !== 'name') filters.sort = sortField.value;
    if (sortDirection.value !== 'asc') filters.direction = sortDirection.value;

    router.get(`/panel/${getCurrentVesselId()}/crew-roles`, filters, {
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

    router.delete(`/panel/${getCurrentVesselId()}/crew-roles/${crewPositionToDelete.value.id}`, {
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

const getPermissionLevelBadgeClass = (roleName: string): string => {
    const classes: Record<string, string> = {
        'administrator': 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200',
        'supervisor': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200',
        'moderator': 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200',
        'normal': 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200',
    };
    return classes[roleName] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200';
};
</script>

<template>
    <Head :title="t('Crew Roles')" />

    <VesselLayout :breadcrumbs="[{ title: t('Crew Roles'), href: `/panel/${getCurrentVesselId()}/crew-roles` }]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">{{ t('Crew Roles') }}</h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">{{ t('Manage crew positions and roles') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            @click="isPermissionsInfoModalOpen = true"
                            class="inline-flex items-center gap-2 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card px-4 py-2 text-sm font-medium text-card-foreground dark:text-card-foreground transition-colors hover:bg-sidebar-accent dark:hover:bg-sidebar-accent"
                        >
                            <Icon name="info" class="h-4 w-4" />
                            {{ t('Permission Types') }}
                        </button>
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
                    :sort-field="sortField"
                    :sort-direction="sortDirection"
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
                    <template #cell-vessel_role_access="{ item }">
                        <span
                            v-if="item.vessel_role_access"
                            :class="[
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                getPermissionLevelBadgeClass(item.vessel_role_access.name),
                            ]"
                        >
                            {{ item.vessel_role_access.display_name }}
                        </span>
                        <span v-else class="text-muted-foreground text-sm">-</span>
                    </template>
                    <template #cell-crew_members_count="{ item }">
                        <span class="text-muted-foreground">{{ item.crew_members_count || 0 }}</span>
                    </template>
                    <template #cell-description="{ item }">
                        <span class="text-muted-foreground">{{ item.description || '-' }}</span>
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
            :vessel-role-accesses="props.vesselRoleAccesses || []"
            @update:open="isCreateModalOpen = $event"
            @saved="handleModalSaved"
            @open-permissions-info="isPermissionsInfoModalOpen = true"
        />

        <!-- Update Modal -->
        <CrewRoleUpdateModal
            :open="isUpdateModalOpen"
            :crew-position="editingCrewPosition"
            :vessel-role-accesses="props.vesselRoleAccesses || []"
            @update:open="isUpdateModalOpen = $event"
            @saved="handleModalSaved"
            @open-permissions-info="isPermissionsInfoModalOpen = true"
        />

        <!-- Permissions Info Modal -->
        <PermissionsInfoModal
            v-if="props.permissionsConfig"
            :open="isPermissionsInfoModalOpen"
            :permissions-config="props.permissionsConfig"
            @update:open="isPermissionsInfoModalOpen = $event"
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

