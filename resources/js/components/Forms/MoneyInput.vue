<template>
  <div class="relative">
    <input
      ref="inputRef"
      :id="id"
      :value="displayValue"
      @input="handleInput"
      @focus="handleFocus"
      @blur="handleBlur"
      @keydown="handleKeyDown"
      @click="handleClick"
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
const inputRef = ref<HTMLInputElement | null>(null)
const cursorPosition = ref(0)

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

// Get the numeric part of a formatted value (extract just the number part)
const extractNumericPartFromFormatted = (formattedValue: string): string => {
  if (!props.showCurrency) return formattedValue

  // Format a test value to see the pattern
  const testValue = formatCurrency(10000, props.currency, props.decimals, props.locale)
  const testNumeric = formatCurrencyWithoutSymbol(10000, props.decimals, props.locale)

  // Determine if currency is at start or end by comparing patterns
  // If testValue starts with currency symbol (like €), currency is at start
  // If testValue ends with currency code (like AOA), currency is at end
  const currencyAtStart = !testValue.endsWith(testNumeric)
  const currencyAtEnd = testValue.endsWith(testNumeric + ' ' + props.currency) || testValue.endsWith(props.currency)

  if (currencyAtStart) {
    // Currency at start: find where number starts
    // For example: "€100,00" -> "100,00"
    const match = formattedValue.match(/[\d\s,.-]+/)
    return match ? match[0].trim() : formattedValue
  } else if (currencyAtEnd) {
    // Currency at end: find number part before currency
    // For example: "100,00 AOA" -> "100,00"
    const currencyPattern = new RegExp(`\\s*${props.currency.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}$`, 'i')
    return formattedValue.replace(currencyPattern, '').trim()
  }

  // Fallback: try to extract numeric part
  return formatCurrencyWithoutSymbol(
    extractNumericValue(formattedValue, props.decimals) || 0,
    props.decimals,
    props.locale
  )
}

// Get position before currency symbol in formatted string
const getPositionBeforeCurrency = (formattedValue: string): number => {
  if (!props.showCurrency) return formattedValue.length

  const numericPart = extractNumericPartFromFormatted(formattedValue)

  // Find the position of the end of numeric part in the formatted string
  // This is where currency symbol starts
  const testValue = formatCurrency(10000, props.currency, props.decimals, props.locale)
  const testNumeric = formatCurrencyWithoutSymbol(10000, props.decimals, props.locale)
  const currencyAtStart = !testValue.endsWith(testNumeric)

  if (currencyAtStart) {
    // Currency at start: position is after currency
    const match = formattedValue.match(/[\d\s,.-]+/)
    if (match && match.index !== undefined) {
      return match.index + match[0].length
    }
    return formattedValue.length
  } else {
    // Currency at end: position is at end of numeric part
    const currencyPattern = new RegExp(`\\s*${props.currency.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}$`, 'i')
    const beforeCurrency = formattedValue.replace(currencyPattern, '')
    return beforeCurrency.trimEnd().length
  }
}

// Handle focus - start editing mode
const handleFocus = () => {
  isEditing.value = true

  // Keep formatted value with currency when focused (user wants to see currency)
  // But ensure cursor is positioned before currency symbol
  if (props.modelValue !== null && props.modelValue !== undefined) {
    const formattedValue = props.showCurrency
      ? formatCurrency(props.modelValue, props.currency, props.decimals, props.locale)
      : formatCurrencyWithoutSymbol(props.modelValue, props.decimals, props.locale)
    displayValue.value = formattedValue

    // Position cursor at the end of numeric part (before currency symbol)
    nextTick(() => {
      if (inputRef.value) {
        if (props.showCurrency) {
          const posBeforeCurrency = getPositionBeforeCurrency(formattedValue)
          inputRef.value.setSelectionRange(posBeforeCurrency, posBeforeCurrency)
          cursorPosition.value = posBeforeCurrency
        } else {
          const endPos = formattedValue.length
          inputRef.value.setSelectionRange(endPos, endPos)
          cursorPosition.value = endPos
        }
      }
    })
  } else {
    // Position cursor at start if empty
    nextTick(() => {
      if (inputRef.value) {
        inputRef.value.setSelectionRange(0, 0)
        cursorPosition.value = 0
      }
    })
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

      // Reset cursor position on blur
      cursorPosition.value = 0
    }
  } else {
    cursorPosition.value = 0
  }
}

// Handle click - ensure cursor is positioned correctly
const handleClick = (event: MouseEvent) => {
  if (!props.showCurrency || !inputRef.value) return

  // Use setTimeout to ensure selection is set after click event
  setTimeout(() => {
    if (!inputRef.value) return

    const clickPos = inputRef.value.selectionStart || 0
    const currencyStartPos = getPositionBeforeCurrency(inputRef.value.value)

    // If user clicked at or after currency symbol, move cursor to before currency
    if (clickPos >= currencyStartPos) {
      inputRef.value.setSelectionRange(currencyStartPos, currencyStartPos)
      cursorPosition.value = currencyStartPos
    }
  }, 0)
}

// Handle input changes during editing
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  const inputValue = target.value
  const currentCursorPos = target.selectionStart || 0

  // If input is empty, clear and notify parent
  if (!inputValue || inputValue.trim() === '') {
    displayValue.value = ''
    emit('update:modelValue', null)
    emit('value-change', null, '', null, null)
    cursorPosition.value = 0
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

    // Position cursor appropriately after formatting
    nextTick(() => {
      if (inputRef.value && isEditing.value) {
        if (props.showCurrency) {
          // Get position before currency in the new formatted value
          const posBeforeCurrency = getPositionBeforeCurrency(formattedValue)

          // If cursor was at or after currency position, keep it before currency
          // Otherwise, try to maintain relative position within numeric part
          const oldCurrencyPos = getPositionBeforeCurrency(inputValue)
          let newCursorPos: number

          if (currentCursorPos >= oldCurrencyPos) {
            // Cursor was at/after currency, move to before currency in new value
            newCursorPos = posBeforeCurrency
          } else {
            // Cursor was in numeric part, try to maintain position
            // Calculate relative position in numeric part
            const numericPart = extractNumericPartFromFormatted(inputValue)
            const relativePos = currentCursorPos
            // Map to new formatted value
            const newNumericPart = extractNumericPartFromFormatted(formattedValue)
            newCursorPos = Math.min(relativePos, newNumericPart.length, posBeforeCurrency)
          }

          inputRef.value.setSelectionRange(newCursorPos, newCursorPos)
          cursorPosition.value = newCursorPos
        } else {
          // Position cursor at the end if no currency
          const endPos = formattedValue.length
          inputRef.value.setSelectionRange(endPos, endPos)
          cursorPosition.value = endPos
        }
      }
    })
  } else {
    // If we can't extract a numeric value, show raw input but notify parent with null
    displayValue.value = inputValue
    emit('update:modelValue', null)
    emit('value-change', null, inputValue, null, null)
    cursorPosition.value = currentCursorPos
  }
}

// Handle keydown events to prevent deleting currency symbol
const handleKeyDown = (event: KeyboardEvent) => {
  const target = event.target as HTMLInputElement
  const currentValue = target.value
  const cursorPos = target.selectionStart || 0
  const selectionEnd = target.selectionEnd || 0

  // Only handle if showing currency and cursor is at or after currency symbol position
  if (!props.showCurrency) return

  const currencyStartPos = getPositionBeforeCurrency(currentValue)

  // Handle backspace key
  if (event.key === 'Backspace' && !event.shiftKey && !event.ctrlKey && !event.metaKey) {
    // If cursor is at or after currency symbol position
    if (cursorPos >= currencyStartPos) {
      event.preventDefault()

      // Delete last digit of the number (not the currency symbol)
      const numericValue = extractNumericValue(currentValue, props.decimals)
      if (numericValue !== null && numericValue > 0) {
        // Remove last digit by dividing by 10
        const newNumericValue = Math.floor(numericValue / 10)
        const formValue = convertToFormValue(newNumericValue, props.returnType, props.decimals)
        const formattedValue = formatCurrency(newNumericValue, props.currency, props.decimals, props.locale)

        displayValue.value = formattedValue
        emit('update:modelValue', newNumericValue)
        emit('value-change', newNumericValue, formattedValue, formValue, newNumericValue)

        // Position cursor before currency symbol
        nextTick(() => {
          if (inputRef.value) {
            const newPos = getPositionBeforeCurrency(formattedValue)
            inputRef.value.setSelectionRange(newPos, newPos)
            cursorPosition.value = newPos
          }
        })
      } else {
        // Clear the value if it becomes 0 or less
        displayValue.value = ''
        emit('update:modelValue', null)
        emit('value-change', null, '', null, null)
        nextTick(() => {
          if (inputRef.value) {
            inputRef.value.setSelectionRange(0, 0)
            cursorPosition.value = 0
          }
        })
      }
      return
    }

    // If selection spans into currency symbol, prevent deletion
    if (selectionEnd > currencyStartPos && cursorPos < currencyStartPos) {
      event.preventDefault()
      // Move cursor to end of numeric part
      nextTick(() => {
        if (inputRef.value) {
          inputRef.value.setSelectionRange(currencyStartPos, currencyStartPos)
          cursorPosition.value = currencyStartPos
        }
      })
      return
    }
  }

  // Handle delete key
  if (event.key === 'Delete' && !event.shiftKey && !event.ctrlKey && !event.metaKey) {
    // If cursor is before currency symbol but delete would affect it
    if (cursorPos < currencyStartPos && selectionEnd >= currencyStartPos) {
      event.preventDefault()
      // Move cursor to end of numeric part
      nextTick(() => {
        if (inputRef.value) {
          inputRef.value.setSelectionRange(currencyStartPos, currencyStartPos)
          cursorPosition.value = currencyStartPos
        }
      })
      return
    }
  }

  // Handle arrow keys - prevent moving cursor into currency area
  if (event.key === 'ArrowRight' || event.key === 'End') {
    if (cursorPos >= currencyStartPos) {
      event.preventDefault()
      nextTick(() => {
        if (inputRef.value) {
          inputRef.value.setSelectionRange(currencyStartPos, currencyStartPos)
          cursorPosition.value = currencyStartPos
        }
      })
      return
    }
  }

  // Prevent typing at or after currency symbol position
  if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
    // If cursor is at or after currency symbol, move it to before currency and insert
    if (cursorPos >= currencyStartPos) {
      event.preventDefault()
      nextTick(() => {
        if (inputRef.value) {
          // Move cursor to end of numeric part and insert character
          const numericPart = extractNumericPartFromFormatted(currentValue)
          // Insert the character at the end of numeric part
          const newNumericPart = numericPart + event.key
          // Extract numeric value from the new string
          const newNumericValue = extractNumericValue(newNumericPart, props.decimals)
          if (newNumericValue !== null) {
            const formattedValue = formatCurrency(newNumericValue, props.currency, props.decimals, props.locale)
            displayValue.value = formattedValue
            const formValue = convertToFormValue(newNumericValue, props.returnType, props.decimals)
            emit('update:modelValue', newNumericValue)
            emit('value-change', newNumericValue, formattedValue, formValue, newNumericValue)

            // Position cursor before currency symbol
            nextTick(() => {
              if (inputRef.value) {
                const newPos = getPositionBeforeCurrency(formattedValue)
                inputRef.value.setSelectionRange(newPos, newPos)
                cursorPosition.value = newPos
              }
            })
          }
        }
      })
    }
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
