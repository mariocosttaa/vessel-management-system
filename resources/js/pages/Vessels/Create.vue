<template>
  <IndexDefaultLayout :breadcrumbs="breadcrumbs">
    <!-- Main Content -->
    <main class="flex-1 p-6">
      <div class="max-w-2xl mx-auto">
        <!-- Simple Header -->
        <div class="text-center mb-8">
          <h1 class="text-4xl font-bold text-card-foreground dark:text-card-foreground mb-4">
            Create New Vessel
          </h1>
          <p class="text-lg text-muted-foreground dark:text-muted-foreground">
            Add a new vessel to your fleet
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
              <Label for="name">Vessel Name *</Label>
              <Input
                id="name"
                name="name"
                type="text"
                required
                autofocus
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
                placeholder="Enter registration number"
                :class="{ 'border-destructive': errors.registration_number }"
              />
              <InputError :message="errors.registration_number" />
            </div>

            <!-- Vessel Type -->
            <div class="space-y-2">
              <Label for="vessel_type">Vessel Type *</Label>
              <select
                id="vessel_type"
                name="vessel_type"
                required
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                :class="{ 'border-destructive': errors.vessel_type }"
              >
                <option value="">Select vessel type</option>
                <option v-for="(label, value) in vesselTypes" :key="value" :value="value">
                  {{ label }}
                </option>
              </select>
              <InputError :message="errors.vessel_type" />
            </div>

            <!-- Status -->
            <div class="space-y-2">
              <Label for="status">Status *</Label>
              <select
                id="status"
                name="status"
                required
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                :class="{ 'border-destructive': errors.status }"
              >
                <option value="">Select status</option>
                <option v-for="(label, value) in statuses" :key="value" :value="value">
                  {{ label }}
                </option>
              </select>
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
                placeholder="Enter year built"
                :class="{ 'border-destructive': errors.year_built }"
              />
              <InputError :message="errors.year_built" />
            </div>

            <!-- Country -->
            <div class="space-y-2">
              <Label for="country_code">Country</Label>
              <select
                id="country_code"
                name="country_code"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                :class="{ 'border-destructive': errors.country_code }"
              >
                <option value="">Select country</option>
                <option v-for="country in countries" :key="country.code" :value="country.code">
                  {{ country.name }}
                </option>
              </select>
              <InputError :message="errors.country_code" />
            </div>

            <!-- Currency -->
            <div class="space-y-2">
              <Label for="currency_code">Currency</Label>
              <select
                id="currency_code"
                name="currency_code"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                :class="{ 'border-destructive': errors.currency_code }"
              >
                <option value="">Select currency</option>
                <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                  {{ currency.name }} ({{ currency.symbol }})
                </option>
              </select>
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
                  name="plus"
                  class="w-4 h-4 mr-2"
                />
                {{ processing ? 'Creating...' : 'Create Vessel' }}
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
import { ArrowLeft, Plus, LoaderCircle } from 'lucide-vue-next'
import Icon from '@/Components/Icon.vue'
import InputError from '@/Components/InputError.vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import IndexDefaultLayout from '@/layouts/IndexDefault/IndexDefaultLayout.vue'
import type { BreadcrumbItemType } from '@/types'

interface Props {
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
    title: 'Create New Vessel',
    href: '/panel/vessel/create',
  },
]

const goBack = () => {
  router.visit('/panel')
}
</script>
