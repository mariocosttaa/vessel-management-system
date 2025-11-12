<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Icon from '@/components/Icon.vue';
import { useI18n } from '@/composables/useI18n';
import suppliers from '@/routes/panel/suppliers';

interface Props {
    open: boolean;
}

const props = defineProps<Props>();
const { t } = useI18n();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'saved': [];
}>();

const form = useForm({
    company_name: '',
    description: '',
    email: '',
    phone: '',
    address: '',
    notes: '',
});

// Reset form when modal opens/closes
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.clearErrors();
    }
});

const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

const handleSave = () => {
    form.post(suppliers.store.url({ vessel: getCurrentVesselId() }), {
        onSuccess: () => {
            emit('saved');
            emit('update:open', false);
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
                <DialogTitle>{{ t('Create New Supplier') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Add a new supplier to your vendor management system') }}
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
                <form @submit.prevent="handleSave" class="space-y-6">
                    <div class="space-y-6">
                        <!-- Company Name -->
                        <div>
                            <Label for="company_name" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Company Name') }} <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="company_name"
                                v-model="form.company_name"
                                type="text"
                                :placeholder="t('Enter company name')"
                                required
                                :class="{ 'border-destructive dark:border-destructive': form.errors.company_name }"
                            />
                            <InputError :message="form.errors.company_name" class="mt-1" />
                        </div>

                        <!-- Description -->
                        <div>
                            <Label for="description" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Description') }}
                            </Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                :placeholder="t('Enter supplier description')"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.description }"
                            ></textarea>
                            <InputError :message="form.errors.description" class="mt-1" />
                        </div>

                        <!-- Email -->
                        <div>
                            <Label for="email" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Email') }}
                            </Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                :placeholder="t('Enter email address')"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.email }"
                            />
                            <InputError :message="form.errors.email" class="mt-1" />
                        </div>

                        <!-- Phone -->
                        <div>
                            <Label for="phone" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Phone') }}
                            </Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                :placeholder="t('Enter phone number')"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.phone }"
                            />
                            <InputError :message="form.errors.phone" class="mt-1" />
                        </div>

                        <!-- Address -->
                        <div>
                            <Label for="address" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Address') }}
                            </Label>
                            <textarea
                                id="address"
                                v-model="form.address"
                                rows="3"
                                :placeholder="t('Enter address')"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.address }"
                            ></textarea>
                            <InputError :message="form.errors.address" class="mt-1" />
                        </div>

                        <!-- Notes -->
                        <div>
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
                    {{ t('Create Supplier') }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
