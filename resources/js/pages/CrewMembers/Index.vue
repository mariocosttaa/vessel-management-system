<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import DataTable from '@/components/ui/DataTable.vue';
import Pagination from '@/components/ui/Pagination.vue';
import CrewMemberCreateModal from '@/components/modals/CrewMember/create.vue';
import CrewMemberUpdateModal from '@/components/modals/CrewMember/update.vue';
import CrewMemberShowModal from '@/components/modals/CrewMember/show.vue';
import PermissionGate from '@/components/PermissionGate.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { usePermissions } from '@/composables/usePermissions';
import crewMembers from '@/routes/panel/crew-members';

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

interface CrewMember {
    id: number;
    vessel_id?: number;
    vessel_name?: string;
    position_id: number;
    position_name: string;
    name: string;
    email?: string;
    phone?: string;
    date_of_birth?: string;
    formatted_date_of_birth?: string;
    hire_date: string;
    formatted_hire_date: string;
    salary_amount: number;
    formatted_salary: string;
    salary_currency: string;
    payment_frequency: string;
    payment_frequency_label: string;
    status: string;
    status_label: string;
    status_color: string;
    notes?: string;
    created_at: string;
}

interface Vessel {
    id: number;
    name: string;
}

interface CrewPosition {
    id: number;
    name: string;
}

interface Props {
    crewMembers: {
        data: CrewMember[];
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        status?: string;
        position_id?: string;
        sort?: string;
        direction?: string;
    };
    positions: CrewPosition[];
    statuses: Record<string, string>;
    currencies: Array<{
        code: string;
        name: string;
        symbol: string;
    }>;
    paymentFrequencies: Record<string, string>;
}

const props = defineProps<Props>();

// Permissions
const { can } = usePermissions();

// Computed property for crew members data
const crewMembersData = computed(() => props.crewMembers?.data || []);

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const vesselFilter = ref('');
const positionFilter = ref(props.filters.position_id || '');
const sortField = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');

// Modal state
const isCreateModalOpen = ref(false);
const isUpdateModalOpen = ref(false);
const isShowModalOpen = ref(false);
const editingCrewMember = ref<CrewMember | null>(null);
const viewingCrewMember = ref<CrewMember | null>(null);

// Confirmation dialog state
const showDeleteDialog = ref(false);
const crewMemberToDelete = ref<CrewMember | null>(null);
const isDeleting = ref(false);

// Table configuration
const columns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'position_name', label: 'Position', sortable: false },
    { key: 'status_label', label: 'Status', sortable: false },
    { key: 'formatted_salary', label: 'Salary', sortable: false },
    { key: 'formatted_hire_date', label: 'Hire Date', sortable: true },
];

// Actions configuration based on permissions
const actions = computed(() => {
    const availableActions = [];

    if (can('view', 'crew')) {
        availableActions.push({
            label: 'View Details',
            icon: 'eye',
            onClick: (crewMember: CrewMember) => openShowModal(crewMember),
        });
    }

    if (can('edit', 'crew')) {
        availableActions.push({
            label: 'Edit Crew Member',
            icon: 'edit',
            onClick: (crewMember: CrewMember) => openEditModal(crewMember),
        });
    }

    if (can('delete', 'crew')) {
        availableActions.push({
            label: 'Delete Crew Member',
            icon: 'trash-2',
            variant: 'destructive' as const,
            onClick: (crewMember: CrewMember) => deleteCrewMember(crewMember),
        });
    }

    return availableActions;
});

// Watch for changes and update URL
watch([search, statusFilter, positionFilter, sortField, sortDirection], () => {
    const filters: Record<string, any> = {};

    if (search.value) filters.search = search.value;
    if (statusFilter.value) filters.status = statusFilter.value;
    if (positionFilter.value) filters.position_id = positionFilter.value;
    if (sortField.value !== 'created_at') filters.sort = sortField.value;
    if (sortDirection.value !== 'desc') filters.direction = sortDirection.value;

    router.get(crewMembers.index.url({ vessel: getCurrentVesselId() }), filters, {
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

const clearFilters = () => {
    search.value = '';
    statusFilter.value = '';
    vesselFilter.value = '';
    positionFilter.value = '';
    sortField.value = 'created_at';
    sortDirection.value = 'desc';
};

// Modal functions
const openCreateModal = () => {
    isCreateModalOpen.value = true;
};

const openEditModal = (crewMember: CrewMember) => {
    editingCrewMember.value = crewMember;
    isUpdateModalOpen.value = true;
};

const openShowModal = (crewMember: CrewMember) => {
    viewingCrewMember.value = crewMember;
    isShowModalOpen.value = true;
};

const handleModalSaved = () => {
    // Refresh the page to show updated data
    router.reload();
};

// Delete functions
const deleteCrewMember = (crewMember: CrewMember) => {
    crewMemberToDelete.value = crewMember;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!crewMemberToDelete.value) return;

    isDeleting.value = true;

    router.delete(crewMembers.destroy.url({ vessel: getCurrentVesselId(), crewMember: crewMemberToDelete.value.id }), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            crewMemberToDelete.value = null;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
};

const cancelDelete = () => {
    showDeleteDialog.value = false;
    crewMemberToDelete.value = null;
    isDeleting.value = false;
};

const getStatusBadgeClass = (status: string) => {
    const baseClass = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium';

    switch (status) {
        case 'active':
            return `${baseClass} bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400`;
        case 'inactive':
            return `${baseClass} bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400`;
        case 'on_leave':
            return `${baseClass} bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400`;
        default:
            return `${baseClass} bg-muted text-muted-foreground dark:bg-muted dark:text-muted-foreground`;
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};
</script>

<template>
    <Head title="Crew Members" />

    <VesselLayout :breadcrumbs="[{ title: 'Crew Members', href: crewMembers.index.url({ vessel: getCurrentVesselId() }) }]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">Crew Members</h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">Manage your crew members and their information</p>
                    </div>
                    <PermissionGate permission="crew.create">
                        <button
                            @click="openCreateModal"
                            class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
                        >
                            <Icon name="plus" class="w-4 h-4 mr-2" />
                            Add Crew Member
                        </button>
                    </PermissionGate>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                            Search
                        </label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search crew members..."
                            class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                            Status
                        </label>
                        <select
                            v-model="statusFilter"
                            class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                        >
                            <option value="">All Statuses</option>
                            <option v-for="(label, value) in statuses" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <!-- Position Filter -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                            Position
                        </label>
                        <select
                            v-model="positionFilter"
                            class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                        >
                            <option value="">All Positions</option>
                            <option v-for="position in positions" :key="position.id" :value="position.id">
                                {{ position.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Clear Filters -->
                    <div class="flex items-end">
                        <button
                            @click="clearFilters"
                            class="w-full px-4 py-2 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground font-medium transition-colors"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <DataTable
                :columns="columns"
                :data="crewMembersData"
                :clickable="true"
                :on-row-click="openShowModal"
                :actions="actions"
                :sort-field="sortField.value"
                :sort-direction="sortDirection.value"
                :on-sort="handleSort"
                :loading="false"
                empty-message="No crew members found"
            >
                <!-- Custom cell for crew member name -->
                <template #cell-name="{ item }">
                    <div>
                        <div class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            {{ item.name }}
                        </div>
                        <div v-if="item.email" class="text-sm text-muted-foreground dark:text-muted-foreground">
                            {{ item.email }}
                        </div>
                    </div>
                </template>

                <!-- Custom cell for status -->
                <template #cell-status_label="{ item }">
                    <span :class="getStatusBadgeClass(item.status)">
                        {{ item.status_label }}
                    </span>
                </template>

                <!-- Custom cell for hire date -->
                <template #cell-formatted_hire_date="{ item }">
                    {{ item.formatted_hire_date || formatDate(item.hire_date) }}
                </template>
            </DataTable>

            <!-- Pagination -->
            <Pagination
                v-if="crewMembers?.links && crewMembers.links.length > 3"
                :links="crewMembers.links"
                :meta="crewMembers"
            />
        </div>

        <!-- Crew Member Modals -->
        <CrewMemberCreateModal
            v-model:open="isCreateModalOpen"
            :positions="positions"
            :statuses="statuses"
            :currencies="currencies"
            :payment-frequencies="paymentFrequencies"
            @saved="handleModalSaved"
        />

        <CrewMemberUpdateModal
            v-model:open="isUpdateModalOpen"
            :crew-member="editingCrewMember"
            :positions="positions"
            :statuses="statuses"
            :currencies="currencies"
            :payment-frequencies="paymentFrequencies"
            @saved="handleModalSaved"
        />

        <CrewMemberShowModal
            v-model:open="isShowModalOpen"
            :crew-member="viewingCrewMember"
        />

        <!-- Confirmation Dialog -->
        <ConfirmationDialog
            v-model:open="showDeleteDialog"
            title="Delete Crew Member"
            description="This action cannot be undone."
            :message="`Are you sure you want to delete the crew member '${crewMemberToDelete?.name}'? This will permanently remove the crew member and all their data.`"
            confirm-text="Delete Crew Member"
            cancel-text="Cancel"
            variant="destructive"
            type="danger"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />

    </VesselLayout>
</template>
