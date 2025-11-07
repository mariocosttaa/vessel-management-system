<template>
    <div class="fixed top-4 right-4 z-[9999] space-y-2 max-w-sm">
        <TransitionGroup
            name="notification"
            tag="div"
            class="space-y-2"
        >
            <NotificationItem
                v-for="notification in notifications"
                :key="notification.id"
                :notification="notification"
                @remove="removeNotification"
            />
        </TransitionGroup>
    </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useNotifications } from '@/composables/useNotifications'
import NotificationItem from '@/components/NotificationItem.vue'

const { notifications, processFlashMessages, removeNotification } = useNotifications()

// Process flash messages when component mounts
onMounted(() => {
    processFlashMessages()
})
</script>

<style scoped>
.notification-enter-active,
.notification-leave-active {
    transition: all 0.3s ease;
}

.notification-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.notification-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

.notification-move {
    transition: transform 0.3s ease;
}
</style>
