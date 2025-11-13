<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { useNotifications } from '@/composables/useNotifications';
import { useI18n } from '@/composables/useI18n';
import { Plus, ChevronDown, ChevronUp } from 'lucide-vue-next';

interface Props {
    open: boolean;
    categoryType: 'income' | 'expense'; // Type of category to create
    onSuccess?: (category: { id: string; name: string; type: string; color: string }) => void;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [category: { id: string; name: string; type: string; color: string }];
}>();

const { addNotification } = useNotifications();
const { t } = useI18n();

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel IDs (alphanumeric strings) or numeric IDs
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

// Color presets for category
const colorPresets = [
    '#ef4444', '#f59e0b', '#eab308', '#84cc16', '#22c55e',
    '#10b981', '#14b8a6', '#06b6d4', '#3b82f6', '#6366f1',
    '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e',
    '#64748b', '#475569', '#334155', '#1e293b', '#0f172a',
];

const typeOptions = [
    { value: 'income', label: t('Income') },
    { value: 'expense', label: t('Expense') },
];

const form = useForm({
    name: '',
    type: props.categoryType,
    color: colorPresets[Math.floor(Math.random() * colorPresets.length)],
    description: '',
});

// Loading state for manual fetch
const isSubmitting = ref(false);

// Color section visibility
const showColorOptions = ref(false);

// Watch categoryType prop to update form type
watch(() => props.categoryType, (newType) => {
    form.type = newType;
});

// Reset form when modal opens
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.type = props.categoryType;
        form.color = colorPresets[Math.floor(Math.random() * colorPresets.length)];
        form.clearErrors();
        showColorOptions.value = false; // Hide color options by default
    }
});

const handleDialogUpdate = (value: boolean) => {
    if (!value) {
        emit('close');
    }
};

const submit = () => {
    const vesselId = getCurrentVesselId();
    if (!vesselId) {
        addNotification({
            type: 'error',
            title: t('Error'),
            message: t('Unable to determine vessel ID.'),
        });
        return;
    }

    // Set loading state
    isSubmitting.value = true;

    // Use FormData for the request (supports multipart if needed in future)
    const formData = new FormData();
    formData.append('name', form.name);
    formData.append('type', form.type);
    if (form.color) {
        formData.append('color', form.color);
    }
    if (form.description) {
        formData.append('description', form.description);
    }

    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        addNotification({
            type: 'error',
            title: t('Error'),
            message: t('CSRF token not found. Please refresh the page.'),
        });
        return;
    }

    // Use fetch for JSON response
    fetch(`/panel/${vesselId}/categories`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: formData,
    })
    .then(async response => {
        const responseData = await response.json().catch(() => ({}));
        if (!response.ok) {
            // Try to get validation errors from response
            if (responseData.errors) {
                // Set form errors manually for display
                Object.keys(responseData.errors).forEach(key => {
                    const errorMessage = Array.isArray(responseData.errors[key])
                        ? responseData.errors[key][0]
                        : responseData.errors[key];
                    // Use Inertia form setError if available, otherwise just log
                    if (typeof form.setError === 'function') {
                        form.setError(key, errorMessage);
                    }
                });
            }
            throw new Error(responseData.message || 'Failed to create category');
        }
        return responseData;
    })
    .then(data => {
        if (data.category) {
            const createdCategory = data.category;

            // Emit success with the created category
            emit('success', createdCategory);

            // If parent provided onSuccess callback, call it
            if (props.onSuccess) {
                props.onSuccess(createdCategory);
            }

            // Close modal
            emit('close');

            // Reset form
            form.reset();
            form.clearErrors();

            // Reset loading state
            isSubmitting.value = false;

            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Category created successfully.'),
            });
        } else {
            isSubmitting.value = false;
            throw new Error('Category not returned from server');
        }
    })
    .catch(error => {
        console.error('Error creating category:', error);

        // Reset loading state
        isSubmitting.value = false;

        // Try to extract validation errors from response if available
        let errorMessage = error.message || t('Failed to create category. Please check the form for errors.');

        // Check if it's a validation error response
        if (error.response && error.response.data && error.response.data.errors) {
            const errors = error.response.data.errors;
            const firstError = Object.values(errors).flat()[0];
            errorMessage = firstError || errorMessage;
        }

        addNotification({
            type: 'error',
            title: t('Error'),
            message: errorMessage,
        });
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="handleDialogUpdate">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Plus class="h-5 w-5" />
                    {{ t('Create Category') }}
                </DialogTitle>
                <DialogDescription>
                    {{ t('Create a new category for this vessel') }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Category Name -->
                <div class="space-y-2">
                    <Label for="name">{{ t('Category Name') }} <span class="text-destructive">*</span></Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        :placeholder="t('Enter category name')"
                        :error="!!form.errors.name"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <!-- Category Type -->
                <div class="space-y-2">
                    <Label for="type">{{ t('Category Type') }} <span class="text-destructive">*</span></Label>
                    <Select
                        id="type"
                        v-model="form.type"
                        :options="typeOptions"
                        :placeholder="t('Select category type')"
                        :error="!!form.errors.type"
                        required
                    />
                    <InputError :message="form.errors.type" />
                </div>

                <!-- Color Selection (Collapsible) -->
                <div class="space-y-2">
                    <button
                        type="button"
                        @click="showColorOptions = !showColorOptions"
                        class="flex items-center justify-between w-full text-left"
                    >
                        <Label class="cursor-pointer">
                            {{ t('Color') }}
                        </Label>
                        <component
                            :is="showColorOptions ? ChevronUp : ChevronDown"
                            class="h-4 w-4 text-muted-foreground"
                        />
                    </button>

                    <div v-if="showColorOptions" class="space-y-2 pt-2 border-t border-border">
                        <div class="flex gap-2 items-center">
                            <input
                                id="color"
                                v-model="form.color"
                                type="color"
                                class="h-10 w-20 rounded border border-input cursor-pointer"
                            />
                            <Input
                                v-model="form.color"
                                type="text"
                                :placeholder="'#000000'"
                                :error="!!form.errors.color"
                                class="flex-1"
                                pattern="^#[0-9A-Fa-f]{6}$"
                            />
                        </div>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <button
                                v-for="presetColor in colorPresets"
                                :key="presetColor"
                                type="button"
                                class="w-8 h-8 rounded border-2 transition-all hover:scale-110"
                                :class="form.color === presetColor ? 'border-primary ring-2 ring-primary' : 'border-border'"
                                :style="{ backgroundColor: presetColor }"
                                @click="form.color = presetColor"
                            />
                        </div>
                        <InputError :message="form.errors.color" />
                        <p class="text-xs text-muted-foreground">
                            {{ t('Select a color for this category') }}
                        </p>
                    </div>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="description">{{ t('Description') }}</Label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        :placeholder="t('Optional description for this category')"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.description }"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <Button type="button" variant="outline" @click="emit('close')">
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="isSubmitting || !form.name || !form.type">
                        {{ isSubmitting ? t('Creating...') : t('Create Category') }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

