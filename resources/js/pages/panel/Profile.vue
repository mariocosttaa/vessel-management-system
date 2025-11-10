<template>
  <IndexDefaultLayout :breadcrumbs="breadcrumbs">
    <!-- Main Content -->
    <main class="flex-1 p-6">
      <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-card-foreground dark:text-card-foreground mb-2">
            {{ t('Profile Settings') }}
          </h1>
          <p class="text-muted-foreground dark:text-muted-foreground">
            {{ t('Manage your account settings and preferences') }}
          </p>
        </div>

        <!-- Profile Settings Card -->
        <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6 mb-6">
          <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-6">
            {{ t('Profile Information') }}
          </h2>

          <form @submit.prevent="updateProfile" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Name -->
              <div class="space-y-2">
                <Label for="name">{{ t('Name') }}</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  type="text"
                  required
                  autocomplete="name"
                  :placeholder="t('Full name')"
                />
                <InputError :message="form.errors.name" />
              </div>

              <!-- Email -->
              <div class="space-y-2">
                <Label for="email">{{ t('Email Address') }}</Label>
                <Input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  autocomplete="username"
                  :placeholder="t('Email address')"
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
                    {{ t('Email Address Unverified') }}
                  </p>
                  <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                    {{ t('Your email address is unverified.') }}
                    <Link
                      href="/email/verification-notification"
                      as="button"
                      class="underline hover:no-underline"
                    >
                      {{ t('Click here to resend the verification email.') }}
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
                  {{ t('A new verification link has been sent to your email address.') }}
                </p>
              </div>
            </div>

            <!-- Notification Settings (only show if user has high vessel access) -->
            <div v-if="hasHighVesselAccess" class="space-y-4 pt-4 border-t border-sidebar-border/70 dark:border-sidebar-border">
              <div class="flex items-center justify-between">
                <div class="space-y-1">
                  <Label for="vessel_admin_notification" class="text-base font-medium">
                    {{ t('Administration Notifications') }}
                  </Label>
                  <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                    {{ t('Receive email notifications when other people make important changes to transactions and mareas of your vessels.') }}
                  </p>
                </div>
                <Switch
                  id="vessel_admin_notification"
                  v-model:checked="form.vessel_admin_notification"
                  :disabled="form.processing"
                />
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
                {{ form.processing ? t('Saving...') : t('Save Changes') }}
              </Button>
            </div>
          </form>
        </div>

        <!-- Password Settings Card -->
        <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6 mb-6">
          <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-6">
            {{ t('Password Settings') }}
          </h2>

          <form @submit.prevent="updatePassword" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Current Password -->
              <div class="space-y-2">
                <Label for="current_password">{{ t('Current Password') }}</Label>
                <Input
                  id="current_password"
                  v-model="passwordForm.current_password"
                  type="password"
                  required
                  autocomplete="current-password"
                  :placeholder="t('Enter current password')"
                />
                <InputError :message="passwordForm.errors.current_password" />
              </div>

              <!-- New Password -->
              <div class="space-y-2">
                <Label for="password">{{ t('New Password') }}</Label>
                <Input
                  id="password"
                  v-model="passwordForm.password"
                  type="password"
                  required
                  autocomplete="new-password"
                  :placeholder="t('Enter new password')"
                />
                <InputError :message="passwordForm.errors.password" />
              </div>
            </div>

            <!-- Confirm Password -->
            <div class="space-y-2">
              <Label for="password_confirmation">{{ t('Confirm New Password') }}</Label>
              <Input
                id="password_confirmation"
                v-model="passwordForm.password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="t('Confirm new password')"
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
                {{ passwordForm.processing ? t('Updating...') : t('Update Password') }}
              </Button>
            </div>
          </form>
        </div>

        <!-- Account Actions Card -->
        <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
          <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-6">
            {{ t('Account Actions') }}
          </h2>

          <div class="space-y-4">
            <!-- Delete Account -->
            <div class="flex items-center justify-between p-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
              <div>
                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                  {{ t('Delete Account') }}
                </h3>
                <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                  {{ t('Permanently delete your account and all associated data. This action cannot be undone.') }}
                </p>
              </div>
              <Button
                variant="destructive"
                size="sm"
                @click="showDeleteModal = true"
                class="ml-4"
              >
                <Icon name="trash-2" class="w-4 h-4 mr-2" />
                {{ t('Delete Account') }}
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
          {{ t('Delete Account') }}
        </h3>
        <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-6">
          {{ t('Are you sure you want to delete your account? This action cannot be undone and will permanently remove all your data.') }}
        </p>
        <div class="flex justify-end space-x-3">
          <Button
            variant="outline"
            @click="showDeleteModal = false"
          >
            {{ t('Cancel') }}
          </Button>
          <Button
            variant="destructive"
            @click="deleteAccount"
          >
            {{ t('Delete Account') }}
          </Button>
        </div>
      </div>
    </div>
  </IndexDefaultLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
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
import { Switch } from '@/components/ui/switch'
import InputError from '@/components/InputError.vue'
import IndexDefaultLayout from '@/layouts/IndexDefault/IndexDefaultLayout.vue'
import type { BreadcrumbItemType } from '@/types'
import { useI18n } from '@/composables/useI18n'

interface User {
  id: number
  name: string
  email: string
  email_verified_at: string | null
  vessel_admin_notification?: boolean
}

interface Props {
  user: User
  mustVerifyEmail: boolean
  status?: string
  hasHighVesselAccess?: boolean
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
  {
    title: t('Profile Settings'),
    href: '/panel/profile',
  },
])

const showDeleteModal = ref(false)

// Computed property for high vessel access
const hasHighVesselAccess = computed(() => props.hasHighVesselAccess ?? false)

// Profile form
const form = useForm({
  name: props.user.name,
  email: props.user.email,
  vessel_admin_notification: Boolean(props.user.vessel_admin_notification ?? false),
})

// Password form
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const updateProfile = () => {
  // Debug: Log form data before submission
  console.log('Form data before submission:', {
    name: form.name,
    email: form.email,
    vessel_admin_notification: form.vessel_admin_notification,
    hasHighVesselAccess: hasHighVesselAccess.value
  })

  form.patch('/panel/profile', {
    preserveScroll: true,
    onSuccess: () => {
      // Profile updated successfully
    },
    onError: (errors) => {
      console.error('Profile update errors:', errors)
      console.error('Form data that was sent:', form.data())
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
