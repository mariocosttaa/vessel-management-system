<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Bell } from 'lucide-vue-next';
import { usePermissions } from '@/composables/usePermissions';

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

const { isAdmin } = usePermissions();
const auditLogs = ref<AuditLog[]>([]);
const isLoading = ref(false);
const isOpen = ref(false);

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

// Fetch recent audit logs
const fetchRecentLogs = async () => {
    if (!isAdmin.value) {
        return;
    }

    isLoading.value = true;
    try {
        const vesselId = getCurrentVesselId();
        const url = vesselId
            ? `/panel/${vesselId}/audit-logs/recent`
            : '/audit-logs/recent';

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            const data = await response.json();
            auditLogs.value = data.data || [];
        }
    } catch (error) {
        console.error('Failed to fetch audit logs:', error);
    } finally {
        isLoading.value = false;
    }
};

// Watch for dropdown open state to fetch logs
watch(isOpen, (newValue: boolean) => {
    if (newValue && auditLogs.value.length === 0 && !isLoading.value) {
        fetchRecentLogs();
    }
});

// Get dot color class based on action type
const getDotColor = (action: string) => {
    switch (action) {
        case 'create':
            return 'bg-green-500';
        case 'update':
            return 'bg-blue-500';
        case 'delete':
            return 'bg-red-500';
        default:
            return 'bg-muted-foreground';
    }
};

// Format date and time for display
const formatDateTime = (dateString: string) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Get audit logs page URL
const getAuditLogsUrl = () => {
    const vesselId = getCurrentVesselId();
    return vesselId ? `/panel/${vesselId}/audit-logs` : '/audit-logs';
};

// Handle log click - navigate to audit logs page
const handleLogClick = () => {
    router.visit(getAuditLogsUrl());
    isOpen.value = false;
};

// Only show if user is admin
const shouldShow = computed(() => isAdmin.value);
</script>

<template>
    <DropdownMenu v-if="shouldShow" v-model:open="isOpen">
        <DropdownMenuTrigger as-child>
            <button
                class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-muted/40 hover:bg-muted/70 dark:bg-muted/20 dark:hover:bg-muted/40 transition-all duration-200 relative group"
                title="Notifications"
            >
                <Bell class="w-4 h-4 text-muted-foreground group-hover:text-foreground transition-colors" />
                <span
                    v-if="auditLogs.length > 0"
                    class="absolute top-1.5 right-1.5 w-2 h-2 bg-primary rounded-full ring-2 ring-background"
                />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            align="end"
            class="w-96 max-h-[500px] overflow-y-auto p-0"
            :side-offset="8"
        >
            <!-- Header -->
            <div class="sticky top-0 z-10 border-b border-border bg-card px-4 py-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-card-foreground">Notifications</h3>
                    <Link
                        :href="getAuditLogsUrl()"
                        class="text-xs text-primary hover:underline"
                        @click="isOpen = false"
                    >
                        View All
                    </Link>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="p-8 text-center">
                <p class="text-sm text-muted-foreground">Loading...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="auditLogs.length === 0" class="p-8 text-center">
                <p class="text-sm text-muted-foreground">No notifications</p>
            </div>

            <!-- Notifications List -->
            <div v-else class="p-2 space-y-2">
                <div
                    v-for="log in auditLogs"
                    :key="log.id"
                    @click="handleLogClick"
                    class="px-4 py-3 cursor-pointer bg-muted/50 dark:bg-muted/30 rounded-lg hover:bg-muted/70 dark:hover:bg-muted/50 transition-colors duration-150 flex items-start gap-3 group"
                >
                    <!-- Notification Message -->
                    <div class="flex-1 min-w-0">
                        <!-- Date and Time -->
                        <p class="text-xs text-muted-foreground mb-1.5">
                            {{ formatDateTime(log.created_at) }}
                        </p>
                        <!-- Message -->
                        <p class="text-sm text-card-foreground leading-relaxed">
                            {{ log.message }}
                        </p>
                    </div>

                    <!-- Colored Dot Indicator -->
                    <div class="flex-shrink-0 mt-1.5">
                        <span
                            :class="['w-2 h-2 rounded-full', getDotColor(log.action)]"
                        />
                    </div>
                </div>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

