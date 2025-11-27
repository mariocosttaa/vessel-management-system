<script setup lang="ts">
import { watch, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import BaseModal from '@/components/modals/BaseModal.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import InputError from '@/components/InputError.vue';
import { useI18n } from '@/composables/useI18n';

interface CrewPosition {
    id: number;
    name: string;
    is_global: boolean;
    is_administrative?: boolean;
    vessel_role_access_id?: number | null;
    vessel_role?: {
        id: number;
        name: string;
        display_name: string;
        description?: string;
    } | null;
}

interface VesselRole {
    id: number;
    name: string;
    display_name: string;
    description?: string;
}

interface Props {
    open: boolean;
    crewPosition?: CrewPosition | null;
}

const props = defineProps<Props>();
const { t } = useI18n();
const page = usePage();

// Get vessel roles from page props or API data
const vesselRoles = computed(() => {
    // Try to get from page props first
    const pageRoles = (page.props as any).vesselRoles;
    if (pageRoles && pageRoles.length > 0) {
        return pageRoles;
    }
    return [];
});

// Convert to Select options
const roleOptions = computed(() => {
    const options = [
        { value: '', label: t('No Role (Default)') }
    ];
    vesselRoles.value.forEach((role: VesselRole) => {
        options.push({
            value: role.id.toString(),
            label: role.display_name
        });
    });
    return options;
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    'saved': [];
    'open-permissions-info': [];
}>();

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel IDs (alphanumeric strings) or numeric IDs
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

const form = useForm({
    name: '',
    is_administrative: false,
    vessel_role_access_id: '' as string | number | null,
});

// API URL for lazy loading
const apiUrl = computed(() => {
    if (props.crewPosition) {
        const vesselId = getCurrentVesselId();
        return vesselId ? `/panel/${vesselId}/api/crew-roles/${props.crewPosition.id}/details` : undefined;
    }
    return undefined;
});

// Watch for crew position changes to populate form
watch(() => props.crewPosition, (position) => {
    if (position) {
        form.name = position.name;
        form.is_administrative = position.is_administrative ?? false;
        form.vessel_role_access_id = position.vessel_role_access_id
            ? position.vessel_role_access_id.toString()
            : '';
    } else {
        form.reset();
    }
}, { immediate: true });

// Reset form when modal opens/closes
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        if (props.crewPosition) {
            form.name = props.crewPosition.name;
        }
        form.clearErrors();
    } else {
        form.reset();
        form.clearErrors();
    }
});

// Handle data loaded from API (for lazy loading)
const handleDataLoaded = (data: any) => {
    if (data?.crewPosition) {
        form.name = data.crewPosition.name;
        form.is_administrative = data.crewPosition.is_administrative ?? false;
        form.vessel_role_access_id = data.crewPosition.vessel_role_access_id
            ? data.crewPosition.vessel_role_access_id.toString()
            : '';
        form.clearErrors();
    }
    // Update vessel roles if provided in API response
    if (data?.vesselRoles) {
        // Store in a way that roleOptions can access
        (page.props as any).vesselRoles = data.vesselRoles;
    }
};

const handleSave = () => {
    const vesselId = getCurrentVesselId();
    if (!vesselId || !props.crewPosition) return;

    // Convert vessel_role_access_id to number or null before submitting
    const roleId = form.vessel_role_access_id && form.vessel_role_access_id !== ''
        ? Number(form.vessel_role_access_id)
        : null;

    // Update form data with converted role ID
    form.vessel_role_access_id = roleId;

    form.put(`/panel/${vesselId}/crew-roles/${props.crewPosition.id}`, {
        onSuccess: () => {
            emit('saved');
            emit('update:open', false);
        },
    });
};

const handleClose = () => {
    emit('update:open', false);
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <BaseModal
        :open="open"
        :title="t('Edit Crew Role')"
        :description="t('Update crew role information')"
        size="lg"
        :loading="form.processing"
        :disabled="form.processing"
        :api-url="apiUrl"
        :enable-lazy-loading="true"
        :confirm-text="t('Update')"
        @update:open="handleClose"
        @confirm="handleSave"
        @cancel="handleClose"
        @data-loaded="handleDataLoaded"
    >
        <template #default="{ loading: apiLoading, data }">
            <form @submit.prevent="handleSave" class="space-y-6">
                <!-- Role Name -->
                <div>
                    <Label for="name" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                        {{ t('Role Name') }} <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        :placeholder="t('Enter role name (e.g., Captain, Engineer)')"
                        required
                        :disabled="apiLoading"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.name }"
                    />
                    <InputError :message="form.errors.name" class="mt-1" />
                </div>

                <!-- Scope Info (read-only) -->
                <div v-if="props.crewPosition || data?.crewPosition">
                    <Label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                        {{ t('Scope') }}
                    </Label>
                    <div class="mt-1">
                        <span
                            :class="[
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                (props.crewPosition?.is_global || data?.crewPosition?.is_global)
                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200'
                                    : 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200',
                            ]"
                        >
                            {{ (props.crewPosition?.is_global || data?.crewPosition?.is_global) ? t('Default') : t('Created') }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ t('The scope cannot be changed after creation.') }}
                    </p>
                </div>

                <!-- Vessel Role Access -->
                <div>
                    <Label for="vessel_role_access_id" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                        {{ t('Access Level') }}
                    </Label>
                    <Select
                        id="vessel_role_access_id"
                        v-model="form.vessel_role_access_id"
                        :options="roleOptions"
                        :placeholder="t('Select access level for this position')"
                        searchable
                        :disabled="apiLoading"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.vessel_role_access_id }"
                    >
                        <template #option="{ option }">
                            <div>
                                <div class="font-medium">{{ option.label }}</div>
                                <div v-if="option.value && vesselRoles.find((r: VesselRole) => r.id.toString() === option.value)" class="text-xs text-muted-foreground">
                                    {{ vesselRoles.find((r: VesselRole) => r.id.toString() === option.value)?.description }}
                                </div>
                            </div>
                        </template>
                    </Select>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ t('Members assigned to this position will automatically receive this access level. Changing this will update all existing members with this position.') }}
                    </p>
                    <InputError :message="form.errors.vessel_role_access_id" class="mt-1" />
                </div>

                <!-- Administrative Role -->
                <div>
                    <div class="flex items-center justify-between">
                        <Label for="is_administrative" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            {{ t('Administrative Role') }}
                        </Label>
                        <Switch
                            id="is_administrative"
                            v-model:checked="form.is_administrative"
                            :disabled="apiLoading"
                        />
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ t('Mark this role as administrative. Administrative roles will appear in a separate tab.') }}
                    </p>
                    <InputError :message="form.errors.is_administrative" class="mt-1" />
                </div>
            </form>
        </template>
    </BaseModal>
</template>

