<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <button
        class="flex items-center justify-center w-full px-3 py-2 rounded-md text-sm font-medium text-muted-foreground hover:text-card-foreground hover:bg-muted transition-colors"
        :title="themeTitle"
      >
        <Icon :name="themeIcon" class="w-4 h-4" />
      </button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-44">
      <DropdownMenuRadioGroup :model-value="appearance" @update:model-value="updateAppearance">
        <DropdownMenuRadioItem value="light">
          <div class="flex items-center gap-2">
            <Icon name="sun" class="w-4 h-4" />
            <span>{{ t('Light') }}</span>
          </div>
        </DropdownMenuRadioItem>
        <DropdownMenuRadioItem value="dark">
          <div class="flex items-center gap-2">
            <Icon name="moon" class="w-4 h-4" />
            <span>{{ t('Dark') }}</span>
          </div>
        </DropdownMenuRadioItem>
        <DropdownMenuRadioItem value="system">
          <div class="flex items-center gap-2">
            <Icon name="monitor" class="w-4 h-4" />
            <span>{{ t('Automatic') }}</span>
          </div>
        </DropdownMenuRadioItem>
      </DropdownMenuRadioGroup>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppearance } from '@/composables/useAppearance'
import { useI18n } from '@/composables/useI18n'
import Icon from '@/components/Icon.vue'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

const { appearance, updateAppearance } = useAppearance()
const { t } = useI18n()

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
      return t('Light mode')
    case 'dark':
      return t('Dark mode')
    case 'system':
    default:
      return t('Automatic (System)')
  }
})
</script>

