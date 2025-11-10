<script setup lang="ts">
import Icon from '@/components/Icon.vue';

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
    auditLog: AuditLog | null;
    isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const close = () => {
    emit('close');
};

// Format date for display
const formatDate = (dateString: string) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

// Action badge class
const getActionBadgeClass = (action: string) => {
    switch (action) {
        case 'create':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'update':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        case 'delete':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
};
</script>

<template>
    <div
        v-if="props.isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 dark:bg-black/70"
        @click.self="close"
    >
        <div
            class="relative w-full max-w-2xl mx-4 bg-card dark:bg-card rounded-xl border border-sidebar-border/70 dark:border-sidebar-border shadow-xl"
            @click.stop
        >
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-sidebar-border/70 dark:border-sidebar-border">
                <div>
                    <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground">Auditoryog Details</h2>
                    <p class="text-sm text-muted-foreground dark:text-muted-foreground mt-1">Detailed information about this action</p>
                </div>
                <button
                    @click="close"
                    class="p-2 rounded-lg hover:bg-muted/50 dark:hover:bg-muted/30 text-muted-foreground hover:text-foreground transition-colors"
                >
                    <Icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <!-- Content -->
            <div v-if="props.auditLog" class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                <!-- Message -->
                <div>
                    <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Message</label>
                    <p class="mt-1 text-sm text-card-foreground dark:text-card-foreground">{{ props.auditLog.message }}</p>
                </div>

                <!-- Action -->
                <div>
                    <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Action</label>
                    <div class="mt-1">
                        <span
                            :class="[
                                'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                                getActionBadgeClass(props.auditLog.action)
                            ]"
                        >
                            {{ props.auditLog.action.charAt(0).toUpperCase() + props.auditLog.action.slice(1) }}
                        </span>
                    </div>
                </div>

                <!-- User Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">User</label>
                        <p class="mt-1 text-sm text-card-foreground dark:text-card-foreground">{{ props.auditLog.user_name }}</p>
                        <p v-if="props.auditLog.user_email" class="text-xs text-muted-foreground dark:text-muted-foreground">{{ props.auditLog.user_email }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Page</label>
                        <p class="mt-1 text-sm text-card-foreground dark:text-card-foreground">{{ props.auditLog.page_name || props.auditLog.model_name }}</p>
                        <p v-if="props.auditLog.model_id" class="text-xs text-muted-foreground dark:text-muted-foreground">ID: {{ props.auditLog.model_id }}</p>
                    </div>
                </div>

                <!-- Vessel -->
                <div v-if="props.auditLog.vessel_name">
                    <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Vessel</label>
                    <p class="mt-1 text-sm text-card-foreground dark:text-card-foreground">{{ props.auditLog.vessel_name }}</p>
                </div>

                <!-- Timestamp -->
                <div>
                    <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Date & Time</label>
                    <p class="mt-1 text-sm text-card-foreground dark:text-card-foreground">{{ formatDate(props.auditLog.created_at) }}</p>
                    <p class="text-xs text-muted-foreground dark:text-muted-foreground">{{ props.auditLog.created_at_human }}</p>
                </div>

                <!-- Technical Details -->
                <div class="pt-4 border-t border-sidebar-border/70 dark:border-sidebar-border">
                    <h3 class="text-sm font-semibold text-card-foreground dark:text-card-foreground mb-3">Technical Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="props.auditLog.ip_address">
                            <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">IP Address</label>
                            <p class="mt-1 text-sm text-card-foreground dark:text-card-foreground font-mono">{{ props.auditLog.ip_address }}</p>
                        </div>

                        <div v-if="props.auditLog.user_agent">
                            <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">User Agent</label>
                            <p class="mt-1 text-sm text-card-foreground dark:text-card-foreground break-all">{{ props.auditLog.user_agent }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 p-6 border-t border-sidebar-border/70 dark:border-sidebar-border">
                <button
                    @click="close"
                    class="px-4 py-2 text-sm font-medium border border-input dark:border-input rounded-lg bg-background dark:bg-background hover:bg-muted/50 text-foreground dark:text-foreground transition-colors"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

