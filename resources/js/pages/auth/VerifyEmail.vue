<script setup lang="ts">
import EmailVerificationNotificationController from '@/actions/App/Http/Controllers/Auth/EmailVerificationNotificationController';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const { t } = useI18n();
</script>

<template>
    <AuthLayout
        :title="t('Verify email')"
        :description="t('Please verify your email address by clicking on the link we just emailed to you.')"
    >
        <Head :title="t('Email verification')" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ t('A new verification link has been sent to the email address you provided during registration.') }}
        </div>

        <Form
            v-bind="EmailVerificationNotificationController.store.form()"
            class="space-y-6 text-center"
            v-slot="{ processing }"
        >
            <Button :disabled="processing" variant="secondary">
                <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                {{ t('Resend verification email') }}
            </Button>

            <TextLink
                href="/logout"
                as="button"
                class="mx-auto block text-sm"
            >
                {{ t('Log out') }}
            </TextLink>
        </Form>
    </AuthLayout>
</template>
