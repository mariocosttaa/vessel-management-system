<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useNotifications } from '@/composables/useNotifications';
import { usePage } from '@inertiajs/vue3';
import transactions from '@/routes/panel/movimentations';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import Label from '@/components/ui/label/Label.vue';
import Input from '@/components/ui/input/Input.vue';
import InputError from '@/components/InputError.vue';
import Button from '@/components/ui/button/Button.vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue';
import Select from '@/components/ui/select/Select.vue';

const { t } = useI18n();
const { addNotification } = useNotifications();
const page = usePage();

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel IDs (alphanumeric strings) or numeric IDs
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

// Get vessel currency from shared props
const vesselCurrencyData = computed(() => {
    const vessel = (page.props as any).vessel;
    if (vessel?.currency_code) {
        return {
            code: vessel.currency_code,
            symbol: vessel.currency_symbol || vessel.currency_code,
        };
    }
    return {
        code: props.defaultCurrency || 'EUR',
        symbol: props.defaultCurrency || 'EUR',
    };
});

const currentCurrencyDecimals = computed(() => {
    const vessel = (page.props as any).vessel;
    return vessel?.house_of_zeros ?? 2;
});

interface Transaction {
    id: string | number;
    transaction_number: string;
    crew_member_id: string | number | null;
    crew_member?: {
        id: string | number;
        name: string;
        email: string;
    };
    amount: number;
    transaction_date: string;
    description: string | null;
    notes: string | null;
    currency: string;
    house_of_zeros: number;
    status: string;
}

interface CrewMember {
    id: string | number;
    name: string;
    email: string;
}

interface Props {
    open: boolean;
    transaction: Transaction | null;
    crewMembers: CrewMember[];
    defaultCurrency?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'success'): void;
}>();

// Get current currency
const currentCurrency = computed(() => {
    return vesselCurrencyData.value.code || props.defaultCurrency || 'EUR';
});

// Initialize form with empty/default values
const form = useForm({
    amount: null as number | null,
    currency: 'EUR' as string,
    house_of_zeros: 2 as number,
    transaction_date: '' as string,
    description: '' as string,
    notes: '' as string,
    status: 'completed' as string,
});

const isFormInitialized = ref(false);

// Get crew member name for display
const selectedCrewMember = computed(() => {
    if (!props.transaction?.crew_member_id) return null;

    // Try to find in crew_member nested object first
    if (props.transaction.crew_member) {
        return props.transaction.crew_member;
    }

    // Fallback to crewMembers prop
    const member = props.crewMembers.find(m => {
        const memberId = typeof m.id === 'string' ? m.id : String(m.id);
        const transactionId = typeof props.transaction.crew_member_id === 'string'
            ? props.transaction.crew_member_id
            : String(props.transaction.crew_member_id);
        return memberId === transactionId;
    });

    return member || null;
});

// Helper function to initialize form from transaction
const initializeFormFromTransaction = () => {
    if (!props.transaction) {
        return;
    }

    // Set all form fields
    form.amount = props.transaction.amount ?? null;
    form.currency = props.transaction.currency || currentCurrency.value || 'EUR';
    form.house_of_zeros = props.transaction.house_of_zeros ?? currentCurrencyDecimals.value;

    // Normalize transaction_date to YYYY-MM-DD format
    if (props.transaction.transaction_date) {
        try {
            let dateStr = String(props.transaction.transaction_date).trim();

            // If it's already in YYYY-MM-DD format, use it directly
            if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
                form.transaction_date = dateStr;
            } else {
                // Try to parse and format it
                const date = new Date(dateStr);
                if (!isNaN(date.getTime())) {
                    // Format as YYYY-MM-DD
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    form.transaction_date = `${year}-${month}-${day}`;
                } else {
                    // Fallback: try to extract date parts if it's in a different format
                    const dateMatch = dateStr.match(/(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/);
                    if (dateMatch) {
                        const [, year, month, day] = dateMatch;
                        form.transaction_date = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
                    } else {
                        form.transaction_date = '';
                    }
                }
            }
        } catch (e) {
            form.transaction_date = '';
        }
    } else {
        form.transaction_date = '';
    }

    form.description = props.transaction.description || '';
    form.notes = props.transaction.notes || '';
    form.status = props.transaction.status || 'completed';

    form.clearErrors();

    // Mark form as initialized
    isFormInitialized.value = true;
};

// Reset form when modal opens/closes or transaction changes
watch(() => [props.open, props.transaction?.id], ([isOpen, transactionId]) => {
    if (isOpen && transactionId && props.transaction) {
        // Initialize form immediately
        initializeFormFromTransaction();
    } else if (!isOpen) {
        // Clear form when modal closes
        form.reset();
        form.clearErrors();
        isFormInitialized.value = false;
    }
}, { immediate: true });

const submit = () => {
    // Ensure form is initialized before submission
    if (!isFormInitialized.value) {
        // Wait a bit and try again
        setTimeout(() => {
            if (isFormInitialized.value) {
                submit();
            } else {
                addNotification({
                    type: 'error',
                    title: t('Error'),
                    message: t('Form is not ready. Please wait a moment and try again.'),
                });
            }
        }, 100);
        return;
    }

    // Ensure transaction_date is set and properly formatted (required field)
    if (!form.transaction_date || form.transaction_date === '') {
        form.setError('transaction_date', t('Transaction date is required.'));
        addNotification({
            type: 'error',
            title: t('Error'),
            message: t('Transaction date is required.'),
        });
        return;
    }

    // Ensure amount is set (required field)
    if (!form.amount || form.amount === 0) {
        addNotification({
            type: 'error',
            title: t('Error'),
            message: t('Amount is required.'),
        });
        return;
    }

    // Ensure status is set (required field)
    if (!form.status) {
        form.status = props.transaction?.status || 'completed';
    }

    // Ensure currency and house_of_zeros are set
    form.currency = currentCurrency.value || props.transaction?.currency || 'EUR';
    form.house_of_zeros = currentCurrencyDecimals.value;

    const vesselId = getCurrentVesselId();
    if (!vesselId) {
        addNotification({
            type: 'error',
            title: t('Error'),
            message: t('Unable to determine vessel ID.'),
        });
        return;
    }

    // For salary payments, only send: crew_member_id, amount, transaction_date, description, notes
    // Backend will automatically set the salary category
    const finalFormData: any = {
        amount: form.amount,
        transaction_date: form.transaction_date,
        description: form.description,
        notes: form.notes,
        status: form.status,
        currency: form.currency,
        house_of_zeros: form.house_of_zeros,
        crew_member_id: props.transaction?.crew_member_id || null, // Required for salary payments
        type: 'expense', // Salary payments are always expenses
        // Note: category_id is NOT sent - backend will automatically set it to salary category
    };

    // Use router.put directly with the finalFormData
    // Backend will automatically set the salary category when crew_member_id is present
    router.put(
        transactions.update.url({ vessel: vesselId, movimentationId: props.transaction?.id }),
        finalFormData,
        {
            preserveScroll: true,
            onSuccess: () => {
                addNotification({
                    type: 'success',
                    title: t('Success'),
                    message: `${t('Salary payment')} '${props.transaction?.transaction_number}' ${t('has been updated successfully.')}`,
                });
                emit('success');
                emit('close');
            },
            onError: (errors) => {
                addNotification({
                    type: 'error',
                    title: t('Error'),
                    message: t('Failed to update salary payment. Please check the form for errors.'),
                });
            },
        }
    );
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="text-red-600 dark:text-red-400">
                    {{ t('Update Salary Payment') }} #{{ transaction?.transaction_number }}
                </DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Crew Member (Fixed/Disabled) -->
                <div class="space-y-2">
                    <Label for="crew_member_id">{{ t('Crew Member') }} <span class="text-muted-foreground text-sm">({{ t('Fixed') }})</span></Label>
                    <Input
                        id="crew_member_id"
                        :model-value="selectedCrewMember ? `${selectedCrewMember.name}${selectedCrewMember.email ? ' (' + selectedCrewMember.email + ')' : ''}` : t('No crew member selected')"
                        type="text"
                        disabled
                        class="bg-muted/50 cursor-not-allowed"
                    />
                    <p class="text-sm text-muted-foreground">
                        {{ t('The crew member cannot be changed for salary payments.') }}
                    </p>
                </div>

                <!-- Amount and Date -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Amount -->
                    <div class="space-y-2">
                        <MoneyInputWithLabel
                            v-model="form.amount"
                            :label="t('Amount')"
                            :currency="currentCurrency"
                            placeholder="0,00"
                            :error="form.errors.amount"
                            :show-currency="true"
                            return-type="int"
                            :decimals="currentCurrencyDecimals"
                            required
                        />
                    </div>

                    <!-- Transaction Date -->
                    <div class="space-y-2">
                        <Label for="transaction_date">{{ t('Transaction Date') }} <span class="text-destructive">*</span></Label>
                        <DateInput
                            id="transaction_date"
                            v-model="form.transaction_date"
                            :max="new Date().toISOString().split('T')[0]"
                            :class="{ 'border-destructive dark:border-destructive': form.errors.transaction_date }"
                        />
                        <InputError :message="form.errors.transaction_date" />
                    </div>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="description">{{ t('Description') }}</Label>
                    <Input
                        id="description"
                        v-model="form.description"
                        :placeholder="t('Enter transaction description')"
                        :error="form.errors.description"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <!-- Notes -->
                <div class="space-y-2">
                    <Label for="notes">{{ t('Notes') }} ({{ t('Optional') }})</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        :placeholder="t('Additional notes')"
                        rows="3"
                        class="flex min-h-[80px] w-full rounded-md border border-input dark:border-input bg-background dark:bg-background px-3 py-2 text-sm text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="emit('close')" :disabled="form.processing">
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="form.processing || !form.amount || !form.transaction_date">
                        {{ t('Update Salary Payment') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

