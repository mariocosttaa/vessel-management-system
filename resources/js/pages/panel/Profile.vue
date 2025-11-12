<template>
  <IndexDefaultLayout :breadcrumbs="breadcrumbs">
    <!-- Main Content -->
    <main class="flex-1 pt-6 pb-4 px-4">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-5">
          <h1 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-1.5">
            {{ t('Profile Settings') }}
          </h1>
          <p class="text-sm text-muted-foreground dark:text-muted-foreground">
            {{ t('Manage your account settings and preferences') }}
          </p>
        </div>

        <!-- Success/Error Messages -->
        <div v-if="page.props.flash?.success" class="mb-4 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-3">
          <div class="flex items-center">
            <Icon name="check-circle" class="w-4 h-4 text-green-600 dark:text-green-400 mr-2 flex-shrink-0" />
            <p class="text-xs font-medium text-green-800 dark:text-green-200">
              {{ page.props.flash.success }}
            </p>
          </div>
        </div>

        <div v-if="page.props.flash?.error" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-3">
          <div class="flex items-center">
            <Icon name="alert-triangle" class="w-4 h-4 text-red-600 dark:text-red-400 mr-2 flex-shrink-0" />
            <p class="text-xs font-medium text-red-800 dark:text-red-200">
              {{ page.props.flash.error }}
            </p>
          </div>
        </div>

        <!-- Tab Navigation -->
        <div class="mb-5 border-b border-border dark:border-sidebar-border">
          <nav class="flex space-x-1">
            <button
              @click="activeTab = 'profile'"
              :class="[
                'px-4 py-2 text-sm font-medium transition-colors border-b-2',
                activeTab === 'profile'
                  ? 'text-primary border-primary'
                  : 'text-muted-foreground border-transparent hover:text-card-foreground hover:border-muted-foreground'
              ]"
            >
              {{ t('Profile Information') }}
            </button>
            <button
              @click="activeTab = 'password'"
              :class="[
                'px-4 py-2 text-sm font-medium transition-colors border-b-2',
                activeTab === 'password'
                  ? 'text-primary border-primary'
                  : 'text-muted-foreground border-transparent hover:text-card-foreground hover:border-muted-foreground'
              ]"
            >
              {{ t('Password Settings') }}
            </button>
            <button
              @click="activeTab = 'account'"
              :class="[
                'px-4 py-2 text-sm font-medium transition-colors border-b-2',
                activeTab === 'account'
                  ? 'text-primary border-primary'
                  : 'text-muted-foreground border-transparent hover:text-card-foreground hover:border-muted-foreground'
              ]"
            >
              {{ t('Account Actions') }}
            </button>
          </nav>
        </div>

        <!-- Profile Settings Card -->
        <div v-show="activeTab === 'profile'" class="rounded-lg border border-border dark:border-sidebar-border bg-card dark:bg-card p-5 mb-4">
          <form @submit.prevent="updateProfile" class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
            <div v-if="mustVerifyEmail && !user.email_verified_at" class="rounded-lg border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 p-3">
              <div class="flex items-center">
                <Icon name="alert-triangle" class="w-4 h-4 text-yellow-600 dark:text-yellow-400 mr-2 flex-shrink-0" />
                <div>
                  <p class="text-xs font-medium text-yellow-800 dark:text-yellow-200">
                    {{ t('Email Address Unverified') }}
                  </p>
                  <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
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
            <div v-if="status === 'verification-link-sent'" class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-3">
              <div class="flex items-center">
                <Icon name="check-circle" class="w-4 h-4 text-green-600 dark:text-green-400 mr-2 flex-shrink-0" />
                <p class="text-xs font-medium text-green-800 dark:text-green-200">
                  {{ t('A new verification link has been sent to your email address.') }}
                </p>
              </div>
            </div>

            <!-- Notification Settings (only show if user has high vessel access) -->
            <div v-if="hasHighVesselAccess" class="space-y-3 pt-4 border-t border-border dark:border-sidebar-border">
              <div class="flex items-center justify-between">
                <div class="space-y-1">
                  <Label for="vessel_admin_notification" class="text-sm font-medium">
                    {{ t('Administration Notifications') }}
                  </Label>
                  <p class="text-xs text-muted-foreground dark:text-muted-foreground">
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
            <div class="flex items-center justify-between pt-2">
              <Button
                type="submit"
                :disabled="form.processing"
                size="sm"
                class="inline-flex items-center"
              >
                <Icon name="save" class="w-3.5 h-3.5 mr-1.5" />
                {{ form.processing ? t('Saving...') : t('Save Changes') }}
              </Button>
            </div>
          </form>
        </div>

        <!-- Password Settings Card -->
        <div v-show="activeTab === 'password'" class="rounded-lg border border-border dark:border-sidebar-border bg-card dark:bg-card p-5 mb-4">
          <form @submit.prevent="updatePassword" class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
            <div class="flex items-center justify-between pt-2">
              <Button
                type="submit"
                variant="outline"
                size="sm"
                :disabled="passwordForm.processing"
                class="inline-flex items-center"
              >
                <Icon name="key" class="w-3.5 h-3.5 mr-1.5" />
                {{ passwordForm.processing ? t('Updating...') : t('Update Password') }}
              </Button>
            </div>
          </form>
        </div>

        <!-- Account Actions Card -->
        <div v-show="activeTab === 'account'" class="rounded-lg border border-border dark:border-sidebar-border bg-card dark:bg-card p-5">
          <div class="space-y-4">
            <!-- Connected Accounts (OAuth) -->
            <div class="space-y-3">
              <h3 class="text-sm font-medium text-card-foreground dark:text-card-foreground mb-3">
                {{ t('Connected Accounts') }}
              </h3>
              <p class="text-xs text-muted-foreground dark:text-muted-foreground mb-4">
                {{ t('Connect your account to sign in with Google or Microsoft.') }}
              </p>

              <!-- Google Account -->
              <div class="flex items-center justify-between p-4 rounded-lg border border-border dark:border-sidebar-border bg-card dark:bg-card">
                <div class="flex items-center space-x-3">
                  <div class="flex-shrink-0">
                    <svg
                      class="w-8 h-8"
                      viewBox="0 0 24 24"
                      fill="currentColor"
                    >
                      <path
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                        fill="#4285F4"
                      />
                      <path
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                        fill="#34A853"
                      />
                      <path
                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                        fill="#FBBC05"
                      />
                      <path
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                        fill="#EA4335"
                      />
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                      Google
                    </p>
                    <p class="text-xs text-muted-foreground dark:text-muted-foreground">
                      {{ oauthConnected.google ? t('Connected') : t('Not connected') }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center space-x-3">
                  <div v-if="oauthConnected.google" class="flex items-center text-green-600 dark:text-green-400">
                    <Icon name="check-circle" class="w-5 h-5" />
                  </div>
                  <Button
                    v-if="!oauthConnected.google"
                    variant="outline"
                    size="sm"
                    @click="connectOAuth('google')"
                    class="inline-flex items-center"
                  >
                    {{ t('Connect') }}
                  </Button>
                  <Button
                    v-else
                    variant="outline"
                    size="sm"
                    @click="disconnectOAuth('google')"
                    class="inline-flex items-center text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                  >
                    {{ t('Disconnect') }}
                  </Button>
                </div>
              </div>

              <!-- Microsoft Account -->
              <div class="flex items-center justify-between p-4 rounded-lg border border-border dark:border-sidebar-border bg-card dark:bg-card opacity-60">
                <div class="flex items-center space-x-3">
                  <div class="flex-shrink-0">
                    <svg
                      class="w-8 h-8 opacity-50"
                      viewBox="0 0 23 23"
                      fill="currentColor"
                    >
                      <path
                        fill="#f25022"
                        d="M1 1h10v10H1z"
                      />
                      <path
                        fill="#00a4ef"
                        d="M12 1h10v10H12z"
                      />
                      <path
                        fill="#7fba00"
                        d="M1 12h10v10H1z"
                      />
                      <path
                        fill="#ffb900"
                        d="M12 12h10v10H12z"
                      />
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                      Microsoft
                    </p>
                    <p class="text-xs text-muted-foreground dark:text-muted-foreground">
                      {{ t('Coming soon') }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center space-x-3">
                  <div v-if="oauthConnected.microsoft" class="flex items-center text-green-600 dark:text-green-400">
                    <Icon name="check-circle" class="w-5 h-5" />
                  </div>
                  <Button
                    variant="outline"
                    size="sm"
                    disabled
                    class="inline-flex items-center"
                  >
                    {{ t('Connect') }}
                  </Button>
                </div>
              </div>
            </div>

            <!-- Delete Account -->
            <div class="pt-4 border-t border-border dark:border-sidebar-border">
              <div class="flex items-center justify-between p-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
                <div>
                  <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                    {{ t('Delete Account') }}
                  </h3>
                  <p class="text-xs text-red-700 dark:text-red-300 mt-1">
                    {{ t('Permanently delete your account and all associated data. This action cannot be undone.') }}
                  </p>
                </div>
                <Button
                  variant="destructive"
                  size="sm"
                  @click="showDeleteModal = true"
                  class="ml-4"
                >
                  <Icon name="trash-2" class="w-3.5 h-3.5 mr-1.5" />
                  {{ t('Delete Account') }}
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Delete Account Confirmation Dialog -->
    <ConfirmationDialog
      :open="showDeleteModal"
      @update:open="showDeleteModal = $event"
      :title="t('Delete Account')"
      :description="t('Are you sure you want to delete your account? This action cannot be undone and will permanently remove all your data.')"
      :message="requiresPasswordForDeletion ? t('Please enter your password to confirm this action.') : ''"
      :confirm-text="deleteAccountForm.processing ? t('Deleting...') : t('Delete Account')"
      :cancel-text="t('Cancel')"
      variant="destructive"
      type="danger"
      :loading="deleteAccountForm.processing"
      @confirm="requiresPasswordForDeletion ? showPasswordDialog = true : confirmDeleteAccount()"
      @cancel="showDeleteModal = false"
    />

    <!-- Password Confirmation Dialog -->
    <Dialog :open="showPasswordDialog" @update:open="handlePasswordDialogClose">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle class="text-red-600">
            {{ t('Confirm Deletion') }}
          </DialogTitle>
          <DialogDescription>
            {{ t('Please enter your password to confirm account deletion.') }}
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="confirmDeleteAccount" class="space-y-4 py-4">
          <div class="space-y-2">
            <Label for="delete_password">{{ t('Password') }}</Label>
            <Input
              id="delete_password"
              v-model="deleteAccountForm.password"
              type="password"
              required
              autocomplete="current-password"
              :placeholder="t('Enter your password')"
              :disabled="deleteAccountForm.processing"
            />
            <InputError :message="deleteAccountForm.errors.password" />
          </div>
          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              @click="handlePasswordDialogClose(false)"
              :disabled="deleteAccountForm.processing"
            >
              {{ t('Cancel') }}
            </Button>
            <Button
              type="submit"
              variant="destructive"
              :disabled="deleteAccountForm.processing"
            >
              <Icon v-if="deleteAccountForm.processing" name="loader-2" class="w-4 h-4 mr-2 animate-spin" />
              {{ deleteAccountForm.processing ? t('Deleting...') : t('Delete Account') }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
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
import ConfirmationDialog from '@/components/ConfirmationDialog.vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
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
  oauth_connected?: {
    google: boolean
    microsoft: boolean
  }
  requires_password_for_deletion?: boolean
}

const props = defineProps<Props>()
const page = usePage()
const { t } = useI18n()

// Breadcrumbs
const breadcrumbs = computed<BreadcrumbItemType[]>(() => [
  {
    title: t('Panel'),
    href: '/panel',
  },
  {
    title: t('Profile Settings'),
    href: '/panel/profile',
  },
])

const showDeleteModal = ref(false)
const showPasswordDialog = ref(false)
// Check if we should open account tab (e.g., after OAuth linking)
const initialTab = page.props.flash?.active_tab === 'account' ? 'account' : 'profile'
const activeTab = ref<'profile' | 'password' | 'account'>(initialTab as 'profile' | 'password' | 'account')

// Computed property for high vessel access
const hasHighVesselAccess = computed(() => props.hasHighVesselAccess ?? false)

// OAuth connection status
const oauthConnected = computed(() => props.oauth_connected ?? { google: false, microsoft: false })

// Check if password is required for deletion
const requiresPasswordForDeletion = computed(() => props.requires_password_for_deletion ?? true)

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

// Delete account form
const deleteAccountForm = useForm({
  password: '',
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

const handlePasswordDialogClose = (open: boolean) => {
  showPasswordDialog.value = open
  if (!open) {
    // Reset form when closing
    deleteAccountForm.reset()
    deleteAccountForm.clearErrors()
    // Also close the confirmation dialog
    showDeleteModal.value = false
  }
}

const confirmDeleteAccount = () => {
  // For OAuth users, no password needed - delete directly
  // For regular users, password is already validated in the form
  if (requiresPasswordForDeletion.value) {
    // Regular user - password is in the form
    deleteAccountForm.delete('/panel/profile', {
      onSuccess: () => {
        // User will be redirected to login
        showPasswordDialog.value = false
        showDeleteModal.value = false
        deleteAccountForm.reset()
      },
      onError: (errors) => {
        console.error('Delete account errors:', errors)
        // Keep dialog open to show errors
      }
    })
  } else {
    // OAuth user - no password needed, delete directly
    deleteAccountForm.delete('/panel/profile', {
      onSuccess: () => {
        // User will be redirected to login
        showDeleteModal.value = false
        deleteAccountForm.reset()
      },
      onError: (errors) => {
        console.error('Delete account errors:', errors)
        showDeleteModal.value = false
      }
    })
  }
}

const connectOAuth = (provider: 'google' | 'microsoft') => {
  // Redirect to OAuth with source=link to indicate account linking
  window.location.href = `/auth/${provider}?source=link`
}

const disconnectOAuth = (provider: 'google' | 'microsoft') => {
  if (confirm(t('Are you sure you want to disconnect your') + ' ' + provider + ' ' + t('account?'))) {
    router.post(`/panel/profile/oauth/${provider}/disconnect`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        // Page will reload with updated OAuth status from the redirect
      }
    })
  }
}
</script>
