<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { useI18n } from '@/composables/useI18n';

interface Props {
    open: boolean;
    title: string;
    description?: string;
    size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
    showCloseButton?: boolean;
    showCancelButton?: boolean;
    showConfirmButton?: boolean;
    confirmText?: string;
    cancelText?: string;
    loading?: boolean;
    disabled?: boolean;
    // New props for API request functionality
    apiUrl?: string;
    apiMethod?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    apiData?: any;
    enableLazyLoading?: boolean;
    retryOnError?: boolean;
}

const { t } = useI18n();

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    showCloseButton: true,
    showCancelButton: true,
    showConfirmButton: true,
    confirmText: 'Save',
    cancelText: 'Cancel',
    loading: false,
    disabled: false,
    apiMethod: 'GET',
    enableLazyLoading: false,
    retryOnError: true,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    'confirm': [];
    'cancel': [];
    'close': [];
    'data-loaded': [data: any];
    'error': [error: string];
}>();

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'max-w-sm';
        case 'md':
            return 'max-w-md';
        case 'lg':
            return 'max-w-lg';
        case 'xl':
            return 'max-w-xl';
        case '2xl':
            return 'max-w-2xl';
        default:
            return 'max-w-md';
    }
});

// API request state management
const apiLoading = ref(false);
const apiError = ref<string | null>(null);
const apiData = ref<any>(null);

// Computed loading state (combines prop loading and API loading)
const isLoading = computed(() => props.loading || apiLoading.value);

// API request function
const makeApiRequest = async () => {
    if (!props.apiUrl) return;

    apiLoading.value = true;
    apiError.value = null;

    try {
        const requestOptions: RequestInit = {
            method: props.apiMethod,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
        };

        // Add body for non-GET requests
        if (props.apiMethod !== 'GET' && props.apiData) {
            requestOptions.body = JSON.stringify(props.apiData);
        }

        const response = await fetch(props.apiUrl, requestOptions);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        apiData.value = data;
        emit('data-loaded', data);
    } catch (err) {
        const errorMessage = err instanceof Error ? err.message : t('An unknown error occurred');
        apiError.value = errorMessage;
        emit('error', errorMessage);
        console.error('API request failed:', err);
    } finally {
        apiLoading.value = false;
    }
};

// Watch for modal open to trigger API request
watch(() => props.open, async (isOpen) => {
    if (isOpen && props.enableLazyLoading && props.apiUrl) {
        await makeApiRequest();
    } else if (!isOpen) {
        // Reset state when modal closes
        apiData.value = null;
        apiError.value = null;
    }
}, { immediate: true });

// Retry function for error state
const retryRequest = async () => {
    if (props.retryOnError) {
        await makeApiRequest();
    }
};

const handleClose = () => {
    emit('update:open', false);
    emit('close');
};

const handleCancel = () => {
    emit('cancel');
    handleClose();
};

const handleConfirm = () => {
    emit('confirm');
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent :class="sizeClasses">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
                <DialogDescription v-else class="sr-only">
                    {{ title }} {{ t('dialog') }}
                </DialogDescription>
            </DialogHeader>

            <!-- API Loading State -->
            <div v-if="apiLoading" class="flex items-center justify-center py-8">
                <div class="flex items-center space-x-2">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                    <span class="text-muted-foreground">{{ t('Loading data...') }}</span>
                </div>
            </div>

            <!-- API Error State -->
            <div v-else-if="apiError" class="flex items-center justify-center py-8">
                <div class="text-center">
                    <p class="text-red-600 mb-4">{{ apiError }}</p>
                    <Button v-if="retryOnError" @click="retryRequest" variant="outline">
                        {{ t('Try Again') }}
                    </Button>
                </div>
            </div>

            <!-- Main Content -->
            <div v-else class="py-4" :class="{ 'blur-sm pointer-events-none': isLoading }">
                <!-- Default slot for content -->
                <slot :data="apiData" :loading="isLoading" />
            </div>

            <!-- Footer Actions -->
            <div v-if="showCancelButton || showConfirmButton" class="flex items-center justify-end space-x-4">
                <Button
                    v-if="showCancelButton"
                    variant="outline"
                    @click="handleCancel"
                    :disabled="isLoading"
                >
                    {{ cancelText || t('Cancel') }}
                </Button>
                <Button
                    v-if="showConfirmButton"
                    @click="handleConfirm"
                    :disabled="disabled || isLoading"
                >
                    <Icon v-if="isLoading" name="loader" class="w-4 h-4 mr-2 animate-spin" />
                    {{ confirmText || t('Save') }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
