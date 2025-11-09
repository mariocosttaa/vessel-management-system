<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Icon from '@/components/Icon.vue';
import axios from 'axios';

interface Props {
    open: boolean;
    vesselId: string | number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'saved': [];
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
const loadingNextNumber = ref(false);
const nextMareaNumber = ref<string>('');

const form = useForm({
    marea_number: '' as string,
    estimated_departure_date: '' as string,
    estimated_return_date: '' as string,
});

// Load next marea number when modal opens
const loadNextMareaNumber = async () => {
    if (!props.open) return;

    loadingNextNumber.value = true;
    try {
        const response = await axios.get(`/panel/${vesselId.value}/mareas/create`);
        nextMareaNumber.value = response.data.next_marea_number;
        // Auto-fill the form with the next number
        form.marea_number = nextMareaNumber.value;
    } catch (error) {
        console.error('Failed to load next marea number:', error);
        // Fallback: try to generate a number on the client side
        const year = new Date().getFullYear();
        form.marea_number = `MARE${year}000001`;
    } finally {
        loadingNextNumber.value = false;
    }
};

// Watch for modal open to load next number
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        loadNextMareaNumber();
    } else {
        // Reset form when modal closes
        form.reset();
        form.clearErrors();
    }
});

// Get today's date in YYYY-MM-DD format
const getTodayDate = () => {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const handleSave = () => {
    form.post(`/panel/${vesselId.value}/mareas`, {
        onSuccess: () => {
            emit('saved');
            emit('update:open', false);
            form.reset();
        },
        onError: () => {
            // Errors are handled by the form
        },
    });
};

const handleClose = () => {
    emit('update:open', false);
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>Create New Marea</DialogTitle>
                <DialogDescription>
                    Create a new expedition/trip for your vessel
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
                <form @submit.prevent="handleSave" class="space-y-6">
                    <!-- Marea Number -->
                    <div>
                        <Label for="marea_number" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            Marea Number <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="marea_number"
                            v-model="form.marea_number"
                            type="text"
                            placeholder="MARE2025000001"
                            required
                            :disabled="loadingNextNumber || form.processing"
                            :class="{ 'border-destructive dark:border-destructive': form.errors.marea_number }"
                        />
                        <InputError :message="form.errors.marea_number" class="mt-1" />
                        <p v-if="loadingNextNumber" class="mt-1 text-xs text-muted-foreground dark:text-muted-foreground">
                            Loading next marea number...
                        </p>
                        <p v-else-if="nextMareaNumber && form.marea_number === nextMareaNumber" class="mt-1 text-xs text-muted-foreground dark:text-muted-foreground">
                            This number was auto-generated. You can change it if needed.
                        </p>
                    </div>

                    <!-- Estimated Departure Date -->
                    <div>
                        <Label for="estimated_departure_date" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            Estimated Departure Date
                        </Label>
                        <Input
                            id="estimated_departure_date"
                            v-model="form.estimated_departure_date"
                            type="date"
                            :min="getTodayDate()"
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
                            :min="form.estimated_departure_date || getTodayDate()"
                            :class="{ 'border-destructive dark:border-destructive': form.errors.estimated_return_date }"
                        />
                        <InputError :message="form.errors.estimated_return_date" class="mt-1" />
                        <p class="mt-1 text-xs text-muted-foreground dark:text-muted-foreground">
                            Must be after or equal to departure date
                        </p>
                    </div>
                </form>
            </div>

            <div class="flex items-center justify-end space-x-4">
                <Button
                    variant="outline"
                    @click="handleClose"
                    :disabled="form.processing || loadingNextNumber"
                >
                    Cancel
                </Button>
                <Button
                    @click="handleSave"
                    :disabled="form.processing || loadingNextNumber || !form.marea_number"
                >
                    <Icon v-if="form.processing" name="loader" class="w-4 h-4 mr-2 animate-spin" />
                    <Icon v-else name="plus" class="w-4 h-4 mr-2" />
                    {{ form.processing ? 'Creating...' : 'Create Marea' }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

