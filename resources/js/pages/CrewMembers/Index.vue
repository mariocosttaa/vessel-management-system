<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import DataTable from '@/components/ui/DataTable.vue';
import { Select } from '@/components/ui/select';
import Pagination from '@/components/ui/Pagination.vue';
import CrewMemberCreateModal from '@/components/modals/CrewMember/create.vue';
import CrewMemberUpdateModal from '@/components/modals/CrewMember/update.vue';
import CrewMemberShowModal from '@/components/modals/CrewMember/show.vue';
import PermissionGate from '@/components/PermissionGate.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useI18n } from '@/composables/useI18n';
import crewMembers from '@/routes/panel/crew-members';

// Get current vessel ID from URL (hashed ID)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel ID (alphanumeric string after /panel/)
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
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
    login_permitted?: boolean;
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
    pendingInvitations?: Array<{
        id: number;
        name: string;
        email: string;
        position: string | null;
        invitation_sent_at: string | null;
        days_since_invitation: number | null;
        email_send_count?: number;
        can_resend?: boolean;
    }>;
}

const props = defineProps<Props>();

// Permissions
const { can } = usePermissions();
const { t } = useI18n();

// Computed property for crew members data
const crewMembersData = computed(() => props.crewMembers?.data || []);

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const vesselFilter = ref('');
const positionFilter = ref(props.filters.position_id || '');
const sortField = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');

// Convert to Select component options format
const statusOptions = computed(() => {
    const options = [{ value: '', label: t('All Statuses') }];
    Object.entries(props.statuses).forEach(([value, label]) => {
        options.push({ value, label: label as string });
    });
    return options;
});

const positionOptions = computed(() => {
    const options = [{ value: '', label: t('All Positions') }];
    props.positions.forEach(position => {
        options.push({ value: String(position.id), label: position.name });
    });
    return options;
});

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

// Cancel invitation confirmation dialog state
const showCancelInvitationDialog = ref(false);
const invitationToCancel = ref<typeof props.pendingInvitations[0] | null>(null);
const isCancellingInvitation = ref(false);

// Table configuration
const columns = computed(() => [
    { key: 'name', label: t('Name'), sortable: true },
    { key: 'position_name', label: t('Position'), sortable: false },
    { key: 'status_label', label: t('Status'), sortable: false },
    { key: 'login_permitted', label: t('System Access'), sortable: false },
]);

// Actions configuration based on permissions
const actions = computed(() => {
    const availableActions = [];

    if (can('view', 'crew')) {
        availableActions.push({
            label: t('View Details'),
            icon: 'eye',
            onClick: (crewMember: CrewMember) => openShowModal(crewMember),
        });
    }

    if (can('edit', 'crew')) {
        availableActions.push({
            label: t('Edit Crew Member'),
            icon: 'edit',
            onClick: (crewMember: CrewMember) => openEditModal(crewMember),
        });
    }

    if (can('delete', 'crew')) {
        availableActions.push({
            label: t('Delete Crew Member'),
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
    // Close modals before reloading
    isCreateModalOpen.value = false;
    isUpdateModalOpen.value = false;
    // Refresh the page to show updated data
    router.reload();
};

const handleRemoveFromModal = (crewMember: CrewMember) => {
    // Close update modal
    isUpdateModalOpen.value = false;
    // Open delete confirmation dialog
    deleteCrewMember(crewMember);
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

// Invitation functions
const cancelInvitation = (invitation: typeof props.pendingInvitations[0]) => {
    invitationToCancel.value = invitation;
    showCancelInvitationDialog.value = true;
};

const confirmCancelInvitation = () => {
    if (!invitationToCancel.value) return;

    const invitation = invitationToCancel.value;
    const vesselId = getCurrentVesselId();
    if (!vesselId) {
        console.error('Could not determine vessel ID from URL');
        showCancelInvitationDialog.value = false;
        invitationToCancel.value = null;
        return;
    }

    isCancellingInvitation.value = true;

    router.post(crewMembers.cancelInvitation.url({ vessel: vesselId, crewMember: invitation.id }), {}, {
        onSuccess: () => {
            showCancelInvitationDialog.value = false;
            invitationToCancel.value = null;
            isCancellingInvitation.value = false;
            router.reload();
        },
        onError: (errors) => {
            console.error('Error cancelling invitation:', errors);
            isCancellingInvitation.value = false;
        },
    });
};

const cancelCancelInvitation = () => {
    showCancelInvitationDialog.value = false;
    invitationToCancel.value = null;
    isCancellingInvitation.value = false;
};

const resendInvitation = (invitation: typeof props.pendingInvitations[0]) => {
    if (!invitation.can_resend) {
        return;
    }

    const vesselId = getCurrentVesselId();
    if (!vesselId) {
        console.error('Could not determine vessel ID from URL');
        return;
    }

    router.post(crewMembers.resendInvitation.url({ vessel: vesselId, crewMember: invitation.id }), {}, {
        onSuccess: () => {
            router.reload();
        },
        onError: (errors) => {
            console.error('Error resending invitation:', errors);
        },
    });
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

const getSystemAccessBadgeClass = (hasAccess: boolean | undefined) => {
    const baseClass = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium';

    if (hasAccess) {
        return `${baseClass} bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400`;
    } else {
        return `${baseClass} bg-muted text-muted-foreground dark:bg-muted dark:text-muted-foreground`;
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};
</script>

<template>
    <Head :title="t('Crew Members')" />

    <VesselLayout :breadcrumbs="[{ title: t('Crew Members'), href: `/panel/${getCurrentVesselId()}/crew-members` }]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">{{ t('Crew Members') }}</h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">{{ t('Manage your crew members and their information') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <PermissionGate permission="crew.create">
                            <button
                                @click="openCreateModal"
                                class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
                            >
                                <Icon name="plus" class="w-4 h-4 mr-2" />
                                {{ t('Add Crew Member') }}
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
                                :placeholder="t('Search crew members...')"
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

                    <!-- Position Filter -->
                    <div class="min-w-[150px]">
                        <Select
                            v-model="positionFilter"
                            :options="positionOptions"
                            :placeholder="t('All Positions')"
                            searchable
                        />
                    </div>

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

            <!-- Pending Invitations Section -->
            <div v-if="pendingInvitations && pendingInvitations.length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card overflow-hidden">
                <div class="p-4 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground">
                        {{ t('Pending Invitations') }}
                    </h2>
                    <p class="text-sm text-muted-foreground dark:text-muted-foreground mt-1">
                        {{ t('Users who have been invited but have not yet accepted') }}
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border dark:divide-border">
                        <thead class="bg-muted/50 dark:bg-muted/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground dark:text-muted-foreground uppercase tracking-wider">
                                    {{ t('Name') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground dark:text-muted-foreground uppercase tracking-wider">
                                    {{ t('Email') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground dark:text-muted-foreground uppercase tracking-wider">
                                    {{ t('Position') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground dark:text-muted-foreground uppercase tracking-wider">
                                    {{ t('Invited') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground dark:text-muted-foreground uppercase tracking-wider">
                                    {{ t('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border dark:divide-border bg-card dark:bg-card">
                            <tr v-for="invitation in pendingInvitations" :key="invitation.id" class="hover:bg-muted/50 dark:hover:bg-muted/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                        {{ invitation.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                        {{ invitation.email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                        {{ invitation.position || t('Not specified') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-muted-foreground dark:text-muted-foreground">
                                        <span v-if="invitation.days_since_invitation !== null">
                                            <template v-if="Math.floor(invitation.days_since_invitation) === 0">{{ t('Today') }}</template>
                                            <template v-else-if="Math.floor(invitation.days_since_invitation) === 1">{{ t('1 day ago') }}</template>
                                            <template v-else>{{ Math.floor(invitation.days_since_invitation) }} {{ t('days ago') }}</template>
                                        </span>
                                        <span v-else>{{ t('Unknown') }}</span>
                                        <div v-if="invitation.email_send_count !== undefined" class="text-xs text-muted-foreground mt-1">
                                            {{ t('Sent') }}: {{ invitation.email_send_count }}/3
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click.prevent="resendInvitation(invitation)"
                                            :disabled="!invitation.can_resend"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-primary hover:text-primary/80 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                        >
                                            <Icon name="mail" class="w-3 h-3 mr-1" />
                                            {{ t('Resend') }}
                                        </button>
                                        <button
                                            type="button"
                                            @click.prevent="cancelInvitation(invitation)"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-destructive hover:text-destructive/80 transition-colors"
                                        >
                                            <Icon name="x" class="w-3 h-3 mr-1" />
                                            {{ t('Cancel') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Data Table -->
            <DataTable
                :columns="columns"
                :data="crewMembersData"
                :clickable="true"
                :on-row-click="openShowModal"
                :actions="actions"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                :on-sort="handleSort"
                :loading="false"
                :empty-message="t('No crew members found')"
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

                <!-- Custom cell for system access -->
                <template #cell-login_permitted="{ item }">
                    <span :class="getSystemAccessBadgeClass(item.login_permitted)">
                        {{ item.login_permitted ? t('Yes') : t('No') }}
                    </span>
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
            @remove="handleRemoveFromModal"
        />

        <CrewMemberShowModal
            v-model:open="isShowModalOpen"
            :crew-member="viewingCrewMember"
        />

        <!-- Confirmation Dialog for Delete -->
        <ConfirmationDialog
            v-model:open="showDeleteDialog"
            :title="t('Delete Crew Member')"
            :description="t('This action cannot be undone.')"
            :message="`${t('Are you sure you want to delete the crew member')} '${crewMemberToDelete?.name}'? ${t('This will permanently remove the crew member and all their data')}.`"
            :confirm-text="t('Delete Crew Member')"
            :cancel-text="t('Cancel')"
            variant="destructive"
            type="danger"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />

        <!-- Confirmation Dialog for Cancel Invitation -->
        <ConfirmationDialog
            v-model:open="showCancelInvitationDialog"
            :title="t('Cancel Invitation')"
            :description="t('An email will be sent to notify the user.')"
            :message="`${t('Are you sure you want to cancel the invitation for')} '${invitationToCancel?.name}' (${invitationToCancel?.email})? ${t('This will cancel the invitation and send a notification email to the user')}.`"
            :confirm-text="t('Cancel Invitation')"
            :cancel-text="t('Keep Invitation')"
            variant="destructive"
            type="warning"
            :loading="isCancellingInvitation"
            @confirm="confirmCancelInvitation"
            @cancel="cancelCancelInvitation"
        />

    </VesselLayout>
</template>
