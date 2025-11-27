import { ref, computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from '@/composables/useI18n'

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
    const { t } = useI18n()
    const notifications = ref<Notification[]>([])
    const processedFlashMessages = ref<Set<string>>(new Set())

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
        // Check for duplicate notifications (same type and message)
        const isDuplicate = notifications.value.some(
            n => n.type === notification.type && n.message === notification.message
        )

        if (isDuplicate) {
            // Return existing notification ID instead of creating a duplicate
            const existing = notifications.value.find(
                n => n.type === notification.type && n.message === notification.message
            )
            return existing?.id || null
        }

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

    // Process flash messages with deduplication
    const processFlashMessages = () => {
        const flash = flashMessages.value
        const customDelay = flash.notification_delay

        // Create a unique key for each flash message to track processed ones
        const createFlashKey = (type: string, message: string) => `${type}:${message}`

        if (flash.success) {
            const key = createFlashKey('success', flash.success)
            if (!processedFlashMessages.value.has(key)) {
                processedFlashMessages.value.add(key)
                addNotification({
                    type: 'success',
                    title: t('Success!'),
                    message: flash.success,
                    duration: customDelay ? customDelay * 1000 : undefined, // Convert seconds to milliseconds
                    persistent: customDelay === 0, // If delay is 0, make it persistent
                })
            }
        }

        if (flash.error) {
            const key = createFlashKey('error', flash.error)
            if (!processedFlashMessages.value.has(key)) {
                processedFlashMessages.value.add(key)
                addNotification({
                    type: 'error',
                    title: t('Error!'),
                    message: flash.error,
                    duration: customDelay ? customDelay * 1000 : undefined,
                    persistent: customDelay === 0 || customDelay === undefined, // Default persistent for errors
                })
            }
        }

        if (flash.warning) {
            const key = createFlashKey('warning', flash.warning)
            if (!processedFlashMessages.value.has(key)) {
                processedFlashMessages.value.add(key)
                addNotification({
                    type: 'warning',
                    title: t('Warning!'),
                    message: flash.warning,
                    duration: customDelay ? customDelay * 1000 : undefined,
                    persistent: customDelay === 0,
                })
            }
        }

        if (flash.info) {
            const key = createFlashKey('info', flash.info)
            if (!processedFlashMessages.value.has(key)) {
                processedFlashMessages.value.add(key)
                addNotification({
                    type: 'info',
                    title: t('Information!'),
                    message: flash.info,
                    duration: customDelay ? customDelay * 1000 : undefined,
                    persistent: customDelay === 0,
                })
            }
        }
    }

    // Clear processed flash messages when navigating to a new page
    // This ensures new flash messages on new pages can be processed
    watch(() => page.url, () => {
        processedFlashMessages.value.clear()
    })

    // Watch for flash message changes (for subsequent updates)
    watch(flashMessages, (newFlash, oldFlash) => {
        const customDelay = newFlash.notification_delay

        if (newFlash.success && newFlash.success !== oldFlash?.success) {
            addNotification({
                type: 'success',
                title: t('Success!'),
                message: newFlash.success,
                duration: customDelay ? customDelay * 1000 : undefined,
                persistent: customDelay === 0,
            })
        }

        if (newFlash.error && newFlash.error !== oldFlash?.error) {
            addNotification({
                type: 'error',
                title: t('Error!'),
                message: newFlash.error,
                duration: customDelay ? customDelay * 1000 : undefined,
                persistent: customDelay === 0 || customDelay === undefined,
            })
        }

        if (newFlash.warning && newFlash.warning !== oldFlash?.warning) {
            addNotification({
                type: 'warning',
                title: t('Warning!'),
                message: newFlash.warning,
                duration: customDelay ? customDelay * 1000 : undefined,
                persistent: customDelay === 0,
            })
        }

        if (newFlash.info && newFlash.info !== oldFlash?.info) {
            addNotification({
                type: 'info',
                title: t('Information!'),
                message: newFlash.info,
                duration: customDelay ? customDelay * 1000 : undefined,
                persistent: customDelay === 0,
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
