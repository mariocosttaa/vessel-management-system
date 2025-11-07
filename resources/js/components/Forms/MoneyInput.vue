<template>
  <div class="relative">
    <input
      :id="id"
      :value="displayValue"
      @input="handleInput"
      @focus="handleFocus"
      @blur="handleBlur"
      :placeholder="placeholder"
      :disabled="disabled"
      :class="inputClasses"
      type="text"
      v-bind="$attrs"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import {
  formatCurrency,
  formatCurrencyWithoutSymbol,
  extractNumericValue,
  sanitizeMoneyInput,
  convertToFormValue,
  type ReturnType
} from '@/helpers/moneyFormat'

interface Props {
  modelValue: number | null
  currency?: string
  decimals?: number
  locale?: string
  placeholder?: string
  disabled?: boolean
  showCurrency?: boolean
  returnType?: ReturnType
  id?: string
  error?: boolean
  className?: string
}

const props = withDefaults(defineProps<Props>(), {
  currency: 'EUR',
  decimals: 2,
  locale: 'pt-PT',
  placeholder: '0,00',
  disabled: false,
  showCurrency: true,
  returnType: 'int',
  error: false
})

const emit = defineEmits<{
  'update:modelValue': [value: number | null]
  'value-change': [rawValue: number | null, formattedValue: string, formValue: number | string | null, centsValue: number | null]
}>()

// Reactive state
const displayValue = ref('')
const isEditing = ref(false)
const lastValue = ref<number | null>(null)

// Computed classes
const inputClasses = computed(() => {
  const baseClasses = "flex h-10 w-full rounded-md border border-input dark:border-input bg-background dark:bg-background px-3 py-2 text-base ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"

  const errorClasses = props.error ? 'border-destructive dark:border-destructive focus-visible:ring-destructive' : ''
  const customClasses = props.className || ''

  return `${baseClasses} ${errorClasses} ${customClasses}`.trim()
})

// Initialize display value
const initializeDisplayValue = () => {
  if (props.modelValue === null || props.modelValue === undefined) {
    displayValue.value = ''
    return
  }

  // Format based on showCurrency prop - use formatCurrencyWithoutSymbol when false
  const formattedValue = props.showCurrency
    ? formatCurrency(props.modelValue, props.currency, props.decimals, props.locale)
    : formatCurrencyWithoutSymbol(props.modelValue, props.decimals, props.locale)
  displayValue.value = formattedValue
}

// Handle focus - start editing mode
const handleFocus = () => {
  isEditing.value = true

  // Show plain number when focused for easier editing
  if (props.modelValue !== null && props.modelValue !== undefined) {
    const plainNumber = (props.modelValue / Math.pow(10, props.decimals)).toFixed(props.decimals)
    displayValue.value = plainNumber
  }
}

// Handle blur - end editing mode and ensure proper formatting
const handleBlur = () => {
  isEditing.value = false

  // Ensure the value is properly formatted on blur
  if (displayValue.value && displayValue.value.trim() !== '') {
    const numericValue = extractNumericValue(displayValue.value, props.decimals)
    if (numericValue !== null) {
      const formattedValue = props.showCurrency
        ? formatCurrency(numericValue, props.currency, props.decimals, props.locale)
        : formatCurrencyWithoutSymbol(numericValue, props.decimals, props.locale)
      displayValue.value = formattedValue
    }
  }
}

// Handle input changes during editing
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  const inputValue = target.value

  // If input is empty, clear and notify parent
  if (!inputValue || inputValue.trim() === '') {
    displayValue.value = ''
    emit('update:modelValue', null)
    emit('value-change', null, '', null, null)
    return
  }

  // Extract numeric value and notify parent
  const numericValue = extractNumericValue(inputValue, props.decimals)
  if (numericValue !== null) {
    const formValue = convertToFormValue(numericValue, props.returnType, props.decimals)

    // Format the display value while typing based on showCurrency prop
    const formattedValue = props.showCurrency
      ? formatCurrency(numericValue, props.currency, props.decimals, props.locale)
      : formatCurrencyWithoutSymbol(numericValue, props.decimals, props.locale)
    displayValue.value = formattedValue

    emit('update:modelValue', numericValue)
    emit('value-change', numericValue, formattedValue, formValue, numericValue)
  } else {
    // If we can't extract a numeric value, show raw input but notify parent with null
    displayValue.value = inputValue
    emit('update:modelValue', null)
    emit('value-change', null, inputValue, null, null)
  }
}

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  // Only update if the value actually changed and we're not currently editing
  if (newValue !== lastValue.value && !isEditing.value) {
    lastValue.value = newValue
    initializeDisplayValue()
  }
}, { immediate: true })

// Initialize on mount
initializeDisplayValue()
</script>
