import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

interface User {
    id: number
    name: string
    email: string
    vessel_role?: string // Current vessel role
    permissions: Record<string, boolean>
}

interface PageProps {
    auth: {
        user: User | null
    }
}

export function usePermissions() {
    const page = usePage<PageProps>()

    const user = computed(() => page.props.auth.user)
    const permissions = computed(() => user.value?.permissions || {})
    const currentVesselRole = computed(() => user.value?.vessel_role || 'viewer')

    const hasRole = (role: string): boolean => {
        return currentVesselRole.value === role
    }

    const hasAnyRole = (roleList: string[]): boolean => {
        return roleList.includes(currentVesselRole.value)
    }

    const hasAllRoles = (roleList: string[]): boolean => {
        return roleList.includes(currentVesselRole.value)
    }

    const hasPermission = (permission: string): boolean => {
        return permissions.value[permission] === true
    }

    const can = (action: string, resource: string): boolean => {
        const permission = `${resource}.${action}`
        return hasPermission(permission)
    }

    const canView = (resource: string): boolean => can('view', resource)
    const canCreate = (resource: string): boolean => can('create', resource)
    const canEdit = (resource: string): boolean => can('edit', resource)
    const canDelete = (resource: string): boolean => can('delete', resource)

    const isAdmin = computed(() => hasRole('Administrator'))
    const isSupervisor = computed(() => hasRole('Supervisor'))
    const isModerator = computed(() => hasRole('Moderator'))
    const isNormal = computed(() => hasRole('Normal User'))

    return {
        user,
        permissions,
        currentVesselRole,
        hasRole,
        hasAnyRole,
        hasAllRoles,
        hasPermission,
        can,
        canView,
        canCreate,
        canEdit,
        canDelete,
        isAdmin,
        isSupervisor,
        isModerator,
        isNormal
    }
}
