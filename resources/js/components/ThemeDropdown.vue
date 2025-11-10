<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <button
        class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-muted/40 hover:bg-muted/70 dark:bg-muted/20 dark:hover:bg-muted/40 transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
        :title="themeTitle"
      >
        <Icon :name="themeIcon" class="w-4 h-4 text-muted-foreground group-hover:text-foreground transition-colors" />
      </button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-44">
      <DropdownMenuRadioGroup :model-value="appearance" @update:model-value="updateAppearance">
        <DropdownMenuRadioItem value="light">
          <div class="flex items-center gap-2">
            <Icon name="sun" class="w-4 h-4" />
            <span>Light</span>
          </div>
        </DropdownMenuRadioItem>
        <DropdownMenuRadioItem value="dark">
          <div class="flex items-center gap-2">
            <Icon name="moon" class="w-4 h-4" />
            <span>Dark</span>
          </div>
        </DropdownMenuRadioItem>
        <DropdownMenuRadioItem value="system">
          <div class="flex items-center gap-2">
            <Icon name="monitor" class="w-4 h-4" />
            <span>Automatic</span>
          </div>
        </DropdownMenuRadioItem>
      </DropdownMenuRadioGroup>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppearance } from '@/composables/useAppearance'
import Icon from '@/components/Icon.vue'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

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
      return 'Light mode'
    case 'dark':
      return 'Dark mode'
    case 'system':
    default:
      return 'Automatic (System)'
  }
})
</script>

