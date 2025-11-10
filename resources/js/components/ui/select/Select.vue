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
        :value="displayValue || ''"
        type="text"
        :placeholder="placeholder"
        :class="inputClasses"
        @click="handleInputClick"
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
            'h-4 w-4 text-muted-foreground transition-transform duration-200',
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
        class="absolute z-50 mt-1 w-full rounded-lg border border-border dark:border-border bg-card dark:bg-card shadow-lg"
        @click.stop
        @mousedown="handleDropdownMouseDown"
      >
        <!-- Search Input -->
        <div v-if="searchable" class="p-2 border-b border-border dark:border-border">
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            placeholder="Search..."
            class="w-full px-3 py-2 text-sm border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            @keydown="handleSearchKeydown"
            @mousedown.stop
            @focus.stop
          />
        </div>

        <!-- Options -->
        <div class="max-h-60 overflow-auto">
          <div
            v-if="filteredOptions.length === 0"
            class="px-3 py-2 text-sm text-muted-foreground dark:text-muted-foreground"
          >
            No options found
          </div>
          <button
            v-for="(option, index) in filteredOptions"
            :key="getOptionValue(option)"
            type="button"
            :class="[
              'w-full px-3 py-2 text-left text-sm transition-colors',
              'hover:bg-muted/50 dark:hover:bg-muted/50',
              'focus:outline-none focus:bg-muted/50 dark:focus:bg-muted/50',
              isSelected(option)
                ? 'bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary font-medium'
                : 'text-card-foreground dark:text-card-foreground',
              index === highlightedIndex ? 'bg-muted/50 dark:bg-muted/50' : ''
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

const handleInputClick = () => {
  if (props.disabled) return
  // Only open on click, not on focus
  if (!isOpen.value) {
    toggleOpen()
  }
}

const toggleOpen = () => {
  if (props.disabled) return

  isOpen.value = !isOpen.value
  if (isOpen.value && props.searchable) {
    // Clear search query when opening dropdown to allow fresh search
    searchQuery.value = ''
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
  // Clear search query when option is selected
  searchQuery.value = ''
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

// Clear search query when dropdown closes
watch(() => isOpen.value, (isOpenNow) => {
  if (!isOpenNow) {
    // Clear search query when closing dropdown
    searchQuery.value = ''
  }
})
</script>
