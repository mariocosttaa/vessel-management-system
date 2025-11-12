<script setup lang="ts">
import { computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { edit } from '@/routes/vat-configuration';
import { useNotifications } from '@/composables/useNotifications';
import { useI18n } from '@/composables/useI18n';

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
const { t } = useI18n();

const form = useForm({
    default_vat_profile_id: props.defaultVatProfileId as number | null,
});

// Convert to Select component options format
const vatProfileOptions = computed(() => {
    const options = [{ value: null, label: t('No default VAT profile') }];
    props.vatProfiles.forEach(profile => {
        const countryPart = profile.country ? ` (${profile.country.name})` : '';
        const defaultPart = profile.is_default ? ` (${t('Default')})` : '';
        const label = `${profile.name}${countryPart} - ${profile.percentage}%${defaultPart}`;
        options.push({ value: profile.id, label });
    });
    return options;
});

const submit = () => {
    form.patch(edit().url, {
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('VAT configuration updated successfully.'),
            });
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to update VAT configuration. Please try again.'),
            });
        },
    });
};

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('VAT Configuration'),
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('VAT Configuration')" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    :title="t('VAT Configuration')"
                    :description="t('Configure the default VAT profile for transactions')"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="default_vat_profile_id">{{ t('Default VAT Profile') }}</Label>
                        <Select
                            id="default_vat_profile_id"
                            v-model="form.default_vat_profile_id"
                            :options="vatProfileOptions"
                            :placeholder="t('No default VAT profile')"
                            searchable
                            :error="!!form.errors.default_vat_profile_id"
                        />
                        <InputError :message="form.errors.default_vat_profile_id" />
                        <p class="text-sm text-muted-foreground">
                            {{ t('This VAT profile will be used as the default for new transactions when no specific profile is selected.') }}
                        </p>
                    </div>

                    <div v-if="defaultVatProfile" class="rounded-lg border p-4 bg-muted/50">
                        <h3 class="font-semibold mb-2">{{ t('Current Default VAT Profile') }}</h3>
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
                            {{ form.processing ? t('Saving...') : t('Save Changes') }}
                        </Button>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

