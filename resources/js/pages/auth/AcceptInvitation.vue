<script setup lang="ts">
import { Form, Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { useI18n } from '@/composables/useI18n';
import { LoaderCircle } from 'lucide-vue-next';

const props = defineProps<{
    token: string;
    user: {
        name: string;
        first_name: string;
        surname: string;
        email: string;
    };
    vessel: {
        id: number;
        name: string;
    } | null;
    roleName: string | null;
}>();

const { t } = useI18n();

const form = useForm({
    first_name: props.user.first_name || '',
    surname: props.user.surname || '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(`/invitation/${props.token}`, {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};
</script>

<template>
    <AuthLayout
        :title="t('Accept Invitation')"
        :description="t('Set your password to accept the invitation and join the vessel')"
    >
        <Head :title="t('Accept Invitation')" />

        <div class="space-y-6">
            <!-- Invitation Details -->
            <div class="bg-muted/50 border border-border rounded-lg p-4 space-y-2">
                <div class="text-sm font-medium text-card-foreground">
                    {{ t('You have been invited to join') }}
                </div>
                <div v-if="vessel" class="text-lg font-semibold text-card-foreground">
                    {{ vessel.name }}
                </div>
                <div v-if="roleName" class="text-sm text-muted-foreground">
                    {{ t('Role') }}: {{ roleName }}
                </div>
                <div class="text-sm text-muted-foreground">
                    {{ t('Email') }}: {{ user.email }}
                </div>
            </div>

            <Form @submit.prevent="submit" v-slot="{ processing }">
                <div class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="first_name">{{ t('First Name') }}</Label>
                        <Input
                            id="first_name"
                            type="text"
                            name="first_name"
                            v-model="form.first_name"
                            autocomplete="given-name"
                            class="mt-1 block w-full"
                            autofocus
                            :placeholder="t('First Name')"
                            :class="{ 'border-destructive': form.errors.first_name }"
                        />
                        <InputError :message="form.errors.first_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="surname">{{ t('Surname') }}</Label>
                        <Input
                            id="surname"
                            type="text"
                            name="surname"
                            v-model="form.surname"
                            autocomplete="family-name"
                            class="mt-1 block w-full"
                            :placeholder="t('Surname')"
                            :class="{ 'border-destructive': form.errors.surname }"
                        />
                        <InputError :message="form.errors.surname" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">{{ t('Password') }}</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            v-model="form.password"
                            autocomplete="new-password"
                            class="mt-1 block w-full"
                            :placeholder="t('Password')"
                            :class="{ 'border-destructive': form.errors.password }"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">
                            {{ t('Confirm Password') }}
                        </Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            v-model="form.password_confirmation"
                            autocomplete="new-password"
                            class="mt-1 block w-full"
                            :placeholder="t('Confirm password')"
                            :class="{ 'border-destructive': form.errors.password_confirmation }"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <Button
                        type="submit"
                        class="mt-4 w-full"
                        :disabled="processing || form.processing"
                    >
                        <LoaderCircle
                            v-if="processing || form.processing"
                            class="h-4 w-4 animate-spin mr-2"
                        />
                        {{ t('Accept Invitation') }}
                    </Button>
                </div>
            </Form>
        </div>
    </AuthLayout>
</template>

