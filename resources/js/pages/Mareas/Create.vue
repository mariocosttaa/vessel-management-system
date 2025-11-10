<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DateInput } from '@/components/ui/date-input';
import { Select } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import mareas from '@/routes/panel/mareas';

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

interface DistributionProfile {
    id: number;
    name: string;
    description: string | null;
    is_default: boolean;
}

interface Props {
    distributionProfiles: DistributionProfile[];
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    description: '',
    estimated_departure_date: '',
    estimated_return_date: '',
    distribution_profile_id: null as number | null,
});

// Get default distribution profile
const defaultProfile = computed(() => {
    return props.distributionProfiles.find(p => p.is_default) || null;
});

// Convert to Select component options format
const distributionProfileOptions = computed(() => {
    const options = [{ value: null, label: 'No Distribution Profile' }];
    props.distributionProfiles.forEach(profile => {
        const label = profile.is_default ? `${profile.name} (Default)` : profile.name;
        options.push({ value: profile.id, label });
    });
    return options;
});

// Set default profile on mount
if (defaultProfile.value) {
    form.distribution_profile_id = defaultProfile.value.id;
}

const handleSubmit = () => {
    form.post(mareas.store.url({ vessel: getCurrentVesselId() }), {
        onSuccess: () => {
            router.visit(mareas.index.url({ vessel: getCurrentVesselId() }));
        },
    });
};

const handleCancel = () => {
    router.visit(mareas.index.url({ vessel: getCurrentVesselId() }));
};
</script>

<template>
    <Head title="Create Marea" />

    <VesselLayout :breadcrumbs="[
        { title: 'Mareas', href: mareas.index.url({ vessel: getCurrentVesselId() }) },
        { title: 'Create Marea', href: mareas.create.url({ vessel: getCurrentVesselId() }) }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="max-w-3xl mx-auto w-full">
                <!-- Header Card -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6 mb-6">
                    <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground mb-2">
                        Create New Marea
                    </h1>
                    <p class="text-muted-foreground dark:text-muted-foreground">
                        Create a new expedition/trip for your vessel
                    </p>
                </div>

                <!-- Form Card -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <Label for="name" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                Name (Optional)
                            </Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter marea name (e.g., 'Summer Fishing Trip')"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.name }"
                            />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>

                        <!-- Description -->
                        <div>
                            <Label for="description" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                Description (Optional)
                            </Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                placeholder="Enter description or notes about this marea"
                                class="flex min-h-[80px] w-full rounded-md border border-input dark:border-input bg-background dark:bg-background px-3 py-2 text-sm text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.description }"
                            ></textarea>
                            <InputError :message="form.errors.description" class="mt-1" />
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Estimated Departure Date -->
                            <div>
                                <Label for="estimated_departure_date" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    Estimated Departure Date
                                </Label>
                                <DateInput
                                    id="estimated_departure_date"
                                    v-model="form.estimated_departure_date"
                                    :class="{ 'border-destructive dark:border-destructive': form.errors.estimated_departure_date }"
                                />
                                <InputError :message="form.errors.estimated_departure_date" class="mt-1" />
                            </div>

                            <!-- Estimated Return Date -->
                            <div>
                                <Label for="estimated_return_date" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    Estimated Return Date
                                </Label>
                                <DateInput
                                    id="estimated_return_date"
                                    v-model="form.estimated_return_date"
                                    :class="{ 'border-destructive dark:border-destructive': form.errors.estimated_return_date }"
                                />
                                <InputError :message="form.errors.estimated_return_date" class="mt-1" />
                            </div>
                        </div>

                        <!-- Distribution Profile -->
                        <div>
                            <Label for="distribution_profile_id" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                Distribution Profile (Optional)
                            </Label>
                            <Select
                                id="distribution_profile_id"
                                v-model="form.distribution_profile_id"
                                :options="distributionProfileOptions"
                                placeholder="No Distribution Profile"
                                searchable
                                :error="!!form.errors.distribution_profile_id"
                            />
                            <InputError :message="form.errors.distribution_profile_id" class="mt-1" />
                            <p v-if="distributionProfiles.length === 0" class="mt-1 text-xs text-muted-foreground dark:text-muted-foreground">
                                No distribution profiles available. You can create one later in settings.
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-6 border-t border-border dark:border-border">
                            <button
                                type="button"
                                @click="handleCancel"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-muted-foreground hover:text-card-foreground transition-colors"
                            >
                                <Icon name="arrow-left" class="w-4 h-4 mr-2" />
                                Cancel
                            </button>

                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2"
                            >
                                <Icon
                                    v-if="form.processing"
                                    name="loader-circle"
                                    class="w-4 h-4 mr-2 animate-spin"
                                />
                                <Icon
                                    v-else
                                    name="plus"
                                    class="w-4 h-4 mr-2"
                                />
                                {{ form.processing ? 'Creating...' : 'Create Marea' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </VesselLayout>
</template>

