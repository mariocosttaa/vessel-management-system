<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

interface Marea {
    id: number;
    marea_number: string;
    name: string | null;
    description: string | null;
    estimated_departure_date: string | null;
    estimated_return_date: string | null;
    distribution_profile_id: number | null;
    status: string;
}

interface Props {
    marea: Marea;
    distributionProfiles: DistributionProfile[];
}

const props = defineProps<Props>();

const form = useForm({
    name: props.marea.name || '',
    description: props.marea.description || '',
    estimated_departure_date: props.marea.estimated_departure_date || '',
    estimated_return_date: props.marea.estimated_return_date || '',
    distribution_profile_id: props.marea.distribution_profile_id,
});

const handleSubmit = () => {
    // Ensure distribution_profile_id is properly converted (empty string to null)
    const distributionProfileId = form.distribution_profile_id === '' || form.distribution_profile_id === null || form.distribution_profile_id === undefined
        ? null
        : Number(form.distribution_profile_id);

    form.put(mareas.update.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }), {
        data: {
            ...form.data(),
            distribution_profile_id: distributionProfileId,
        },
        onSuccess: () => {
            router.visit(mareas.show.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }));
        },
    });
};

const handleCancel = () => {
    router.visit(mareas.show.url({ vessel: getCurrentVesselId(), mareaId: props.marea.id }));
};
</script>

<template>
    <Head :title="`Edit Marea ${marea.marea_number}`" />

    <VesselLayout :breadcrumbs="[
        { title: 'Mareas', href: mareas.index.url({ vessel: getCurrentVesselId() }) },
        { title: marea.marea_number, href: mareas.show.url({ vessel: getCurrentVesselId(), mareaId: marea.id }) },
        { title: 'Edit', href: mareas.edit.url({ vessel: getCurrentVesselId(), mareaId: marea.id }) }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="max-w-3xl mx-auto w-full">
                <!-- Header Card -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6 mb-6">
                    <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground mb-2">
                        Edit Marea: {{ marea.marea_number }}
                    </h1>
                    <p class="text-muted-foreground dark:text-muted-foreground">
                        Update marea information
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
                                <Input
                                    id="estimated_departure_date"
                                    v-model="form.estimated_departure_date"
                                    type="date"
                                    :class="{ 'border-destructive dark:border-destructive': form.errors.estimated_departure_date }"
                                />
                                <InputError :message="form.errors.estimated_departure_date" class="mt-1" />
                            </div>

                            <!-- Estimated Return Date -->
                            <div>
                                <Label for="estimated_return_date" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    Estimated Return Date
                                </Label>
                                <Input
                                    id="estimated_return_date"
                                    v-model="form.estimated_return_date"
                                    type="date"
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
                            <select
                                id="distribution_profile_id"
                                v-model="form.distribution_profile_id"
                                class="flex h-10 w-full rounded-md border border-input dark:border-input bg-background dark:bg-background px-3 py-2 text-sm text-foreground dark:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.distribution_profile_id }"
                            >
                                <option :value="null">No Distribution Profile</option>
                                <option
                                    v-for="profile in distributionProfiles"
                                    :key="profile.id"
                                    :value="profile.id"
                                >
                                    {{ profile.name }}
                                    <span v-if="profile.is_default"> (Default)</span>
                                </option>
                            </select>
                            <InputError :message="form.errors.distribution_profile_id" class="mt-1" />
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
                                    name="save"
                                    class="w-4 h-4 mr-2"
                                />
                                {{ form.processing ? 'Updating...' : 'Update Marea' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </VesselLayout>
</template>

