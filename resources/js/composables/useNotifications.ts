import { ref, computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

export interface Notification {
    id: string
    type: 'success' | 'error' | 'warning' | 'info'
    title: string
    message: string
    duration?: number
    persistent?: boolean
}

export function useNotifications() {
    const page = usePage()
    const notifications = ref<Notification[]>([])

    // Get flash messages from Inertia
    const flashMessages = computed(() => {
        const flash = page.props.flash as any
        return {
            success: flash?.success,
            error: flash?.error,
            warning: flash?.warning,
            info: flash?.info,
            notification_delay: flash?.notification_delay,
        }
    })

    const addNotification = (notification: Omit<Notification, 'id'>) => {
        const id = Math.random().toString(36).substr(2, 9)
        const newNotification: Notification = {
            id,
            duration: 5000, // 5 seconds default
            persistent: false,
            ...notification,
        }

        notifications.value.push(newNotification)

        // Auto-remove notification after duration
        if (!newNotification.persistent && newNotification.duration) {
            setTimeout(() => {
                removeNotification(id)
            }, newNotification.duration)
        }

        return id
    }

    const removeNotification = (id: string) => {
        const index = notifications.value.findIndex(n => n.id === id)
        if (index > -1) {
            notifications.value.splice(index, 1)
        }
    }

    const clearAllNotifications = () => {
        notifications.value = []
    }

    // Convenience methods
    const success = (title: string, message: string, options?: Partial<Notification>) => {
        return addNotification({
            type: 'success',
            title,
            message,
            ...options,
        })
    }

    const error = (title: string, message: string, options?: Partial<Notification>) => {
        return addNotification({
            type: 'error',
            title,
            message,
            persistent: true, // Errors should persist until manually dismissed
            ...options,
        })
    }

    const warning = (title: string, message: string, options?: Partial<Notification>) => {
        return addNotification({
            type: 'warning',
            title,
            message,
            ...options,
        })
    }

    const info = (title: string, message: string, options?: Partial<Notification>) => {
        return addNotification({
            type: 'info',
            title,
            message,
            ...options,
        })
    }

    // Process flash messages immediately when they're available
    const processFlashMessages = () => {
        const flash = flashMessages.value
        const customDelay = flash.notification_delay

        if (flash.success) {
            addNotification({
                type: 'success',
                title: 'Success',
                message: flash.success,
                duration: customDelay ? customDelay * 1000 : undefined, // Convert seconds to milliseconds
                persistent: customDelay === 0, // If delay is 0, make it persistent
            })
        }

        if (flash.error) {
            addNotification({
                type: 'error',
                title: 'Error',
                message: flash.error,
                duration: customDelay ? customDelay * 1000 : undefined,
                persistent: customDelay === 0 || customDelay === undefined, // Default persistent for errors
            })
        }

        if (flash.warning) {
            addNotification({
                type: 'warning',
                title: 'Warning',
                message: flash.warning,
                duration: customDelay ? customDelay * 1000 : undefined,
                persistent: customDelay === 0,
            })
        }

        if (flash.info) {
            addNotification({
                type: 'info',
                title: 'Information',
                message: flash.info,
                duration: customDelay ? customDelay * 1000 : undefined,
                persistent: customDelay === 0,
            })
        }
    }

    // Process flash messages immediately when component mounts
    processFlashMessages()

    // Watch for flash message changes (for subsequent updates)
    watch(flashMessages, (newFlash, oldFlash) => {
        if (newFlash.success && newFlash.success !== oldFlash?.success) {
            addNotification({
                type: 'success',
                title: 'Success',
                message: newFlash.success,
            })
        }

        if (newFlash.error && newFlash.error !== oldFlash?.error) {
            addNotification({
                type: 'error',
                title: 'Error',
                message: newFlash.error,
            })
        }

        if (newFlash.warning && newFlash.warning !== oldFlash?.warning) {
            addNotification({
                type: 'warning',
                title: 'Warning',
                message: newFlash.warning,
            })
        }

        if (newFlash.info && newFlash.info !== oldFlash?.info) {
            addNotification({
                type: 'info',
                title: 'Information',
                message: newFlash.info,
            })
        }
    }, { deep: true })

    return {
        notifications: computed(() => notifications.value),
        flashMessages,
        processFlashMessages,
        addNotification,
        removeNotification,
        clearAllNotifications,
        success,
        error,
        warning,
        info,
    }
}
