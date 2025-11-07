<template>
  <IndexDefaultLayout :breadcrumbs="breadcrumbs">
    <!-- Main Content -->
    <main class="flex-1 p-6">
      <div class="max-w-7xl mx-auto">
        <!-- Simple Header (only show if user has permission to create vessels) -->
        <div v-if="permissions.can_create_vessels" class="text-center mb-8">
          <h1 class="text-4xl font-bold text-foreground mb-4">
            Your Vessels
          </h1>
          <p class="text-lg text-muted-foreground">
            Choose a vessel to manage its financial operations
          </p>
        </div>

        <!-- Vessels Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <!-- Blank Vessel Card for Creating New Vessel (only if user has permission) -->
          <div
            v-if="permissions.can_create_vessels"
            @click="createVessel"
            class="rounded-xl border-2 border-dashed border-border bg-card/50 p-8 cursor-pointer hover:bg-muted/50 transition-colors group flex flex-col items-center justify-center min-h-[200px]"
          >
            <div class="flex flex-col items-center justify-center text-center">
              <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/20 transition-colors">
                <Icon name="plus" class="w-6 h-6 text-primary" />
              </div>
              <h3 class="text-lg font-semibold text-card-foreground mb-2 group-hover:text-primary transition-colors">
                Create New Vessel
              </h3>
              <p class="text-sm text-muted-foreground">
                Add a new vessel to your fleet
              </p>
            </div>
          </div>

          <!-- Existing Vessels -->
          <div
            v-for="vessel in vessels"
            :key="vessel.id"
            @click="selectVessel(vessel.id)"
            class="rounded-xl border border-border bg-card p-6 cursor-pointer hover:bg-muted/50 transition-colors group"
          >
            <!-- Vessel Header -->
            <div class="flex items-start justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-card-foreground group-hover:text-primary transition-colors">
                  {{ vessel.name }}
                </h3>
                <p class="text-sm text-muted-foreground">
                  {{ vessel.registration_number }}
                </p>
              </div>
              <Badge :variant="getStatusVariant(vessel.status)">
                {{ vessel.status_label }}
              </Badge>
            </div>

            <!-- Vessel Details -->
                <div class="space-y-2 mb-4">
                  <div class="flex items-center text-sm text-muted-foreground">
                    <Icon name="ship" class="w-4 h-4 mr-2" />
                    {{ vessel.vessel_type }}
                  </div>
                  <div class="flex items-center text-sm text-muted-foreground">
                    <Icon name="users" class="w-4 h-4 mr-2" />
                    {{ vessel.crew_count }} crew members
                  </div>
                  <div class="flex items-center text-sm text-muted-foreground">
                    <Icon name="receipt" class="w-4 h-4 mr-2" />
                    {{ vessel.transaction_count }} transactions
                  </div>
                </div>

            <!-- User Role and Actions -->
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <Icon name="shield" class="w-4 h-4 mr-2 text-primary" />
                <span class="text-sm font-medium text-primary capitalize">
                  {{ vessel.user_role }}
                </span>
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center space-x-2">
                <!-- Edit Button -->
                <button
                  v-if="vessel.permissions.can_edit"
                  @click.stop="editVessel(vessel.id)"
                  class="p-1 text-muted-foreground hover:text-primary transition-colors"
                  title="Edit vessel"
                >
                  <Icon name="edit" class="w-4 h-4" />
                </button>

                <!-- Delete Button -->
                <button
                  v-if="vessel.permissions.can_delete"
                  @click.stop="deleteVessel(vessel.id, vessel.name)"
                  class="p-1 text-muted-foreground hover:text-destructive transition-colors"
                  title="Delete vessel"
                >
                  <Icon name="trash-2" class="w-4 h-4" />
                </button>

                    <!-- Select Arrow -->
                    <Icon name="arrow-right" class="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State (when no vessels exist and user can't create vessels) -->
            <div v-if="vessels.length === 0 && !permissions.can_create_vessels" class="text-center py-12">
              <h3 class="text-lg font-semibold text-foreground mb-2">
                You don't have any vessel yet
              </h3>
              <p class="text-muted-foreground mb-6">
                Get started by creating your first vessel or contact us for a subscription upgrade.
              </p>

              <!-- CTA Card for Paid System -->
              <div class="rounded-xl border border-primary/20 bg-gradient-to-br from-primary/5 to-primary/10 p-8 max-w-lg mx-auto">
                <div class="flex items-center justify-center mb-4">
                  <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
                    <Icon name="crown" class="h-6 w-6 text-primary" />
                  </div>
                </div>
                <h4 class="text-xl font-semibold text-card-foreground mb-2">
                  Upgrade to Paid System
                </h4>
                <p class="text-sm text-muted-foreground mb-6">
                  Get full access to vessel management, crew tracking, and financial operations with our professional plan.
                </p>
                <div class="space-y-3">
                  <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                    <Icon name="check" class="w-4 h-4 text-green-500" />
                    <span>Unlimited vessel management</span>
                  </div>
                  <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                    <Icon name="check" class="w-4 h-4 text-green-500" />
                    <span>Crew member tracking</span>
                  </div>
                  <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                    <Icon name="check" class="w-4 h-4 text-green-500" />
                    <span>Financial operations</span>
                  </div>
                  <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                    <Icon name="check" class="w-4 h-4 text-green-500" />
                    <span>Priority support</span>
                  </div>
                </div>
            <div class="mt-6">
              <a
                href="mailto:geral@bindamy.site?subject=Vessel Management System - Upgrade Request"
                class="inline-flex items-center px-6 py-3 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors font-medium"
              >
                <Icon name="mail" class="w-4 h-4 mr-2" />
                Contact for Upgrade
              </a>
            </div>
                <p class="text-xs text-muted-foreground mt-3">
                  Email: geral@bindamy.site
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
                Upgrade Required
              </h3>
              <p class="text-sm text-muted-foreground mb-6">
                You need a paid subscription to create and manage vessels. Upgrade to our professional plan to get started.
              </p>

              <div class="space-y-3 mb-6">
                <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                  <Icon name="check" class="w-4 h-4 text-green-500" />
                  <span>Create unlimited vessels</span>
                </div>
                <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                  <Icon name="check" class="w-4 h-4 text-green-500" />
                  <span>Full vessel management</span>
                </div>
                <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                  <Icon name="check" class="w-4 h-4 text-green-500" />
                  <span>Priority support</span>
                </div>
              </div>

          <div class="space-y-3">
            <a
              href="mailto:geral@bindamy.site?subject=Vessel Management System - Upgrade Request"
              class="block w-full px-6 py-3 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors font-medium text-center"
            >
              <Icon name="mail" class="w-4 h-4 mr-2 inline" />
              Contact for Upgrade
            </a>
            <button
              @click="showUpgradeModal = false"
              class="block w-full px-6 py-2 text-muted-foreground hover:text-card-foreground transition-colors"
            >
              Maybe Later
            </button>
          </div>

              <p class="text-xs text-muted-foreground mt-4">
                Email: geral@bindamy.site
              </p>
        </div>
      </div>
    </div>
  </IndexDefaultLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Icon from '@/components/Icon.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import IndexDefaultLayout from '@/layouts/IndexDefault/IndexDefaultLayout.vue'
import type { BreadcrumbItemType } from '@/types'

interface Vessel {
  id: number
  name: string
  registration_number: string
  vessel_type: string
  status: string
  status_label: string
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

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
  {
    title: 'Vessels',
    href: '/panel',
  },
]

const user = computed(() => page.props.auth?.user || props.user)

const isSelecting = ref(false)
const showUpgradeModal = ref(false)

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

const selectVessel = (vesselId: number) => {
  if (isSelecting.value) return

  isSelecting.value = true

  router.post('/panel/select', {
    vessel_id: vesselId
  }, {
    onFinish: () => {
      isSelecting.value = false
    }
  })
}

const createVessel = () => {
  router.visit('/panel/vessel/create')
}

const editVessel = (vesselId: number) => {
  router.visit(`/panel/vessel/${vesselId}/edit`)
}

const deleteVessel = (vesselId: number, vesselName: string) => {
  if (confirm(`Are you sure you want to delete "${vesselName}"? This action cannot be undone.`)) {
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
</script>
