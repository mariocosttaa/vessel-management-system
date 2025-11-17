<template>
  <IndexDefaultLayout :breadcrumbs="breadcrumbs">
    <!-- Main Content -->
    <main class="flex-1 p-6">
      <div class="max-w-2xl mx-auto">
        <!-- Simple Header -->
        <div class="text-center mb-8">
          <h1 class="text-4xl font-bold text-card-foreground dark:text-card-foreground mb-4">
            {{ t('Create New Vessel') }}
          </h1>
          <p class="text-lg text-muted-foreground dark:text-muted-foreground">
            {{ t('Add a new vessel to your fleet') }}
          </p>
        </div>

        <!-- Form Card -->
        <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
        <Form
          action="/panel/vessel"
          method="post"
          v-slot="{ errors, processing }"
          class="space-y-6"
        >
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Vessel Name -->
            <div class="space-y-2">
              <Label for="name">{{ t('Vessel Name') }} <span class="text-destructive">*</span></Label>
              <Input
                id="name"
                name="name"
                type="text"
                required
                autofocus
                :placeholder="t('Enter vessel name')"
                :class="{ 'border-destructive': errors.name }"
              />
              <InputError :message="errors.name" />
            </div>

            <!-- Registration Number -->
            <div class="space-y-2">
              <Label for="registration_number">{{ t('Registration Number') }} <span class="text-destructive">*</span></Label>
              <Input
                id="registration_number"
                name="registration_number"
                type="text"
                required
                :placeholder="t('Enter registration number')"
                :class="{ 'border-destructive': errors.registration_number }"
              />
              <InputError :message="errors.registration_number" />
            </div>

            <!-- Vessel Type -->
            <div class="space-y-2">
              <Label for="vessel_type">{{ t('Vessel Type') }} <span class="text-destructive">*</span></Label>
              <Select
                id="vessel_type"
                name="vessel_type"
                v-model="vesselType"
                :options="vesselTypeOptions"
                :placeholder="t('Select vessel type')"
                :error="!!errors.vessel_type"
              />
              <InputError :message="errors.vessel_type" />
            </div>

            <!-- Status -->
            <div class="space-y-2">
              <Label for="status">{{ t('Status') }} <span class="text-destructive">*</span></Label>
              <Select
                id="status"
                name="status"
                v-model="status"
                :options="statusOptions"
                :placeholder="t('Select status')"
                :error="!!errors.status"
              />
              <InputError :message="errors.status" />
            </div>
          </div>

          <!-- Additional Fields -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Capacity -->
            <div class="space-y-2">
              <Label for="capacity">{{ t('Capacity') }}</Label>
              <Input
                id="capacity"
                name="capacity"
                type="number"
                min="1"
                :placeholder="t('Enter capacity')"
                :class="{ 'border-destructive': errors.capacity }"
              />
              <InputError :message="errors.capacity" />
            </div>

            <!-- Year Built -->
            <div class="space-y-2">
              <Label for="year_built">{{ t('Year Built') }}</Label>
              <Input
                id="year_built"
                name="year_built"
                type="number"
                :min="1900"
                :max="new Date().getFullYear()"
                :placeholder="t('Enter year built')"
                :class="{ 'border-destructive': errors.year_built }"
              />
              <InputError :message="errors.year_built" />
            </div>

            <!-- Country -->
            <div class="space-y-2">
              <Label for="country_code">{{ t('Country') }} <span class="text-destructive">*</span></Label>
              <Select
                id="country_code"
                name="country_code"
                v-model="countryCode"
                :options="countryOptions"
                :placeholder="t('Select country')"
                searchable
                :error="!!errors.country_code"
              />
              <InputError :message="errors.country_code" />
            </div>

            <!-- Currency -->
            <div class="space-y-2">
              <Label for="currency_code">{{ t('Currency') }} <span class="text-destructive">*</span></Label>
              <Select
                id="currency_code"
                name="currency_code"
                v-model="currencyCode"
                :options="currencyOptions"
                :placeholder="t('Select currency')"
                searchable
                :error="!!errors.currency_code"
              />
              <InputError :message="errors.currency_code" />
            </div>
          </div>

          <!-- VAT Profile -->
          <div class="space-y-2">
            <Label for="vat_profile_id">{{ t('VAT Profile') }} <span class="text-destructive">*</span></Label>
            <Select
              id="vat_profile_id"
              name="vat_profile_id"
              v-model="vatProfileId"
              :options="vatProfileOptions"
              :placeholder="t('Select a VAT profile')"
              searchable
              :error="!!errors.vat_profile_id"
            />
            <InputError :message="errors.vat_profile_id" />
          </div>

          <!-- Notes -->
          <div class="space-y-2">
            <Label for="notes">{{ t('Notes') }}</Label>
            <textarea
              id="notes"
              name="notes"
              rows="3"
              :placeholder="t('Enter additional notes')"
              class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              :class="{ 'border-destructive': errors.notes }"
            ></textarea>
            <InputError :message="errors.notes" />
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between pt-6">
            <button
              type="button"
              @click="goBack"
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-muted-foreground hover:text-card-foreground transition-colors"
            >
              <Icon name="arrow-left" class="w-4 h-4 mr-2" />
              {{ t('Back to Vessels') }}
            </button>

            <div class="flex items-center space-x-3">
              <button
                type="button"
                @click="goBack"
                class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-card-foreground transition-colors"
              >
                {{ t('Cancel') }}
              </button>
              <Button
                type="submit"
                :disabled="processing"
                class="px-6 py-2"
              >
                <Icon
                  v-if="processing"
                  name="loader-circle"
                  class="w-4 h-4 mr-2 animate-spin"
                />
                <Icon
                  v-else
                  name="plus"
                  class="w-4 h-4 mr-2"
                />
                {{ processing ? t('Creating...') : t('Create Vessel') }}
              </Button>
            </div>
          </div>
        </Form>
        </div>
      </div>
    </main>
  </IndexDefaultLayout>
</template>

<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Icon from '@/components/Icon.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select } from '@/components/ui/select'
import { Label } from '@/components/ui/label'
import IndexDefaultLayout from '@/layouts/IndexDefault/IndexDefaultLayout.vue'
import type { BreadcrumbItemType } from '@/types'
import { useI18n } from '@/composables/useI18n'

interface VatProfile {
  id: number
  name: string
  percentage: number
  country: {
    id: number
    name: string
    code: string
  } | null
}

interface Props {
  vesselTypes: Record<string, string>
  statuses: Record<string, string>
  countries: Array<{ code: string; name: string }>
  currencies: Array<{ code: string; name: string; symbol: string }>
  vatProfiles: VatProfile[]
}

const props = defineProps<Props>()
const { t } = useI18n()

// Form values for Select components
const vesselType = ref('')
const status = ref('')
const countryCode = ref('')
const currencyCode = ref('')
const vatProfileId = ref<number | null>(null)

// Convert to Select component options format
const vesselTypeOptions = computed(() => {
    const options = [{ value: '', label: t('Select vessel type') }];
    Object.entries(props.vesselTypes).forEach(([value, label]) => {
        options.push({ value, label: label as string });
    });
    return options;
});

const statusOptions = computed(() => {
    const options = [{ value: '', label: t('Select status') }];
    Object.entries(props.statuses).forEach(([value, label]) => {
        options.push({ value, label: label as string });
    });
    return options;
});

const countryOptions = computed(() => {
    const options = [{ value: '', label: t('Select country') }];
    props.countries.forEach(country => {
        options.push({ value: country.code, label: country.name });
    });
    return options;
});

const currencyOptions = computed(() => {
    const options = [{ value: '', label: t('Select currency') }];
    props.currencies.forEach(currency => {
        options.push({ value: currency.code, label: `${currency.name} (${currency.symbol})` });
    });
    return options;
});

const vatProfileOptions = computed(() => {
    const options = [{ value: null, label: t('Select a VAT profile') }];
    props.vatProfiles.forEach(profile => {
        const countryPart = profile.country ? ` (${profile.country.name})` : '';
        const label = `${profile.name}${countryPart} - ${profile.percentage}%`;
        options.push({ value: profile.id, label });
    });
    return options;
});

// Breadcrumbs
const breadcrumbs = computed<BreadcrumbItemType[]>(() => [
  {
    title: t('Vessels'),
    href: '/panel',
  },
  {
    title: t('Create New Vessel'),
    href: '/panel/vessel/create',
  },
])

const goBack = () => {
  router.visit('/panel')
}
</script>
