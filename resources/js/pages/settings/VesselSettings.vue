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
import { useNotifications } from '@/composables/useNotifications';
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

// General form (vessel information)
const generalForm = useForm({
    name: vesselData.value?.name || '',
    registration_number: vesselData.value?.registration_number || '',
    vessel_type: vesselData.value?.vessel_type || '',
    capacity: vesselData.value?.capacity || null,
    year_built: vesselData.value?.year_built || null,
    status: vesselData.value?.status || 'active',
    notes: vesselData.value?.notes || null,
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
        generalForm.name = newVessel.name || '';
        generalForm.registration_number = newVessel.registration_number || '';
        generalForm.vessel_type = newVessel.vessel_type || '';
        generalForm.capacity = newVessel.capacity || null;
        generalForm.year_built = newVessel.year_built || null;
        generalForm.status = newVessel.status || 'active';
        generalForm.notes = newVessel.notes || null;
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
    generalForm.patch(settings.update.general.url({ vessel: getCurrentVesselId() }), {
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: 'Success',
                message: 'Vessel information has been updated successfully.',
            });
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
            addNotification({
                type: 'error',
                title: 'Error',
                message: 'Failed to update vessel information. Please try again.',
            });
        },
    });
};

const submitLocation = () => {
    locationForm.patch(settings.update.location.url({ vessel: getCurrentVesselId() }), {
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: 'Success',
                message: 'Vessel location settings have been updated successfully.',
            });
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
            addNotification({
                type: 'error',
                title: 'Error',
                message: 'Failed to update vessel location settings. Please try again.',
            });
        },
    });
};
</script>

<template>
    <Head title="Vessel Settings" />

    <VesselLayout :breadcrumbs="[{ title: 'Settings', href: settings.edit.url({ vessel: getCurrentVesselId() }) }]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">Vessel Settings</h1>
                    <p class="text-muted-foreground dark:text-muted-foreground mt-1">
                        Manage vessel information and configuration settings
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
                            General
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
                            Location & Currency
                        </button>
                    </div>
                </div>

                <!-- General Tab -->
                <div v-show="activeTab === 'general'" class="space-y-6">
                    <form @submit.prevent="submitGeneral" class="space-y-6">
                        <!-- Vessel Name -->
                        <div class="space-y-2">
                            <Label for="name">Vessel Name</Label>
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
                            <Label for="registration_number">Registration Number</Label>
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

                        <!-- Vessel Type -->
                        <div class="space-y-2">
                            <Label for="vessel_type">Vessel Type</Label>
                            <Select
                                id="vessel_type"
                                v-model="generalForm.vessel_type"
                                :options="vesselTypeOptions"
                                placeholder="Select a vessel type"
                                :error="!!generalForm.errors.vessel_type"
                                class="w-full"
                            />
                            <InputError :message="generalForm.errors.vessel_type" />
                        </div>

                        <!-- Capacity and Year Built -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="capacity">Capacity</Label>
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
                                <Label for="year_built">Year Built</Label>
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
                            <Label for="status">Status</Label>
                            <Select
                                id="status"
                                v-model="generalForm.status"
                                :options="statusOptions"
                                placeholder="Select a status"
                                :error="!!generalForm.errors.status"
                                class="w-full"
                            />
                            <InputError :message="generalForm.errors.status" />
                        </div>

                        <!-- Notes -->
                        <div class="space-y-2">
                            <Label for="notes">Notes</Label>
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
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                :disabled="generalForm.processing"
                            >
                                {{ generalForm.processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
                        </div>
                    </form>
                </div>

                <!-- Location & Currency Tab -->
                <div v-show="activeTab === 'location'" class="space-y-6">
                    <form @submit.prevent="submitLocation" class="space-y-6">
                        <!-- Default Country -->
                        <div class="space-y-2">
                            <Label for="country_code">Default Country</Label>
                            <Select
                                id="country_code"
                                v-model="locationForm.country_code"
                                :options="countryOptions"
                                placeholder="Select a country"
                                :searchable="true"
                                :error="!!locationForm.errors.country_code"
                                class="w-full"
                            />
                            <InputError :message="locationForm.errors.country_code" />
                            <p class="text-xs text-muted-foreground">
                                This country will be used as the default for new transactions and other vessel operations.
                            </p>
                        </div>

                        <!-- Default Currency -->
                        <div class="space-y-2">
                            <Label for="currency_code">Default Currency</Label>
                            <Select
                                id="currency_code"
                                v-model="locationForm.currency_code"
                                :options="currencyOptions"
                                placeholder="Select a currency"
                                :searchable="true"
                                :error="!!locationForm.errors.currency_code"
                                class="w-full"
                            />
                            <InputError :message="locationForm.errors.currency_code" />
                            <p class="text-xs text-muted-foreground">
                                This currency will be used as the default for new transactions when no specific currency is selected.
                            </p>
                        </div>

                        <!-- Default VAT Profile -->
                        <div class="space-y-2">
                            <Label for="vat_profile_id">Default VAT Profile</Label>
                            <Select
                                id="vat_profile_id"
                                v-model="locationForm.vat_profile_id"
                                :options="vatProfileOptions"
                                placeholder="Select a VAT profile"
                                :searchable="true"
                                :error="!!locationForm.errors.vat_profile_id"
                                class="w-full"
                            />
                            <InputError :message="locationForm.errors.vat_profile_id" />
                            <p class="text-xs text-muted-foreground">
                                This VAT profile will be used as the default for new income transactions (Add Transaction). Expenses (Remove Transaction) do not use VAT.
                            </p>
                        </div>

                        <!-- Current Settings Display -->
                        <div v-if="settingData?.country || settingData?.currency || settingData?.vat_profile" class="rounded-lg border p-4 bg-muted/50 space-y-3">
                            <h3 class="font-semibold text-sm">Current Settings</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div v-if="settingData?.country">
                                    <p class="text-muted-foreground">Country:</p>
                                    <p class="font-medium">{{ settingData.country.name }}</p>
                                </div>
                                <div v-if="settingData?.currency">
                                    <p class="text-muted-foreground">Currency:</p>
                                    <p class="font-medium">{{ settingData.currency.name }} ({{ settingData.currency.symbol }})</p>
                                </div>
                                <div v-if="settingData?.vat_profile">
                                    <p class="text-muted-foreground">VAT Profile:</p>
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
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                :disabled="locationForm.processing"
                            >
                                {{ locationForm.processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </VesselLayout>
</template>
