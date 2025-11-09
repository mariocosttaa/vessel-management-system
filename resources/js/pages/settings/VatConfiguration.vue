<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { edit } from '@/routes/vat-configuration';
import { useNotifications } from '@/composables/useNotifications';

interface VatProfile {
    id: number;
    name: string;
    percentage: number;
    code: string | null;
    country: {
        id: number;
        name: string;
        code: string;
    } | null;
    description: string | null;
    is_default: boolean;
}

interface Props {
    vatProfiles: VatProfile[];
    defaultVatProfileId: number | null;
    defaultVatProfile: VatProfile | null;
}

const props = defineProps<Props>();
const { addNotification } = useNotifications();

const form = useForm({
    default_vat_profile_id: props.defaultVatProfileId as number | null,
});

const submit = () => {
    form.patch(edit().url, {
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: 'Success',
                message: 'VAT configuration updated successfully.',
            });
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
            addNotification({
                type: 'error',
                title: 'Error',
                message: 'Failed to update VAT configuration. Please try again.',
            });
        },
    });
};

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'VAT Configuration',
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="VAT Configuration" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="VAT Configuration"
                    description="Configure the default VAT profile for transactions"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="default_vat_profile_id">Default VAT Profile</Label>
                        <select
                            id="default_vat_profile_id"
                            v-model="form.default_vat_profile_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :class="{ 'border-destructive dark:border-destructive': form.errors.default_vat_profile_id }"
                        >
                            <option :value="null">No default VAT profile</option>
                            <option
                                v-for="profile in vatProfiles"
                                :key="profile.id"
                                :value="profile.id"
                            >
                                {{ profile.name }}
                                <span v-if="profile.country">({{ profile.country.name }})</span>
                                - {{ profile.percentage }}%
                            </option>
                        </select>
                        <InputError :message="form.errors.default_vat_profile_id" />
                        <p class="text-sm text-muted-foreground">
                            This VAT profile will be used as the default for new transactions when no specific profile is selected.
                        </p>
                    </div>

                    <div v-if="defaultVatProfile" class="rounded-lg border p-4 bg-muted/50">
                        <h3 class="font-semibold mb-2">Current Default VAT Profile</h3>
                        <p class="text-sm">
                            <strong>{{ defaultVatProfile.name }}</strong>
                            <span v-if="defaultVatProfile.country"> ({{ defaultVatProfile.country.name }})</span>
                            - {{ defaultVatProfile.percentage }}%
                        </p>
                        <p v-if="defaultVatProfile.description" class="text-sm text-muted-foreground mt-1">
                            {{ defaultVatProfile.description }}
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

