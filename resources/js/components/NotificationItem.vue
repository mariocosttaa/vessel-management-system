<template>
    <div
        :class="notificationClasses"
        class="rounded-lg border p-4 shadow-lg backdrop-blur-sm"
        role="alert"
    >
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <Icon :name="iconName" :class="iconClasses" />
            </div>

            <div class="flex-1 min-w-0">
                <h4 class="text-sm font-semibold text-foreground">
                    {{ notification.title }}
                </h4>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ notification.message }}
                </p>
            </div>

            <div class="flex-shrink-0">
                <button
                    @click="$emit('remove', notification.id)"
                    class="text-muted-foreground hover:text-foreground transition-colors"
                    aria-label="Close notification"
                >
                    <Icon name="x" class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- Progress bar for auto-dismiss -->
        <div
            v-if="!notification.persistent && notification.duration"
            class="absolute bottom-0 left-0 h-1 bg-current opacity-20 rounded-b-lg"
            :style="{ animation: `shrink ${notification.duration}ms linear forwards` }"
        />

        <!-- Circular loader at border -->
        <div
            v-if="!notification.persistent && notification.duration"
            class="absolute top-0 right-0 w-4 h-4 border-2 border-current border-t-transparent rounded-full opacity-30"
            :style="{ animation: `spin ${notification.duration}ms linear forwards` }"
        />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';
import type { Notification } from '@/composables/useNotifications';

const props = defineProps<{
    notification: Notification;
}>();

defineEmits(['remove']);

const notificationClasses = computed(() => {
    const baseClasses = 'relative overflow-hidden'

    switch (props.notification.type) {
        case 'success':
            return `${baseClasses} bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-200`
        case 'error':
            return `${baseClasses} bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200`
        case 'warning':
            return `${baseClasses} bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-200`
        case 'info':
            return `${baseClasses} bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-200`
        default:
            return `${baseClasses} bg-card border-border text-card-foreground`
    }
});

const iconClasses = computed(() => {
    const baseClasses = 'h-5 w-5'

    switch (props.notification.type) {
        case 'success':
            return `${baseClasses} text-green-600 dark:text-green-400`
        case 'error':
            return `${baseClasses} text-red-600 dark:text-red-400`
        case 'warning':
            return `${baseClasses} text-yellow-600 dark:text-yellow-400`
        case 'info':
            return `${baseClasses} text-blue-600 dark:text-blue-400`
        default:
            return `${baseClasses} text-muted-foreground`
    }
});

const iconName = computed(() => {
    switch (props.notification.type) {
        case 'success':
            return 'check-circle'
        case 'error':
            return 'x-circle'
        case 'warning':
            return 'alert-triangle'
        case 'info':
            return 'info'
        default:
            return 'info'
    }
});
</script>

<style scoped>
@keyframes shrink {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
