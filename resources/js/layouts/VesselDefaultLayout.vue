<template>
  <div class="min-h-screen bg-background">
    <!-- Sidebar Layout -->
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
      <slot />
    </AppSidebarLayout>

    <!-- Global Notifications -->
    <NotificationContainer />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue'
import NotificationContainer from '@/components/NotificationContainer.vue'
import type { BreadcrumbItemType } from '@/types'

interface Props {
  breadcrumbs?: BreadcrumbItemType[]
}

const props = withDefaults(defineProps<Props>(), {
  breadcrumbs: () => []
})
</script>
