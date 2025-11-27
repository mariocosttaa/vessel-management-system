<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { useI18n } from '@/composables/useI18n';

interface VesselRole {
    id: number;
    name: string;
    display_name: string;
    description?: string;
}

interface CrewPosition {
    id: number;
    name: string;
    description?: string;
    vessel_id?: number | null;
    is_global: boolean;
    scope_label: string;
    crew_members_count?: number;
    vessel_role?: VesselRole | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    open: boolean;
    crewPosition?: CrewPosition | null;
}

const props = defineProps<Props>();
const { t } = useI18n();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const crewPositionData = ref<CrewPosition | null>(null);
const isLoading = ref(false);

const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel IDs (alphanumeric strings) or numeric IDs
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

const fetchCrewPositionDetails = async () => {
    if (!props.crewPosition?.id) return;

    const vesselId = getCurrentVesselId();
    if (!vesselId) {
        console.error('Vessel ID not found in URL');
        return;
    }

    isLoading.value = true;
    try {
        const response = await fetch(`/panel/${vesselId}/api/crew-roles/${props.crewPosition.id}/details`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            const data = await response.json();
            crewPositionData.value = data.crewPosition;
        }
    } catch (error) {
        // Error handled silently
    } finally {
        isLoading.value = false;
    }
};

// Watch for modal open and crew position changes
watch(() => props.open, (isOpen) => {
    if (isOpen && props.crewPosition) {
        fetchCrewPositionDetails();
    } else {
        crewPositionData.value = null;
    }
});

watch(() => props.crewPosition, (position) => {
    if (position && props.open) {
        fetchCrewPositionDetails();
    }
});

const handleClose = () => {
    emit('update:open', false);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const getRoleBadgeClass = (roleName: string) => {
    const roleClasses: Record<string, string> = {
        'administrator': 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-200',
        'supervisor': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200',
        'moderator': 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200',
        'normal': 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200',
    };
    return roleClasses[roleName] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200';
};

const getRoleDescription = (roleName: string) => {
    const roleDescriptions: Record<string, string> = {
        'administrator': t('Full access to vessel including deletion and user management'),
        'supervisor': t('Can view, edit vessel information and manage crew'),
        'moderator': t('Can view and edit basic vessel information'),
        'normal': t('Basic read-only access to vessel information'),
    };
    return roleDescriptions[roleName] || null;
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ t('Crew Role Details') }}</DialogTitle>
                <DialogDescription>
                    {{ t('View detailed information about this crew role') }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="isLoading" class="py-8 flex items-center justify-center">
                <Icon name="loader-2" class="h-6 w-6 animate-spin text-muted-foreground" />
            </div>

            <div v-else-if="crewPositionData" class="py-4">
                <div class="space-y-6">
                    <!-- Basic Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">
                            {{ t('Role Information') }}
                        </h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Role Name') }}</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground font-medium">{{ crewPositionData.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Scope') }}</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            crewPositionData.is_global
                                                ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200'
                                                : 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200',
                                        ]"
                                    >
                                        {{ crewPositionData.scope_label }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Crew Members') }}</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground">
                                    {{ crewPositionData.crew_members_count || 0 }} {{ t('member(s) assigned') }}
                                </dd>
                            </div>
                            <div v-if="crewPositionData.vessel_role">
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Access Level') }}</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            getRoleBadgeClass(crewPositionData.vessel_role.name)
                                        ]"
                                    >
                                        {{ crewPositionData.vessel_role.display_name }}
                                    </span>
                                    <p v-if="crewPositionData.vessel_role.description" class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                                        {{ getRoleDescription(crewPositionData.vessel_role.name) || crewPositionData.vessel_role.description }}
                                    </p>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- System Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">
                            {{ t('System Information') }}
                        </h3>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Created') }}</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground">
                                    {{ formatDate(crewPositionData.created_at) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Last Updated') }}</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground">
                                    {{ formatDate(crewPositionData.updated_at) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button
                    variant="outline"
                    @click="handleClose"
                >
                    {{ t('Close') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>


