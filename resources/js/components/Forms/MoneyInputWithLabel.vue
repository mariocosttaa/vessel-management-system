<template>
  <div class="space-y-2">
    <label
      v-if="label"
      :for="inputId"
      class="block text-sm font-medium text-card-foreground dark:text-card-foreground"
    >
      {{ label }}
      <span v-if="required" class="text-destructive dark:text-destructive ml-1">*</span>
    </label>

    <MoneyInput
      :id="inputId"
      :model-value="modelValue"
      :currency="currency"
      :decimals="decimals"
      :locale="locale"
      :placeholder="placeholder"
      :disabled="disabled"
      :show-currency="showCurrency"
      :return-type="returnType"
      :error="!!error"
      :class="inputClass"
      @update:model-value="$emit('update:modelValue', $event)"
      @value-change="$emit('value-change', $event)"
      v-bind="$attrs"
    />

    <p v-if="error" class="text-sm text-destructive dark:text-destructive">
      {{ error }}
    </p>

    <p v-if="helperText && !error" class="text-sm text-muted-foreground dark:text-muted-foreground">
      {{ helperText }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import MoneyInput from './MoneyInput.vue'
import type { ReturnType } from '@/helpers/moneyFormat'

interface Props {
  modelValue: number | null
  label?: string
  currency?: string
  decimals?: number
  locale?: string
  placeholder?: string
  disabled?: boolean
  showCurrency?: boolean
  returnType?: ReturnType
  error?: string
  helperText?: string
  required?: boolean
  inputClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  currency: 'EUR',
  decimals: 2,
  locale: 'pt-PT',
  placeholder: '0,00',
  disabled: false,
  showCurrency: true,
  returnType: 'int',
  required: false
})

const emit = defineEmits<{
  'update:modelValue': [value: number | null]
  'value-change': [rawValue: number | null, formattedValue: string, formValue: number | string | null, centsValue: number | null]
}>()

// Generate unique ID for the input
const inputId = computed(() => `money-input-${Math.random().toString(36).substr(2, 9)}`)
</script>
