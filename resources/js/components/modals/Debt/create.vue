<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Icon from '@/components/Icon.vue';
import MoneyInput from '@/components/Forms/MoneyInput.vue';
import { useI18n } from '@/composables/useI18n';
import debts from '@/routes/panel/debts';

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
    description: '',
    holder: '',
    amount: null as number | null,
    due_date: '',
    notes: '',
});

// Reset form when modal opens/closes
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.clearErrors();
    }
});

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel IDs (alphanumeric strings) or numeric IDs
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

const handleSave = () => {
    const vesselId = getCurrentVesselId();
    if (!vesselId) {
        return;
    }

    form.post(debts.store.url({ vessel: vesselId }), {
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
                <DialogTitle>{{ t('Create New Debt') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Register a new outstanding debt') }}
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
                <form @submit.prevent="handleSave" class="space-y-6">
                    <div class="space-y-6">
                        <!-- Description -->
                        <div>
                            <Label for="description" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Description') }} <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="description"
                                v-model="form.description"
                                type="text"
                                :placeholder="t('e.g., Fuel supply invoice')"
                                required
                                :class="{ 'border-destructive dark:border-destructive': form.errors.description }"
                            />
                            <InputError :message="form.errors.description" class="mt-1" />
                        </div>

                        <!-- Holder -->
                        <div>
                            <Label for="holder" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Holder') }}
                            </Label>
                            <Input
                                id="holder"
                                v-model="form.holder"
                                type="text"
                                :placeholder="t('e.g., Company name or person')"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.holder }"
                            />
                            <InputError :message="form.errors.holder" class="mt-1" />
                        </div>

                        <!-- Amount and Due Date -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Amount -->
                            <div>
                                <Label for="amount" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    {{ t('Amount') }} <span class="text-destructive">*</span>
                                </Label>
                                <MoneyInput
                                    id="amount"
                                    v-model="form.amount"
                                    currency="EUR"
                                    placeholder="0,00"
                                    :error="!!form.errors.amount"
                                />
                                <InputError :message="form.errors.amount" class="mt-1" />
                            </div>

                            <!-- Due Date -->
                            <div>
                                <Label for="due_date" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    {{ t('Due Date') }}
                                </Label>
                                <Input
                                    id="due_date"
                                    v-model="form.due_date"
                                    type="date"
                                    :class="{ 'border-destructive dark:border-destructive': form.errors.due_date }"
                                />
                                <InputError :message="form.errors.due_date" class="mt-1" />
                            </div>
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
                                :placeholder="t('Additional details...')"
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
                    {{ t('Create Debt') }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
