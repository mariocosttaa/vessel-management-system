# Frontend Patterns

> **Important**: This document should be used in conjunction with [Layout Patterns & Design System](../layout-patterns.md) for complete frontend development guidelines.

## 🎨 Design System Integration

### Color Usage
Always use the design system colors defined in `resources/css/app.css`. Never use hardcoded colors like `text-gray-900` or `bg-white`. Instead use:

- **Text**: `text-card-foreground dark:text-card-foreground`
- **Secondary Text**: `text-muted-foreground dark:text-muted-foreground`
- **Backgrounds**: `bg-card dark:bg-card`
- **Borders**: `border-border dark:border-border`
- **Inputs**: `bg-background dark:bg-background` with `border-input dark:border-input`

See [Layout Patterns](../layout-patterns.md) for complete color reference.

### Layout Structure
All pages must follow the card-based layout structure:

```vue
<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Header Card -->
      <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
        <!-- Header content -->
      </div>
      
      <!-- Content Cards -->
      <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
        <!-- Main content -->
      </div>
    </div>
  </AppLayout>
</template>
```

## Page Structure (Inertia Pages)

### Basic Page Structure
```vue
<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Header Card -->
      <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">{{ title }}</h1>
            <p class="mt-1 text-sm text-muted-foreground dark:text-muted-foreground">{{ description }}</p>
          </div>
          <Link
            :href="createUrl"
            class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
            v-if="canCreate"
          >
            <Icon name="plus" class="w-4 h-4 mr-2" />
            {{ createButtonText }}
          </Link>
        </div>
      </div>

      <!-- Filters Card -->
      <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
        <TransactionFilters 
          v-if="showFilters"
          :filters="filters"
          :vessels="vessels"
          :categories="categories"
          @update:filters="updateFilters"
        />
      </div>

      <!-- Content Card -->
      <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card overflow-hidden">
        <TransactionList 
          :transactions="transactions"
          @edit="editTransaction"
          @delete="deleteTransaction"
        />
        
        <!-- Pagination -->
        <div v-if="transactions.links" class="bg-card dark:bg-card px-4 py-3 border-t border-border dark:border-border sm:px-6">
          <Pagination 
            :links="transactions.links"
            @page-change="handlePageChange"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/ui/button.vue'
import TransactionFilters from '@/Components/Transactions/TransactionFilters.vue'
import TransactionList from '@/Components/Transactions/TransactionList.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { useMoney } from '@/Composables/useMoney'

// Props
interface Props {
  transactions: {
    data: Transaction[]
    links: PaginationLinks
  }
  vessels: Vessel[]
  categories: TransactionCategory[]
  filters: FilterState
}

const props = defineProps<Props>()

// Composables
const { format } = useMoney()

// Reactive data
const showFilters = ref(false)

// Computed
const title = computed(() => 'Transactions')
const description = computed(() => 'Manage financial transactions')
const canCreate = computed(() => true) // Based on permissions
const createButtonText = computed(() => 'New Transaction')

// Methods
const createNew = () => {
  router.visit(route('transactions.create'))
}

const editTransaction = (transaction: Transaction) => {
  router.visit(route('transactions.edit', transaction.id))
}

const deleteTransaction = (transaction: Transaction) => {
  if (confirm('Are you sure you want to delete this transaction?')) {
    router.delete(route('transactions.destroy', transaction.id))
  }
}

const updateFilters = (newFilters: FilterState) => {
  router.get(route('transactions.index'), newFilters, {
    preserveState: true,
    replace: true
  })
}

const handlePageChange = (url: string) => {
  router.visit(url)
}
</script>
```

### Props Handling and TypeScript Types

#### TypeScript Interfaces
```typescript
// types/Transaction.ts
export interface Transaction {
  id: number
  transaction_number: string
  type: 'income' | 'expense' | 'transfer'
  type_label: string
  amount: number
  formatted_amount: string
  currency: string
  house_of_zeros: number
  transaction_date: string
  formatted_transaction_date: string
  description?: string
  status: 'pending' | 'completed' | 'cancelled'
  status_label: string
  vessel?: Vessel
  category: TransactionCategory
  bank_account: BankAccount
  supplier?: Supplier
  crew_member?: CrewMember
  vat_rate?: VatRate
  created_at: string
  updated_at: string
}

export interface Vessel {
  id: number
  name: string
  registration_number: string
  vessel_type: string
  vessel_type_label: string
  capacity?: number
  year_built?: number
  status: 'active' | 'maintenance' | 'inactive'
  status_label: string
  notes?: string
}

export interface TransactionCategory {
  id: number
  name: string
  type: 'income' | 'expense'
  color: string
}

export interface BankAccount {
  id: number
  name: string
  bank_name: string
  account_number?: string
  iban?: string
  current_balance: number
  formatted_current_balance: string
  currency: string
  status: 'active' | 'inactive'
}

export interface FilterState {
  vessel_id?: number
  type?: string
  date_from?: string
  date_to?: string
  category_id?: number
}

export interface PaginationLinks {
  first: string
  last: string
  prev?: string
  next?: string
}
```

#### Props Definition
```vue
<script setup lang="ts">
// Define props with TypeScript
interface Props {
  transactions: {
    data: Transaction[]
    links: PaginationLinks
  }
  vessels: Vessel[]
  categories: TransactionCategory[]
  filters: FilterState
  canCreate: boolean
  canEdit: boolean
  canDelete: boolean
}

const props = defineProps<Props>()

// Or with defaults
interface Props {
  title?: string
  showFilters?: boolean
  pageSize?: number
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Transactions',
  showFilters: true,
  pageSize: 15
})
</script>
```

## Component Organization

### Form Components

#### MoneyInput Component
```vue
<template>
  <div class="space-y-2">
    <Label :for="id" class="text-sm font-medium text-card-foreground dark:text-card-foreground">{{ label }}</Label>
    <div class="relative">
      <Input
        :id="id"
        :value="displayValue"
        @input="handleInput"
        @blur="handleBlur"
        :placeholder="placeholder"
        :class="[
          'w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent',
          error ? 'border-destructive dark:border-destructive' : ''
        ]"
        type="text"
      />
      <div class="absolute inset-y-0 right-0 flex items-center pr-3">
        <span class="text-muted-foreground dark:text-muted-foreground text-sm">{{ currency }}</span>
      </div>
    </div>
    <p v-if="error" class="text-sm text-destructive dark:text-destructive">{{ error }}</p>
    <p v-if="help" class="text-sm text-muted-foreground dark:text-muted-foreground">{{ help }}</p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { useMoney } from '@/Composables/useMoney'

interface Props {
  modelValue: number
  currency?: string
  decimals?: number
  label?: string
  placeholder?: string
  error?: string
  help?: string
  id?: string
}

const props = withDefaults(defineProps<Props>(), {
  currency: 'EUR',
  decimals: 2,
  label: 'Amount',
  placeholder: '0.00'
})

const emit = defineEmits<{
  'update:modelValue': [value: number]
}>()

const { toInteger, toFloat, formatWithoutSymbol } = useMoney()

const displayValue = ref('')

// Convert integer value to display format
const updateDisplayValue = () => {
  displayValue.value = formatWithoutSymbol(props.modelValue, props.decimals)
}

// Handle input changes
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  let value = target.value
  
  // Remove non-numeric characters except decimal point
  value = value.replace(/[^\d.,]/g, '')
  
  // Replace comma with dot
  value = value.replace(',', '.')
  
  // Limit decimal places
  const parts = value.split('.')
  if (parts[1] && parts[1].length > props.decimals) {
    value = parts[0] + '.' + parts[1].substring(0, props.decimals)
  }
  
  displayValue.value = value
}

// Handle blur - convert to integer and emit
const handleBlur = () => {
  const numericValue = parseFloat(displayValue.value) || 0
  const integerValue = toInteger(numericValue, props.decimals)
  emit('update:modelValue', integerValue)
}

// Watch for external changes
watch(() => props.modelValue, updateDisplayValue, { immediate: true })
</script>
```

#### DatePicker Component
```vue
<template>
  <div class="space-y-2">
    <Label :for="id">{{ label }}</Label>
    <Input
      :id="id"
      :value="modelValue"
      @input="handleInput"
      type="date"
      :max="maxDate"
      :min="minDate"
      :class="{ 'border-red-500': error }"
    />
    <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    <p v-if="help" class="text-sm text-gray-600">{{ help }}</p>
  </div>
</template>

<script setup lang="ts">
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'

interface Props {
  modelValue: string
  label?: string
  error?: string
  help?: string
  id?: string
  maxDate?: string
  minDate?: string
}

const props = withDefaults(defineProps<Props>(), {
  label: 'Date',
  maxDate: new Date().toISOString().split('T')[0] // Today
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}
</script>
```

#### Select Component
```vue
<template>
  <div class="space-y-2">
    <Label :for="id">{{ label }}</Label>
    <select
      :id="id"
      :value="modelValue"
      @change="handleChange"
      :class="selectClasses"
    >
      <option v-if="placeholder" value="">{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="getOptionValue(option)"
        :value="getOptionValue(option)"
      >
        {{ getOptionLabel(option) }}
      </option>
    </select>
    <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    <p v-if="help" class="text-sm text-gray-600">{{ help }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Label } from '@/Components/ui/label'

interface Props {
  modelValue: string | number | null
  options: any[]
  label?: string
  placeholder?: string
  error?: string
  help?: string
  id?: string
  valueKey?: string
  labelKey?: string
}

const props = withDefaults(defineProps<Props>(), {
  label: 'Select',
  valueKey: 'id',
  labelKey: 'name'
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number | null]
}>()

const selectClasses = computed(() => [
  'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
  props.error ? 'border-red-500' : ''
])

const getOptionValue = (option: any) => {
  return option[props.valueKey]
}

const getOptionLabel = (option: any) => {
  return option[props.labelKey]
}

const handleChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  const value = target.value === '' ? null : target.value
  emit('update:modelValue', value)
}
</script>
```

### Display Components

#### DataTable Component
```vue
<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-border dark:divide-border">
      <thead class="bg-muted/50 dark:bg-muted/50">
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            class="px-6 py-3 text-left text-xs font-medium text-muted-foreground dark:text-muted-foreground uppercase tracking-wider"
            :class="column.class"
          >
            {{ column.label }}
          </th>
          <th v-if="hasActions" class="px-6 py-3 text-right text-xs font-medium text-muted-foreground dark:text-muted-foreground uppercase tracking-wider">
            Actions
          </th>
        </tr>
      </thead>
      <tbody class="bg-card dark:bg-card divide-y divide-border dark:divide-border">
        <tr v-for="item in data" :key="getItemKey(item)" class="hover:bg-muted/50 dark:hover:bg-muted/50 transition-colors">
          <td
            v-for="column in columns"
            :key="column.key"
            class="px-6 py-4 whitespace-nowrap text-sm text-card-foreground dark:text-card-foreground"
            :class="column.cellClass"
          >
            <slot :name="`cell-${column.key}`" :item="item" :value="getItemValue(item, column.key)">
              {{ formatCellValue(item, column) }}
            </slot>
          </td>
          <td v-if="hasActions" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
            <slot name="actions" :item="item">
              <button
                v-if="canEdit"
                @click="$emit('edit', item)"
                class="inline-flex items-center px-3 py-1 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground text-sm font-medium transition-colors mr-2"
              >
                Edit
              </button>
              <button
                v-if="canDelete"
                @click="$emit('delete', item)"
                class="text-destructive hover:text-destructive/80 dark:text-destructive dark:hover:text-destructive/80 transition-colors"
              >
                Delete
              </button>
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Button from '@/Components/ui/button.vue'
import { useMoney } from '@/Composables/useMoney'

interface Column {
  key: string
  label: string
  class?: string
  cellClass?: string
  type?: 'text' | 'money' | 'date' | 'status'
}

interface Props {
  data: any[]
  columns: Column[]
  keyField?: string
  canEdit?: boolean
  canDelete?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  keyField: 'id',
  canEdit: true,
  canDelete: true
})

const emit = defineEmits<{
  edit: [item: any]
  delete: [item: any]
}>()

const { format } = useMoney()

const hasActions = computed(() => props.canEdit || props.canDelete)

const getItemKey = (item: any) => {
  return item[props.keyField]
}

const getItemValue = (item: any, key: string) => {
  return key.split('.').reduce((obj, k) => obj?.[k], item)
}

const formatCellValue = (item: any, column: Column) => {
  const value = getItemValue(item, column.key)
  
  switch (column.type) {
    case 'money':
      return format(value, item.currency || 'EUR', item.house_of_zeros || 2)
    case 'date':
      return value ? new Date(value).toLocaleDateString('pt-PT') : ''
    case 'status':
      return item[`${column.key}_label`] || value
    default:
      return value
  }
}
</script>
```

#### MoneyDisplay Component
```vue
<template>
  <span :class="textClass">
    {{ formattedValue }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useMoney } from '@/Composables/useMoney'

interface Props {
  value: number
  currency?: string
  decimals?: number
  showSymbol?: boolean
  variant?: 'default' | 'positive' | 'negative' | 'neutral'
  size?: 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
  currency: 'EUR',
  decimals: 2,
  showSymbol: true,
  variant: 'default',
  size: 'md'
})

const { format, formatWithoutSymbol } = useMoney()

const formattedValue = computed(() => {
  if (props.showSymbol) {
    return format(props.value, props.currency, props.decimals)
  }
  return formatWithoutSymbol(props.value, props.decimals)
})

const textClass = computed(() => {
  const baseClasses = 'font-medium'
  const sizeClasses = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg'
  }
  const variantClasses = {
    default: 'text-gray-900',
    positive: 'text-green-600',
    negative: 'text-red-600',
    neutral: 'text-gray-500'
  }
  
  return [
    baseClasses,
    sizeClasses[props.size],
    variantClasses[props.variant]
  ].join(' ')
})
</script>
```

## Composables Usage

### useMoney Composable
```typescript
// Composables/useMoney.ts
import { computed } from 'vue'

export function useMoney() {
  const toInteger = (value: number, decimals: number = 2): number => {
    return Math.round(value * Math.pow(10, decimals))
  }
  
  const toFloat = (value: number, decimals: number = 2): number => {
    return value / Math.pow(10, decimals)
  }
  
  const format = (value: number, currency: string = 'EUR', decimals: number = 2): string => {
    const float = toFloat(value, decimals)
    return new Intl.NumberFormat('pt-PT', {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    }).format(float)
  }
  
  const formatWithoutSymbol = (value: number, decimals: number = 2): string => {
    const float = toFloat(value, decimals)
    return new Intl.NumberFormat('pt-PT', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    }).format(float)
  }
  
  const calculateVat = (amount: number, vatRate: number, decimals: number = 2): number => {
    return Math.round((amount * vatRate) / 100)
  }
  
  return {
    toInteger,
    toFloat,
    format,
    formatWithoutSymbol,
    calculateVat
  }
}
```

### useTransaction Composable
```typescript
// Composables/useTransaction.ts
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useMoney } from './useMoney'

export function useTransaction() {
  const { toInteger, calculateVat } = useMoney()
  
  const form = ref({
    vessel_id: null as number | null,
    bank_account_id: null as number | null,
    category_id: null as number | null,
    supplier_id: null as number | null,
    crew_member_id: null as number | null,
    type: 'expense' as 'income' | 'expense' | 'transfer',
    amount: 0,
    currency: 'EUR',
    house_of_zeros: 2,
    vat_rate_id: null as number | null,
    transaction_date: new Date().toISOString().split('T')[0],
    description: '',
    notes: '',
    reference: ''
  })
  
  const vatRates = ref([])
  
  const vatAmount = computed(() => {
    if (!form.value.vat_rate_id || !form.value.amount) return 0
    const rate = vatRates.value.find((r: any) => r.id === form.value.vat_rate_id)
    return calculateVat(toInteger(form.value.amount), rate?.rate || 0)
  })
  
  const totalAmount = computed(() => {
    return toInteger(form.value.amount) + vatAmount.value
  })
  
  const submitTransaction = (route: string, method: 'post' | 'put' = 'post') => {
    const data = {
      ...form.value,
      amount: toInteger(form.value.amount),
      vat_amount: vatAmount.value,
      total_amount: totalAmount.value
    }
    
    router[method](route, data, {
      onSuccess: () => {
        // Reset form or show success
        resetForm()
      },
      onError: (errors) => {
        // Handle errors
        console.error('Transaction submission failed:', errors)
      }
    })
  }
  
  const resetForm = () => {
    form.value = {
      vessel_id: null,
      bank_account_id: null,
      category_id: null,
      supplier_id: null,
      crew_member_id: null,
      type: 'expense',
      amount: 0,
      currency: 'EUR',
      house_of_zeros: 2,
      vat_rate_id: null,
      transaction_date: new Date().toISOString().split('T')[0],
      description: '',
      notes: '',
      reference: ''
    }
  }
  
  return {
    form,
    vatAmount,
    totalAmount,
    submitTransaction,
    resetForm,
    vatRates
  }
}
```

### useFilters Composable
```typescript
// Composables/useFilters.ts
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

export function useFilters(initialFilters: Record<string, any> = {}) {
  const filters = ref({ ...initialFilters })
  
  const updateFilter = (key: string, value: any) => {
    filters.value[key] = value
  }
  
  const clearFilter = (key: string) => {
    delete filters.value[key]
  }
  
  const clearAllFilters = () => {
    filters.value = {}
  }
  
  const applyFilters = (route: string) => {
    router.get(route, filters.value, {
      preserveState: true,
      replace: true
    })
  }
  
  // Watch for filter changes and auto-apply
  watch(filters, () => {
    applyFilters(window.location.pathname)
  }, { deep: true })
  
  return {
    filters,
    updateFilter,
    clearFilter,
    clearAllFilters,
    applyFilters
  }
}
```

## Complete Page Examples

### Transactions/Index.vue
```vue
<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">Transactions</h1>
          <p class="mt-1 text-sm text-gray-600">Manage financial transactions</p>
        </div>
        <Button @click="createNew" v-if="canCreate">
          <Plus class="w-4 h-4 mr-2" />
          New Transaction
        </Button>
      </div>

      <!-- Filters -->
      <TransactionFilters 
        :filters="filters"
        :vessels="vessels"
        :categories="categories"
        :bank-accounts="bankAccounts"
        @update:filters="updateFilters"
      />

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <SummaryCard
          title="Total Income"
          :value="summary.total_income"
          currency="EUR"
          variant="positive"
        />
        <SummaryCard
          title="Total Expense"
          :value="summary.total_expense"
          currency="EUR"
          variant="negative"
        />
        <SummaryCard
          title="Net Balance"
          :value="summary.net_balance"
          currency="EUR"
          :variant="summary.net_balance >= 0 ? 'positive' : 'negative'"
        />
        <SummaryCard
          title="Transaction Count"
          :value="summary.count"
          variant="neutral"
        />
      </div>

      <!-- Transactions Table -->
      <div class="bg-white shadow rounded-lg">
        <DataTable
          :data="transactions.data"
          :columns="columns"
          @edit="editTransaction"
          @delete="deleteTransaction"
        >
          <template #cell-type="{ item }">
            <StatusBadge :status="item.type" :label="item.type_label" />
          </template>
          <template #cell-amount="{ item }">
            <MoneyDisplay 
              :value="item.amount" 
              :currency="item.currency"
              :variant="item.type === 'income' ? 'positive' : 'negative'"
            />
          </template>
          <template #cell-transaction_date="{ item }">
            {{ item.formatted_transaction_date }}
          </template>
        </DataTable>
      </div>

      <!-- Pagination -->
      <Pagination 
        v-if="transactions.links"
        :links="transactions.links"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/ui/button.vue'
import TransactionFilters from '@/Components/Transactions/TransactionFilters.vue'
import DataTable from '@/Components/Common/DataTable.vue'
import MoneyDisplay from '@/Components/Common/MoneyDisplay.vue'
import StatusBadge from '@/Components/Common/StatusBadge.vue'
import SummaryCard from '@/Components/Common/SummaryCard.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import type { Transaction, Vessel, TransactionCategory, BankAccount } from '@/types'

interface Props {
  transactions: {
    data: Transaction[]
    links: any
  }
  vessels: Vessel[]
  categories: TransactionCategory[]
  bankAccounts: BankAccount[]
  filters: Record<string, any>
  summary: {
    total_income: number
    total_expense: number
    net_balance: number
    count: number
  }
  canCreate: boolean
}

const props = defineProps<Props>()

const columns = [
  { key: 'transaction_number', label: 'Number', type: 'text' },
  { key: 'type', label: 'Type', type: 'status' },
  { key: 'amount', label: 'Amount', type: 'money' },
  { key: 'vessel.name', label: 'Vessel', type: 'text' },
  { key: 'category.name', label: 'Category', type: 'text' },
  { key: 'transaction_date', label: 'Date', type: 'date' },
  { key: 'description', label: 'Description', type: 'text' },
]

const createNew = () => {
  router.visit(route('transactions.create'))
}

const editTransaction = (transaction: Transaction) => {
  router.visit(route('transactions.edit', transaction.id))
}

const deleteTransaction = (transaction: Transaction) => {
  if (confirm('Are you sure you want to delete this transaction?')) {
    router.delete(route('transactions.destroy', transaction.id))
  }
}

const updateFilters = (newFilters: Record<string, any>) => {
  router.get(route('transactions.index'), newFilters, {
    preserveState: true,
    replace: true
  })
}
</script>
```

### Transactions/Create.vue
```vue
<template>
  <AppLayout>
    <div class="max-w-2xl mx-auto">
      <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Create Transaction</h1>
        <p class="mt-1 text-sm text-gray-600">Add a new financial transaction</p>
      </div>

      <form @submit.prevent="submitForm" class="space-y-6">
        <div class="bg-white shadow rounded-lg p-6 space-y-6">
          <!-- Transaction Type -->
          <div>
            <Label>Transaction Type</Label>
            <Select
              v-model="form.type"
              :options="typeOptions"
              placeholder="Select type"
              :error="errors.type"
            />
          </div>

          <!-- Basic Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>Bank Account</Label>
              <Select
                v-model="form.bank_account_id"
                :options="bankAccounts"
                placeholder="Select bank account"
                :error="errors.bank_account_id"
              />
            </div>
            <div>
              <Label>Category</Label>
              <Select
                v-model="form.category_id"
                :options="categories"
                placeholder="Select category"
                :error="errors.category_id"
              />
            </div>
          </div>

          <!-- Vessel and Supplier -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label>Vessel (Optional)</Label>
              <Select
                v-model="form.vessel_id"
                :options="vessels"
                placeholder="Select vessel"
                :error="errors.vessel_id"
              />
            </div>
            <div>
              <Label>Supplier (Optional)</Label>
              <Select
                v-model="form.supplier_id"
                :options="suppliers"
                placeholder="Select supplier"
                :error="errors.supplier_id"
              />
            </div>
          </div>

          <!-- Amount and VAT -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <MoneyInput
                v-model="form.amount"
                label="Amount"
                currency="EUR"
                :error="errors.amount"
                placeholder="0.00"
              />
            </div>
            <div>
              <Label>VAT Rate (Optional)</Label>
              <Select
                v-model="form.vat_rate_id"
                :options="vatRates"
                placeholder="Select VAT rate"
                :error="errors.vat_rate_id"
              />
            </div>
          </div>

          <!-- VAT Amount Display -->
          <div v-if="vatAmount > 0" class="bg-gray-50 p-4 rounded-lg">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-gray-700">VAT Amount:</span>
              <MoneyDisplay :value="vatAmount" currency="EUR" />
            </div>
            <div class="flex justify-between items-center mt-2">
              <span class="text-sm font-medium text-gray-700">Total Amount:</span>
              <MoneyDisplay :value="totalAmount" currency="EUR" />
            </div>
          </div>

          <!-- Date and Description -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <DatePicker
                v-model="form.transaction_date"
                label="Transaction Date"
                :error="errors.transaction_date"
              />
            </div>
            <div>
              <Label>Reference (Optional)</Label>
              <Input
                v-model="form.reference"
                placeholder="External reference"
                :error="errors.reference"
              />
            </div>
          </div>

          <!-- Description -->
          <div>
            <Label>Description</Label>
            <Textarea
              v-model="form.description"
              placeholder="Transaction description"
              :error="errors.description"
              rows="3"
            />
          </div>

          <!-- Notes -->
          <div>
            <Label>Notes (Optional)</Label>
            <Textarea
              v-model="form.notes"
              placeholder="Additional notes"
              :error="errors.notes"
              rows="2"
            />
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
          <Button
            type="button"
            variant="outline"
            @click="router.visit(route('transactions.index'))"
          >
            Cancel
          </Button>
          <Button type="submit" :disabled="isSubmitting">
            <Loader2 v-if="isSubmitting" class="w-4 h-4 mr-2 animate-spin" />
            Create Transaction
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Loader2 } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/ui/button.vue'
import Input from '@/Components/ui/input.vue'
import Textarea from '@/Components/ui/textarea.vue'
import Label from '@/Components/ui/label.vue'
import Select from '@/Components/Forms/Select.vue'
import MoneyInput from '@/Components/Forms/MoneyInput.vue'
import DatePicker from '@/Components/Forms/DatePicker.vue'
import MoneyDisplay from '@/Components/Common/MoneyDisplay.vue'
import { useTransaction } from '@/Composables/useTransaction'
import type { Vessel, TransactionCategory, BankAccount, Supplier, VatRate } from '@/types'

interface Props {
  vessels: Vessel[]
  categories: TransactionCategory[]
  bankAccounts: BankAccount[]
  suppliers: Supplier[]
  vatRates: VatRate[]
  errors: Record<string, string>
}

const props = defineProps<Props>()

const { form, vatAmount, totalAmount, submitTransaction } = useTransaction()

const isSubmitting = ref(false)

const typeOptions = [
  { id: 'income', name: 'Income' },
  { id: 'expense', name: 'Expense' },
  { id: 'transfer', name: 'Transfer' }
]

const submitForm = async () => {
  isSubmitting.value = true
  
  try {
    await submitTransaction(route('transactions.store'))
  } finally {
    isSubmitting.value = false
  }
}
</script>
```

## Notification System

### useNotifications Composable

The notification system provides centralized notification handling with TypeScript support:

```typescript
// composables/useNotifications.ts
import { ref, computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

export interface Notification {
    id: string
    type: 'success' | 'error' | 'warning' | 'info'
    title: string
    message: string
    duration?: number
    persistent?: boolean
}

export function useNotifications() {
    const page = usePage()
    const notifications = ref<Notification[]>([])

    // Get flash messages from Inertia
    const flashMessages = computed(() => {
        const flash = page.props.flash as any
        return {
            success: flash?.success,
            error: flash?.error,
            warning: flash?.warning,
            info: flash?.info,
        }
    })

    // Process flash messages immediately when they're available
    const processFlashMessages = () => {
        const flash = flashMessages.value

        if (flash.success) {
            addNotification({
                type: 'success',
                title: 'Success',
                message: flash.success,
            })
        }

        if (flash.error) {
            addNotification({
                type: 'error',
                title: 'Error',
                message: flash.error,
            })
        }

        if (flash.warning) {
            addNotification({
                type: 'warning',
                title: 'Warning',
                message: flash.warning,
            })
        }

        if (flash.info) {
            addNotification({
                type: 'info',
                title: 'Information',
                message: flash.info,
            })
        }
    }

    // Process flash messages immediately when component mounts
    processFlashMessages()

    // Watch for flash message changes (for subsequent updates)
    watch(flashMessages, (newFlash, oldFlash) => {
        if (newFlash.success && newFlash.success !== oldFlash?.success) {
            addNotification({
                type: 'success',
                title: 'Success',
                message: newFlash.success,
            })
        }

        if (newFlash.error && newFlash.error !== oldFlash?.error) {
            addNotification({
                type: 'error',
                title: 'Error',
                message: newFlash.error,
            })
        }

        if (newFlash.warning && newFlash.warning !== oldFlash?.warning) {
            addNotification({
                type: 'warning',
                title: 'Warning',
                message: newFlash.warning,
            })
        }

        if (newFlash.info && newFlash.info !== oldFlash?.info) {
            addNotification({
                type: 'info',
                title: 'Information',
                message: newFlash.info,
            })
        }
    }, { deep: true })

    const addNotification = (notification: Omit<Notification, 'id'>) => {
        const id = Math.random().toString(36).substr(2, 9)
        const newNotification: Notification = {
            id,
            duration: 5000, // 5 seconds default
            persistent: false,
            ...notification,
        }

        notifications.value.push(newNotification)

        // Auto-remove notification after duration
        if (!newNotification.persistent && newNotification.duration) {
            setTimeout(() => {
                removeNotification(id)
            }, newNotification.duration)
        }

        return id
    }

    const removeNotification = (id: string) => {
        const index = notifications.value.findIndex(n => n.id === id)
        if (index > -1) {
            notifications.value.splice(index, 1)
        }
    }

    const clearAllNotifications = () => {
        notifications.value = []
    }

    // Convenience methods
    const success = (title: string, message: string, options?: Partial<Notification>) => {
        return addNotification({
            type: 'success',
            title,
            message,
            ...options,
        })
    }

    const error = (title: string, message: string, options?: Partial<Notification>) => {
        return addNotification({
            type: 'error',
            title,
            message,
            persistent: true, // Errors should persist until manually dismissed
            ...options,
        })
    }

    const warning = (title: string, message: string, options?: Partial<Notification>) => {
        return addNotification({
            type: 'warning',
            title,
            message,
            ...options,
        })
    }

    const info = (title: string, message: string, options?: Partial<Notification>) => {
        return addNotification({
            type: 'info',
            title,
            message,
            ...options,
        })
    }

    return {
        notifications: computed(() => notifications.value),
        flashMessages,
        processFlashMessages,
        addNotification,
        removeNotification,
        clearAllNotifications,
        success,
        error,
        warning,
        info,
    }
}
```

### NotificationContainer Component

Global container for displaying notifications:

```vue
<!-- components/NotificationContainer.vue -->
<template>
    <div class="fixed top-4 right-4 z-[9999] space-y-2 max-w-sm">
        <TransitionGroup
            name="notification"
            tag="div"
            class="space-y-2"
        >
            <NotificationItem
                v-for="notification in notifications"
                :key="notification.id"
                :notification="notification"
                @remove="removeNotification"
            />
        </TransitionGroup>
    </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useNotifications } from '@/composables/useNotifications'
import NotificationItem from '@/components/NotificationItem.vue'

const { notifications, processFlashMessages, removeNotification } = useNotifications()

// Process flash messages when component mounts
onMounted(() => {
    processFlashMessages()
})
</script>

<style scoped>
.notification-enter-active,
.notification-leave-active {
    transition: all 0.3s ease;
}

.notification-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.notification-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

.notification-move {
    transition: transform 0.3s ease;
}
</style>
```

### ConfirmationDialog Component

Reusable confirmation dialog for destructive operations:

```vue
<!-- components/ConfirmationDialog.vue -->
<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent :class="dialogSizeClass">
            <DialogHeader>
                <DialogTitle :class="titleClass">
                    <Icon v-if="iconName" :name="iconName" :class="iconClass" class="mr-2" />
                    {{ title }}
                </DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>
            <div v-if="message" :class="messageClass">
                {{ message }}
            </div>
            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    @click="$emit('cancel')"
                    :disabled="loading"
                >
                    {{ cancelText }}
                </Button>
                <Button
                    :variant="variant"
                    @click="$emit('confirm')"
                    :disabled="loading"
                >
                    <Icon v-if="loading" name="loader-2" class="w-4 h-4 mr-2 animate-spin" />
                    {{ confirmText }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';

type DialogType = 'info' | 'warning' | 'danger';

interface Props {
    open: boolean;
    title: string;
    description: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
    type?: DialogType;
    loading?: boolean;
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    variant: 'default',
    type: 'info',
    loading: false,
    size: 'md',
});

defineEmits(['update:open', 'confirm', 'cancel']);

const dialogSizeClass = computed(() => {
    switch (props.size) {
        case 'sm': return 'sm:max-w-sm';
        case 'md': return 'sm:max-w-md';
        case 'lg': return 'sm:max-w-lg';
        default: return 'sm:max-w-md';
    }
});

const iconName = computed(() => {
    switch (props.type) {
        case 'warning': return 'alert-triangle';
        case 'danger': return 'x-circle';
        case 'info': return 'info';
        default: return 'info';
    }
});

const iconClass = computed(() => {
    switch (props.type) {
        case 'warning': return 'text-yellow-500';
        case 'danger': return 'text-red-500';
        case 'info': return 'text-blue-500';
        default: return 'text-gray-500';
    }
});

const titleClass = computed(() => {
    switch (props.type) {
        case 'warning': return 'text-yellow-600';
        case 'danger': return 'text-red-600';
        case 'info': return 'text-blue-600';
        default: return 'text-gray-900';
    }
});

const messageClass = computed(() => {
    switch (props.type) {
        case 'warning': return 'text-yellow-700 text-sm';
        case 'danger': return 'text-red-700 text-sm';
        case 'info': return 'text-blue-700 text-sm';
        default: return 'text-gray-700 text-sm';
    }
});
</script>
```

### Using Notifications in Pages

#### Integration with Confirmation Dialogs

```vue
<template>
    <AppLayout>
        <!-- Page content -->
        
        <!-- Confirmation Dialog -->
        <ConfirmationDialog
            :open="showDeleteDialog"
            title="Delete Vessel"
            description="This action cannot be undone."
            :message="`Are you sure you want to delete the vessel '${vesselToDelete?.name}'? This will permanently remove the vessel and all its data.`"
            confirm-text="Delete Vessel"
            cancel-text="Cancel"
            variant="destructive"
            type="danger"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import vessels from '@/routes/vessels';

// Confirmation dialog state
const showDeleteDialog = ref(false);
const vesselToDelete = ref<Vessel | null>(null);
const isDeleting = ref(false);

const deleteVessel = (vessel: Vessel) => {
    vesselToDelete.value = vessel;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!vesselToDelete.value) return;
    
    isDeleting.value = true;
    
    router.delete(vessels.destroy.url(vesselToDelete.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            vesselToDelete.value = null;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
};

const cancelDelete = () => {
    showDeleteDialog.value = false;
    vesselToDelete.value = null;
    isDeleting.value = false;
};
</script>
```

#### Manual Notification Usage

```typescript
// In any Vue component
import { useNotifications } from '@/composables/useNotifications';

const { success, error, warning, info } = useNotifications();

// Trigger notifications
success('Operation Complete', 'The data has been saved successfully.');
error('Operation Failed', 'An error occurred while saving the data.');
warning('Warning', 'This action may have unintended consequences.');
info('Information', 'Please review the changes before proceeding.');
```

### Notification Best Practices

#### 1. Always Use TypeScript
- Use TypeScript interfaces for type safety
- Define proper notification types
- Use type-safe notification handling

#### 2. Process Flash Messages Immediately
- Call `processFlashMessages()` on component mount
- Watch for flash message changes with deep watching
- Handle both initial and subsequent flash messages

#### 3. Provide Confirmation Dialogs
- Always confirm destructive operations
- Use appropriate dialog types (warning, danger, info)
- Include loading states during operations

#### 4. Use Appropriate Notification Types
- Success: For successful operations
- Error: For failures (persistent until dismissed)
- Warning: For potential issues
- Info: For informational messages

#### 5. Set Appropriate Durations
- Success: 5 seconds (auto-dismiss)
- Error: Persistent (manual dismiss)
- Warning: 7 seconds (auto-dismiss)
- Info: 5 seconds (auto-dismiss)

## Permissions System

### usePermissions Composable

The permissions system provides a centralized way to check user permissions and roles in the frontend. Always use TypeScript for composables.

```typescript
// composables/usePermissions.ts
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

interface User {
    id: number
    name: string
    email: string
    vessel_role?: string // Current vessel role
    permissions: Record<string, boolean>
}

interface PageProps {
    auth: {
        user: User | null
    }
}

export function usePermissions() {
    const page = usePage<PageProps>()
    
    const user = computed(() => page.props.auth.user)
    const permissions = computed(() => user.value?.permissions || {})
    const currentVesselRole = computed(() => user.value?.vessel_role || 'viewer')
    
    const hasRole = (role: string): boolean => {
        return currentVesselRole.value === role
    }
    
    const hasAnyRole = (roleList: string[]): boolean => {
        return roleList.includes(currentVesselRole.value)
    }
    
    const hasAllRoles = (roleList: string[]): boolean => {
        return roleList.every(role => currentVesselRole.value === role)
    }
    
    const hasPermission = (permission: string): boolean => {
        return permissions.value[permission] === true
    }
    
    const can = (action: string, resource: string): boolean => {
        const permission = `${resource}.${action}`
        return hasPermission(permission)
    }
    
    const canView = (resource: string): boolean => can('view', resource)
    const canCreate = (resource: string): boolean => can('create', resource)
    const canEdit = (resource: string): boolean => can('edit', resource)
    const canDelete = (resource: string): boolean => can('delete', resource)
    
    const isAdministrator = computed(() => hasRole('Administrator'))
    const isSupervisor = computed(() => hasRole('Supervisor'))
    const isModerator = computed(() => hasRole('Moderator'))
    const isNormalUser = computed(() => hasRole('Normal User'))
    
    return {
        user,
        permissions,
        currentVesselRole,
        hasRole,
        hasAnyRole,
        hasAllRoles,
        hasPermission,
        can,
        canView,
        canCreate,
        canEdit,
        canDelete,
        isAdministrator,
        isSupervisor,
        isModerator,
        isNormalUser
    }
}
```

### PermissionGate Component

Use the PermissionGate component to conditionally render content based on user permissions or roles.

```vue
<template>
    <div v-if="hasAccess">
        <slot />
    </div>
    <div v-else-if="fallback" class="text-muted-foreground text-sm">
        {{ fallback }}
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePermissions } from '@/composables/usePermissions'

interface Props {
    permission?: string
    role?: string | string[]
    fallback?: string
}

const props = defineProps<Props>()

const { hasPermission, hasRole, hasAnyRole } = usePermissions()

const hasAccess = computed(() => {
    // Check permission if provided
    if (props.permission && !hasPermission(props.permission)) {
        return false
    }
    
    // Check role if provided
    if (props.role) {
        const roles = Array.isArray(props.role) ? props.role : [props.role]
        if (!hasAnyRole(roles)) {
            return false
        }
    }
    
    // If no permission or role specified, allow access
    return true
})
</script>
```

### Using Permissions in Components

#### Conditional Rendering

```vue
<template>
    <div>
        <!-- Using PermissionGate component -->
        <PermissionGate permission="vessels.create">
            <Button @click="openCreateModal">
                Add Vessel
            </Button>
        </PermissionGate>

        <!-- Using composable directly -->
        <Button 
            v-if="canCreate('vessels')"
            @click="openCreateModal"
        >
            Add Vessel
        </Button>

        <!-- Multiple permissions -->
        <PermissionGate :role="['admin', 'manager']">
            <Button @click="openSettingsModal">
                Settings
            </Button>
        </PermissionGate>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePermissions } from '@/composables/usePermissions'
import PermissionGate from '@/components/PermissionGate.vue'

const { canCreate, canEdit, canDelete, isAdmin } = usePermissions()
</script>
```

#### Dynamic Actions Based on Permissions

```vue
<template>
    <div>
        <!-- Actions configuration based on permissions -->
        <div class="flex space-x-2">
            <Button 
                v-if="canView('vessels')"
                @click="viewVessel(vessel)"
                variant="outline"
            >
                View
            </Button>
            <Button 
                v-if="canEdit('vessels')"
                @click="editVessel(vessel)"
            >
                Edit
            </Button>
            <Button 
                v-if="canDelete('vessels')"
                @click="deleteVessel(vessel)"
                variant="destructive"
            >
                Delete
            </Button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePermissions } from '@/composables/usePermissions'

const { canView, canEdit, canDelete } = usePermissions()

// Computed actions array for dynamic rendering
const availableActions = computed(() => {
    const actions = []
    
    if (canView('vessels')) {
        actions.push({
            label: 'View Details',
            icon: 'eye',
            onClick: (vessel: Vessel) => viewVessel(vessel),
        })
    }
    
    if (canEdit('vessels')) {
        actions.push({
            label: 'Edit Vessel',
            icon: 'edit',
            onClick: (vessel: Vessel) => editVessel(vessel),
        })
    }
    
    if (canDelete('vessels')) {
        actions.push({
            label: 'Delete Vessel',
            icon: 'trash-2',
            variant: 'destructive' as const,
            onClick: (vessel: Vessel) => deleteVessel(vessel),
        })
    }
    
    return actions
})
</script>
```

#### Navigation Based on Permissions

```vue
<template>
    <nav>
        <ul class="space-y-2">
            <li>
                <Link :href="dashboard()">Dashboard</Link>
            </li>
            <li v-if="canView('vessels')">
                <Link :href="vessels.index.url()">Vessels</Link>
            </li>
            <li v-if="canView('crew')">
                <Link :href="crewMembers.index.url()">Crew Members</Link>
            </li>
            <li v-if="canView('suppliers')">
                <Link :href="suppliers.index.url()">Suppliers</Link>
            </li>
            <li v-if="isAdmin">
                <Link :href="settings()">Settings</Link>
            </li>
        </ul>
    </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePermissions } from '@/composables/usePermissions'
import { Link } from '@inertiajs/vue3'

const { canView, isAdmin } = usePermissions()
</script>
```

### Best Practices for Permissions

#### 1. Always Use TypeScript
- Use TypeScript for all composables
- Define proper interfaces for user and permission data
- Use type-safe permission checking

#### 2. Consistent Permission Naming
- Use `resource.action` format (e.g., `vessels.create`)
- Keep permission names consistent across frontend and backend
- Use descriptive permission names

#### 3. Graceful Degradation
- Always provide fallback content for users without permissions
- Use the `fallback` prop in PermissionGate for better UX
- Don't leave empty spaces where content should be

#### 4. Performance Considerations
- Use computed properties for permission checks
- Cache permission results when possible
- Avoid checking permissions in loops

#### 5. Security
- Remember that frontend permissions are for UX only
- Always implement proper backend authorization
- Never rely solely on frontend permission checks

```vue
<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import BaseModal from '@/components/modals/BaseModal.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import entities from '@/routes/entities'

interface Props {
    open: boolean
    entity?: EntityType | null
    relatedData?: RelatedDataType[]
}

const props = defineProps<Props>()
const emit = defineEmits<{
    'update:open': [value: boolean]
    'saved': []
}>()

const isEditing = computed(() => !!props.entity)

const form = useForm({
    name: '',
    description: '',
    // ... other fields
})

// Reset form when modal opens/closes or entity changes
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        if (props.entity) {
            // Populate form for editing
            form.name = props.entity.name
            form.description = props.entity.description
            // ... populate other fields
        } else {
            // Reset form for creating
            form.reset()
        }
        form.clearErrors()
    }
})

const handleSave = () => {
    if (isEditing.value && props.entity) {
        form.put(entities.update.url(props.entity.id), {
            onSuccess: () => {
                emit('saved')
                emit('update:open', false)
            },
        })
    } else {
        form.post(entities.store.url(), {
            onSuccess: () => {
                emit('saved')
                emit('update:open', false)
            },
        })
    }
}

const handleClose = () => {
    emit('update:open', false)
}
</script>

<template>
    <BaseModal
        :open="open"
        :title="isEditing ? 'Edit Entity' : 'Create Entity'"
        :description="isEditing ? 'Update entity information' : 'Add a new entity'"
        size="lg"
        :loading="form.processing"
        :disabled="form.processing"
        confirm-text="Save Entity"
        @update:open="handleClose"
        @confirm="handleSave"
        @cancel="handleClose"
    >
        <form @submit.prevent="handleSave" class="space-y-6">
            <!-- Form fields -->
        </form>
    </BaseModal>
</template>
```

### Modal Integration in Pages

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import EntityFormModal from '@/components/modals/EntityFormModal.vue'

// Modal state
const isModalOpen = ref(false)
const editingEntity = ref(null)

// Methods
const openCreateModal = () => {
    editingEntity.value = null
    isModalOpen.value = true
}

const openEditModal = (entity) => {
    editingEntity.value = entity
    isModalOpen.value = true
}

const handleModalSaved = () => {
    // Refresh the page to show updated data
    router.reload()
}
</script>

<template>
    <div>
        <!-- Page content -->
        <Button @click="openCreateModal">
            Add Entity
        </Button>

        <!-- Table with edit buttons -->
        <Button @click="openEditModal(entity)">
            Edit
        </Button>

        <!-- Modal -->
        <EntityFormModal
            :open="isModalOpen"
            :entity="editingEntity"
            :related-data="relatedData"
            @update:open="isModalOpen = $event"
            @saved="handleModalSaved"
        />
    </div>
</template>
```

### Modal Sizing Guidelines

- **sm**: Simple confirmations, single field forms
- **md**: Standard forms with 2-3 fields
- **lg**: Complex forms with 4-6 fields (default for CRUD)
- **xl**: Forms with many fields or complex layouts
- **2xl**: Very complex forms or data tables

### Confirmation Dialog Pattern

```vue
<BaseModal
    :open="showDeleteModal"
    title="Delete Item"
    description="Are you sure you want to delete this item? This action cannot be undone."
    size="sm"
    confirm-text="Delete"
    cancel-text="Cancel"
    @confirm="handleDelete"
    @update:open="showDeleteModal = $event"
/>
```

## Best Practices for Modals

### 1. Always Use BaseModal
Never create custom modal implementations. Always extend BaseModal.

### 2. Consistent Form Structure
- Use the same form field patterns
- Include proper validation
- Show loading states during submission

### 3. Proper Event Handling
- Emit `saved` event after successful operations
- Handle errors gracefully
- Reset form state on close

### 4. Responsive Design
- Use appropriate modal sizes
- Ensure forms work on mobile devices
- Test with different screen sizes

### 5. Data Management
- Watch for prop changes to populate forms
- Reset forms when switching between create/edit
- Handle related data properly
