<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { useNotifications } from '@/composables/useNotifications';
import bankAccounts from '@/routes/panel/bank-accounts';

interface Country {
    id: number;
    name: string;
    code: string;
}

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
    formatted_display: string;
}

interface Props {
    open: boolean;
    countries: Country[];
    currencies: Currency[];
    statuses: Record<string, string>;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const { addNotification } = useNotifications();

// Checkbox states
const useIban = ref(true);
const useAccountNumber = ref(false);
const hasInitialBalance = ref(false);

const form = useForm({
    name: '',
    bank_name: '',
    account_number: '',
    iban: '',
    country_id: null as number | null,
    initial_balance: 0,
    status: 'active',
    notes: '',
});

// Computed properties
const showIbanField = computed(() => {
    console.log('showIbanField computed:', useIban.value);
    return useIban.value;
});
const showAccountNumberField = computed(() => {
    console.log('showAccountNumberField computed:', useAccountNumber.value);
    return useAccountNumber.value;
});
const showInitialBalanceField = computed(() => {
    console.log('showInitialBalanceField computed:', hasInitialBalance.value);
    return hasInitialBalance.value;
});

// Watch for changes in checkbox states
watch(useIban, (newValue) => {
    console.log('useIban changed to:', newValue);
});
watch(useAccountNumber, (newValue) => {
    console.log('useAccountNumber changed to:', newValue);
});
watch(hasInitialBalance, (newValue) => {
    console.log('hasInitialBalance changed to:', newValue);
});

// Reset form when modal opens/closes
watch(() => props.open, (isOpen) => {
    console.log('Modal open state changed to:', isOpen);
    if (isOpen) {
        console.log('Resetting form and checkbox states...');
        form.reset();
        form.country_id = null;
        form.status = 'active';
        form.clearErrors();
        // Reset checkbox states
        useIban.value = true;
        useAccountNumber.value = false;
        hasInitialBalance.value = false;
        console.log('After reset - useIban:', useIban.value, 'useAccountNumber:', useAccountNumber.value, 'hasInitialBalance:', hasInitialBalance.value);
    }
});

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

const submit = () => {
    // Clear fields based on checkbox selection
    if (!useIban.value) {
        form.iban = '';
    }
    if (!useAccountNumber.value) {
        form.account_number = '';
    }
    if (!hasInitialBalance.value) {
        form.initial_balance = 0;
    }

    form.post(bankAccounts.store.url({ vessel: getCurrentVesselId() }), {
        onSuccess: () => {
            addNotification({
                type: 'success',
                message: `Bank account '${form.name}' has been created successfully.`,
            });
            emit('success');
        },
        onError: () => {
            addNotification({
                type: 'error',
                message: 'Failed to create bank account. Please try again.',
            });
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Add Bank Account</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Account Name -->
                    <div class="space-y-2">
                        <Label for="name">Account Name *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g., Main Business Account"
                            :class="{ 'border-red-500': form.errors.name }"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Bank Name -->
                    <div class="space-y-2">
                        <Label for="bank_name">Bank Name *</Label>
                        <Input
                            id="bank_name"
                            v-model="form.bank_name"
                            type="text"
                            placeholder="e.g., Banco Santander"
                            :class="{ 'border-red-500': form.errors.bank_name }"
                        />
                        <InputError :message="form.errors.bank_name" />
                    </div>
                </div>

                <!-- Account Information Checkboxes -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <input
                                type="checkbox"
                                id="use_iban"
                                v-model="useIban"
                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary focus:ring-offset-2"
                            />
                            <Label for="use_iban" class="text-sm font-medium cursor-pointer">Use IBAN</Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <input
                                type="checkbox"
                                id="use_account_number"
                                v-model="useAccountNumber"
                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary focus:ring-offset-2"
                            />
                            <Label for="use_account_number" class="text-sm font-medium cursor-pointer">Use Account Number</Label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Account Number -->
                        <div class="space-y-2" v-if="showAccountNumberField">
                            <Label for="account_number">Account Number</Label>
                            <Input
                                id="account_number"
                                v-model="form.account_number"
                                type="text"
                                placeholder="e.g., 1234567890"
                                :class="{ 'border-red-500': form.errors.account_number }"
                            />
                            <InputError :message="form.errors.account_number" />
                        </div>

                        <!-- IBAN -->
                        <div class="space-y-2" v-if="showIbanField">
                            <Label for="iban">IBAN</Label>
                            <Input
                                id="iban"
                                v-model="form.iban"
                                type="text"
                                placeholder="e.g., PT50 0000 0000 0000 0000 0000 0"
                                :class="{ 'border-red-500': form.errors.iban }"
                            />
                            <InputError :message="form.errors.iban" />
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="space-y-2">
                    <Label for="status">Status *</Label>
                    <select
                        id="status"
                        v-model="form.status"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.status }"
                    >
                        <option value="">Select status</option>
                        <option
                            v-for="(label, value) in statuses"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.status" />
                    <p class="text-xs text-muted-foreground mt-1">
                        💡 Country will be automatically detected from IBAN
                    </p>
                </div>

                <!-- Initial Balance -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <input
                            type="checkbox"
                            id="has_initial_balance"
                            v-model="hasInitialBalance"
                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary focus:ring-offset-2"
                        />
                        <Label for="has_initial_balance" class="text-sm font-medium cursor-pointer">Set Initial Balance</Label>
                    </div>

                    <div class="space-y-2" v-if="showInitialBalanceField">
                        <Label for="initial_balance">Initial Balance *</Label>
                        <Input
                            id="initial_balance"
                            v-model="form.initial_balance"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            :class="{ 'border-red-500': form.errors.initial_balance }"
                        />
                        <InputError :message="form.errors.initial_balance" />
                    </div>
                </div>

                <!-- Notes -->
                <div class="space-y-2">
                    <Label for="notes">Notes</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        placeholder="Additional notes about this bank account..."
                        rows="3"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive dark:border-destructive': form.errors.notes }"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <Button type="button" variant="outline" @click="emit('close')">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create Bank Account' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
