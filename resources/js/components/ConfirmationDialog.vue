<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent :class="dialogSizeClass">
            <DialogHeader>
                <DialogTitle :class="[titleClass, 'flex items-center']">
                    <Icon v-if="iconName" :name="iconName" :class="iconClass" class="mr-2 flex-shrink-0" />
                    <span>{{ title }}</span>
                </DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>
            <div v-if="message" :class="messageClass">
                {{ message }}
            </div>
            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    @click="$emit('cancel')"
                    :disabled="loading"
                >
                    {{ cancelText || t('Cancel') }}
                </Button>
                <Button
                    type="button"
                    :variant="variant"
                    @click="$emit('confirm')"
                    :disabled="loading"
                >
                    <Icon v-if="loading" name="loader-2" class="w-4 h-4 mr-2 animate-spin" />
                    {{ confirmText || t('Confirm') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { useI18n } from '@/composables/useI18n';

type DialogType = 'info' | 'warning' | 'danger';

interface Props {
    open: boolean;
    title: string;
    description: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
    type?: DialogType;
    loading?: boolean;
    size?: 'sm' | 'md' | 'lg';
}

const { t } = useI18n();

const props = withDefaults(defineProps<Props>(), {
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    variant: 'default',
    type: 'info',
    loading: false,
    size: 'md',
});

defineEmits(['update:open', 'confirm', 'cancel']);

const dialogSizeClass = computed(() => {
    switch (props.size) {
        case 'sm': return 'sm:max-w-sm';
        case 'md': return 'sm:max-w-md';
        case 'lg': return 'sm:max-w-lg';
        default: return 'sm:max-w-md';
    }
});

const iconName = computed(() => {
    switch (props.type) {
        case 'warning': return 'alert-triangle';
        case 'danger': return 'x-circle';
        case 'info': return 'info';
        default: return 'info';
    }
});

const iconClass = computed(() => {
    switch (props.type) {
        case 'warning': return 'text-yellow-500';
        case 'danger': return 'text-red-500';
        case 'info': return 'text-blue-500';
        default: return 'text-gray-500';
    }
});

const titleClass = computed(() => {
    switch (props.type) {
        case 'warning': return 'text-yellow-600';
        case 'danger': return 'text-red-600';
        case 'info': return 'text-blue-600';
        default: return 'text-gray-900';
    }
});

const messageClass = computed(() => {
    switch (props.type) {
        case 'warning': return 'text-yellow-700 text-sm';
        case 'danger': return 'text-red-700 text-sm';
        case 'info': return 'text-blue-700 text-sm';
        default: return 'text-gray-700 text-sm';
    }
});
</script>
