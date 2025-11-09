<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseModal from '@/components/modals/BaseModal.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

interface CrewPosition {
    id: number;
    name: string;
    description?: string;
    is_global: boolean;
}

interface Props {
    open: boolean;
    crewPosition?: CrewPosition | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'saved': [];
}>();

const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

const form = useForm({
    name: '',
    description: '',
    is_global: false,
});

// Computed properties
const isEditing = computed(() => !!props.crewPosition);

const modalTitle = computed(() =>
    isEditing.value ? 'Edit Crew Role' : 'Create Crew Role'
);

const modalDescription = computed(() =>
    isEditing.value
        ? 'Update crew role information'
        : 'Add a new crew role. Default roles are available to all vessels, while vessel-specific roles are only available to this vessel.'
);

// API URL for edit modal (lazy loading)
const apiUrl = computed(() => {
    if (isEditing.value && props.crewPosition) {
        const vesselId = getCurrentVesselId();
        return vesselId ? `/panel/${vesselId}/api/crew-roles/${props.crewPosition.id}/details` : undefined;
    }
    return undefined;
});

// Watch for crew position changes to populate form
watch(() => props.crewPosition, (position) => {
    if (position) {
        form.name = position.name;
        form.description = position.description || '';
        form.is_global = position.is_global;
    } else {
        form.reset();
        form.is_global = false;
    }
}, { immediate: true });

// Reset form when modal opens/closes
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        if (!props.crewPosition) {
            // Reset form for create
            form.reset();
            form.is_global = false;
        }
        form.clearErrors();
    } else {
        form.reset();
        form.clearErrors();
    }
});

// Handle data loaded from API (for edit modal)
const handleDataLoaded = (data: any) => {
    if (data?.crewPosition) {
        form.name = data.crewPosition.name;
        form.description = data.crewPosition.description || '';
        form.is_global = data.crewPosition.is_global;
        form.clearErrors();
    }
};

const handleSave = () => {
    const vesselId = getCurrentVesselId();
    if (!vesselId) return;

    if (isEditing.value && props.crewPosition) {
        // Update
        form.put(`/panel/${vesselId}/crew-roles/${props.crewPosition.id}`, {
            onSuccess: () => {
                emit('saved');
                emit('update:open', false);
            },
        });
    } else {
        // Create
        form.post(`/panel/${vesselId}/crew-roles`, {
            onSuccess: () => {
                emit('saved');
                emit('update:open', false);
            },
        });
    }
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
        :title="modalTitle"
        :description="modalDescription"
        size="lg"
        :loading="form.processing"
        :disabled="form.processing"
        :api-url="apiUrl"
        :enable-lazy-loading="isEditing"
        :confirm-text="isEditing ? 'Update' : 'Create'"
        @update:open="handleClose"
        @confirm="handleSave"
        @cancel="handleClose"
        @data-loaded="handleDataLoaded"
    >
        <template #default="{ loading: apiLoading }">
            <form @submit.prevent="handleSave" class="space-y-6">
                <!-- Role Name -->
                <div>
                    <Label for="name" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                        Role Name <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        placeholder="Enter role name (e.g., Captain, Engineer)"
                        required
                        :disabled="apiLoading"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.name }"
                    />
                    <InputError :message="form.errors.name" class="mt-1" />
                </div>

                <!-- Description -->
                <div>
                    <Label for="description" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                        Description
                    </Label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        placeholder="Enter role description (optional)"
                        :disabled="apiLoading"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.description }"
                    ></textarea>
                    <InputError :message="form.errors.description" class="mt-1" />
                </div>

                <!-- Default Role Toggle (only for create) -->
                <div v-if="!isEditing">
                    <div class="flex items-center space-x-2">
                        <input
                            id="is_global"
                            v-model="form.is_global"
                            type="checkbox"
                            :disabled="apiLoading"
                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <Label for="is_global" class="text-sm font-medium text-card-foreground dark:text-card-foreground cursor-pointer">
                            Make this a default role (available to all vessels)
                        </Label>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Default roles are shared across all vessels. Vessel-specific roles are only available to this vessel.
                    </p>
                    <InputError :message="form.errors.is_global" class="mt-1" />
                </div>

                <!-- Scope Info (for edit) -->
                <div v-if="isEditing && props.crewPosition">
                    <Label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                        Scope
                    </Label>
                    <div class="mt-1">
                        <span
                            :class="[
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                props.crewPosition.is_global
                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200'
                                    : 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200',
                            ]"
                        >
                            {{ props.crewPosition.is_global ? 'Default' : 'Vessel-Specific' }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        The scope cannot be changed after creation.
                    </p>
                </div>
            </form>
        </template>
    </BaseModal>
</template>

