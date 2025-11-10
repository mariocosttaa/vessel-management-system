<template>
  <div class="relative">
    <button
      @click="toggleTheme"
      class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-muted/40 hover:bg-muted/70 dark:bg-muted/20 dark:hover:bg-muted/40 transition-all duration-200 group"
      :title="themeTitle"
    >
      <Icon :name="themeIcon" class="w-4 h-4 text-muted-foreground group-hover:text-foreground transition-colors" />
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
