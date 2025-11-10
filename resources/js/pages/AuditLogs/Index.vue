<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import { DateInput } from '@/components/ui/date-input';
import { Select } from '@/components/ui/select';
import Pagination from '@/components/ui/Pagination.vue';
import AuditLogDetailsModal from '@/components/modals/AuditLog/Details.vue';

// Get current vessel ID from URL (if available)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

interface AuditLog {
    id: number;
    user_id: number | null;
    user_name: string;
    user_email: string | null;
    model_type: string;
    model_id: number | null;
    model_name: string;
    page_name: string;
    action: string;
    message: string;
    vessel_id: number | null;
    vessel_name: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    created_at_human: string;
}

interface Props {
    auditLogs: {
        data: AuditLog[];
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        action?: string;
        model_type?: string;
        user_id?: string;
        date_from?: string;
        date_to?: string;
        vessel_id?: string;
    };
    actions: string[];
    modelTypes: Array<{
        value: string;
        label: string;
    }>;
    users: Array<{
        id: number;
        name: string;
        email: string;
    }>;
    vessels: Array<{
        id: number;
        name: string;
    }> | null;
    currentVesselId: number | string | null;
}

const props = defineProps<Props>();

// Convert currentVesselId to number if it's a string
const currentVesselId = computed(() => {
    const id = props.currentVesselId;
    if (typeof id === 'string') {
        return parseInt(id) || null;
    }
    return id;
});

// Computed property for audit logs data
const auditLogsData = computed(() => props.auditLogs?.data || []);
const paginatedAuditLogs = computed(() => props.auditLogs);

// Filters
const search = ref(props.filters.search || '');
const actionFilter = ref(props.filters.action || '');
const modelTypeFilter = ref(props.filters.model_type || '');
const userIdFilter = ref(props.filters.user_id || '');
const dateFromFilter = ref(props.filters.date_from || '');
const dateToFilter = ref(props.filters.date_to || '');
const vesselIdFilter = ref(props.filters.vessel_id || '');

// Convert to Select component options format
const actionOptions = computed(() => {
    const options = [{ value: '', label: 'All Actions' }];
    props.actions.forEach(action => {
        options.push({ value: action, label: action.charAt(0).toUpperCase() + action.slice(1) });
    });
    return options;
});

const modelTypeOptions = computed(() => {
    const options = [{ value: '', label: 'All Pages' }];
    props.modelTypes.forEach(modelType => {
        options.push({ value: modelType.value, label: modelType.label });
    });
    return options;
});

const userOptions = computed(() => {
    const options = [{ value: '', label: 'All Users' }];
    props.users.forEach(user => {
        options.push({ value: String(user.id), label: user.name });
    });
    return options;
});

const vesselOptions = computed(() => {
    if (!props.vessels) return [];
    const options = [{ value: '', label: 'All Vessels' }];
    props.vessels.forEach(vessel => {
        options.push({ value: String(vessel.id), label: vessel.name });
    });
    return options;
});

// Selected audit log for modal
const selectedAuditLog = ref<AuditLog | null>(null);
const isModalOpen = ref(false);

// Open modal with audit log details
const openModal = (auditLog: AuditLog) => {
    selectedAuditLog.value = auditLog;
    isModalOpen.value = true;
};

// Close modal
const closeModal = () => {
    isModalOpen.value = false;
    selectedAuditLog.value = null;
};

// Get card classes based on action type (for color coding)
const getCardClasses = (action: string) => {
    const baseClasses = 'p-4 cursor-pointer transition-all duration-200 border-l-4 rounded-lg';

    switch (action) {
        case 'create':
            // Success/Green - for create actions
            return `${baseClasses} bg-green-50/60 dark:bg-green-900/15 border-green-500 hover:bg-green-50 dark:hover:bg-green-900/25 hover:shadow-md hover:border-green-600`;
        case 'update':
            // Info/Blue - for update actions
            return `${baseClasses} bg-blue-50/60 dark:bg-blue-900/15 border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/25 hover:shadow-md hover:border-blue-600`;
        case 'delete':
            // Danger/Red - for delete actions
            return `${baseClasses} bg-red-50/60 dark:bg-red-900/15 border-red-500 hover:bg-red-50 dark:hover:bg-red-900/25 hover:shadow-md hover:border-red-600`;
        default:
            // Default/Gray - for unknown actions
            return `${baseClasses} bg-card dark:bg-card border-sidebar-border/70 hover:bg-muted/30 dark:hover:bg-muted/20 hover:shadow-sm`;
    }
};

// Get text color classes based on action type
const getTextClasses = (action: string) => {
    switch (action) {
        case 'create':
            return 'text-green-900 dark:text-green-200';
        case 'update':
            return 'text-blue-900 dark:text-blue-200';
        case 'delete':
            return 'text-red-900 dark:text-red-200';
        default:
            return 'text-card-foreground dark:text-card-foreground';
    }
};

// Watch filters and reload data
watch([search, actionFilter, modelTypeFilter, userIdFilter, dateFromFilter, dateToFilter, vesselIdFilter], () => {
    applyFilters();
}, { deep: true });

// Apply filters
const applyFilters = () => {
    const vesselId = getCurrentVesselId();
    const url = vesselId
        ? `/panel/${vesselId}/audit-logs`
        : '/audit-logs';

    router.get(url, {
        search: search.value || undefined,
        action: actionFilter.value || undefined,
        model_type: modelTypeFilter.value || undefined,
        user_id: userIdFilter.value || undefined,
        date_from: dateFromFilter.value || undefined,
        date_to: dateToFilter.value || undefined,
        vessel_id: vesselIdFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Clear all filters
const clearFilters = () => {
    search.value = '';
    actionFilter.value = '';
    modelTypeFilter.value = '';
    userIdFilter.value = '';
    dateFromFilter.value = '';
    dateToFilter.value = '';
    vesselIdFilter.value = '';
    applyFilters();
};

// Format date for display
const formatDate = (dateString: string) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Breadcrumbs
const vesselId = computed(() => {
    const id = getCurrentVesselId();
    return id ? parseInt(id) : null;
});
const breadcrumbs = computed(() => {
    const id = vesselId.value;
    return id
        ? [
              { title: 'Auditory', href: `/panel/${id}/audit-logs` }
          ]
        : [
              { title: 'Auditory', href: '/audit-logs' }
          ];
});
</script>

<template>
    <Head title="Auditory - Monitoring" />

    <VesselLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div>
                    <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">Auditory</h1>
                    <p class="text-muted-foreground dark:text-muted-foreground mt-1">Monitor all system activities and user actions</p>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="lg:col-span-2">
                        <div class="relative">
                            <Icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search audit logs..."
                                class="w-full pl-10 pr-4 py-2 text-sm border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-colors"
                            />
                        </div>
                    </div>

                    <!-- Action Filter -->
                    <div>
                        <Select
                            v-model="actionFilter"
                            :options="actionOptions"
                            placeholder="All Actions"
                            searchable
                        />
                    </div>

                    <!-- Page Type Filter -->
                    <div>
                        <Select
                            v-model="modelTypeFilter"
                            :options="modelTypeOptions"
                            placeholder="All Pages"
                            searchable
                        />
                    </div>

                    <!-- User Filter -->
                    <div>
                        <Select
                            v-model="userIdFilter"
                            :options="userOptions"
                            placeholder="All Users"
                            searchable
                        />
                    </div>

                    <!-- Vessel Filter (only if not vessel-scoped) -->
                    <div v-if="!currentVesselId && vessels">
                        <Select
                            v-model="vesselIdFilter"
                            :options="vesselOptions"
                            placeholder="All Vessels"
                            searchable
                        />
                    </div>

                    <!-- Date From Filter -->
                    <div>
                        <DateInput
                            v-model="dateFromFilter"
                            placeholder="Date From"
                        />
                    </div>

                    <!-- Date To Filter -->
                    <div>
                        <DateInput
                            v-model="dateToFilter"
                            placeholder="Date To"
                        />
                    </div>

                    <!-- Clear Filters Button -->
                    <div class="flex items-end">
                        <button
                            @click="clearFilters"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border border-input dark:border-input rounded-lg bg-background dark:bg-background hover:bg-muted/50 text-muted-foreground hover:text-foreground transition-colors"
                        >
                            <Icon name="x" class="h-4 w-4" />
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Auditory List -->
                <div v-if="auditLogsData.length === 0" class="p-12 text-center">
                    <p class="text-muted-foreground dark:text-muted-foreground">No audit logs found</p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="log in auditLogsData"
                        :key="log.id"
                        @click="openModal(log)"
                        :class="getCardClasses(log.action)"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <!-- Message (left side) -->
                            <div class="flex-1 min-w-0">
                                <p :class="['text-sm font-medium leading-relaxed', getTextClasses(log.action)]">
                                    {{ log.message }}
                                </p>
                            </div>

                            <!-- Date & Time (right side, upper) -->
                            <div class="flex-shrink-0 text-right">
                                <div :class="['text-xs font-semibold', getTextClasses(log.action)]">
                                    {{ formatDate(log.created_at) }}
                                </div>
                                <div class="text-xs opacity-70 mt-0.5" :class="getTextClasses(log.action)">
                                    {{ log.created_at_human }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Pagination -->
            <Pagination
                v-if="paginatedAuditLogs?.meta && Array.isArray(paginatedAuditLogs.links)"
                :links="paginatedAuditLogs.links"
                :meta="paginatedAuditLogs.meta"
            />
        </div>

        <!-- Auditoryog Details Modal -->
        <AuditLogDetailsModal
            :audit-log="selectedAuditLog"
            :is-open="isModalOpen"
            @close="closeModal"
        />
    </VesselLayout>
</template>

