<script setup lang="ts">
import { watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseModal from '@/components/modals/BaseModal.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { useI18n } from '@/composables/useI18n';

interface Props {
    open: boolean;
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
    is_global: false, // Always false - users can only create vessel-specific roles
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
            </form>
        </template>
    </BaseModal>
</template>

