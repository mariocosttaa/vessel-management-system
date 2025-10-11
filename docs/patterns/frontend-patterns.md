# Frontend Patterns

## Page Structure (Inertia Pages)

### Basic Page Structure
```vue
<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">{{ title }}</h1>
          <p class="mt-1 text-sm text-gray-600">{{ description }}</p>
        </div>
        <Button @click="createNew" v-if="canCreate">
          <Plus class="w-4 h-4 mr-2" />
          {{ createButtonText }}
        </Button>
      </div>

      <!-- Filters -->
      <TransactionFilters 
        v-if="showFilters"
        :filters="filters"
        :vessels="vessels"
        :categories="categories"
        @update:filters="updateFilters"
      />

      <!-- Content -->
      <div class="bg-white shadow rounded-lg">
        <TransactionList 
          :transactions="transactions"
          @edit="editTransaction"
          @delete="deleteTransaction"
        />
      </div>

      <!-- Pagination -->
      <Pagination 
        v-if="transactions.links"
        :links="transactions.links"
        @page-change="handlePageChange"
      />
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
    <Label :for="id">{{ label }}</Label>
    <div class="relative">
      <Input
        :id="id"
        :value="displayValue"
        @input="handleInput"
        @blur="handleBlur"
        :placeholder="placeholder"
        :class="{ 'border-red-500': error }"
        type="text"
      />
      <div class="absolute inset-y-0 right-0 flex items-center pr-3">
        <span class="text-gray-500 text-sm">{{ currency }}</span>
      </div>
    </div>
    <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    <p v-if="help" class="text-sm text-gray-600">{{ help }}</p>
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
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            :class="column.class"
          >
            {{ column.label }}
          </th>
          <th v-if="hasActions" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
            Actions
          </th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-for="item in data" :key="getItemKey(item)" class="hover:bg-gray-50">
          <td
            v-for="column in columns"
            :key="column.key"
            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
            :class="column.cellClass"
          >
            <slot :name="`cell-${column.key}`" :item="item" :value="getItemValue(item, column.key)">
              {{ formatCellValue(item, column) }}
            </slot>
          </td>
          <td v-if="hasActions" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
            <slot name="actions" :item="item">
              <Button
                v-if="canEdit"
                @click="$emit('edit', item)"
                variant="outline"
                size="sm"
                class="mr-2"
              >
                Edit
              </Button>
              <Button
                v-if="canDelete"
                @click="$emit('delete', item)"
                variant="destructive"
                size="sm"
              >
                Delete
              </Button>
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
