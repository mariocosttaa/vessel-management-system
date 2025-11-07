<template>
  <div class="relative">
    <button
      @click="toggleTheme"
      class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card hover:bg-muted/50 dark:hover:bg-muted/50 transition-colors"
      :title="themeTitle"
    >
      <Icon :name="themeIcon" class="w-4 h-4 text-card-foreground dark:text-card-foreground" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppearance } from '@/composables/useAppearance'
import Icon from '@/Components/Icon.vue'

const { appearance, updateAppearance } = useAppearance()

const themeIcon = computed(() => {
  switch (appearance.value) {
    case 'light':
      return 'sun'
    case 'dark':
      return 'moon'
    case 'system':
    default:
      return 'monitor'
  }
})

const themeTitle = computed(() => {
  switch (appearance.value) {
    case 'light':
      return 'Switch to dark mode'
    case 'dark':
      return 'Switch to system theme'
    case 'system':
    default:
      return 'Switch to light mode'
  }
})

const toggleTheme = () => {
  switch (appearance.value) {
    case 'light':
      updateAppearance('dark')
      break
    case 'dark':
      updateAppearance('system')
      break
    case 'system':
    default:
      updateAppearance('light')
      break
  }
}
</script>
