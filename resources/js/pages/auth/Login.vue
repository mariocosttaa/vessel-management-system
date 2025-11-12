<script setup lang="ts">
import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const { t } = useI18n();
const page = usePage();

const errorMessage = computed(() => page.props.flash?.error);
const showSignupModal = computed(() => page.props.flash?.show_signup_modal === true);
const oauthProvider = computed(() => page.props.flash?.oauth_provider || 'google');
const oauthEmail = computed(() => page.props.flash?.oauth_email || '');
const oauthName = computed(() => page.props.flash?.oauth_name || '');

const isModalOpen = ref(false);

// Watch for signup modal flag and open modal
watch(showSignupModal, (newValue) => {
    if (newValue) {
        isModalOpen.value = true;
    }
}, { immediate: true });

const handleGoogleLogin = () => {
    window.location.href = '/auth/google?source=login';
};

const handleSignupWithOAuth = () => {
    window.location.href = `/auth/${oauthProvider.value}?source=register`;
};

const closeModal = () => {
    isModalOpen.value = false;
    // Clear the flash data by visiting the login page again
    router.visit('/login', { only: [] });
};
</script>

<template>
    <AuthBase
        :title="t('Log in to your account')"
        :description="t('Enter your email and password below to log in')"
    >
        <Head :title="t('Log in')" />

        <div
            v-if="status"
            class="mb-4 rounded-lg border border-primary/20 bg-primary/10 px-4 py-3 text-center text-sm font-medium text-primary dark:bg-primary/20 dark:text-primary"
        >
            {{ status }}
        </div>

        <div
            v-if="errorMessage"
            class="mb-4 rounded-lg border border-destructive/20 bg-destructive/10 px-4 py-3 text-center text-sm font-medium text-destructive dark:bg-destructive/20 dark:text-destructive"
        >
            {{ errorMessage }}
        </div>

        <Form
            v-bind="AuthenticatedSessionController.store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">{{ t('Email address') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        :placeholder="t('Email address')"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">{{ t('Password') }}</Label>
                        <TextLink
                            v-if="canResetPassword"
                            href="/password/reset"
                            class="text-sm"
                            :tabindex="5"
                        >
                            {{ t('Forgot password?') }}
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        :placeholder="t('Password')"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>{{ t('Remember me') }}</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <LoaderCircle
                        v-if="processing"
                        class="h-4 w-4 animate-spin"
                    />
                    {{ t('Log in') }}
                </Button>
            </div>

            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <span class="w-full border-t border-border" />
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-background px-2 text-muted-foreground">
                        {{ t('Or continue with') }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <Button
                    type="button"
                    variant="outline"
                    class="w-full"
                    @click="handleGoogleLogin"
                >
                    <svg
                        class="mr-2 h-4 w-4"
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
                    {{ t('Google') }}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    class="w-full"
                    disabled
                    title="Microsoft login will be available soon"
                >
                    <svg
                        class="mr-2 h-4 w-4 opacity-50"
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
                    {{ t('Microsoft') }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                {{ t("Don't have an account?") }}
                <TextLink href="/register" :tabindex="5">{{ t('Sign up') }}</TextLink>
            </div>
        </Form>

        <!-- Signup Modal -->
        <Dialog :open="isModalOpen" @update:open="closeModal">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('Account Not Found') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('You don\'t have an account yet. Would you like to sign up with') }} {{ oauthProvider === 'google' ? 'Google' : 'Microsoft' }}?
                    </DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <div class="space-y-3">
                        <p class="text-sm text-muted-foreground">
                            <strong>{{ oauthName || oauthEmail }}</strong>
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ t('Click the button below to create your account using your') }} {{ oauthProvider === 'google' ? 'Google' : 'Microsoft' }} {{ t('account.') }}
                        </p>
                    </div>
                </div>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="closeModal"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        @click="handleSignupWithOAuth"
                    >
                        <svg
                            v-if="oauthProvider === 'google'"
                            class="mr-2 h-4 w-4"
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
                        {{ t('Sign up with') }} {{ oauthProvider === 'google' ? 'Google' : 'Microsoft' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthBase>
</template>
