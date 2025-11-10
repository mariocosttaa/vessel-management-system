<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import IndexDefaultLayout from '@/layouts/IndexDefault/IndexDefaultLayout.vue'
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
    // Get URL from page props or window location - most reliable
    let url = ''
    if (typeof window !== 'undefined') {
        url = window.location.pathname
    }
    if (page.url?.value) {
        url = page.url.value
    }

    // Landing page (public website at /) uses IndexDefaultLayout
    if (url === '/' || url === '') {
        return IndexDefaultLayout
    }

    // Panel selector page uses IndexDefaultLayout (but different navbar)
    if (url === '/panel' || url === '/panel/') {
        return IndexDefaultLayout
    }

    // All panel routes with vessel ID use VesselLayout (app sidebar)
    if (url.startsWith('/panel/')) {
        return VesselLayout
    }

    // Default to VesselLayout for other routes
    return VesselLayout
})
</script>

<template>
    <component :is="layout" :breadcrumbs="breadcrumbs">
        <slot />
    </component>
</template>
