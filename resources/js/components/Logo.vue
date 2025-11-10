<template>
  <img
    :key="themeKey"
    :src="logoSrc"
    :alt="alt"
    :class="[
      'inline-block object-contain',
      className
    ]"
    :style="logoStyle"
  />
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onBeforeMount, onUnmounted } from 'vue'

interface Props {
  variant?: 'light' | 'dark' | 'auto'
  type?: 'svg' | 'png'
  height?: string
  width?: string
  className?: string
  alt?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'auto',
  type: 'svg',
  height: undefined,
  width: undefined,
  className: '',
  alt: 'Bindamy Mareas'
})

// Reactive state for current theme
const isDark = ref(false)

// Get current theme from DOM - this is the source of truth
const checkTheme = (): boolean => {
  if (typeof window === 'undefined') {
    return false
  }
  return document.documentElement.classList.contains('dark')
}

// Update function
const updateTheme = () => {
  const newIsDark = checkTheme()
  if (newIsDark !== isDark.value) {
    isDark.value = newIsDark
  }
}

let observer: MutationObserver | null = null
let mediaQuery: MediaQueryList | null = null
let systemHandler: (() => void) | null = null

// Check theme before mount to ensure correct initial state
onBeforeMount(() => {
  if (typeof window !== 'undefined') {
    updateTheme()
  }
})

onMounted(() => {
  if (typeof window === 'undefined') {
    return
  }

  // Update immediately on mount
  updateTheme()

  // Also check on next frame to catch any theme initialization that happens after mount
  requestAnimationFrame(() => {
    updateTheme()
  })

  // Watch for DOM changes
  observer = new MutationObserver(() => {
    updateTheme()
  })

  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class']
  })

  // Watch system theme changes
  mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
  systemHandler = () => {
    // Only update if no explicit theme is set
    const html = document.documentElement
    if (!html.classList.contains('dark') && !html.classList.contains('light')) {
      updateTheme()
    }
  }
  mediaQuery.addEventListener('change', systemHandler)

  // Additional checks to catch late theme initialization
  // initializeTheme() runs in app.ts and might complete after mount
  setTimeout(updateTheme, 10)
  setTimeout(updateTheme, 100)
  setTimeout(updateTheme, 250)
})

onUnmounted(() => {
  observer?.disconnect()
  if (mediaQuery && systemHandler) {
    mediaQuery.removeEventListener('change', systemHandler)
  }
})

// Compute logo source
// For initial render accuracy, check DOM directly
// For reactivity, also depend on isDark ref which updates via MutationObserver
const logoSrc = computed(() => {
  let useDark = false

  if (props.variant === 'auto') {
    // Check DOM directly for accuracy, especially on initial render
    // The ref might not be updated yet when computed first runs
    if (typeof window !== 'undefined') {
      const domIsDark = document.documentElement.classList.contains('dark')
      // Update ref if different (triggers reactivity)
      if (domIsDark !== isDark.value) {
        isDark.value = domIsDark
      }
      useDark = domIsDark
    } else {
      useDark = isDark.value
    }
  } else {
    useDark = props.variant === 'dark'
  }

  const ext = props.type === 'png' ? 'png' : 'svg'
  return useDark
    ? `/bindamy-marea-logo-dark.${ext}`
    : `/bindamy-marea-logo-light.${ext}`
})

// Theme key for forcing re-render when theme changes
const themeKey = computed(() => {
  return `${isDark.value ? 'dark' : 'light'}-${props.type}`
})

// Logo style
const logoStyle = computed(() => {
  const style: Record<string, string> = {}
  if (props.height) {
    style.height = props.height
    if (!props.width) style.width = 'auto'
  }
  if (props.width) {
    style.width = props.width
    if (!props.height) style.height = 'auto'
  }
  return style
})
</script>
