<script setup lang="ts">
import { watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseModal from '@/components/modals/BaseModal.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { useI18n } from '@/composables/useI18n';

interface CrewPosition {
    id: number;
    name: string;
    is_global: boolean;
}

interface Props {
    open: boolean;
    crewPosition?: CrewPosition | null;
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
        form.clearErrors();
    }
};

const handleSave = () => {
    const vesselId = getCurrentVesselId();
    if (!vesselId || !props.crewPosition) return;

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
            </form>
        </template>
    </BaseModal>
</template>

