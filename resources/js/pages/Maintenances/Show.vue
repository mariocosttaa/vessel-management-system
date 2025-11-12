<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useNotifications } from '@/composables/useNotifications';
import { useI18n } from '@/composables/useI18n';
import maintenances from '@/routes/panel/maintenances';
import CreateRemoveModal from '@/components/modals/Transaction/create-remove.vue';
import { DateInput } from '@/components/ui/date-input';
import { Button } from '@/components/ui/button';

// Get current vessel ID from URL (supports both hashed and numeric IDs)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed vessel IDs (alphanumeric strings) or numeric IDs
    const vesselMatch = path.match(/\/panel\/([^\/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

interface Maintenance {
    id: number;
    maintenance_number: string;
    name: string | null;
    description: string | null;
    status: string;
    start_date: string | null;
    end_date: string | null;
    closed_at: string | null;
    currency: string;
    house_of_zeros: number;
    total_expenses: number;
    formatted_total_expenses: string;
    transactions: Array<{
        id: number;
        transaction_number: string;
        type: string;
        amount: number;
        total_amount: number;
        currency: string;
        transaction_date: string | null;
        description: string | null;
        category: {
            id: number;
            name: string;
            type: string;
            color: string;
        } | null;
        supplier: {
            id: number;
            company_name: string;
        } | null;
    }>;
    created_at: string | null;
    created_by: {
        id: number;
        name: string;
    } | null;
}

interface Props {
    maintenance: Maintenance;
    transactionCount?: number;
    defaultCurrency?: string;
    categories?: Array<{
        id: number;
        name: string;
        type: string;
        color: string;
    }>;
    suppliers?: Array<{
        id: number;
        company_name: string;
        description?: string;
    }>;
    crewMembers?: Array<{
        id: number;
        name: string;
        email: string;
    }>;
    vatProfiles?: Array<{
        id: number;
        name: string;
        percentage: number;
        country_id?: number | null;
    }>;
    defaultVatProfile?: {
        id: number;
        name: string;
        percentage: number;
        country_id?: number | null;
    } | null;
}

const props = defineProps<Props>();
const { canEdit, canDelete } = usePermissions();
const { addNotification } = useNotifications();
const { t } = useI18n();

// Modal states
const showCreateExpenseModal = ref(false);
const showDeleteTransactionDialog = ref(false);
const transactionToDelete = ref<any>(null);

// End date form for open maintenances
const endDateForm = useForm({
    end_date: props.maintenance.end_date || '',
});

// Get status badge color
const getStatusColor = (status: string) => {
    switch (status) {
        case 'open':
            return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300';
        case 'closed':
            return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300';
        case 'cancelled':
            return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

// Format date
const formatDate = (dateString: string | null) => {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Remove transaction from maintenance
const removeTransaction = (transaction: any) => {
    transactionToDelete.value = transaction;
    showDeleteTransactionDialog.value = true;
};

const confirmRemoveTransaction = () => {
    if (!transactionToDelete.value) return;

    router.delete(maintenances.removeTransaction.url({
        vessel: getCurrentVesselId(),
        maintenanceId: props.maintenance.id,
        transaction: transactionToDelete.value!.id
    }), {
        onSuccess: () => {
            showDeleteTransactionDialog.value = false;
            transactionToDelete.value = null;
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Transaction has been removed from the maintenance.'),
            });
        },
        onError: () => {
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to remove transaction. Please try again.'),
            });
        },
    });
};

const cancelRemoveTransaction = () => {
    showDeleteTransactionDialog.value = false;
    transactionToDelete.value = null;
};

// Update end date
const updateEndDate = () => {
    endDateForm.put(maintenances.update.url({
        vessel: getCurrentVesselId(),
        maintenanceId: props.maintenance.id
    }), {
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('End date has been updated.'),
            });
        },
        onError: () => {
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to update end date. Please try again.'),
            });
        },
    });
};

// Finalize maintenance
const finalizeMaintenance = () => {
    if (!endDateForm.end_date) {
        addNotification({
            type: 'error',
            title: t('Error'),
            message: t('Please set an end date before finalizing.'),
        });
        return;
    }

    router.post(maintenances.finalize.url({
        vessel: getCurrentVesselId(),
        maintenanceId: props.maintenance.id
    }), {
        end_date: endDateForm.end_date
    }, {
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: t('Success'),
                message: t('Maintenance has been finalized.'),
            });
        },
        onError: () => {
            addNotification({
                type: 'error',
                title: t('Error'),
                message: t('Failed to finalize maintenance. Please try again.'),
            });
        },
    });
};

// Get default currency
const defaultCurrency = computed(() => props.defaultCurrency || 'EUR');
</script>

<template>
    <Head :title="`Maintenance ${props.maintenance.maintenance_number}`" />

    <VesselLayout :breadcrumbs="[
        { title: t('Maintenances'), href: maintenances.index.url({ vessel: getCurrentVesselId() }) },
        { title: props.maintenance.maintenance_number, href: maintenances.show.url({ vessel: getCurrentVesselId(), maintenanceId: props.maintenance.id }) }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">
                                {{ props.maintenance.maintenance_number }}
                            </h1>
                            <span
                                :class="[
                                    'inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium',
                                    getStatusColor(props.maintenance.status)
                                ]"
                            >
                                {{ props.maintenance.status === 'open' ? t('Open') : props.maintenance.status === 'closed' ? t('Closed') : t('Cancelled') }}
                            </span>
                        </div>
                        <p v-if="props.maintenance.name" class="text-muted-foreground dark:text-muted-foreground mt-1">
                            {{ props.maintenance.name }}
                        </p>
                        <div class="mt-2 flex items-center gap-4 text-sm text-muted-foreground dark:text-muted-foreground">
                            <span v-if="props.maintenance.start_date">
                                <Icon name="calendar" class="w-4 h-4 inline mr-1" />
                                {{ t('Start') }}: {{ formatDate(props.maintenance.start_date) }}
                            </span>
                            <span v-if="props.maintenance.end_date">
                                <Icon name="calendar" class="w-4 h-4 inline mr-1" />
                                {{ t('End') }}: {{ formatDate(props.maintenance.end_date) }}
                            </span>
                        </div>
                    </div>
                    <div v-if="canEdit('maintenances') && props.maintenance.status === 'open'" class="flex gap-3">
                        <button
                            @click="showCreateExpenseModal = true"
                            class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
                        >
                            <Icon name="plus" class="w-4 h-4 mr-2" />
                            {{ t('Add Expense') }}
                        </button>
                    </div>
                </div>

                <!-- End Date and Finalize Section (only for open maintenances) -->
                <div v-if="canEdit('maintenances') && props.maintenance.status === 'open'" class="mt-6 pt-6 border-t border-border dark:border-border">
                    <div class="flex items-end gap-4">
                        <div class="flex-1">
                            <label for="end_date" class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                                {{ t('End Date') }}
                            </label>
                            <div class="flex items-center gap-3">
                                <DateInput
                                    id="end_date"
                                    v-model="endDateForm.end_date"
                                    :min="props.maintenance.start_date || undefined"
                                    :class="{ 'border-destructive dark:border-destructive': endDateForm.errors.end_date }"
                                    class="max-w-xs"
                                />
                                <Button
                                    @click="updateEndDate"
                                    :disabled="endDateForm.processing || !endDateForm.end_date"
                                    variant="outline"
                                    size="sm"
                                >
                                    <Icon v-if="endDateForm.processing" name="loader" class="w-4 h-4 mr-2 animate-spin" />
                                    <Icon v-else name="save" class="w-4 h-4 mr-2" />
                                    {{ endDateForm.processing ? t('Saving...') : t('Save End Date') }}
                                </Button>
                            </div>
                            <p v-if="endDateForm.errors.end_date" class="mt-1 text-sm text-destructive">
                                {{ endDateForm.errors.end_date }}
                            </p>
                            <p v-else class="mt-1 text-xs text-muted-foreground dark:text-muted-foreground">
                                {{ t('Set the end date when maintenance is completed') }}
                            </p>
                        </div>
                        <div>
                            <Button
                                @click="finalizeMaintenance"
                                :disabled="!endDateForm.end_date"
                                variant="default"
                                class="bg-green-600 hover:bg-green-700 text-white"
                            >
                                <Icon name="check" class="w-4 h-4 mr-2" />
                                {{ t('Finalize Maintenance') }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">{{ t('Summary') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-lg bg-muted/50 dark:bg-muted/20">
                        <div class="text-sm text-muted-foreground dark:text-muted-foreground mb-1">{{ t('Total Expenses') }}</div>
                        <MoneyDisplay
                            :value="props.maintenance.total_expenses"
                            :currency="defaultCurrency"
                            variant="negative"
                            size="lg"
                            class="font-bold"
                        />
                    </div>
                    <div class="p-4 rounded-lg bg-muted/50 dark:bg-muted/20">
                        <div class="text-sm text-muted-foreground dark:text-muted-foreground mb-1">{{ t('Transactions') }}</div>
                        <div class="text-2xl font-bold text-card-foreground dark:text-card-foreground">
                            {{ props.maintenance.transactions.length }}
                        </div>
                    </div>
                    <div class="p-4 rounded-lg bg-muted/50 dark:bg-muted/20">
                        <div class="text-sm text-muted-foreground dark:text-muted-foreground mb-1">{{ t('Status') }}</div>
                        <div class="text-lg font-semibold text-card-foreground dark:text-card-foreground">
                            {{ props.maintenance.status === 'open' ? 'Open' : props.maintenance.status === 'closed' ? 'Closed' : 'Cancelled' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card overflow-hidden">
                <div class="p-6 border-b border-border dark:border-border">
                    <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground">{{ t('Expenses') }}</h2>
                </div>
                <div v-if="!props.maintenance.transactions || props.maintenance.transactions.length === 0"
                     class="px-6 py-12 text-center text-muted-foreground dark:text-muted-foreground">
                    {{ t('No expenses found. Add an expense to get started.') }}
                </div>
                <div v-else class="divide-y divide-border dark:divide-border">
                    <div
                        v-for="transaction in props.maintenance.transactions"
                        :key="transaction.id"
                        class="px-6 py-4 hover:bg-muted/30 dark:hover:bg-muted/20 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                        {{ transaction.transaction_number }}
                                    </span>
                                    <span
                                        v-if="transaction.category"
                                        :class="[
                                            'inline-flex items-center px-2 py-1 rounded-md text-xs font-medium',
                                            `bg-[${transaction.category.color}]/20 text-[${transaction.category.color}]`
                                        ]"
                                    >
                                        {{ t(transaction.category.name) }}
                                    </span>
                                </div>
                                <div class="mt-1 text-sm text-muted-foreground dark:text-muted-foreground">
                                    <span v-if="transaction.description">{{ transaction.description }}</span>
                                    <span v-if="transaction.supplier" class="ml-2">
                                        • {{ transaction.supplier.company_name }}
                                    </span>
                                    <span v-if="transaction.transaction_date" class="ml-2">
                                        • {{ formatDate(transaction.transaction_date) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <MoneyDisplay
                                    :value="transaction.total_amount"
                                    :currency="transaction.currency || defaultCurrency"
                                    variant="negative"
                                    size="sm"
                                    class="font-semibold"
                                />
                                <button
                                    v-if="canEdit('maintenances') && props.maintenance.status === 'open'"
                                    @click="removeTransaction(transaction)"
                                    class="p-2 text-destructive hover:bg-muted rounded-lg transition-colors"
                                >
                                    <Icon name="trash-2" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Expense Modal -->
        <CreateRemoveModal
            :open="showCreateExpenseModal"
            @update:open="showCreateExpenseModal = $event"
            @close="showCreateExpenseModal = false"
            :categories="props.categories || []"
            :suppliers="props.suppliers || []"
            :crew-members="props.crewMembers || []"
            :vat-profiles="props.vatProfiles || []"
            :default-vat-profile="props.defaultVatProfile"
            :default-currency="defaultCurrency"
            :maintenance-id="props.maintenance.id"
            @success="() => { showCreateExpenseModal = false; router.reload(); }"
        />

        <!-- Remove Transaction Confirmation -->
        <ConfirmationDialog
            v-model:open="showDeleteTransactionDialog"
            :title="t('Remove Transaction')"
            :description="t('This will remove the transaction from this maintenance, but the transaction will still exist.')"
            :message="transactionToDelete ? t('Are you sure you want to remove transaction') + ` '${transactionToDelete.transaction_number}' ` + t('from this maintenance?') : ''"
            :confirm-text="t('Remove')"
            :cancel-text="t('Cancel')"
            variant="destructive"
            type="warning"
            @confirm="confirmRemoveTransaction"
            @cancel="cancelRemoveTransaction"
        />
    </VesselLayout>
</template>

