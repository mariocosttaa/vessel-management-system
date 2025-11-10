<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import BaseModal from '@/components/modals/BaseModal.vue';
import Icon from '@/components/Icon.vue';

interface Props {
    open: boolean;
    permissionsConfig: Record<string, {
        name: string;
        permissions: Record<string, boolean>;
        grouped_permissions: Record<string, string[]>;
    }>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

// Role display order
const roleOrder = ['Administrator', 'Supervisor', 'Moderator', 'Normal User'];

// Active role tab
const activeRole = ref<string>('');

// Resource labels
const resourceLabels: Record<string, string> = {
    'vessels': 'Vessels',
    'crew': 'Crew Members',
    'crew-roles': 'Crew Roles',
    'suppliers': 'Suppliers',
    'bank-accounts': 'Bank Accounts',
    'transactions': 'Transactions',
    'mareas': 'Mareas',
    'distribution-profiles': 'Distribution Profiles',
    'reports': 'Reports',
    'settings': 'Settings',
    'users': 'Users',
    'recycle_bin': 'Recycle Bin',
};

// Action labels
const actionLabels: Record<string, string> = {
    'view': 'View',
    'create': 'Create',
    'edit': 'Edit',
    'delete': 'Delete',
    'access': 'Access',
    'manage': 'Manage',
    'manage-status': 'Manage Status',
    'restore': 'Restore',
};

// Role descriptions
const roleDescriptions: Record<string, string> = {
    'Administrator': 'Full control over the vessel, including deletion and user management. Can perform all actions.',
    'Supervisor': 'Can view, edit basic and advanced vessel data, and manage crew. Cannot delete vessels or manage users.',
    'Moderator': 'Can view and edit basic vessel data. Limited access to certain resources.',
    'Normal User': 'View-only access to vessel data. Cannot edit or delete anything.',
};

// Role badge classes
const roleBadgeClasses: Record<string, string> = {
    'Administrator': 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200',
    'Supervisor': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200',
    'Moderator': 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200',
    'Normal User': 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200',
};

// Sort roles by predefined order
const sortedRoles = computed(() => {
    return roleOrder
        .filter(role => props.permissionsConfig[role])
        .map(role => ({
            name: role,
            ...props.permissionsConfig[role],
        }));
});

// Get active role data
const activeRoleData = computed(() => {
    if (!activeRole.value) return null;
    return sortedRoles.value.find(role => role.name === activeRole.value);
});

// Get resource display name
const getResourceLabel = (resource: string): string => {
    return resourceLabels[resource] || resource.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Get action display name
const getActionLabel = (action: string): string => {
    return actionLabels[action] || action.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Get role description
const getRoleDescription = (roleName: string): string => {
    return roleDescriptions[roleName] || 'Custom permission level.';
};

// Get role badge class
const getRoleBadgeClass = (roleName: string): string => {
    return roleBadgeClasses[roleName] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200';
};

// Set active role
const setActiveRole = (roleName: string) => {
    activeRole.value = roleName;
};

// Handle modal close
const handleClose = () => {
    emit('update:open', false);
};

// Initialize active role when modal opens or when permissions config changes
watch([() => props.open, sortedRoles], ([isOpen, roles]) => {
    if (isOpen && roles.length > 0) {
        // Always set to first role when modal opens
        if (!activeRole.value || !roles.find(r => r.name === activeRole.value)) {
            activeRole.value = roles[0].name;
        }
    }
}, { immediate: true });
</script>

<template>
    <BaseModal
        :open="open"
        title="Permission Types Reference"
        description="Understand what each permission level allows users to do in the system. Click on a permission type to view its details."
        size="2xl"
        :show-confirm-button="false"
        :show-cancel-button="false"
        @update:open="handleClose"
    >
        <template #default>
            <div class="max-h-[70vh] overflow-y-auto">
                <div class="space-y-6">
                    <!-- Introduction -->
                    <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                        <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                            When creating a crew role, you can assign a permission level that determines what actions users with that role can perform.
                            Each permission level has specific capabilities across different resources in the system.
                        </p>
                    </div>

                    <!-- Permission Type Navigation Tabs -->
                    <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-2">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="role in sortedRoles"
                                :key="role.name"
                                @click="setActiveRole(role.name)"
                                :class="[
                                    'flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors',
                                    activeRole === role.name
                                        ? 'bg-primary text-primary-foreground shadow-sm'
                                        : 'bg-background text-card-foreground hover:bg-sidebar-accent dark:hover:bg-sidebar-accent',
                                ]"
                            >
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                        activeRole === role.name
                                            ? 'bg-primary-foreground/20 text-primary-foreground'
                                            : getRoleBadgeClass(role.name),
                                    ]"
                                >
                                    {{ role.name }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Active Role Content -->
                    <div v-if="activeRoleData" class="space-y-4">
                        <!-- Role Header -->
                        <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground">
                                        {{ activeRoleData.name }}
                                    </h3>
                                    <p class="mt-1 text-sm text-muted-foreground dark:text-muted-foreground">
                                        {{ getRoleDescription(activeRoleData.name) }}
                                    </p>
                                </div>
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium',
                                        getRoleBadgeClass(activeRoleData.name),
                                    ]"
                                >
                                    {{ activeRoleData.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Permissions by Resource -->
                        <div class="space-y-3">
                            <div
                                v-for="(actions, resource) in activeRoleData.grouped_permissions"
                                :key="resource"
                                class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4"
                            >
                                <div class="flex items-center gap-2 mb-2">
                                    <Icon name="check-circle-2" class="h-4 w-4 text-primary" />
                                    <h4 class="font-medium text-card-foreground dark:text-card-foreground">
                                        {{ getResourceLabel(resource) }}
                                    </h4>
                                </div>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <span
                                        v-for="action in actions"
                                        :key="action"
                                        class="inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-medium bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary"
                                    >
                                        {{ getActionLabel(action) }}
                                    </span>
                                </div>
                            </div>

                            <!-- No permissions message -->
                            <div
                                v-if="Object.keys(activeRoleData.grouped_permissions).length === 0"
                                class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4"
                            >
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                                    This role has no permissions assigned.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- No active role message -->
                    <div v-else class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-8 text-center">
                        <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                            Select a permission type above to view its details.
                        </p>
                    </div>

                    <!-- Footer Note -->
                    <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                        <div class="flex items-start gap-3">
                            <Icon name="info" class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
                            <div>
                                <p class="text-sm font-medium text-card-foreground dark:text-card-foreground mb-1">
                                    Important Note
                                </p>
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                                    Permission levels are hierarchical. Users with higher permission levels automatically inherit the capabilities of lower levels.
                                    For example, an Administrator can perform all actions that a Moderator can, plus additional administrative tasks.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end">
                <button
                    @click="handleClose"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                >
                    Close
                </button>
            </div>
        </template>
    </BaseModal>
</template>
