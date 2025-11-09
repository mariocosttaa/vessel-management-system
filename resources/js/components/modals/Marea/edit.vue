<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Icon from '@/components/Icon.vue';

interface Marea {
    id: number;
    marea_number: string;
    name: string | null;
    description: string | null;
    estimated_departure_date: string | null;
    estimated_return_date: string | null;
    status: string;
}

interface Props {
    open: boolean;
    marea: Marea;
    vesselId: string | number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'success': [];
    'close': [];
}>();

// Get current vessel ID from URL if not provided
const getCurrentVesselId = () => {
    if (props.vesselId) {
        return props.vesselId.toString();
    }
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

const vesselId = computed(() => getCurrentVesselId());

const form = useForm({
    name: props.marea.name || '',
    description: props.marea.description || '',
    estimated_departure_date: props.marea.estimated_departure_date || '',
    estimated_return_date: props.marea.estimated_return_date || '',
});

// Watch for marea changes to update form
watch(() => props.marea, (newMarea) => {
    if (newMarea) {
        form.name = newMarea.name || '';
        form.description = newMarea.description || '';
        form.estimated_departure_date = newMarea.estimated_departure_date || '';
        form.estimated_return_date = newMarea.estimated_return_date || '';
    }
}, { immediate: true });

// Watch for modal open to reset form
watch(() => props.open, (isOpen) => {
    if (isOpen && props.marea) {
        form.name = props.marea.name || '';
        form.description = props.marea.description || '';
        form.estimated_departure_date = props.marea.estimated_departure_date || '';
        form.estimated_return_date = props.marea.estimated_return_date || '';
        form.clearErrors();
    }
});

const handleSubmit = () => {
    form.put(`/panel/${vesselId.value}/mareas/${props.marea.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            emit('update:open', false);
            form.clearErrors();
        },
        onError: () => {
            // Errors are handled by the form
        },
    });
};

const handleClose = () => {
    emit('update:open', false);
    emit('close');
    form.clearErrors();
};

// Get today's date in YYYY-MM-DD format
const getTodayDate = () => {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Edit Marea: {{ marea.marea_number }}</DialogTitle>
                <DialogDescription>
                    Update marea information
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
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
                            :disabled="form.processing"
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
                            :disabled="form.processing"
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
                                :disabled="form.processing"
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
                                :min="form.estimated_departure_date || getTodayDate()"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.estimated_return_date }"
                                :disabled="form.processing"
                            />
                            <InputError :message="form.errors.estimated_return_date" class="mt-1" />
                            <p class="mt-1 text-xs text-muted-foreground dark:text-muted-foreground">
                                Must be after or equal to departure date
                            </p>
                        </div>
                    </div>
                </form>
            </div>

            <DialogFooter>
                <Button
                    variant="outline"
                    @click="handleClose"
                    :disabled="form.processing"
                >
                    Cancel
                </Button>
                <Button
                    @click="handleSubmit"
                    :disabled="form.processing"
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
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

