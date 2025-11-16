<script setup lang="ts">
import { watch, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import BaseModal from '@/components/modals/BaseModal.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import { useI18n } from '@/composables/useI18n';

interface VesselRole {
    id: number;
    name: string;
    display_name: string;
    description?: string;
}

interface Props {
    open: boolean;
}

const props = defineProps<Props>();
const { t } = useI18n();
const page = usePage();

// Get vessel roles from page props
const vesselRoles = computed(() => {
    return (page.props as any).vesselRoles || [];
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
    is_global: false, // Always false - users can only create vessel-specific roles
    vessel_role_access_id: '' as string | number | null,
});

// Reset form when modal opens/closes
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.is_global = false; // Always false - users can only create vessel-specific roles
        form.clearErrors();
    } else {
        form.reset();
        form.clearErrors();
    }
});

const handleSave = () => {
    const vesselId = getCurrentVesselId();
    if (!vesselId) return;

    // Convert vessel_role_access_id to number or null before submitting
    const roleId = form.vessel_role_access_id && form.vessel_role_access_id !== ''
        ? Number(form.vessel_role_access_id)
        : null;

    // Update form data with converted role ID
    form.vessel_role_access_id = roleId;

    form.post(`/panel/${vesselId}/crew-roles`, {
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
        :title="t('Create Crew Role')"
        :description="t('Add a new crew role for this vessel. This role will only be available to this vessel.')"
        size="lg"
        :loading="form.processing"
        :disabled="form.processing"
        :confirm-text="t('Create')"
        @update:open="handleClose"
        @confirm="handleSave"
        @cancel="handleClose"
    >
        <template #default>
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
                        :class="{ 'border-destructive dark:border-destructive': form.errors.name }"
                    />
                    <InputError :message="form.errors.name" class="mt-1" />
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
                        {{ t('Members assigned to this position will automatically receive this access level.') }}
                    </p>
                    <InputError :message="form.errors.vessel_role_access_id" class="mt-1" />
                </div>
            </form>
        </template>
    </BaseModal>
</template>

