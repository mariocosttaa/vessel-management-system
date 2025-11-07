<template>
  <IndexDefaultLayout :breadcrumbs="breadcrumbs">
    <!-- Main Content -->
    <main class="flex-1 p-6">
      <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-card-foreground dark:text-card-foreground mb-2">
            Profile Settings
          </h1>
          <p class="text-muted-foreground dark:text-muted-foreground">
            Manage your account settings and preferences
          </p>
        </div>

        <!-- Profile Settings Card -->
        <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6 mb-6">
          <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-6">
            Profile Information
          </h2>

          <form @submit.prevent="updateProfile" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Name -->
              <div class="space-y-2">
                <Label for="name">Name</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  type="text"
                  required
                  autocomplete="name"
                  placeholder="Full name"
                />
                <InputError :message="form.errors.name" />
              </div>

              <!-- Email -->
              <div class="space-y-2">
                <Label for="email">Email Address</Label>
                <Input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  autocomplete="username"
                  placeholder="Email address"
                />
                <InputError :message="form.errors.email" />
              </div>
            </div>

            <!-- Email Verification Notice -->
            <div v-if="mustVerifyEmail && !user.email_verified_at" class="rounded-lg border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 p-4">
              <div class="flex items-center">
                <Icon name="alert-triangle" class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" />
                <div>
                  <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    Email Address Unverified
                  </p>
                  <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                    Your email address is unverified.
                    <Link
                      href="/email/verification-notification"
                      as="button"
                      class="underline hover:no-underline"
                    >
                      Click here to resend the verification email.
                    </Link>
                  </p>
                </div>
              </div>
            </div>

            <!-- Success Message -->
            <div v-if="status === 'verification-link-sent'" class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-4">
              <div class="flex items-center">
                <Icon name="check-circle" class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" />
                <p class="text-sm font-medium text-green-800 dark:text-green-200">
                  A new verification link has been sent to your email address.
                </p>
              </div>
            </div>

            <!-- Save Button -->
            <div class="flex items-center justify-between">
              <Button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center"
              >
                <Icon name="save" class="w-4 h-4 mr-2" />
                {{ form.processing ? 'Saving...' : 'Save Changes' }}
              </Button>
            </div>
          </form>
        </div>

        <!-- Password Settings Card -->
        <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6 mb-6">
          <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-6">
            Password Settings
          </h2>

          <form @submit.prevent="updatePassword" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Current Password -->
              <div class="space-y-2">
                <Label for="current_password">Current Password</Label>
                <Input
                  id="current_password"
                  v-model="passwordForm.current_password"
                  type="password"
                  required
                  autocomplete="current-password"
                  placeholder="Enter current password"
                />
                <InputError :message="passwordForm.errors.current_password" />
              </div>

              <!-- New Password -->
              <div class="space-y-2">
                <Label for="password">New Password</Label>
                <Input
                  id="password"
                  v-model="passwordForm.password"
                  type="password"
                  required
                  autocomplete="new-password"
                  placeholder="Enter new password"
                />
                <InputError :message="passwordForm.errors.password" />
              </div>
            </div>

            <!-- Confirm Password -->
            <div class="space-y-2">
              <Label for="password_confirmation">Confirm New Password</Label>
              <Input
                id="password_confirmation"
                v-model="passwordForm.password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Confirm new password"
              />
              <InputError :message="passwordForm.errors.password_confirmation" />
            </div>

            <!-- Update Password Button -->
            <div class="flex items-center justify-between">
              <Button
                type="submit"
                variant="outline"
                :disabled="passwordForm.processing"
                class="inline-flex items-center"
              >
                <Icon name="key" class="w-4 h-4 mr-2" />
                {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
              </Button>
            </div>
          </form>
        </div>

        <!-- Account Actions Card -->
        <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
          <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-6">
            Account Actions
          </h2>

          <div class="space-y-4">
            <!-- Delete Account -->
            <div class="flex items-center justify-between p-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
              <div>
                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                  Delete Account
                </h3>
                <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                  Permanently delete your account and all associated data. This action cannot be undone.
                </p>
              </div>
              <Button
                variant="destructive"
                size="sm"
                @click="showDeleteModal = true"
                class="ml-4"
              >
                <Icon name="trash-2" class="w-4 h-4 mr-2" />
                Delete Account
              </Button>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Delete Account Modal -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click="showDeleteModal = false"
    >
      <div
        class="bg-card dark:bg-card rounded-lg p-6 max-w-md w-full mx-4"
        @click.stop
      >
        <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">
          Delete Account
        </h3>
        <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-6">
          Are you sure you want to delete your account? This action cannot be undone and will permanently remove all your data.
        </p>
        <div class="flex justify-end space-x-3">
          <Button
            variant="outline"
            @click="showDeleteModal = false"
          >
            Cancel
          </Button>
          <Button
            variant="destructive"
            @click="deleteAccount"
          >
            Delete Account
          </Button>
        </div>
      </div>
    </div>
  </IndexDefaultLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, Link, usePage, useForm } from '@inertiajs/vue3'
import {
  AlertTriangle,
  CheckCircle,
  Check,
  Save,
  Key,
  Trash2
} from 'lucide-vue-next'
import Icon from '@/components/Icon.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import IndexDefaultLayout from '@/layouts/IndexDefault/IndexDefaultLayout.vue'
import type { BreadcrumbItemType } from '@/types'

interface User {
  id: number
  name: string
  email: string
  email_verified_at: string | null
}

interface Props {
  user: User
  mustVerifyEmail: boolean
  status?: string
}

const props = defineProps<Props>()
const page = usePage()

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
  {
    title: 'Vessels',
    href: '/panel',
  },
  {
    title: 'Profile Settings',
    href: '/panel/profile',
  },
]

const showDeleteModal = ref(false)

// Profile form
const form = useForm({
  name: props.user.name,
  email: props.user.email,
})

// Password form
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const updateProfile = () => {
  form.patch('/panel/profile', {
    preserveScroll: true,
    onSuccess: () => {
      // Profile updated successfully
    },
    onError: (errors) => {
      console.error('Profile update errors:', errors)
    }
  })
}

const updatePassword = () => {
  passwordForm.put('/panel/password', {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset()
    },
    onError: (errors) => {
      console.error('Password update errors:', errors)
    }
  })
}

const deleteAccount = () => {
  router.delete('/panel/profile', {
    onSuccess: () => {
      // User will be redirected to login
    }
  })
}
</script>
