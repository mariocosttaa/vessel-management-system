# Money Input Components

This document provides comprehensive guidance on using the Vue.js Money Input components in the Vessel Management System. These components follow the established money handling patterns where all monetary values are stored as integers (cents).

## 🎯 Overview

The money input system consists of several components and utilities:

- **MoneyInput**: Basic money input component
- **MoneyInputWithLabel**: Money input with label and validation
- **MoneyDisplay**: Display formatted monetary values
- **useMoney**: Composable for money operations
- **moneyFormat.ts**: Helper functions for formatting and parsing

## 📦 Components

### MoneyInput

Basic money input component with automatic formatting and currency support.

```vue
<template>
  <MoneyInput
    v-model="amount"
    currency="EUR"
    placeholder="0,00"
    @value-change="handleValueChange"
  />
</template>

<script setup lang="ts">
import { ref } from 'vue'
import MoneyInput from '@/components/Forms/MoneyInput.vue'

const amount = ref<number | null>(null)

const handleValueChange = (rawValue, formattedValue, formValue, centsValue) => {
  console.log('Value changed:', { rawValue, formattedValue, formValue, centsValue })
}
</script>
```

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `modelValue` | `number \| null` | - | The monetary value in cents |
| `currency` | `string` | `'EUR'` | Currency code (USD, EUR, etc.) |
| `decimals` | `number` | `2` | Number of decimal places |
| `locale` | `string` | `'pt-PT'` | Locale for formatting |
| `placeholder` | `string` | `'0,00'` | Input placeholder |
| `disabled` | `boolean` | `false` | Disable the input |
| `showCurrency` | `boolean` | `true` | Show currency symbol |
| `returnType` | `'float' \| 'int' \| 'string'` | `'int'` | Return type for form submission |
| `error` | `boolean` | `false` | Show error state |
| `className` | `string` | `''` | Additional CSS classes |

#### Events

| Event | Payload | Description |
|-------|---------|-------------|
| `update:modelValue` | `number \| null` | Emitted when the value changes |
| `value-change` | `(rawValue, formattedValue, formValue, centsValue)` | Detailed value change information |

### MoneyInputWithLabel

Money input component with label, validation, and helper text.

```vue
<template>
  <MoneyInputWithLabel
    v-model="amount"
    label="Transaction Amount"
    currency="EUR"
    placeholder="0,00"
    helper-text="Enter the transaction amount"
    :error="errors.amount"
    required
  />
</template>

<script setup lang="ts">
import { ref } from 'vue'
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue'

const amount = ref<number | null>(null)
const errors = ref({ amount: '' })
</script>
```

#### Props

Inherits all props from `MoneyInput` plus:

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | `string` | - | Label text |
| `error` | `string` | - | Error message |
| `helperText` | `string` | - | Helper text |
| `required` | `boolean` | `false` | Mark as required |

### MoneyDisplay

Component for displaying formatted monetary values.

```vue
<template>
  <MoneyDisplay 
    :value="amount" 
    currency="EUR" 
    variant="positive"
    size="lg"
  />
</template>

<script setup lang="ts">
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue'

const amount = 12345 // 123.45 EUR in cents
</script>
```

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `value` | `number \| null` | - | Monetary value in cents |
| `currency` | `string` | `'EUR'` | Currency code |
| `decimals` | `number` | `2` | Number of decimal places |
| `showSymbol` | `boolean` | `true` | Show currency symbol |
| `variant` | `'default' \| 'positive' \| 'negative' \| 'neutral'` | `'default'` | Display variant |
| `size` | `'sm' \| 'md' \| 'lg' \| 'xl'` | `'md'` | Text size |
| `className` | `string` | `''` | Additional CSS classes |

## 🔧 Composables

### useMoney

Composable providing money-related utilities.

```typescript
import { useMoney } from '@/composables/useMoney'

const { 
  toInteger, 
  toFloat, 
  format, 
  formatWithoutSymbol, 
  sanitize, 
  calculateVat, 
  calculateTotal 
} = useMoney()

// Convert float to cents
const cents = toInteger(123.45) // 12345

// Convert cents to float
const float = toFloat(12345) // 123.45

// Format for display
const formatted = format(12345, 'EUR', 2) // "€ 123,45"

// Format without symbol
const noSymbol = formatWithoutSymbol(12345, 2) // "123,45"

// Sanitize user input
const clean = sanitize('€ 123,45') // 12345

// Calculate VAT
const vat = calculateVat(10000, 23) // 2300 (23% of 10000)

// Calculate total
const total = calculateTotal(10000, 2300) // 12300
```

## 🛠️ Helper Functions

### moneyFormat.ts

Core helper functions for money formatting and parsing.

```typescript
import { 
  formatCurrency, 
  extractNumericValue, 
  sanitizeMoneyInput,
  convertToFormValue,
  calculateVat,
  calculateTotal 
} from '@/helpers/moneyFormat'

// Format currency
const formatted = formatCurrency(12345, 'EUR', 2, 'pt-PT') // "€ 123,45"

// Extract numeric value from input
const numeric = extractNumericValue('€ 123,45', 2) // 12345

// Sanitize input
const clean = sanitizeMoneyInput('€ 123,45', 2) // 12345

// Convert for form submission
const formValue = convertToFormValue(12345, 'float', 2) // 123.45

// Calculate VAT
const vat = calculateVat(10000, 23, 2) // 2300

// Calculate total
const total = calculateTotal(10000, 2300) // 12300
```

## 📝 Usage Examples

### Basic Form

```vue
<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <MoneyInputWithLabel
      v-model="form.amount"
      label="Amount"
      currency="EUR"
      placeholder="0,00"
      :error="errors.amount"
      required
    />
    
    <MoneyInputWithLabel
      v-model="form.vatAmount"
      label="VAT Amount"
      currency="EUR"
      placeholder="0,00"
      :error="errors.vatAmount"
    />
    
    <div class="bg-muted/50 p-4 rounded-lg">
      <div class="flex justify-between items-center">
        <span class="font-medium">Total:</span>
        <MoneyDisplay 
          :value="totalAmount" 
          currency="EUR" 
          size="lg"
        />
      </div>
    </div>
    
    <button type="submit" class="btn-primary">
      Submit
    </button>
  </form>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue'
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue'
import { useMoney } from '@/composables/useMoney'

const { calculateTotal } = useMoney()

const form = ref({
  amount: null as number | null,
  vatAmount: null as number | null
})

const errors = ref({
  amount: '',
  vatAmount: ''
})

const totalAmount = computed(() => {
  const amount = form.value.amount || 0
  const vatAmount = form.value.vatAmount || 0
  return calculateTotal(amount, vatAmount)
})

const handleSubmit = () => {
  console.log('Form data:', {
    amount: form.value.amount,
    vatAmount: form.value.vatAmount,
    totalAmount: totalAmount.value
  })
}
</script>
```

### Transaction Form with VAT Calculation

```vue
<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <MoneyInputWithLabel
        v-model="form.amount"
        label="Base Amount"
        currency="EUR"
        placeholder="0,00"
        :error="errors.amount"
        required
      />
      
      <div>
        <label class="block text-sm font-medium mb-2">VAT Rate</label>
        <select v-model="form.vatRate" class="w-full px-3 py-2 border rounded-lg">
          <option value="">No VAT</option>
          <option value="23">23%</option>
          <option value="13">13%</option>
          <option value="6">6%</option>
        </select>
      </div>
    </div>
    
    <div v-if="vatAmount > 0" class="bg-muted/50 p-4 rounded-lg space-y-2">
      <div class="flex justify-between">
        <span>Base Amount:</span>
        <MoneyDisplay :value="form.amount || 0" currency="EUR" />
      </div>
      <div class="flex justify-between">
        <span>VAT Amount:</span>
        <MoneyDisplay :value="vatAmount" currency="EUR" />
      </div>
      <div class="flex justify-between font-bold text-lg border-t pt-2">
        <span>Total Amount:</span>
        <MoneyDisplay :value="totalAmount" currency="EUR" variant="positive" />
      </div>
    </div>
    
    <button type="submit" class="btn-primary">
      Create Transaction
    </button>
  </form>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue'
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue'
import { useMoney } from '@/composables/useMoney'

const { calculateVat, calculateTotal } = useMoney()

const form = ref({
  amount: null as number | null,
  vatRate: ''
})

const errors = ref({
  amount: ''
})

const vatAmount = computed(() => {
  if (!form.value.amount || !form.value.vatRate) return 0
  return calculateVat(form.value.amount, parseFloat(form.value.vatRate))
})

const totalAmount = computed(() => {
  const amount = form.value.amount || 0
  return calculateTotal(amount, vatAmount.value)
})

const handleSubmit = () => {
  console.log('Transaction data:', {
    amount: form.value.amount,
    vatRate: form.value.vatRate,
    vatAmount: vatAmount.value,
    totalAmount: totalAmount.value
  })
}
</script>
```

### Data Table with Money Display

```vue
<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-border">
      <thead class="bg-muted/50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">
            Description
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">
            Amount
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">
            Total
          </th>
        </tr>
      </thead>
      <tbody class="bg-card divide-y divide-border">
        <tr v-for="transaction in transactions" :key="transaction.id">
          <td class="px-6 py-4 whitespace-nowrap text-sm text-card-foreground">
            {{ transaction.description }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm">
            <MoneyDisplay 
              :value="transaction.amount" 
              :currency="transaction.currency"
              :variant="transaction.type === 'income' ? 'positive' : 'negative'"
            />
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm">
            <MoneyDisplay 
              :value="transaction.total_amount" 
              :currency="transaction.currency"
              variant="positive"
            />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue'

interface Transaction {
  id: number
  description: string
  amount: number
  total_amount: number
  currency: string
  type: 'income' | 'expense'
}

interface Props {
  transactions: Transaction[]
}

defineProps<Props>()
</script>
```

## 🎨 Styling

All components use the design system colors and support dark mode:

```vue
<!-- The components automatically use design system colors -->
<MoneyInput 
  v-model="amount"
  class="w-full" 
  :class="{ 'border-red-500': hasError }"
/>
```

### Custom Styling

```vue
<template>
  <MoneyInputWithLabel
    v-model="amount"
    label="Custom Amount"
    currency="EUR"
    class-name="border-2 border-blue-500 focus:border-blue-600"
  />
</template>
```

## 🔍 Validation

### Form Validation

```vue
<template>
  <MoneyInputWithLabel
    v-model="form.amount"
    label="Amount"
    currency="EUR"
    :error="errors.amount"
    required
  />
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

const form = ref({
  amount: null as number | null
})

const errors = computed(() => {
  const errs: Record<string, string> = {}
  
  if (!form.value.amount) {
    errs.amount = 'Amount is required'
  } else if (form.value.amount < 0) {
    errs.amount = 'Amount must be positive'
  }
  
  return errs
})
</script>
```

### Custom Validation

```vue
<script setup lang="ts">
import { ref, watch } from 'vue'

const amount = ref<number | null>(null)
const error = ref('')

watch(amount, (newValue) => {
  if (newValue && newValue > 100000) {
    error.value = 'Amount cannot exceed €1,000.00'
  } else {
    error.value = ''
  }
})
</script>
```

## 🌍 Internationalization

### Currency Support

The components support all major currencies with proper formatting:

```vue
<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <MoneyInputWithLabel
      v-model="amounts.eur"
      label="EUR"
      currency="EUR"
      placeholder="0,00"
    />
    <MoneyInputWithLabel
      v-model="amounts.usd"
      label="USD"
      currency="USD"
      placeholder="0.00"
    />
    <MoneyInputWithLabel
      v-model="amounts.jpy"
      label="JPY"
      currency="JPY"
      :decimals="0"
      placeholder="0"
    />
  </div>
</template>
```

### Locale Configuration

```vue
<script setup lang="ts">
// Portuguese locale (default)
<MoneyInput v-model="amount" locale="pt-PT" />

// US locale
<MoneyInput v-model="amount" locale="en-US" />

// German locale
<MoneyInput v-model="amount" locale="de-DE" />
</script>
```

## 🚀 Best Practices

### 1. Always Use Integer Storage

```typescript
// ✅ Correct - Store as integers (cents)
const amount = 12345 // €123.45

// ❌ Wrong - Don't store as floats
const amount = 123.45
```

### 2. Use Proper Currency Detection

```typescript
// ✅ Correct - Use currency from context
<MoneyInput v-model="amount" :currency="vessel.currency" />

// ❌ Wrong - Don't hardcode currency
<MoneyInput v-model="amount" currency="EUR" />
```

### 3. Handle Null Values

```typescript
// ✅ Correct - Handle null values
const amount = ref<number | null>(null)

// ❌ Wrong - Don't use undefined
const amount = ref<number | undefined>(undefined)
```

### 4. Use Appropriate Return Types

```typescript
// For form submission (backend expects cents)
<MoneyInput v-model="amount" return-type="int" />

// For calculations (frontend needs float)
<MoneyInput v-model="amount" return-type="float" />

// For display (string formatting)
<MoneyInput v-model="amount" return-type="string" />
```

### 5. Implement Proper Validation

```typescript
const validateAmount = (value: number | null): string => {
  if (value === null) return 'Amount is required'
  if (value < 0) return 'Amount must be positive'
  if (value > 10000000) return 'Amount cannot exceed €100,000.00'
  return ''
}
```

## 🐛 Troubleshooting

### Common Issues

1. **Value not updating**: Ensure you're using `v-model` correctly
2. **Formatting issues**: Check currency and locale settings
3. **Validation errors**: Verify the error prop is a string, not boolean
4. **TypeScript errors**: Make sure to import components correctly

### Debug Tips

```vue
<script setup lang="ts">
// Add debug logging
const handleValueChange = (rawValue, formattedValue, formValue, centsValue) => {
  console.log('Money input changed:', {
    rawValue,
    formattedValue,
    formValue,
    centsValue
  })
}
</script>
```

## 📚 Related Documentation

- [Money Handling Patterns](../money-handling.md) - Backend money handling
- [Frontend Patterns](../frontend-patterns.md) - General frontend patterns
- [Layout Patterns](../layout-patterns.md) - Design system and layout
- [Database Schema](../database-schema.md) - Database structure

---

**Remember**: Always follow the established patterns for money handling in the vessel management system. Store values as integers (cents), use proper currency detection, and implement appropriate validation.
