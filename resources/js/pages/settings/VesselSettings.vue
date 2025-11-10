<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import VesselLogoUpload from '@/components/VesselLogoUpload.vue';
import { useNotifications } from '@/composables/useNotifications';
import { useI18n } from '@/composables/useI18n';
import settings from '@/routes/panel/settings';
import { Settings, Globe } from 'lucide-vue-next';

interface Country {
    code: string;
    name: string;
}

interface Currency {
    code: string;
    name: string;
    symbol: string;
}

interface VatProfile {
    id: number;
    name: string;
    percentage: number;
    country?: {
        id: number;
        code: string;
        name: string;
    } | null;
}

interface VesselSetting {
    id: number;
    vessel_id: number;
    country_code: string | null;
    currency_code: string | null;
    vat_profile_id: number | null;
    country?: Country | null;
    currency?: Currency | null;
    vat_profile?: VatProfile | null;
}

interface Vessel {
    id: number;
    name: string;
    registration_number: string;
    vessel_type: string;
    capacity: number | null;
    year_built: number | null;
    status: string;
    notes: string | null;
    logo: string | null;
    logo_url: string | null;
    country_code: string | null;
    currency_code: string | null;
}

interface Props {
    vessel: Vessel;
    setting: VesselSetting;
    countries: Country[];
    currencies: Currency[];
    vatProfiles: VatProfile[];
    vesselTypes: string[];
    statuses: string[];
}

const props = defineProps<Props>();
const { addNotification } = useNotifications();
const { t } = useI18n();
const page = usePage();

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

// Get the actual setting data (Inertia might wrap resources)
const settingData = computed(() => {
    return props.setting?.data || props.setting;
});

// Get the actual vessel data (Inertia might wrap resources)
const vesselData = computed(() => {
    return props.vessel?.data || props.vessel;
});

// Active tab
const activeTab = ref<'general' | 'location'>('general');

// Logo state
const logoFile = ref<File | null>(null)
const removeLogo = ref(false)

// General form (vessel information)
const generalForm = useForm({
    name: vesselData.value?.name || '',
    registration_number: vesselData.value?.registration_number || '',
    vessel_type: vesselData.value?.vessel_type || '',
    capacity: vesselData.value?.capacity || null,
    year_built: vesselData.value?.year_built || null,
    status: vesselData.value?.status || 'active',
    notes: vesselData.value?.notes || null,
    logo: null as File | null,
    remove_logo: false,
});

// Location form (country, currency, VAT)
const locationForm = useForm({
    country_code: settingData.value?.country_code || null,
    currency_code: settingData.value?.currency_code || null,
    vat_profile_id: settingData.value?.vat_profile_id ? Number(settingData.value.vat_profile_id) : null,
});

// Watch for prop changes and update form values
watch(() => vesselData.value, (newVessel) => {
    if (newVessel) {
        // Ensure all required fields are set from vessel data
        generalForm.name = newVessel.name || '';
        generalForm.registration_number = newVessel.registration_number || '';
        generalForm.vessel_type = newVessel.vessel_type || '';
        generalForm.capacity = newVessel.capacity ?? null;
        generalForm.year_built = newVessel.year_built ?? null;
        generalForm.status = newVessel.status || 'active';
        generalForm.notes = newVessel.notes ?? null;
    }
}, { immediate: true, deep: true });

watch(() => settingData.value, (newSetting) => {
    if (newSetting) {
        locationForm.country_code = newSetting.country_code || null;
        locationForm.currency_code = newSetting.currency_code || null;
        locationForm.vat_profile_id = newSetting.vat_profile_id ? Number(newSetting.vat_profile_id) : null;
    }
}, { immediate: true, deep: true });

// Convert data to Select component format
const countryOptions = computed(() => {
    return props.countries.map(country => ({
        value: country.code,
        label: country.name
    }));
});

const currencyOptions = computed(() => {
    return props.currencies.map(currency => ({
        value: currency.code,
        label: `${currency.name} (${currency.symbol})`
    }));
});

const vatProfileOptions = computed(() => {
    return props.vatProfiles.map(profile => ({
        value: profile.id,
        label: `${profile.name}${profile.country ? ` (${profile.country.name})` : ''} - ${profile.percentage}%`
    }));
});

const vesselTypeOptions = computed(() => {
    return props.vesselTypes.map(type => ({
        value: type,
        label: type.charAt(0).toUpperCase() + type.slice(1)
    }));
});

const statusOptions = computed(() => {
    return props.statuses.map(status => ({
        value: status,
        label: status.charAt(0).toUpperCase() + status.slice(1)
    }));
});

const submitGeneral = () => {
    // Always ensure form has latest values from v-model bindings
    // The form object should have the current values, but we'll use vesselData as ultimate fallback
    const formData: Record<string, any> = {
        name: String(generalForm.name || vesselData.value?.name || ''),
        registration_number: String(generalForm.registration_number || vesselData.value?.registration_number || ''),
        vessel_type: String(generalForm.vessel_type || vesselData.value?.vessel_type || ''),
        capacity: generalForm.capacity !== null && generalForm.capacity !== undefined ? Number(generalForm.capacity) : (vesselData.value?.capacity ?? null),
        year_built: generalForm.year_built !== null && generalForm.year_built !== undefined ? Number(generalForm.year_built) : (vesselData.value?.year_built ?? null),
        status: String(generalForm.status || vesselData.value?.status || 'active'),
        notes: generalForm.notes !== null && generalForm.notes !== undefined ? String(generalForm.notes) : (vesselData.value?.notes ?? null),
    }

    // Add logo fields if needed
    if (logoFile.value) {
        formData.logo = logoFile.value
        formData.remove_logo = false
    } else if (removeLogo.value) {
        formData.remove_logo = true
    }

    // Log data before submission for debugging
    console.log('=== FORM SUBMISSION DEBUG ===')
    console.log('Form values from generalForm:', {
        name: generalForm.name,
        registration_number: generalForm.registration_number,
        vessel_type: generalForm.vessel_type,
        status: generalForm.status,
    })
    console.log('VesselData values:', {
        name: vesselData.value?.name,
        registration_number: vesselData.value?.registration_number,
        vessel_type: vesselData.value?.vessel_type,
        status: vesselData.value?.status,
    })
    console.log('Final formData being sent:', formData)
    console.log('Has logo file:', !!logoFile.value)
    console.log('Remove logo:', removeLogo.value)

    const submitUrl = settings.update.general.url({ vessel: getCurrentVesselId() })

    // ALWAYS use POST when files are present (required for multipart/form-data)
    // Laravel will handle method spoofing via _method field
    if (logoFile.value || removeLogo.value) {
        // Create a new form instance with ALL data explicitly set
        // This ensures FormData includes all fields
        const uploadForm = useForm({
            _method: 'PATCH',
            name: formData.name,
            registration_number: formData.registration_number,
            vessel_type: formData.vessel_type,
            capacity: formData.capacity,
            year_built: formData.year_built,
            status: formData.status,
            notes: formData.notes,
            ...(logoFile.value ? { logo: logoFile.value } : {}),
            remove_logo: formData.remove_logo || false,
        })

        console.log('Using POST method with FormData')
        console.log('Upload form data keys:', Object.keys(uploadForm.data()))

        uploadForm.post(submitUrl, {
            preserveScroll: true,
            onSuccess: (page) => {
                logoFile.value = null
                // If logo was removed, keep removeLogo false since it's now removed on server
                // The vessel data will be refreshed by Inertia, so logoUrl will be null
                removeLogo.value = false

                // Update vessel data from the response if available
                if (page.props.vessel) {
                    const updatedVessel = page.props.vessel?.data || page.props.vessel
                    if (updatedVessel) {
                        // Vessel data will be automatically updated by Inertia
                        // The displayLogoUrl computed will reflect the new state
                    }
                }

                addNotification({
                    type: 'success',
                    title: t('Success'),
                    message: t('Vessel information has been updated successfully.'),
                });
            },
            onError: (errors) => {
                console.error('=== FORM SUBMISSION ERROR ===')
                console.error('Errors:', errors)
                console.error('Form data that was sent:', uploadForm.data())
                addNotification({
                    type: 'error',
                    title: t('Error'),
                    message: t('Failed to update vessel information. Please try again.'),
                });
            },
        })
    } else {
        // Use regular PATCH when no files
        console.log('Using PATCH method (no files)')
        generalForm.patch(submitUrl, {
            preserveScroll: true,
            onSuccess: () => {
                addNotification({
                    type: 'success',
                    title: t('Success'),
                    message: t('Vessel information has been updated successfully.'),
                });
            },
            onError: (errors) => {
                console.error('Form submission errors:', errors);
                addNotification({
                    type: 'error',
                    title: t('Error'),
                    message: t('Failed to update vessel information. Please try again.'),
                });
            },
        })
    }
};

const handleLogoChange = (file: File | null) => {
    logoFile.value = file
    if (file) {
        removeLogo.value = false
    }
}

const handleLogoRemove = () => {
    // Set flag to remove logo - this will hide it immediately in the UI
    removeLogo.value = true
    logoFile.value = null
    // The logo will be permanently removed when the form is submitted
}

// Computed logo URL that hides the logo when removal is pending
// This provides immediate visual feedback when user confirms removal
const displayLogoUrl = computed(() => {
    // If removeLogo is true, hide the logo immediately
    if (removeLogo.value) {
        return null
    }
    // Otherwise, show the logo from vessel data
    return vesselData.value?.logo_url || null
})

// Computed to check if logo removal is pending
const isLogoRemovalPending = computed(() => {
    // Removal is pending if removeLogo is true and there was originally a logo
    return removeLogo.value && !!vesselData.value?.logo_url
})

const submitLocation = () => {
    locationForm.patch(settings.update.location.url({ vessel: getCurrentVesselId() }), {
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Vessel location settings have been updated successfully.'),
            });
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to update vessel location settings. Please try again.'),
            });
        },
    });
};
</script>

<template>
    <Head :title="t('Vessel Settings')" />

    <VesselLayout :breadcrumbs="[{ title: t('Settings'), href: settings.edit.url({ vessel: getCurrentVesselId() }) }]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">{{ t('Vessel Settings') }}</h1>
                    <p class="text-muted-foreground dark:text-muted-foreground mt-1">
                        {{ t('Manage vessel information and configuration settings') }}
                    </p>
                </div>

                <!-- Tabs -->
                <div class="mb-6 border-b border-border dark:border-border">
                    <div class="flex gap-1">
                        <button
                            @click="activeTab = 'general'"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px',
                                activeTab === 'general'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-card-foreground hover:border-border'
                            ]"
                        >
                            <Settings class="h-4 w-4" />
                            {{ t('General') }}
                        </button>
                        <button
                            @click="activeTab = 'location'"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px',
                                activeTab === 'location'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-card-foreground hover:border-border'
                            ]"
                        >
                            <Globe class="h-4 w-4" />
                            {{ t('Location & Currency') }}
                        </button>
                    </div>
                </div>

                <!-- General Tab -->
                <div v-show="activeTab === 'general'" class="space-y-6">
                    <form @submit.prevent="submitGeneral" class="space-y-6">
                        <!-- Vessel Name -->
                        <div class="space-y-2">
                            <Label for="name">{{ t('Vessel Name') }}</Label>
                            <Input
                                id="name"
                                v-model="generalForm.name"
                                type="text"
                                :error="!!generalForm.errors.name"
                                class="w-full"
                                required
                            />
                            <InputError :message="generalForm.errors.name" />
                        </div>

                        <!-- Registration Number -->
                        <div class="space-y-2">
                            <Label for="registration_number">{{ t('Registration Number') }}</Label>
                            <Input
                                id="registration_number"
                                v-model="generalForm.registration_number"
                                type="text"
                                :error="!!generalForm.errors.registration_number"
                                class="w-full"
                                required
                            />
                            <InputError :message="generalForm.errors.registration_number" />
                        </div>

                        <!-- Vessel Logo -->
                        <VesselLogoUpload
                            :logo-url="displayLogoUrl"
                            :is-removal-pending="isLogoRemovalPending"
                            v-model="logoFile"
                            :error="generalForm.errors.logo"
                            @remove="handleLogoRemove"
                            @update:model-value="handleLogoChange"
                        />

                        <!-- Vessel Type -->
                        <div class="space-y-2">
                            <Label for="vessel_type">{{ t('Vessel Type') }}</Label>
                            <Select
                                id="vessel_type"
                                v-model="generalForm.vessel_type"
                                :options="vesselTypeOptions"
                                :placeholder="t('Select a vessel type')"
                                :error="!!generalForm.errors.vessel_type"
                                class="w-full"
                            />
                            <InputError :message="generalForm.errors.vessel_type" />
                        </div>

                        <!-- Capacity and Year Built -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="capacity">{{ t('Capacity') }}</Label>
                                <Input
                                    id="capacity"
                                    v-model.number="generalForm.capacity"
                                    type="number"
                                    min="0"
                                    :error="!!generalForm.errors.capacity"
                                    class="w-full"
                                />
                                <InputError :message="generalForm.errors.capacity" />
                            </div>

                            <div class="space-y-2">
                                <Label for="year_built">{{ t('Year Built') }}</Label>
                                <Input
                                    id="year_built"
                                    v-model.number="generalForm.year_built"
                                    type="number"
                                    min="1800"
                                    :max="new Date().getFullYear() + 1"
                                    :error="!!generalForm.errors.year_built"
                                    class="w-full"
                                />
                                <InputError :message="generalForm.errors.year_built" />
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <Label for="status">{{ t('Status') }}</Label>
                            <Select
                                id="status"
                                v-model="generalForm.status"
                                :options="statusOptions"
                                :placeholder="t('Select a status')"
                                :error="!!generalForm.errors.status"
                                class="w-full"
                            />
                            <InputError :message="generalForm.errors.status" />
                        </div>

                        <!-- Notes -->
                        <div class="space-y-2">
                            <Label for="notes">{{ t('Notes') }}</Label>
                            <textarea
                                id="notes"
                                v-model="generalForm.notes"
                                rows="4"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-background dark:border-input"
                                :class="generalForm.errors.notes ? 'border-destructive' : ''"
                            ></textarea>
                            <InputError :message="generalForm.errors.notes" />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-4 pt-4 border-t">
                            <Button
                                type="button"
                                variant="outline"
                                @click="router.visit('/panel/' + getCurrentVesselId() + '/dashboard')"
                            >
                                {{ t('Cancel') }}
                            </Button>
                            <Button
                                type="submit"
                                :disabled="generalForm.processing"
                            >
                                {{ generalForm.processing ? t('Saving...') : t('Save Changes') }}
                            </Button>
                        </div>
                    </form>
                </div>

                <!-- Location & Currency Tab -->
                <div v-show="activeTab === 'location'" class="space-y-6">
                    <form @submit.prevent="submitLocation" class="space-y-6">
                        <!-- Default Country -->
                        <div class="space-y-2">
                            <Label for="country_code">{{ t('Default Country') }}</Label>
                            <Select
                                id="country_code"
                                v-model="locationForm.country_code"
                                :options="countryOptions"
                                :placeholder="t('Select a country')"
                                :searchable="true"
                                :error="!!locationForm.errors.country_code"
                                class="w-full"
                            />
                            <InputError :message="locationForm.errors.country_code" />
                            <p class="text-xs text-muted-foreground">
                                {{ t('This country will be used as the default for new transactions and other vessel operations.') }}
                            </p>
                        </div>

                        <!-- Default Currency -->
                        <div class="space-y-2">
                            <Label for="currency_code">{{ t('Default Currency') }}</Label>
                            <Select
                                id="currency_code"
                                v-model="locationForm.currency_code"
                                :options="currencyOptions"
                                :placeholder="t('Select a currency')"
                                :searchable="true"
                                :error="!!locationForm.errors.currency_code"
                                class="w-full"
                            />
                            <InputError :message="locationForm.errors.currency_code" />
                            <p class="text-xs text-muted-foreground">
                                {{ t('This currency will be used as the default for new transactions when no specific currency is selected.') }}
                            </p>
                        </div>

                        <!-- Default VAT Profile -->
                        <div class="space-y-2">
                            <Label for="vat_profile_id">{{ t('Default VAT Profile') }}</Label>
                            <Select
                                id="vat_profile_id"
                                v-model="locationForm.vat_profile_id"
                                :options="vatProfileOptions"
                                :placeholder="t('Select a VAT profile')"
                                :searchable="true"
                                :error="!!locationForm.errors.vat_profile_id"
                                class="w-full"
                            />
                            <InputError :message="locationForm.errors.vat_profile_id" />
                            <p class="text-xs text-muted-foreground">
                                {{ t('This VAT profile will be used as the default for new income transactions (Add Transaction). Expenses (Remove Transaction) do not use VAT.') }}
                            </p>
                        </div>

                        <!-- Current Settings Display -->
                        <div v-if="settingData?.country || settingData?.currency || settingData?.vat_profile" class="rounded-lg border p-4 bg-muted/50 space-y-3">
                            <h3 class="font-semibold text-sm">{{ t('Current Settings') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div v-if="settingData?.country">
                                    <p class="text-muted-foreground">{{ t('Country') }}:</p>
                                    <p class="font-medium">{{ settingData.country.name }}</p>
                                </div>
                                <div v-if="settingData?.currency">
                                    <p class="text-muted-foreground">{{ t('Currency') }}:</p>
                                    <p class="font-medium">{{ settingData.currency.name }} ({{ settingData.currency.symbol }})</p>
                                </div>
                                <div v-if="settingData?.vat_profile">
                                    <p class="text-muted-foreground">{{ t('VAT Profile') }}:</p>
                                    <p class="font-medium">{{ settingData.vat_profile.name }} - {{ settingData.vat_profile.percentage }}%</p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-4 pt-4 border-t">
                            <Button
                                type="button"
                                variant="outline"
                                @click="router.visit('/panel/' + getCurrentVesselId() + '/dashboard')"
                            >
                                {{ t('Cancel') }}
                            </Button>
                            <Button
                                type="submit"
                                :disabled="locationForm.processing"
                            >
                                {{ locationForm.processing ? t('Saving...') : t('Save Changes') }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </VesselLayout>
</template>
