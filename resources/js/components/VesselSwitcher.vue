<template>
  <div class="relative">
    <!-- Current Vessel Display -->
    <button
      @click="toggleDropdown"
      class="flex items-center w-full px-3 py-2 text-left text-sm bg-background dark:bg-background border border-input dark:border-input rounded-lg hover:bg-muted/50 dark:hover:bg-muted/50 transition-colors"
    >
      <Icon name="ship" class="w-4 h-4 mr-2 text-primary dark:text-primary" />
      <div class="flex-1 min-w-0">
        <div class="font-medium text-card-foreground dark:text-card-foreground truncate">
          {{ currentVessel?.name }}
        </div>
        <div class="text-xs text-muted-foreground dark:text-muted-foreground truncate">
          {{ currentVessel?.registration_number }}
        </div>
      </div>
      <Icon
        :name="isOpen ? 'chevron-up' : 'chevron-down'"
        class="w-4 h-4 text-muted-foreground dark:text-muted-foreground ml-2"
      />
    </button>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute top-full left-0 right-0 mt-1 bg-card dark:bg-card border border-sidebar-border/70 dark:border-sidebar-border rounded-lg shadow-lg z-50 max-h-60 overflow-y-auto"
      >
        <div class="p-2">
          <div class="text-xs font-medium text-muted-foreground dark:text-muted-foreground px-2 py-1 mb-1">
            Switch Vessel
          </div>

          <button
            v-for="vessel in availableVessels"
            :key="vessel.id"
            @click="switchVessel(vessel.id)"
            :class="[
              'w-full flex items-center px-2 py-2 text-sm rounded-md transition-colors',
              vessel.id === currentVessel?.id
                ? 'bg-primary/10 text-primary dark:text-primary'
                : 'hover:bg-muted/50 dark:hover:bg-muted/50 text-card-foreground dark:text-card-foreground'
            ]"
          >
            <Icon name="ship" class="w-4 h-4 mr-2" />
            <div class="flex-1 min-w-0">
              <div class="font-medium truncate">{{ vessel.name }}</div>
              <div class="text-xs text-muted-foreground dark:text-muted-foreground truncate">
                {{ vessel.registration_number }} • {{ vessel.user_role }}
              </div>
            </div>
            <Icon
              v-if="vessel.id === currentVessel?.id"
              name="check"
              class="w-4 h-4 text-primary dark:text-primary ml-2"
            />
          </button>
        </div>

        <div class="border-t border-sidebar-border/70 dark:border-sidebar-border p-2">
          <button
            @click="goToVesselSelector"
            class="w-full flex items-center px-2 py-2 text-sm text-muted-foreground dark:text-muted-foreground hover:bg-muted/50 dark:hover:bg-muted/50 rounded-md transition-colors"
          >
            <Icon name="plus" class="w-4 h-4 mr-2" />
            Manage Vessels
          </button>
        </div>
      </div>
    </Transition>

    <!-- Backdrop -->
    <div
      v-if="isOpen"
      @click="closeDropdown"
      class="fixed inset-0 z-40"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { Ship, ChevronDown, ChevronUp, Check, Plus } from 'lucide-vue-next'
import Icon from '@/Components/Icon.vue'

interface Vessel {
  id: number
  name: string
  registration_number: string
  user_role: string
}

interface Props {
  currentVessel?: Vessel | null
  availableVessels: Vessel[]
}

const props = defineProps<Props>()

const isOpen = ref(false)
const isSwitching = ref(false)

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

const closeDropdown = () => {
  isOpen.value = false
}

const switchVessel = (vesselId: number) => {
  if (isSwitching.value || vesselId === props.currentVessel?.id) return

  isSwitching.value = true
  closeDropdown()

  router.post('/panel/select', {
    vessel_id: vesselId
  }, {
    onFinish: () => {
      isSwitching.value = false
    }
  })
}

const goToVesselSelector = () => {
  closeDropdown()
  router.visit('/panel')
}

// Close dropdown on escape key
const handleEscape = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscape)
})
</script>

