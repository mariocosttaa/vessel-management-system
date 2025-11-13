<template>
  <IndexDefaultLayout :breadcrumbs="breadcrumbs">
    <!-- Main Content -->
    <main class="flex-1 p-6">
      <div class="max-w-2xl mx-auto">
        <!-- Simple Header -->
        <div class="text-center mb-8">
          <h1 class="text-4xl font-bold text-card-foreground dark:text-card-foreground mb-4">
            Edit Vessel
          </h1>
          <p class="text-lg text-muted-foreground dark:text-muted-foreground">
            Update vessel information
          </p>
        </div>

        <!-- Form Card -->
        <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
        <Form
          :action="`/panel/vessel/${vessel.data?.id}`"
          method="put"
          v-slot="{ errors, processing }"
          class="space-y-6"
        >
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Vessel Name -->
            <div class="space-y-2">
              <Label for="name">Vessel Name *</Label>
              <Input
                id="name"
                name="name"
                type="text"
                required
                autofocus
                v-model="formData.name"
                placeholder="Enter vessel name"
                :class="{ 'border-destructive': errors.name }"
              />
              <InputError :message="errors.name" />
            </div>

            <!-- Registration Number -->
            <div class="space-y-2">
              <Label for="registration_number">Registration Number *</Label>
              <Input
                id="registration_number"
                name="registration_number"
                type="text"
                required
                v-model="formData.registration_number"
                placeholder="Enter registration number"
                :class="{ 'border-destructive': errors.registration_number }"
              />
              <InputError :message="errors.registration_number" />
            </div>

            <!-- Vessel Type -->
            <div class="space-y-2">
              <Label for="vessel_type">Vessel Type *</Label>
              <Select
                v-model="formData.vessel_type"
                name="vessel_type"
                :options="Object.entries(vesselTypes).map(([value, label]) => ({ value, label }))"
                placeholder="Select vessel type"
                :error="!!errors.vessel_type"
                class="w-full"
              />
              <InputError :message="errors.vessel_type" />
            </div>

            <!-- Status -->
            <div class="space-y-2">
              <Label for="status">Status *</Label>
              <Select
                v-model="formData.status"
                name="status"
                :options="Object.entries(statuses).map(([value, label]) => ({ value, label }))"
                placeholder="Select status"
                :error="!!errors.status"
                class="w-full"
              />
              <InputError :message="errors.status" />
            </div>
          </div>

          <!-- Additional Fields -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Capacity -->
            <div class="space-y-2">
              <Label for="capacity">Capacity</Label>
              <Input
                id="capacity"
                name="capacity"
                type="number"
                min="1"
                v-model="formData.capacity"
                placeholder="Enter capacity"
                :class="{ 'border-destructive': errors.capacity }"
              />
              <InputError :message="errors.capacity" />
            </div>

            <!-- Year Built -->
            <div class="space-y-2">
              <Label for="year_built">Year Built</Label>
              <Input
                id="year_built"
                name="year_built"
                type="number"
                :min="1900"
                :max="new Date().getFullYear()"
                v-model="formData.year_built"
                placeholder="Enter year built"
                :class="{ 'border-destructive': errors.year_built }"
              />
              <InputError :message="errors.year_built" />
            </div>

            <!-- Country -->
            <div class="space-y-2">
              <Label for="country_code">Country</Label>
              <Select
                v-model="formData.country_code"
                name="country_code"
                :options="countries.map(country => ({ value: country.code, label: country.name }))"
                placeholder="Select country"
                :searchable="true"
                :error="!!errors.country_code"
                class="w-full"
              />
              <InputError :message="errors.country_code" />
            </div>

            <!-- Currency -->
            <div class="space-y-2">
              <Label for="currency_code">Currency</Label>
              <Select
                v-model="formData.currency_code"
                name="currency_code"
                :options="currencies.map(currency => ({ value: currency.code, label: `${currency.name} (${currency.symbol})` }))"
                placeholder="Select currency"
                :searchable="true"
                :error="!!errors.currency_code"
                class="w-full"
              />
              <InputError :message="errors.currency_code" />
            </div>
          </div>

          <!-- Notes -->
          <div class="space-y-2">
            <Label for="notes">Notes</Label>
            <textarea
              id="notes"
              name="notes"
              rows="3"
              v-model="formData.notes"
              placeholder="Enter additional notes"
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
              Back to Vessels
            </button>

            <div class="flex items-center space-x-3">
              <button
                type="button"
                @click="goBack"
                class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-card-foreground transition-colors"
              >
                Cancel
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
                  name="save"
                  class="w-4 h-4 mr-2"
                />
                {{ processing ? 'Updating...' : 'Update Vessel' }}
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
import { ArrowLeft, Save, LoaderCircle } from 'lucide-vue-next'
import Icon from '@/components/Icon.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select } from '@/components/ui/select'
import { ref, watch } from 'vue'
import IndexDefaultLayout from '@/layouts/IndexDefault/IndexDefaultLayout.vue'
import type { BreadcrumbItemType } from '@/types'

interface Vessel {
  id: number
  name: string
  registration_number: string
  vessel_type: string
  status: string
  capacity?: number
  year_built?: number
  notes?: string
  country_code?: string
  currency_code?: string
}

interface Props {
  vessel: Vessel
  vesselTypes: Record<string, string>
  statuses: Record<string, string>
  countries: Array<{ code: string; name: string }>
  currencies: Array<{ code: string; name: string; symbol: string }>
}

const props = defineProps<Props>()

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
  {
    title: 'Vessels',
    href: '/panel',
  },
  {
    title: 'Edit Vessel',
    href: `/panel/vessel/${props.vessel.data?.id}/edit`,
  },
]

// Reactive form data
const formData = ref({
  name: props.vessel.data?.name || '',
  registration_number: props.vessel.data?.registration_number || '',
  vessel_type: props.vessel.data?.vessel_type || '',
  status: props.vessel.data?.status || '',
  capacity: props.vessel.data?.capacity || '',
  year_built: props.vessel.data?.year_built || '',
  notes: props.vessel.data?.notes || '',
  country_code: props.vessel.data?.country_code || '',
  currency_code: props.vessel.data?.currency_code || '',
})

// Watch for prop changes
watch(() => props.vessel, (newVessel) => {
  if (newVessel?.data) {
    formData.value = {
      name: newVessel.data.name || '',
      registration_number: newVessel.data.registration_number || '',
      vessel_type: newVessel.data.vessel_type || '',
      status: newVessel.data.status || '',
      capacity: newVessel.data.capacity || '',
      year_built: newVessel.data.year_built || '',
      notes: newVessel.data.notes || '',
      country_code: newVessel.data.country_code || '',
      currency_code: newVessel.data.currency_code || '',
    }
  }
}, { immediate: true })

const goBack = () => {
  router.visit('/panel')
}
</script>
