<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Icon from '@/components/Icon.vue';
import { useI18n } from '@/composables/useI18n';
import vessels from '@/routes/vessels';

interface Props {
    open: boolean;
    vesselTypes: Record<string, string>;
    statuses: Record<string, string>;
    countries: Array<{ code: string; name: string }>;
    currencies: Array<{ code: string; name: string; symbol: string }>;
}

const props = defineProps<Props>();
const { t } = useI18n();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'saved': [];
}>();

const form = useForm({
    name: '',
    registration_number: '',
    vessel_type: '',
    capacity: null as number | null,
    year_built: null as number | null,
    status: 'active',
    notes: '',
    country_code: '',
    currency_code: '',
});

const handleSave = () => {
    form.post(vessels.store.url(), {
        onSuccess: () => {
            emit('saved');
            emit('update:open', false);
            form.reset();
            form.status = 'active';
        },
    });
};

const handleClose = () => {
    emit('update:open', false);
    form.reset();
    form.status = 'active';
    form.clearErrors();
};

// Convert to Select component options format
const vesselTypeOptions = computed(() => {
    const options = [{ value: '', label: t('Select vessel type') }];
    Object.entries(props.vesselTypes).forEach(([value, label]) => {
        options.push({ value, label: label as string });
    });
    return options;
});

const statusOptions = computed(() => {
    const options: Array<{ value: string; label: string }> = [];
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
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ t('Create New Vessel') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Add a new vessel to your fleet management system') }}
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
                <form @submit.prevent="handleSave" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div class="md:col-span-2">
                            <Label for="name" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Vessel Name') }} <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                :placeholder="t('Enter vessel name')"
                                required
                                :class="{ 'border-destructive dark:border-destructive': form.errors.name }"
                            />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>

                        <!-- Registration Number -->
                        <div class="md:col-span-2">
                            <Label for="registration_number" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Registration Number') }} <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="registration_number"
                                v-model="form.registration_number"
                                type="text"
                                :placeholder="t('Enter registration number')"
                                required
                                :class="{ 'border-destructive dark:border-destructive': form.errors.registration_number }"
                            />
                            <InputError :message="form.errors.registration_number" class="mt-1" />
                        </div>

                        <!-- Vessel Type -->
                        <div>
                            <Label for="vessel_type" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Vessel Type') }} <span class="text-destructive">*</span>
                            </Label>
                            <Select
                                id="vessel_type"
                                v-model="form.vessel_type"
                                :options="vesselTypeOptions"
                                :placeholder="t('Select vessel type')"
                                :error="!!form.errors.vessel_type"
                            />
                            <InputError :message="form.errors.vessel_type" class="mt-1" />
                        </div>

                        <!-- Status -->
                        <div>
                            <Label for="status" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Status') }} <span class="text-destructive">*</span>
                            </Label>
                            <Select
                                id="status"
                                v-model="form.status"
                                :options="statusOptions"
                                :error="!!form.errors.status"
                            />
                            <InputError :message="form.errors.status" class="mt-1" />
                        </div>

                        <!-- Capacity -->
                        <div>
                            <Label for="capacity" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Capacity') }}
                            </Label>
                            <Input
                                id="capacity"
                                v-model.number="form.capacity"
                                type="number"
                                min="1"
                                :placeholder="t('Enter capacity')"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.capacity }"
                            />
                            <InputError :message="form.errors.capacity" class="mt-1" />
                        </div>

                        <!-- Year Built -->
                        <div>
                            <Label for="year_built" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Year Built') }}
                            </Label>
                            <Input
                                id="year_built"
                                v-model.number="form.year_built"
                                type="number"
                                :min="1900"
                                :max="new Date().getFullYear()"
                                :placeholder="t('Enter year built')"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.year_built }"
                            />
                            <InputError :message="form.errors.year_built" class="mt-1" />
                        </div>

                        <!-- Country -->
                        <div>
                            <Label for="country_code" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Country') }}
                            </Label>
                            <Select
                                id="country_code"
                                v-model="form.country_code"
                                :options="countryOptions"
                                :placeholder="t('Select country')"
                                searchable
                                :error="!!form.errors.country_code"
                            />
                            <InputError :message="form.errors.country_code" class="mt-1" />
                        </div>

                        <!-- Currency -->
                        <div>
                            <Label for="currency_code" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Currency') }}
                            </Label>
                            <Select
                                id="currency_code"
                                v-model="form.currency_code"
                                :options="currencyOptions"
                                :placeholder="t('Select currency')"
                                searchable
                                :error="!!form.errors.currency_code"
                            />
                            <InputError :message="form.errors.currency_code" class="mt-1" />
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-2">
                            <Label for="notes" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Notes') }}
                            </Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                :placeholder="t('Enter additional notes')"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.notes }"
                            ></textarea>
                            <InputError :message="form.errors.notes" class="mt-1" />
                        </div>
                    </div>
                </form>
            </div>

            <div class="flex items-center justify-end space-x-4">
                <Button
                    variant="outline"
                    @click="handleClose"
                    :disabled="form.processing"
                >
                    {{ t('Cancel') }}
                </Button>
                <Button
                    @click="handleSave"
                    :disabled="form.processing"
                >
                    <Icon v-if="form.processing" name="loader" class="w-4 h-4 mr-2 animate-spin" />
                    {{ t('Save Vessel') }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
