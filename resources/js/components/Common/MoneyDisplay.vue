<template>
  <span :class="textClasses">
    {{ formattedValue }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useMoney } from '@/composables/useMoney'

interface Props {
  value: number | null
  currency?: string
  decimals?: number
  showSymbol?: boolean
  variant?: 'default' | 'positive' | 'negative' | 'neutral'
  size?: 'sm' | 'md' | 'lg' | 'xl'
  className?: string
}

const props = withDefaults(defineProps<Props>(), {
  currency: 'EUR',
  decimals: 2,
  showSymbol: true,
  variant: 'default',
  size: 'md',
  className: ''
})

const { format, formatWithoutSymbol } = useMoney()

const formattedValue = computed(() => {
  if (props.value === null || props.value === undefined) {
    return '—'
  }

  if (props.showSymbol) {
    return format(props.value, props.currency, props.decimals)
  }
  return formatWithoutSymbol(props.value, props.decimals)
})

const textClasses = computed(() => {
  const baseClasses = 'font-medium'

  const sizeClasses = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg',
    xl: 'text-xl'
  }

  const variantClasses = {
    default: 'text-card-foreground dark:text-card-foreground',
    positive: 'text-green-600 dark:text-green-400',
    negative: 'text-red-600 dark:text-red-400',
    neutral: 'text-muted-foreground dark:text-muted-foreground'
  }

  const classes = [
    baseClasses,
    sizeClasses[props.size],
    variantClasses[props.variant],
    props.className
  ].filter(Boolean)

  return classes.join(' ')
})
</script>
