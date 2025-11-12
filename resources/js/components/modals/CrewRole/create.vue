<script setup lang="ts">
import { watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseModal from '@/components/modals/BaseModal.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Select from '@/components/ui/select/Select.vue';
import Icon from '@/components/Icon.vue';
import { useI18n } from '@/composables/useI18n';

interface VesselRoleAccess {
    id: number;
    name: string;
    display_name: string;
    description: string;
}

interface Props {
    open: boolean;
    vesselRoleAccesses?: VesselRoleAccess[];
}

const props = defineProps<Props>();
const { t } = useI18n();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'saved': [];
    'open-permissions-info': [];
}>();

const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

const form = useForm({
    name: '',
    description: '',
    is_global: false, // Always false - users can only create vessel-specific roles
    vessel_role_access_id: null as number | null,
});

// Reset form when modal opens/closes
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.is_global = false; // Always false - users can only create vessel-specific roles
        form.vessel_role_access_id = null;
        form.clearErrors();
    } else {
        form.reset();
        form.clearErrors();
    }
});

// Prepare select options for vessel role accesses
const vesselRoleAccessOptions = computed(() => {
    return (props.vesselRoleAccesses || []).map(role => ({
        value: role.id,
        label: `${role.display_name} - ${role.description}`,
    }));
});

const handleSave = () => {
    const vesselId = getCurrentVesselId();
    if (!vesselId) return;

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

                <!-- Description -->
                <div>
                    <Label for="description" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                        {{ t('Description') }}
                    </Label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        :placeholder="t('Enter role description (optional)')"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.description }"
                    ></textarea>
                    <InputError :message="form.errors.description" class="mt-1" />
                </div>

                <!-- Permission Level -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <Label for="vessel_role_access_id" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            {{ t('Permission Level') }}
                        </Label>
                        <button
                            type="button"
                            @click="emit('open-permissions-info')"
                            class="text-xs text-primary hover:underline flex items-center gap-1"
                        >
                            <Icon name="info" class="h-3 w-3" />
                            {{ t('Learn about permission types') }}
                        </button>
                    </div>
                    <Select
                        id="vessel_role_access_id"
                        v-model="form.vessel_role_access_id"
                        :options="vesselRoleAccessOptions"
                        :placeholder="t('Select a permission level (optional)')"
                        :searchable="true"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.vessel_role_access_id }"
                    />
                    <InputError :message="form.errors.vessel_role_access_id" class="mt-1" />
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ t('Select the permission level for this crew role. This determines what actions users with this role can perform.') }}
                    </p>
                </div>
            </form>
        </template>
    </BaseModal>
</template>

