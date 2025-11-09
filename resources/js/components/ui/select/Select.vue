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
        :value="searchQuery || displayValue || ''"
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
        ref="dropdownRef"
        class="absolute z-50 mt-1 w-full rounded-md border border-gray-300 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800"
        @click.stop
        @mousedown="handleDropdownMouseDown"
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
            @mousedown.stop
            @focus.stop
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
import { ref, computed, watch, nextTick, onMounted } from 'vue'
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
const dropdownRef = ref<HTMLDivElement>()
const isOpen = ref(false)
const searchQuery = ref('')
const highlightedIndex = ref(-1)
const isClickingInsideDropdown = ref(false)

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
  return option[props.labelKey] || option.toString()
}

const isSelected = (option: any): boolean => {
  return getOptionValue(option) === props.modelValue
}

const toggleOpen = () => {
  if (props.disabled) return

  isOpen.value = !isOpen.value
  if (isOpen.value && props.searchable) {
    nextTick(() => {
      // Small delay to ensure dropdown is rendered
      setTimeout(() => {
        searchInputRef.value?.focus()
      }, 50)
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
  // Check if the new focus target is within the dropdown
  const relatedTarget = event.relatedTarget as HTMLElement | null
  if (relatedTarget && dropdownRef.value?.contains(relatedTarget)) {
    // Focus is moving to an element inside the dropdown, don't close
    return
  }

  // If we just clicked inside the dropdown, don't close
  if (isClickingInsideDropdown.value) {
    isClickingInsideDropdown.value = false
    return
  }

  // Delay closing to allow click events to fire
  setTimeout(() => {
    // Double-check that focus hasn't moved to the dropdown
    const activeElement = document.activeElement
    if (activeElement && dropdownRef.value?.contains(activeElement)) {
      return
    }
    isOpen.value = false
    highlightedIndex.value = -1
  }, 150)
}

const handleDropdownMouseDown = () => {
  // Mark that we're clicking inside the dropdown to prevent blur from closing it
  isClickingInsideDropdown.value = true
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

// Function to update searchQuery from modelValue
const updateDisplayFromModelValue = () => {
  if (props.modelValue !== null && props.modelValue !== undefined && props.options.length > 0) {
    const option = props.options.find((opt: any) => {
      const optValue = getOptionValue(opt)
      const modelValue = props.modelValue
      // Handle both string and number comparisons
      return optValue === modelValue || String(optValue) === String(modelValue)
    })
    if (option) {
      searchQuery.value = getOptionLabel(option)
      return true
    }
  }
  // Only clear if we don't have a valid modelValue
  if (!props.modelValue) {
    searchQuery.value = ''
  }
  return false
}

// Watch for modelValue changes to update display
watch(() => props.modelValue, (newValue: string | number | null) => {
  updateDisplayFromModelValue()
}, { immediate: true })

// Watch for options changes to update display when options load (important for initialization)
watch(() => props.options, (newOptions) => {
  if (newOptions && newOptions.length > 0 && props.modelValue !== null && props.modelValue !== undefined) {
    updateDisplayFromModelValue()
  }
}, { immediate: true, deep: true })

// Also initialize on mount
onMounted(() => {
  nextTick(() => {
    updateDisplayFromModelValue()
  })
})
</script>
