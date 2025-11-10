<template>
  <div class="min-h-screen flex flex-col dark:bg-[#121212] bg-background">
    <!-- Navbar -->
    <Navbar :user="user" />

    <!-- Breadcrumbs -->
    <div v-if="breadcrumbs && breadcrumbs.length > 0" class="pt-28 pb-5 dark:bg-[#121212] bg-background">
      <div class="max-w-7xl mx-auto px-4">
        <nav class="flex items-center space-x-2">
          <Icon name="home" class="w-3.5 h-3.5 text-muted-foreground" />
          <template v-for="(breadcrumb, index) in breadcrumbs" :key="index">
            <Icon name="chevron-right" class="w-3.5 h-3.5 text-muted-foreground mx-1" />
            <span
              v-if="index === breadcrumbs.length - 1"
              class="text-xs font-medium text-card-foreground"
            >
              {{ breadcrumb.title }}
            </span>
            <Link
              v-else
              :href="breadcrumb.href"
              class="text-xs text-muted-foreground hover:text-card-foreground transition-colors"
            >
              {{ breadcrumb.title }}
            </Link>
          </template>
        </nav>
      </div>
    </div>

    <!-- Main Content -->
    <main class="flex-1 pt-0">
      <slot />
    </main>

    <!-- Footer -->
    <Footer />

    <!-- Global Notifications -->
    <NotificationContainer />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import Navbar from '@/layouts/IndexDefault/Navbar.vue'
import Footer from '@/layouts/IndexDefault/Footer.vue'
import NotificationContainer from '@/components/NotificationContainer.vue'
import Icon from '@/components/Icon.vue'
import type { BreadcrumbItemType } from '@/types'

interface Props {
  breadcrumbs?: BreadcrumbItemType[]
}

const props = withDefaults(defineProps<Props>(), {
  breadcrumbs: () => []
})

const page = usePage()

const user = computed(() => page.props.auth?.user)
</script>

