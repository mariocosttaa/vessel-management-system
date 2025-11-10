<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import DataTable from '@/components/ui/DataTable.vue';
import Pagination from '@/components/ui/Pagination.vue';
import SupplierCreateModal from '@/components/modals/Supplier/create.vue';
import SupplierUpdateModal from '@/components/modals/Supplier/update.vue';
import SupplierShowModal from '@/components/modals/Supplier/show.vue';
import PermissionGate from '@/components/PermissionGate.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { usePermissions } from '@/composables/usePermissions';
import suppliers from '@/routes/panel/suppliers';

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

interface Supplier {
    id: number;
    company_name: string;
    email?: string;
    phone?: string;
    address?: string;
    notes?: string;
    created_at: string;
}

interface Props {
    suppliers: {
        data: Supplier[];
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        sort?: string;
        direction?: string;
    };
}

const props = defineProps<Props>();

// Permissions
const { can } = usePermissions();

// Computed property for suppliers data
const suppliersData = computed(() => props.suppliers?.data || []);
const paginatedSuppliers = computed(() => props.suppliers);

const search = ref(props.filters.search || '');
const sortField = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');

// Modal state
const isCreateModalOpen = ref(false);
const isUpdateModalOpen = ref(false);
const isShowModalOpen = ref(false);
const editingSupplier = ref<Supplier | null>(null);
const viewingSupplier = ref<Supplier | null>(null);

// Confirmation dialog state
const showDeleteDialog = ref(false);
const supplierToDelete = ref<Supplier | null>(null);
const isDeleting = ref(false);

// Table configuration
const columns = [
    { key: 'company_name', label: 'Company', sortable: true },
    { key: 'email', label: 'Email', sortable: false },
    { key: 'phone', label: 'Phone', sortable: false },
    { key: 'address', label: 'Address', sortable: false },
    { key: 'created_at', label: 'Created', sortable: true },
];

// Actions configuration based on permissions
const actions = computed(() => {
    const availableActions = [];

    if (can('view', 'suppliers')) {
        availableActions.push({
            label: 'View Details',
            icon: 'eye',
            onClick: (supplier: Supplier) => openShowModal(supplier),
        });
    }

    if (can('edit', 'suppliers')) {
        availableActions.push({
            label: 'Edit Supplier',
            icon: 'edit',
            onClick: (supplier: Supplier) => openEditModal(supplier),
        });
    }

    if (can('delete', 'suppliers')) {
        availableActions.push({
            label: 'Delete Supplier',
            icon: 'trash-2',
            variant: 'destructive' as const,
            onClick: (supplier: Supplier) => deleteSupplier(supplier),
        });
    }

    return availableActions;
});

// Watch for changes and update URL
watch([search, sortField, sortDirection], () => {
    const filters: Record<string, any> = {};

    if (search.value) filters.search = search.value;
    if (sortField.value !== 'created_at') filters.sort = sortField.value;
    if (sortDirection.value !== 'desc') filters.direction = sortDirection.value;

    router.get(suppliers.index.url({ vessel: getCurrentVesselId() }), filters, {
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

const openEditModal = (supplier: Supplier) => {
    editingSupplier.value = supplier;
    isUpdateModalOpen.value = true;
};

const openShowModal = (supplier: Supplier) => {
    viewingSupplier.value = supplier;
    isShowModalOpen.value = true;
};

const handleModalSaved = () => {
    // Refresh the page to show updated data
    router.reload();
};

// Delete functions
const deleteSupplier = (supplier: Supplier) => {
    supplierToDelete.value = supplier;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!supplierToDelete.value) return;

    isDeleting.value = true;

    router.delete(suppliers.destroy.url({ vessel: getCurrentVesselId(), supplier: supplierToDelete.value.id }), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            supplierToDelete.value = null;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
};

const cancelDelete = () => {
    showDeleteDialog.value = false;
    supplierToDelete.value = null;
    isDeleting.value = false;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};
</script>

<template>
    <Head title="Suppliers" />

    <VesselLayout :breadcrumbs="[{ title: 'Suppliers', href: suppliers.index.url({ vessel: getCurrentVesselId() }) }]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">Suppliers</h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">Manage your suppliers and vendors</p>
                    </div>
                    <PermissionGate permission="suppliers.create">
                        <button
                            @click="openCreateModal"
                            class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
                        >
                            <Icon name="plus" class="w-4 h-4 mr-2" />
                            Add Supplier
                        </button>
                    </PermissionGate>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                <div class="flex items-center gap-3">
                    <!-- Search -->
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <Icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search suppliers..."
                                class="w-full pl-10 pr-4 py-2 text-sm border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-colors"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <DataTable
                :columns="columns"
                :data="suppliersData"
                :clickable="true"
                :on-row-click="openShowModal"
                :actions="actions"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                :on-sort="handleSort"
                :loading="false"
                empty-message="No suppliers found"
            >
                <!-- Custom cell for supplier company -->
                <template #cell-company_name="{ item }">
                    <div>
                        <div class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            {{ item.company_name }}
                        </div>
                        <div v-if="item.notes" class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                            {{ item.notes }}
                        </div>
                    </div>
                </template>

                <!-- Custom cell for contact -->
                <template #cell-email="{ item }">
                    {{ item.email || '-' }}
                </template>

                <!-- Custom cell for phone -->
                <template #cell-phone="{ item }">
                    {{ item.phone || '-' }}
                </template>

                <!-- Custom cell for address -->
                <template #cell-address="{ item }">
                    <span class="line-clamp-2" v-if="item.address">{{ item.address }}</span>
                    <span v-else>-</span>
                </template>

                <!-- Custom cell for created date -->
                <template #cell-created_at="{ item }">
                    {{ formatDate(item.created_at) }}
                </template>
            </DataTable>

            <!-- Pagination -->
            <Pagination
                v-if="paginatedSuppliers?.links && paginatedSuppliers.links.length > 3"
                :links="paginatedSuppliers.links"
                :meta="paginatedSuppliers"
            />
        </div>

        <!-- Supplier Modals -->
        <SupplierCreateModal
            v-model:open="isCreateModalOpen"
            @saved="handleModalSaved"
        />

        <SupplierUpdateModal
            v-model:open="isUpdateModalOpen"
            :supplier="editingSupplier"
            @saved="handleModalSaved"
        />

        <SupplierShowModal
            v-model:open="isShowModalOpen"
            :supplier="viewingSupplier"
        />

        <!-- Confirmation Dialog -->
        <ConfirmationDialog
            v-model:open="showDeleteDialog"
            title="Delete Supplier"
            description="This action cannot be undone."
            :message="`Are you sure you want to delete the supplier '${supplierToDelete?.company_name}'? This will permanently remove the supplier and all their data.`"
            confirm-text="Delete Supplier"
            cancel-text="Cancel"
            variant="destructive"
            type="danger"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />

    </VesselLayout>
</template>
