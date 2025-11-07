<template>
    <div v-if="hasAccess">
        <slot />
    </div>
    <div v-else-if="fallback" class="text-muted-foreground text-sm">
        {{ fallback }}
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePermissions } from '@/composables/usePermissions'

const props = defineProps({
    permission: {
        type: String,
        default: null
    },
    role: {
        type: [String, Array],
        default: null
    },
    fallback: {
        type: String,
        default: null
    }
})

const { hasPermission, hasRole, hasAnyRole } = usePermissions()

const hasAccess = computed(() => {
    // Check permission if provided
    if (props.permission && !hasPermission(props.permission)) {
        return false
    }

    // Check role if provided
    if (props.role) {
        const roles = Array.isArray(props.role) ? props.role : [props.role]
        if (!hasAnyRole(roles)) {
            return false
        }
    }

    // If no permission or role specified, allow access
    return true
})
</script>
