<template>
  <div class="min-h-screen bg-background">
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-foreground mb-2">🧪 Test Data Overview</h1>
        <p class="text-muted-foreground">
          Comprehensive test data for user permissions and vessel management testing
        </p>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-card border border-border rounded-lg p-6">
          <div class="flex items-center">
            <div class="p-2 bg-primary/10 rounded-lg">
              <Icon name="users" class="w-6 h-6 text-primary" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-muted-foreground">Total Users</p>
              <p class="text-2xl font-bold text-foreground">{{ stats.total_users }}</p>
            </div>
          </div>
        </div>

        <div class="bg-card border border-border rounded-lg p-6">
          <div class="flex items-center">
            <div class="p-2 bg-primary/10 rounded-lg">
              <Icon name="ship" class="w-6 h-6 text-primary" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-muted-foreground">Total Vessels</p>
              <p class="text-2xl font-bold text-foreground">{{ stats.total_vessels }}</p>
            </div>
          </div>
        </div>

        <div class="bg-card border border-border rounded-lg p-6">
          <div class="flex items-center">
            <div class="p-2 bg-primary/10 rounded-lg">
              <Icon name="link" class="w-6 h-6 text-primary" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-muted-foreground">Vessel-User Links</p>
              <p class="text-2xl font-bold text-foreground">{{ stats.total_vessel_users }}</p>
            </div>
          </div>
        </div>

        <div class="bg-card border border-border rounded-lg p-6">
          <div class="flex items-center">
            <div class="p-2 bg-primary/10 rounded-lg">
              <Icon name="shield" class="w-6 h-6 text-primary" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-muted-foreground">Role Access Types</p>
              <p class="text-2xl font-bold text-foreground">{{ stats.total_role_accesses }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Test Users Section -->
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-foreground mb-4">👥 Test Users</h2>
        <div class="bg-card border border-border rounded-lg overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-muted/50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">User</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Type</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">System Roles</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Vessel Access</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="user in testUsers" :key="user.id" class="hover:bg-muted/25">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div class="text-sm font-medium text-foreground">{{ user.name }}</div>
                      <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <Badge :variant="user.user_type === 'paid_system' ? 'default' : 'secondary'">
                      {{ user.user_type === 'paid_system' ? 'Paid System' : 'Employee' }}
                    </Badge>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex flex-wrap gap-1">
                      <Badge v-for="role in user.roles" :key="role" variant="outline" class="text-xs">
                        {{ role }}
                      </Badge>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="space-y-1">
                      <div v-for="vessel in user.vessels" :key="vessel.vessel_id" class="text-sm">
                        <span class="font-medium">{{ vessel.vessel_name }}</span>
                        <Badge :variant="getRoleVariant(vessel.role)" class="ml-2 text-xs">
                          {{ vessel.role }}
                        </Badge>
                        <Badge v-if="!vessel.is_active" variant="destructive" class="ml-1 text-xs">
                          Inactive
                        </Badge>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <Button size="sm" variant="outline" @click="loginAsUser(user.email)">
                      Login as User
                    </Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Test Vessels Section -->
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-foreground mb-4">🚢 Test Vessels</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="vessel in testVessels" :key="vessel.id" class="bg-card border border-border rounded-lg p-6">
            <div class="flex items-start justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-foreground">{{ vessel.name }}</h3>
                <p class="text-sm text-muted-foreground">{{ vessel.registration_number }}</p>
              </div>
              <Badge :variant="getStatusVariant(vessel.status)">
                {{ vessel.status }}
              </Badge>
            </div>

            <div class="space-y-2 mb-4">
              <div class="text-sm text-muted-foreground">
                <strong>Type:</strong> {{ vessel.type }}
              </div>
              <div class="text-sm text-muted-foreground">
                <strong>Owner:</strong> {{ vessel.owner ? vessel.owner.name : 'No Owner' }}
              </div>
              <div class="text-sm text-muted-foreground">
                <strong>Users:</strong> {{ vessel.users.length }}
              </div>
            </div>

            <div class="space-y-1">
              <div v-for="user in vessel.users" :key="user.user_id" class="text-sm">
                <span class="font-medium">{{ user.user_name }}</span>
                <Badge :variant="getRoleVariant(user.role)" class="ml-2 text-xs">
                  {{ user.role }}
                </Badge>
                <Badge v-if="!user.is_active" variant="destructive" class="ml-1 text-xs">
                  Inactive
                </Badge>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Permission Matrix Section -->
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-foreground mb-4">🎭 Permission Matrix</h2>
        <div class="bg-card border border-border rounded-lg overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-muted/50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Role</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Description</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Permissions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="roleAccess in roleAccesses" :key="roleAccess.id" class="hover:bg-muted/25">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div class="text-sm font-medium text-foreground">{{ roleAccess.display_name }}</div>
                      <div class="text-sm text-muted-foreground">{{ roleAccess.name }}</div>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-muted-foreground">{{ roleAccess.description }}</div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex flex-wrap gap-1">
                      <Badge v-for="permission in roleAccess.permissions" :key="permission" variant="outline" class="text-xs">
                        {{ permission }}
                      </Badge>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Test Credentials Section -->
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-foreground mb-4">🔑 Test Credentials</h2>
        <div class="bg-card border border-border rounded-lg p-6">
          <div class="mb-4">
            <p class="text-sm text-muted-foreground mb-2">
              All test users have the password: <code class="bg-muted px-2 py-1 rounded text-sm">password</code>
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="user in testUsers" :key="user.email" class="flex items-center justify-between p-3 bg-muted/25 rounded-lg">
              <div>
                <div class="font-medium text-sm">{{ user.email }}</div>
                <div class="text-xs text-muted-foreground">{{ user.name }}</div>
              </div>
              <Button size="sm" variant="outline" @click="copyCredentials(user.email)">
                Copy
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Test Scenarios Section -->
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-foreground mb-4">🧪 Test Scenarios</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-card border border-border rounded-lg p-6">
            <h3 class="text-lg font-semibold text-foreground mb-3">User Type Testing</h3>
            <ul class="space-y-2 text-sm text-muted-foreground">
              <li>• Paid system users can create vessels</li>
              <li>• Employee users can only access assigned vessels</li>
              <li>• Mixed permission users have different roles per vessel</li>
              <li>• Edge cases: no vessels, inactive access</li>
            </ul>
          </div>

          <div class="bg-card border border-border rounded-lg p-6">
            <h3 class="text-lg font-semibold text-foreground mb-3">Permission Testing</h3>
            <ul class="space-y-2 text-sm text-muted-foreground">
              <li>• Test vessel creation permissions</li>
              <li>• Test vessel access based on roles</li>
              <li>• Test edit/delete permissions</li>
              <li>• Test multi-vessel access</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Icon from '@/Components/Icon.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Button from '@/Components/ui/button/Button.vue'

interface TestUser {
  id: number
  name: string
  email: string
  user_type: string
  roles: string[]
  vessels: Array<{
    vessel_id: number
    vessel_name: string
    role: string
    is_active: boolean
    permissions: string[]
  }>
}

interface TestVessel {
  id: number
  name: string
  registration_number: string
  type: string
  status: string
  owner: {
    id: number
    name: string
    email: string
  } | null
  users: Array<{
    user_id: number
    user_name: string
    user_email: string
    role: string
    is_active: boolean
  }>
}

interface RoleAccess {
  id: number
  name: string
  display_name: string
  description: string
  permissions: string[]
  is_active: boolean
}

interface Stats {
  total_users: number
  test_users: number
  total_vessels: number
  test_vessels: number
  total_vessel_users: number
  active_vessel_users: number
  inactive_vessel_users: number
  vessels_with_owners: number
  vessels_without_owners: number
  total_role_accesses: number
}

interface Props {
  testUsers: TestUser[]
  testVessels: TestVessel[]
  roleAccesses: RoleAccess[]
  stats: Stats
}

const props = defineProps<Props>()

const getRoleVariant = (role: string) => {
  switch (role) {
    case 'administrator':
      return 'default'
    case 'supervisor':
      return 'secondary'
    case 'moderator':
      return 'outline'
    case 'normal':
      return 'outline'
    default:
      return 'outline'
  }
}

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

const loginAsUser = (email: string) => {
  // This would typically redirect to login with pre-filled email
  // For now, just copy the credentials
  copyCredentials(email)
}

const copyCredentials = async (email: string) => {
  const credentials = `Email: ${email}\nPassword: password`
  try {
    await navigator.clipboard.writeText(credentials)
    // You could add a toast notification here
    console.log('Credentials copied to clipboard')
  } catch (err) {
    console.error('Failed to copy credentials:', err)
  }
}
</script>

