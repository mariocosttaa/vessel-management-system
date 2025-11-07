<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import DefaultLayout from '@/layouts/IndexDefault/IndexDefaultLayout.vue'
import VesselLayout from '@/layouts/VesselLayout.vue'
import type { BreadcrumbItemType } from '@/types'

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage()

// Determine which layout to use based on the current route
const layout = computed(() => {
    const url = page.url?.value || ''

    // Routes that should use IndexDefaultLayout (navbar + footer only)
    const indexLayoutRoutes = [
        '/panel'
    ]

    // Check if current route matches index layout routes
    const shouldUseIndexLayout = indexLayoutRoutes.some(route => {
        return url === route
    })

    return shouldUseIndexLayout ? 'IndexDefaultLayout' : 'VesselLayout'
})
</script>

<template>
    <component :is="layout" :breadcrumbs="breadcrumbs">
        <slot />
    </component>
</template>
