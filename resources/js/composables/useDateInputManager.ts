import { ref } from 'vue'

// Global state to track which date input is currently open
const openDateInputId = ref<string | null>(null)
const dateInputInstances = new Map<string, () => void>()

export function useDateInputManager() {
  const generateId = () => {
    return `date-input-${Math.random().toString(36).substring(2, 9)}`
  }

  const register = (id: string, closeCallback: () => void) => {
    dateInputInstances.set(id, closeCallback)
    return () => {
      dateInputInstances.delete(id)
      if (openDateInputId.value === id) {
        openDateInputId.value = null
      }
    }
  }

  const open = (id: string) => {
    // Close any other open date input
    if (openDateInputId.value && openDateInputId.value !== id) {
      const closeCallback = dateInputInstances.get(openDateInputId.value)
      if (closeCallback) {
        closeCallback()
      }
    }
    openDateInputId.value = id
  }

  const close = (id: string) => {
    if (openDateInputId.value === id) {
      openDateInputId.value = null
    }
  }

  const isOpen = (id: string) => {
    return openDateInputId.value === id
  }

  const closeAll = () => {
    dateInputInstances.forEach((closeCallback) => {
      closeCallback()
    })
    openDateInputId.value = null
  }

  return {
    generateId,
    register,
    open,
    close,
    isOpen,
    closeAll,
  }
}

