<template>
  <IndexDefaultLayout :breadcrumbs="breadcrumbs">
    <!-- Cool Animation Overlay -->
    <Transition
      enter-active-class="transition-opacity duration-500"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-300"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="showCoolAnimation"
        class="fixed inset-0 z-50 flex items-center justify-center bg-background dark:bg-[#121212]"
      >
        <div class="text-center">
          <!-- Animated Company Logo -->
          <div class="mb-8 animate-bounce">
            <Logo
              variant="auto"
              type="svg"
              height="80px"
              className="h-20 max-w-[300px] mx-auto"
            />
          </div>

          <!-- Animated text -->
          <h2 class="text-3xl font-bold mb-4 animate-pulse text-foreground">
            {{ t('Preparing your experience...') }}
          </h2>

          <!-- Animated dots -->
          <div class="flex justify-center space-x-2">
            <div
              class="w-3 h-3 bg-primary rounded-full animate-bounce"
              style="animation-delay: 0ms"
            />
            <div
              class="w-3 h-3 bg-primary rounded-full animate-bounce"
              style="animation-delay: 150ms"
            />
            <div
              class="w-3 h-3 bg-primary rounded-full animate-bounce"
              style="animation-delay: 300ms"
            />
          </div>
        </div>
      </div>
    </Transition>

    <!-- Loading Overlay -->
    <VesselLoading
      v-if="loadingVessel"
      :vessel="loadingVessel"
      :loading-progress="loadingProgress"
      :on-back="handleBackFromLoading"
      :on-skip="handleSkipLoading"
    />

    <!-- Main Content -->
    <main class="flex-1 pt-6 pb-4 px-4" :class="{ 'pointer-events-none opacity-50': isEntering || loadingVessel }">
      <div class="max-w-7xl mx-auto">
        <!-- Simple Header (only show if user has permission to create vessels) -->
        <div v-if="permissions.can_create_vessels" class="text-center mb-5">
          <h1 class="text-2xl font-bold text-foreground mb-1.5">
            {{ t('Your Vessels') }}
          </h1>
          <p class="text-sm text-muted-foreground">
            {{ t('Choose a vessel to manage its financial operations') }}
          </p>
        </div>

        <!-- Vessels Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
          <!-- Blank Vessel Card for Creating New Vessel (only if user has permission) -->
          <div
            v-if="permissions.can_create_vessels"
            @click="createVessel"
            class="rounded-lg border-2 border-dashed border-border bg-card/50 p-4 cursor-pointer hover:bg-muted/50 transition-all duration-300 hover:scale-105 hover:shadow-lg group flex flex-col items-center justify-center min-h-[140px]"
          >
            <div class="flex flex-col items-center justify-center text-center">
              <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center mb-2.5 group-hover:bg-primary/20 transition-colors group-hover:scale-110">
                <Icon name="plus" class="w-4 h-4 text-primary" />
              </div>
              <h3 class="text-sm font-semibold text-card-foreground mb-1 group-hover:text-primary transition-colors">
                {{ t('Create New Vessel') }}
              </h3>
              <p class="text-xs text-muted-foreground leading-tight">
                {{ t('Add a new vessel to your fleet') }}
              </p>
            </div>
          </div>

          <!-- Existing Vessels -->
          <div
            v-for="vessel in vessels"
            :key="vessel.id"
            @click="handleCardClick(vessel)"
            class="rounded-lg border border-border bg-card p-3 cursor-pointer hover:bg-muted/50 transition-all duration-300 hover:scale-105 hover:shadow-xl group relative overflow-hidden"
            :class="{
              'opacity-50 cursor-not-allowed': isEntering
            }"
          >
            <!-- Hover effect overlay -->
            <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none" />

            <!-- Vessel Logo -->
            <div v-if="vessel.logo_url" class="mb-2.5 relative z-10 flex justify-center">
              <div class="w-14 h-14 rounded-lg overflow-hidden border-2 border-border bg-card shadow-sm">
                <img
                  :src="vessel.logo_url"
                  :alt="vessel.name"
                  class="w-full h-full object-cover"
                />
              </div>
            </div>

            <!-- Vessel Header -->
            <div class="flex items-start justify-between mb-2.5 relative z-10">
              <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-card-foreground group-hover:text-primary transition-colors">
                  {{ vessel.name }}
                </h3>
                <p class="text-xs text-muted-foreground leading-tight">
                  {{ vessel.registration_number }}
                </p>
              </div>
              <Badge :variant="getStatusVariant(vessel.status)" class="ml-1.5 flex-shrink-0 text-xs px-1.5 py-0.5">
                {{ vessel.status_label }}
              </Badge>
            </div>

            <!-- Vessel Details -->
            <div class="space-y-1 mb-2.5 relative z-10">
              <div class="flex items-center text-xs text-muted-foreground leading-tight">
                <Icon name="ship" class="w-3 h-3 mr-1.5 flex-shrink-0" />
                <span class="truncate">{{ vessel.vessel_type }}</span>
              </div>
              <div class="flex items-center text-xs text-muted-foreground leading-tight">
                <Icon name="users" class="w-3 h-3 mr-1.5 flex-shrink-0" />
                <span>{{ vessel.crew_count }} {{ t('crew members') }}</span>
              </div>
              <div class="flex items-center text-xs text-muted-foreground leading-tight">
                <Icon name="receipt" class="w-3 h-3 mr-1.5 flex-shrink-0" />
                <span>{{ vessel.transaction_count }} {{ t('transactions') }}</span>
              </div>
            </div>

            <!-- User Role and Actions -->
            <div class="flex items-center justify-between relative z-10">
              <div class="flex items-center">
                <Icon name="shield" class="w-3 h-3 mr-1 text-primary flex-shrink-0" />
                <span class="text-xs font-medium text-primary capitalize leading-tight">
                  {{ vessel.user_role }}
                </span>
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center space-x-1">
                <!-- Edit Button -->
                <button
                  v-if="vessel.permissions.can_edit"
                  @click.stop="editVessel(vessel.id)"
                  class="p-0.5 text-muted-foreground hover:text-primary transition-colors rounded hover:bg-primary/10"
                  :title="t('Edit vessel')"
                >
                  <Icon name="edit" class="w-3 h-3" />
                </button>

                <!-- Delete Button -->
                <button
                  v-if="vessel.permissions.can_delete"
                  @click.stop="deleteVessel(vessel.id, vessel.name)"
                  class="p-0.5 text-muted-foreground hover:text-destructive transition-colors rounded hover:bg-destructive/10"
                  :title="t('Delete vessel')"
                >
                  <Icon name="trash-2" class="w-3 h-3" />
                </button>

                <!-- Select Arrow -->
                <Icon
                  name="arrow-right"
                  class="w-3 h-3 text-muted-foreground group-hover:text-primary transition-all duration-300 group-hover:translate-x-1"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State (when no vessels exist and user can't create vessels) -->
            <div v-if="vessels.length === 0 && !permissions.can_create_vessels" class="text-center py-10">
              <h3 class="text-base font-semibold text-foreground mb-1.5">
                {{ t("You don't have any vessel yet") }}
              </h3>
              <p class="text-sm text-muted-foreground mb-5">
                {{ t('Get started by creating your first vessel or contact us for a subscription upgrade.') }}
              </p>

              <!-- CTA Card for Paid System -->
              <div class="rounded-lg border border-primary/20 bg-gradient-to-br from-primary/5 to-primary/10 p-6 max-w-lg mx-auto">
                <div class="flex items-center justify-center mb-3">
                  <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                    <Icon name="crown" class="h-5 w-5 text-primary" />
                  </div>
                </div>
                <h4 class="text-lg font-semibold text-card-foreground mb-1.5">
                  {{ t('Upgrade to Paid System') }}
                </h4>
                <p class="text-xs text-muted-foreground mb-5">
                  {{ t('Get full access to vessel management, crew tracking, and financial operations with our professional plan.') }}
                </p>
                <div class="space-y-2">
                  <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                    <Icon name="check" class="w-3.5 h-3.5 text-green-500" />
                    <span>{{ t('Unlimited vessel management') }}</span>
                  </div>
                  <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                    <Icon name="check" class="w-3.5 h-3.5 text-green-500" />
                    <span>{{ t('Crew member tracking') }}</span>
                  </div>
                  <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                    <Icon name="check" class="w-3.5 h-3.5 text-green-500" />
                    <span>{{ t('Financial operations') }}</span>
                  </div>
                  <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                    <Icon name="check" class="w-3.5 h-3.5 text-green-500" />
                    <span>{{ t('Priority support') }}</span>
                  </div>
                </div>
            <div class="mt-5">
              <a
                href="mailto:geral@bindamy.site?subject=Vessel Management System - Upgrade Request"
                class="inline-flex items-center px-5 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium"
              >
                <Icon name="mail" class="w-3.5 h-3.5 mr-1.5" />
                {{ t('Contact for Upgrade') }}
              </a>
            </div>
                <p class="text-xs text-muted-foreground mt-2.5">
                  {{ t('Email') }}: geral@bindamy.site
                </p>
          </div>
        </div>
      </div>
    </main>

    <!-- Upgrade Modal -->
    <div
      v-if="showUpgradeModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click="showUpgradeModal = false"
    >
          <div
            class="bg-card rounded-xl p-8 max-w-md w-full mx-4"
            @click.stop
          >
            <div class="text-center">
              <div class="flex items-center justify-center mb-4">
                <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
                  <Icon name="crown" class="h-6 w-6 text-primary" />
                </div>
              </div>
              <h3 class="text-xl font-semibold text-card-foreground mb-2">
                {{ t('Upgrade Required') }}
              </h3>
              <p class="text-sm text-muted-foreground mb-6">
                {{ t('You need a paid subscription to create and manage vessels. Upgrade to our professional plan to get started.') }}
              </p>

              <div class="space-y-3 mb-6">
                <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                  <Icon name="check" class="w-4 h-4 text-green-500" />
                  <span>{{ t('Create unlimited vessels') }}</span>
                </div>
                <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                  <Icon name="check" class="w-4 h-4 text-green-500" />
                  <span>{{ t('Full vessel management') }}</span>
                </div>
                <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                  <Icon name="check" class="w-4 h-4 text-green-500" />
                  <span>{{ t('Priority support') }}</span>
                </div>
              </div>

          <div class="space-y-3">
            <a
              href="mailto:geral@bindamy.site?subject=Vessel Management System - Upgrade Request"
              class="block w-full px-6 py-3 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors font-medium text-center"
            >
              <Icon name="mail" class="w-4 h-4 mr-2 inline" />
              {{ t('Contact for Upgrade') }}
            </a>
            <button
              @click="showUpgradeModal = false"
              class="block w-full px-6 py-2 text-muted-foreground hover:text-card-foreground transition-colors"
            >
              {{ t('Maybe Later') }}
            </button>
          </div>

              <p class="text-xs text-muted-foreground mt-4">
                {{ t('Email') }}: geral@bindamy.site
              </p>
        </div>
      </div>
    </div>
  </IndexDefaultLayout>
</template>

<script setup lang="ts">
import { ref, computed, onBeforeUnmount } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Icon from '@/components/Icon.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import IndexDefaultLayout from '@/layouts/IndexDefault/IndexDefaultLayout.vue'
import VesselLoading from '@/components/VesselLoading.vue'
import Logo from '@/components/Logo.vue'
import type { BreadcrumbItemType } from '@/types'
import { useI18n } from '@/composables/useI18n'

interface Vessel {
  id: number
  name: string
  registration_number: string
  vessel_type: string
  status: string
  status_label: string
  logo?: string | null
  logo_url?: string | null
  user_role: string
  role_access?: {
    name: string
    display_name: string
  } | null
  permissions: {
    can_edit: boolean
    can_delete: boolean
    can_manage_users: boolean
  }
  crew_count: number
  transaction_count: number
}

interface User {
  id: number
  name: string
  email: string
}

interface Permissions {
  can_create_vessels: boolean
  can_edit_vessels: boolean
  can_delete_vessels: boolean
}

interface Props {
  vessels: Vessel[]
  user: User
  permissions: Permissions
}

const props = defineProps<Props>()
const page = usePage()
const { t } = useI18n()

// Breadcrumbs
const breadcrumbs = computed<BreadcrumbItemType[]>(() => [
  {
    title: t('Vessels'),
    href: '/panel',
  },
])

const user = computed(() => page.props.auth?.user || props.user)

const isSelecting = ref(false)
const showUpgradeModal = ref(false)
const isEntering = ref(false)
const showCoolAnimation = ref(false)
const loadingVessel = ref<Vessel | null>(null)
const loadingProgress = ref(0)
const shouldRedirectRef = ref(true)
let intervalId: ReturnType<typeof setInterval> | null = null

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'active':
      return 'default'
    case 'maintenance':
      return 'secondary'
    case 'inactive':
      return 'destructive'
    default:
      return 'outline'
  }
}

const handleCardClick = (vessel: Vessel) => {
  if (isEntering.value || isSelecting.value) return

  setIsEntering(true)
  setShowCoolAnimation(true)

  // Cool animation for 1 second
  setTimeout(() => {
    setShowCoolAnimation(false)
    setIsEntering(false)
    handleAccessDashboard(vessel)
  }, 1000)
}

const setIsEntering = (value: boolean) => {
  isEntering.value = value
}

const setShowCoolAnimation = (value: boolean) => {
  showCoolAnimation.value = value
}

const handleAccessDashboard = (vessel: Vessel) => {
  loadingVessel.value = vessel
  loadingProgress.value = 0
  shouldRedirectRef.value = true

  const dashboardRoute = `/panel/${vessel.id}/dashboard`

  // Animate progress over 4 seconds
  intervalId = setInterval(() => {
    loadingProgress.value += 2.5 // 100% / 4 seconds = 2.5% per 100ms

    if (loadingProgress.value >= 100) {
      loadingProgress.value = 100
      if (intervalId) {
        clearInterval(intervalId)
        intervalId = null
      }

      // Only redirect if user didn't click back
      if (shouldRedirectRef.value) {
        router.visit(dashboardRoute, {
          onError: (errors: any) => {
            loadingVessel.value = null
            loadingProgress.value = 0
            console.error('Error accessing dashboard:', errors)
          },
          onSuccess: () => {
            // Success - page will redirect
          },
          onFinish: () => {
            loadingVessel.value = null
            loadingProgress.value = 0
          }
        })
      }
    }
  }, 100)
}

const handleSkipLoading = () => {
  if (loadingVessel.value) {
    if (intervalId) {
      clearInterval(intervalId)
      intervalId = null
    }
    shouldRedirectRef.value = true

    const dashboardRoute = `/panel/${loadingVessel.value.id}/dashboard`

    router.visit(dashboardRoute, {
      onError: (errors: any) => {
        loadingVessel.value = null
        loadingProgress.value = 0
        console.error('Error accessing dashboard:', errors)
      },
      onFinish: () => {
        loadingVessel.value = null
        loadingProgress.value = 0
      }
    })
  }
}

const handleBackFromLoading = () => {
  shouldRedirectRef.value = false
  if (intervalId) {
    clearInterval(intervalId)
    intervalId = null
  }
  loadingVessel.value = null
  loadingProgress.value = 0
}

const selectVessel = (vesselId: number) => {
  if (isSelecting.value || isEntering.value) return

  const vessel = props.vessels.find(v => v.id === vesselId)
  if (vessel) {
    handleCardClick(vessel)
  }
}

const createVessel = () => {
  if (isEntering.value) return
  router.visit('/panel/vessel/create')
}

const editVessel = (vesselId: number) => {
  if (isEntering.value) return
  router.visit(`/panel/vessel/${vesselId}/edit`)
}

const deleteVessel = (vesselId: number, vesselName: string) => {
  if (isEntering.value) return
  if (confirm(t('Are you sure you want to delete') + ` "${vesselName}"? ` + t('This action cannot be undone.'))) {
    router.delete(`/panel/vessel/${vesselId}`, {
      onSuccess: () => {
        // Vessel will be removed from the list automatically
      },
      onError: (errors: any) => {
        console.error('Error deleting vessel:', errors)
      }
    })
  }
}

// Cleanup interval on unmount
onBeforeUnmount(() => {
  if (intervalId) {
    clearInterval(intervalId)
    intervalId = null
  }
})
</script>
