<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DateInput } from '@/components/ui/date-input';
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
const nextMaintenanceNumber = ref<string>('');

const form = useForm({
    maintenance_number: '' as string,
    start_date: '' as string,
});

// Load next maintenance number when modal opens
const loadNextMaintenanceNumber = async () => {
    if (!props.open) return;

    loadingNextNumber.value = true;
    try {
        const response = await axios.get(`/panel/${vesselId.value}/maintenances/create`);
        nextMaintenanceNumber.value = response.data.next_maintenance_number;
        // Auto-fill the form with the next number
        form.maintenance_number = nextMaintenanceNumber.value;
    } catch (error) {
        console.error('Failed to load next maintenance number:', error);
        // Fallback: try to generate a number on the client side
        const year = new Date().getFullYear();
        form.maintenance_number = `MANT${year}000001`;
    } finally {
        loadingNextNumber.value = false;
    }
};

// Watch for modal open to load next number
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        loadNextMaintenanceNumber();
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
    form.post(`/panel/${vesselId.value}/maintenances`, {
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

const handleDialogUpdate = (value: boolean) => {
    // Only emit close when dialog is being closed (not opened)
    if (!value) {
        emit('update:open', false);
        form.reset();
        form.clearErrors();
    }
};

const handleClose = () => {
    emit('update:open', false);
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <Dialog :open="open" @update:open="handleDialogUpdate">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>Create New Maintenance</DialogTitle>
                <DialogDescription>
                    Create a new maintenance record for your vessel
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
                <form @submit.prevent="handleSave" class="space-y-6">
                    <!-- Maintenance Number -->
                    <div>
                        <Label for="maintenance_number" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            Maintenance Number <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="maintenance_number"
                            v-model="form.maintenance_number"
                            type="text"
                            placeholder="MANT2025000001"
                            required
                            :disabled="loadingNextNumber || form.processing"
                            :class="{ 'border-destructive dark:border-destructive': form.errors.maintenance_number }"
                        />
                        <InputError :message="form.errors.maintenance_number" class="mt-1" />
                        <p v-if="loadingNextNumber" class="mt-1 text-xs text-muted-foreground dark:text-muted-foreground">
                            Loading next maintenance number...
                        </p>
                        <p v-else-if="nextMaintenanceNumber && form.maintenance_number === nextMaintenanceNumber" class="mt-1 text-xs text-muted-foreground dark:text-muted-foreground">
                            This number was auto-generated. You can change it if needed.
                        </p>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <Label for="start_date" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            Start Date <span class="text-destructive">*</span>
                        </Label>
                        <DateInput
                            id="start_date"
                            v-model="form.start_date"
                            :class="{ 'border-destructive dark:border-destructive': form.errors.start_date }"
                            required
                        />
                        <InputError :message="form.errors.start_date" class="mt-1" />
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
                    :disabled="form.processing || loadingNextNumber || !form.maintenance_number || !form.start_date"
                >
                    <Icon v-if="form.processing" name="loader" class="w-4 h-4 mr-2 animate-spin" />
                    <Icon v-else name="plus" class="w-4 h-4 mr-2" />
                    {{ form.processing ? 'Creating...' : 'Create Maintenance' }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

