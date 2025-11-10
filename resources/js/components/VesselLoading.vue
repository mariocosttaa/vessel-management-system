<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 dark:bg-black/90 backdrop-blur-sm px-4">
    <div class="flex flex-col items-center w-full max-w-xs">
      <!-- Logo -->
      <div class="mb-6 animate-pulse">
        <Logo
          variant="auto"
          type="svg"
          height="50px"
          className="h-12 max-w-[250px]"
        />
      </div>

      <!-- Vessel Logo or Icon with pulse animation -->
      <div class="mb-4 animate-pulse">
        <div v-if="vessel.logo_url" class="w-20 h-20 mx-auto rounded-xl overflow-hidden border-2 border-primary/30 dark:border-primary/40 shadow-lg">
          <img
            :src="vessel.logo_url"
            :alt="vessel.name"
            class="w-full h-full object-cover"
          />
        </div>
        <div v-else class="w-20 h-20 mx-auto rounded-xl overflow-hidden border-2 border-primary/30 dark:border-primary/40 shadow-lg bg-gradient-to-br from-primary/20 to-primary/10 flex items-center justify-center">
          <Icon name="ship" class="h-12 w-12 text-primary" />
        </div>
      </div>

      <!-- Vessel Name -->
      <h2 class="text-2xl font-semibold text-white mb-2">
        {{ vessel.name }}
      </h2>

      <!-- Loading text -->
      <p class="text-base mt-1 mb-6 text-gray-400">
        {{ t('Accessing vessel dashboard...') }}
      </p>

      <!-- Progress bar & percentage -->
      <div class="w-full space-y-2 mb-8">
        <div class="w-full h-2 bg-gray-200/20 dark:bg-gray-700/30 rounded-full overflow-hidden">
          <div
            class="h-full bg-primary transition-all duration-100 ease-out rounded-full"
            :style="{ width: `${loadingProgress}%` }"
          />
        </div>
        <p class="text-sm text-center text-gray-400">
          {{ Math.round(loadingProgress) }}%
        </p>
      </div>

      <!-- Buttons -->
      <div class="flex justify-center items-center gap-4">
        <button
          @click="onBack"
          class="flex items-center gap-2 px-4 py-2 text-sm text-gray-400 hover:text-white transition-colors rounded-lg hover:bg-white/10"
        >
          <Icon name="arrow-left" class="w-4 h-4" />
          {{ t('Back') }}
        </button>

        <button
          @click="onSkip"
          class="flex items-center gap-2 px-4 py-2 text-sm bg-primary text-primary-foreground hover:bg-primary/90 transition-colors rounded-lg font-medium"
        >
          <Icon name="chevron-right" class="w-4 h-4" />
          {{ t('Skip') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue'
import Logo from '@/components/Logo.vue'
import { useI18n } from '@/composables/useI18n'

interface Vessel {
  id: number
  name: string
  registration_number?: string
  logo_url?: string | null
}

interface Props {
  vessel: Vessel
  loadingProgress: number
  onBack: () => void
  onSkip: () => void
}

const props = defineProps<Props>()
const { t } = useI18n()
</script>

