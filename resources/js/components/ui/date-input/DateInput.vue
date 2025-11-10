<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { cn } from '@/lib/utils'
import Icon from '@/components/Icon.vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { useDateInputManager } from '@/composables/useDateInputManager'

const props = defineProps<{
  defaultValue?: string
  modelValue?: string
  class?: HTMLAttributes['class']
  placeholder?: string
  disabled?: boolean
  min?: string
  max?: string
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string): void
}>()

const dateInputManager = useDateInputManager()
const instanceId = dateInputManager.generateId()
const inputRef = ref<HTMLInputElement>()
const popupRef = ref<HTMLDivElement>()
const containerRef = ref<HTMLDivElement>()
const isClickingInsidePopup = ref(false)

// Local state for popup visibility
const isOpen = computed({
  get: () => dateInputManager.isOpen(instanceId),
  set: (value: boolean) => {
    if (value) {
      dateInputManager.open(instanceId)
    } else {
      dateInputManager.close(instanceId)
    }
  }
})

// Current viewing month/year (for calendar display)
const currentView = ref(new Date())
const selectedDate = computed(() => {
  if (!props.modelValue) return null
  const date = new Date(props.modelValue + 'T00:00:00')
  return isNaN(date.getTime()) ? null : date
})

// Format date for display (MM/DD/YYYY)
const formatDate = (date: Date | null): string => {
  if (!date) return ''
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const year = date.getFullYear()
  return `${month}/${day}/${year}`
}

// Format date for input value (YYYY-MM-DD)
const formatDateForInput = (date: Date): string => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const displayValue = computed(() => {
  if (selectedDate.value) {
    return formatDate(selectedDate.value)
  }
  return ''
})

// Calendar utilities
const getDaysInMonth = (date: Date): number => {
  return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate()
}

const getFirstDayOfMonth = (date: Date): number => {
  return new Date(date.getFullYear(), date.getMonth(), 1).getDay()
}

const getCalendarDays = computed(() => {
  const year = currentView.value.getFullYear()
  const month = currentView.value.getMonth()
  const daysInMonth = getDaysInMonth(currentView.value)
  const firstDay = getFirstDayOfMonth(currentView.value)

  const days: Array<{ date: Date; isCurrentMonth: boolean; isToday: boolean; isSelected: boolean }> = []

  // Previous month days
  const prevMonth = new Date(year, month - 1, 0)
  const prevMonthDays = prevMonth.getDate()
  for (let i = firstDay - 1; i >= 0; i--) {
    const date = new Date(year, month - 1, prevMonthDays - i)
    const selected = selectedDate.value ? isSameDay(date, selectedDate.value) : false
    days.push({
      date,
      isCurrentMonth: false,
      isToday: isToday(date),
      isSelected: selected
    })
  }

  // Current month days
  for (let day = 1; day <= daysInMonth; day++) {
    const date = new Date(year, month, day)
    const selected = selectedDate.value ? isSameDay(date, selectedDate.value) : false
    days.push({
      date,
      isCurrentMonth: true,
      isToday: isToday(date),
      isSelected: selected
    })
  }

  // Next month days to fill the grid
  const remainingDays = 42 - days.length // 6 weeks * 7 days
  for (let day = 1; day <= remainingDays; day++) {
    const date = new Date(year, month + 1, day)
    const selected = selectedDate.value ? isSameDay(date, selectedDate.value) : false
    days.push({
      date,
      isCurrentMonth: false,
      isToday: isToday(date),
      isSelected: selected
    })
  }

  return days
})

const isToday = (date: Date): boolean => {
  const today = new Date()
  return isSameDay(date, today)
}

const isSameDay = (date1: Date, date2: Date): boolean => {
  return date1.getFullYear() === date2.getFullYear() &&
         date1.getMonth() === date2.getMonth() &&
         date1.getDate() === date2.getDate()
}

const monthYearLabel = computed(() => {
  return currentView.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
})

const weekDays = ['S', 'M', 'T', 'W', 'T', 'F', 'S']

// Navigation
const previousMonth = () => {
  currentView.value = new Date(currentView.value.getFullYear(), currentView.value.getMonth() - 1, 1)
}

const nextMonth = () => {
  currentView.value = new Date(currentView.value.getFullYear(), currentView.value.getMonth() + 1, 1)
}

// Initialize current view to selected date or today
watch(() => props.modelValue, (newValue) => {
  if (newValue && selectedDate.value) {
    currentView.value = new Date(selectedDate.value)
  }
}, { immediate: true })

// Select date
const selectDate = (date: Date) => {
  if (props.disabled) return

  // Check min/max constraints
  if (props.min) {
    const minDate = new Date(props.min + 'T00:00:00')
    if (date < minDate) return
  }
  if (props.max) {
    const maxDate = new Date(props.max + 'T23:59:59')
    if (date > maxDate) return
  }

  emits('update:modelValue', formatDateForInput(date))
  closePopup()
}

// Clear date
const clearDate = () => {
  if (props.disabled) return
  emits('update:modelValue', '')
  closePopup()
}

// Select today
const selectToday = () => {
  if (props.disabled) return
  const today = new Date()
  selectDate(today)
}

// Close function for the manager
const closePopup = () => {
  if (isOpen.value) {
    dateInputManager.close(instanceId)
  }
}

// Handle click outside
const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as Node
  
  // Check if click is on this date input's container or popup
  if (containerRef.value?.contains(target) || popupRef.value?.contains(target)) {
    // Click is inside this date input, don't close
    if (isClickingInsidePopup.value) {
      isClickingInsidePopup.value = false
    }
    return
  }

  // If clicking outside this date input, close it
  // (If clicking on another date input, the manager will handle opening that one and closing this one)
  if (isOpen.value) {
    closePopup()
  }
}

const handlePopupMouseDown = (event: MouseEvent) => {
  // Don't prevent default - we want to allow normal interaction
  // Just mark that we're clicking inside the popup
  isClickingInsidePopup.value = true
}

const handleInputClick = (event: MouseEvent) => {
  if (props.disabled) return
  // Don't prevent default - let focus handle opening
  // This handler is mainly to ensure the popup opens when clicking
  if (!isOpen.value) {
    dateInputManager.open(instanceId)
    if (selectedDate.value) {
      currentView.value = new Date(selectedDate.value)
    } else {
      currentView.value = new Date()
    }
  }
}

const handleInputFocus = () => {
  if (props.disabled) return
  // Open on focus
  if (!isOpen.value) {
    dateInputManager.open(instanceId)
    if (selectedDate.value) {
      currentView.value = new Date(selectedDate.value)
    } else {
      currentView.value = new Date()
    }
  }
}

// Don't use blur - let click outside handle closing
// This prevents issues when clicking between date inputs

// Register this instance with the manager
const unregister = dateInputManager.register(instanceId, closePopup)

onMounted(() => {
  document.addEventListener('click', handleClickOutside, true)
  
  if (selectedDate.value) {
    currentView.value = new Date(selectedDate.value)
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside, true)
  unregister()
})
</script>

<template>
  <div ref="containerRef" class="relative" data-date-input-container>
    <div class="relative">
      <Icon
        name="calendar"
        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none z-10"
      />
      <input
        ref="inputRef"
        :value="displayValue"
        type="text"
        :placeholder="placeholder || 'mm/dd/yyyy'"
        :disabled="disabled"
        readonly
        @focus="handleInputFocus"
        @click="handleInputClick"
        :class="cn(
          'placeholder:text-muted-foreground selection:bg-primary/20 selection:text-primary-foreground dark:bg-input/30 border-input/80 flex h-9 w-full min-w-0 rounded-lg border-2 bg-transparent pl-10 pr-3 py-1 text-base text-foreground transition-all duration-200 outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm cursor-pointer',
          'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:ring-offset-1',
          'hover:border-input',
          'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
          props.class,
        )"
      />
    </div>

    <!-- Calendar Popup -->
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
        ref="popupRef"
        class="absolute z-50 mt-1 w-[280px] rounded-lg border border-border dark:border-border bg-card dark:bg-card shadow-lg p-4"
        data-date-input-container
        @click.stop
        @mousedown="handlePopupMouseDown"
      >
        <!-- Month/Year Header -->
        <div class="flex items-center justify-between mb-4">
          <button
            type="button"
            @click="previousMonth"
            class="p-1 hover:bg-muted dark:hover:bg-muted rounded-md transition-colors"
            :disabled="disabled"
          >
            <ChevronLeft class="h-4 w-4 text-foreground" />
          </button>
          <div class="font-semibold text-sm text-card-foreground dark:text-card-foreground">
            {{ monthYearLabel }}
          </div>
          <button
            type="button"
            @click="nextMonth"
            class="p-1 hover:bg-muted dark:hover:bg-muted rounded-md transition-colors"
            :disabled="disabled"
          >
            <ChevronRight class="h-4 w-4 text-foreground" />
          </button>
        </div>

        <!-- Week Days Header -->
        <div class="grid grid-cols-7 gap-1 mb-2">
          <div
            v-for="day in weekDays"
            :key="day"
            class="text-center text-xs font-medium text-muted-foreground dark:text-muted-foreground py-1"
          >
            {{ day }}
          </div>
        </div>

        <!-- Calendar Days -->
        <div class="grid grid-cols-7 gap-1">
          <button
            v-for="(day, index) in getCalendarDays"
            :key="index"
            type="button"
            @click="selectDate(day.date)"
            :disabled="disabled"
            :class="[
              'h-9 w-9 rounded-md text-sm font-medium transition-colors',
              day.isCurrentMonth
                ? 'text-card-foreground dark:text-card-foreground'
                : 'text-muted-foreground dark:text-muted-foreground opacity-50',
              day.isToday && !day.isSelected
                ? 'bg-muted dark:bg-muted font-semibold'
                : 'hover:bg-muted dark:hover:bg-muted',
              day.isSelected
                ? 'bg-primary text-primary-foreground dark:bg-primary dark:text-primary-foreground font-semibold'
                : ''
            ]"
          >
            {{ day.date.getDate() }}
          </button>
        </div>

        <!-- Footer Buttons -->
        <div class="flex items-center justify-between mt-4 pt-4 border-t border-border dark:border-border">
          <button
            type="button"
            @click="clearDate"
            :disabled="disabled"
            class="text-sm text-muted-foreground dark:text-muted-foreground hover:text-foreground dark:hover:text-foreground transition-colors disabled:opacity-50"
          >
            Clear
          </button>
          <button
            type="button"
            @click="selectToday"
            :disabled="disabled"
            class="text-sm text-muted-foreground dark:text-muted-foreground hover:text-foreground dark:hover:text-foreground transition-colors disabled:opacity-50"
          >
            Today
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>
