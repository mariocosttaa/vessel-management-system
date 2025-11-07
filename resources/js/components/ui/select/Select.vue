<template>
  <div class="relative">
    <!-- Hidden input for form submission -->
    <input
      v-if="name"
      type="hidden"
      :name="name"
      :value="modelValue"
    />

    <div class="relative">
      <input
        ref="inputRef"
        v-model="searchQuery"
        type="text"
        :placeholder="placeholder"
        :class="inputClasses"
        @focus="isOpen = true"
        @blur="handleBlur"
        @keydown="handleKeydown"
        readonly
      />
      <button
        type="button"
        class="absolute inset-y-0 right-0 flex items-center pr-2"
        @click="toggleOpen"
      >
        <ChevronDownIcon
          :class="[
            'h-4 w-4 text-gray-400 transition-transform duration-200',
            isOpen ? 'rotate-180' : ''
          ]"
        />
      </button>
    </div>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <div
        v-if="isOpen"
        class="absolute z-50 mt-1 w-full rounded-md border border-gray-300 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800"
        @click.stop
      >
        <!-- Search Input -->
        <div v-if="searchable" class="p-2 border-b border-gray-200 dark:border-gray-600">
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            placeholder="Search..."
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            @keydown="handleSearchKeydown"
          />
        </div>

        <!-- Options -->
        <div class="max-h-60 overflow-auto">
          <div
            v-if="filteredOptions.length === 0"
            class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400"
          >
            No options found
          </div>
          <button
            v-for="(option, index) in filteredOptions"
            :key="getOptionValue(option)"
            type="button"
            :class="[
              'w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700',
              'focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700',
              isSelected(option) ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-900 dark:text-gray-100',
              index === highlightedIndex ? 'bg-gray-100 dark:bg-gray-700' : ''
            ]"
            @click="selectOption(option)"
            @mouseenter="highlightedIndex = index"
          >
            {{ getOptionLabel(option) }}
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { ChevronDownIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

interface Option {
  value: string | number
  label: string
}

interface Props {
  modelValue?: string | number | null
  options: Option[] | string[] | number[]
  placeholder?: string
  searchable?: boolean
  valueKey?: string
  labelKey?: string
  disabled?: boolean
  error?: boolean
  class?: string
  name?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select an option',
  searchable: false,
  valueKey: 'value',
  labelKey: 'label',
  disabled: false,
  error: false
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number | null]
}>()

const inputRef = ref<HTMLInputElement>()
const searchInputRef = ref<HTMLInputElement>()
const isOpen = ref(false)
const searchQuery = ref('')
const highlightedIndex = ref(-1)

const inputClasses = computed(() => cn(
  'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background',
  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
  'disabled:cursor-not-allowed disabled:opacity-50',
  'cursor-pointer',
  props.error ? 'border-destructive' : '',
  props.class
))

const filteredOptions = computed(() => {
  if (!props.searchable || !searchQuery.value) {
    return props.options
  }

  const query = searchQuery.value.toLowerCase()
  return props.options.filter((option: any) =>
    getOptionLabel(option).toLowerCase().includes(query)
  )
})

const selectedOption = computed(() => {
  return props.options.find((option: any) =>
    getOptionValue(option) === props.modelValue
  )
})

const displayValue = computed(() => {
  return selectedOption.value ? getOptionLabel(selectedOption.value) : ''
})

const getOptionValue = (option: any): string | number => {
  if (typeof option === 'string' || typeof option === 'number') {
    return option
  }
  return option[props.valueKey]
}

const getOptionLabel = (option: any): string => {
  if (typeof option === 'string' || typeof option === 'number') {
    return option.toString()
  }
  return option[props.labelKey]
}

const isSelected = (option: any): boolean => {
  return getOptionValue(option) === props.modelValue
}

const toggleOpen = () => {
  if (props.disabled) return

  isOpen.value = !isOpen.value
  if (isOpen.value && props.searchable) {
    nextTick(() => {
      searchInputRef.value?.focus()
    })
  }
}

const selectOption = (option: any) => {
  const value = getOptionValue(option)
  emit('update:modelValue', value)
  searchQuery.value = getOptionLabel(option)
  isOpen.value = false
  highlightedIndex.value = -1
}

const handleBlur = (event: FocusEvent) => {
  // Delay closing to allow click events to fire
  setTimeout(() => {
    isOpen.value = false
    highlightedIndex.value = -1
  }, 150)
}

const handleKeydown = (event: KeyboardEvent) => {
  if (props.disabled) return

  switch (event.key) {
    case 'Enter':
    case ' ':
      event.preventDefault()
      toggleOpen()
      break
    case 'ArrowDown':
      event.preventDefault()
      if (!isOpen.value) {
        isOpen.value = true
      } else {
        highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredOptions.value.length - 1)
      }
      break
    case 'ArrowUp':
      event.preventDefault()
      if (isOpen.value) {
        highlightedIndex.value = Math.max(highlightedIndex.value - 1, -1)
      }
      break
    case 'Escape':
      isOpen.value = false
      highlightedIndex.value = -1
      break
  }
}

const handleSearchKeydown = (event: KeyboardEvent) => {
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredOptions.value.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      highlightedIndex.value = Math.max(highlightedIndex.value - 1, -1)
      break
    case 'Enter':
      event.preventDefault()
      if (highlightedIndex.value >= 0 && filteredOptions.value[highlightedIndex.value]) {
        selectOption(filteredOptions.value[highlightedIndex.value])
      }
      break
    case 'Escape':
      isOpen.value = false
      highlightedIndex.value = -1
      break
  }
}

// Watch for modelValue changes to update display
watch(() => props.modelValue, (newValue: string | number | null) => {
  if (newValue !== null && newValue !== undefined) {
    const option = props.options.find((opt: any) => getOptionValue(opt) === newValue)
    if (option) {
      searchQuery.value = getOptionLabel(option)
    }
  } else {
    searchQuery.value = ''
  }
}, { immediate: true })

// Watch for options changes to reset search
watch(() => props.options, () => {
  if (props.modelValue !== null && props.modelValue !== undefined) {
    const option = props.options.find((opt: any) => getOptionValue(opt) === props.modelValue)
    if (option) {
      searchQuery.value = getOptionLabel(option)
    }
  }
})
</script>
