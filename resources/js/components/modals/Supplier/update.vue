<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Icon from '@/components/Icon.vue';
import suppliers from '@/routes/panel/suppliers';

interface Supplier {
    id: number;
    company_name: string;
    email?: string;
    phone?: string;
    address?: string;
    notes?: string;
}

interface Props {
    open: boolean;
    supplier?: Supplier | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'saved': [];
}>();

const form = useForm({
    company_name: '',
    email: '',
    phone: '',
    address: '',
    notes: '',
});

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

// Helper function to populate form from supplier
const populateForm = (supplier: Supplier | null) => {
    if (supplier) {
        form.company_name = supplier.company_name || '';
        form.email = supplier.email || '';
        form.phone = supplier.phone || '';
        form.address = supplier.address || '';
        form.notes = supplier.notes || '';
    } else {
        form.reset();
    }
    form.clearErrors();
};

// Watch for modal open/close and supplier changes
watch([() => props.open, () => props.supplier], ([isOpen, supplier]) => {
    if (isOpen && supplier) {
        populateForm(supplier);
    } else if (isOpen) {
        form.reset();
        form.clearErrors();
    }
}, { immediate: true });

const handleSave = () => {
    if (!props.supplier) {
        return;
    }

    const vesselId = getCurrentVesselId();
    if (!vesselId) {
        console.error('Unable to determine vessel ID');
        return;
    }

    form.put(suppliers.update.url({ vessel: vesselId, supplier: props.supplier.id }), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('update:open', false);
            form.reset();
        },
        onError: (errors) => {
            // Validation errors are automatically displayed by InputError components
            // Keep modal open to show errors
            console.error('Update failed:', errors);
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
                <DialogTitle>Edit Supplier</DialogTitle>
                <DialogDescription>
                    Update supplier information
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
                <form @submit.prevent="handleSave" class="space-y-6">
                    <div class="space-y-6">
                        <!-- Company Name -->
                        <div>
                            <Label for="company_name" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                Company Name <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="company_name"
                                v-model="form.company_name"
                                type="text"
                                placeholder="Enter company name"
                                required
                                :class="{ 'border-destructive dark:border-destructive': form.errors.company_name }"
                            />
                            <InputError :message="form.errors.company_name" class="mt-1" />
                        </div>

                        <!-- Email -->
                        <div>
                            <Label for="email" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                Email
                            </Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="Enter email address"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.email }"
                            />
                            <InputError :message="form.errors.email" class="mt-1" />
                        </div>

                        <!-- Phone -->
                        <div>
                            <Label for="phone" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                Phone
                            </Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                placeholder="Enter phone number"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.phone }"
                            />
                            <InputError :message="form.errors.phone" class="mt-1" />
                        </div>

                        <!-- Address -->
                        <div>
                            <Label for="address" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                Address
                            </Label>
                            <textarea
                                id="address"
                                v-model="form.address"
                                rows="3"
                                placeholder="Enter address"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.address }"
                            ></textarea>
                            <InputError :message="form.errors.address" class="mt-1" />
                        </div>

                        <!-- Notes -->
                        <div>
                            <Label for="notes" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                Notes
                            </Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                placeholder="Enter additional notes"
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
                    Cancel
                </Button>
                <Button
                    @click="handleSave"
                    :disabled="form.processing"
                >
                    <Icon v-if="form.processing" name="loader" class="w-4 h-4 mr-2 animate-spin" />
                    Update Supplier
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
