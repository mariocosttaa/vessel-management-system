<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import DataTable from '@/components/ui/DataTable.vue';
import Pagination from '@/components/ui/Pagination.vue';
import BankAccountCreateModal from '@/components/modals/BankAccount/create.vue';
import BankAccountUpdateModal from '@/components/modals/BankAccount/update.vue';
import BankAccountShowModal from '@/components/modals/BankAccount/show.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { Button } from '@/components/ui/button';
import { Plus, Eye, Edit, Trash2 } from 'lucide-vue-next';
import { usePermissions } from '@/composables/usePermissions';
import { useNotifications } from '@/composables/useNotifications';
import bankAccounts from '@/routes/panel/bank-accounts';

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

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

interface BankAccount {
    id: number;
    name: string;
    bank_name: string;
    account_number: string | null;
    iban: string | null;
    country: Country | null;
    formatted_initial_balance: string;
    formatted_current_balance: string;
    status: string;
    status_label: string;
    created_at: string;
}

interface Props {
    bankAccounts: {
        data: BankAccount[];
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        status?: string;
        country_id?: number;
        sort?: string;
        direction?: string;
    };
    statuses: Record<string, string>;
    countries: Country[];
    currencies: Currency[];
}

const props = defineProps<Props>();
const { canCreate, canEdit, canDelete } = usePermissions();
const { addNotification } = useNotifications();

// Modal states
const showCreateModal = ref(false);
const showUpdateModal = ref(false);
const showShowModal = ref(false);
const selectedBankAccount = ref<BankAccount | null>(null);

// Confirmation dialog state
const showDeleteDialog = ref(false);
const bankAccountToDelete = ref<BankAccount | null>(null);
const isDeleting = ref(false);

// Sorting
const sortField = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');

// Data table configuration - reduced columns since we have show modal
const columns = [
    { key: 'name', label: 'Account Name', sortable: true },
    { key: 'bank_name', label: 'Bank', sortable: true },
    { key: 'formatted_current_balance', label: 'Current Balance', sortable: true },
    { key: 'status_label', label: 'Status', sortable: true },
];

const actions = computed(() => {
    const actionItems = [];

    if (canEdit('bank-accounts')) {
        actionItems.push({
            label: 'View Details',
            icon: 'eye',
            onClick: (item: BankAccount) => openShowModal(item),
        });
        actionItems.push({
            label: 'Edit Bank Account',
            icon: 'edit',
            onClick: (item: BankAccount) => openUpdateModal(item),
        });
    }

    if (canDelete('bank-accounts')) {
        actionItems.push({
            label: 'Delete Bank Account',
            icon: 'trash-2',
            onClick: (item: BankAccount) => deleteBankAccount(item),
            variant: 'destructive' as const,
        });
    }

    return actionItems;
});

// Transform data for DataTable
const bankAccountsData = computed(() => {
    return props.bankAccounts.data.map(account => ({
        ...account,
        account_number: account.account_number || 'N/A',
        iban: account.iban || 'N/A',
        country: account.country ? `${account.country.name} (${account.country.code})` : 'N/A',
    }));
});

// Modal handlers
const openCreateModal = () => {
    showCreateModal.value = true;
};

const openUpdateModal = (bankAccount: BankAccount) => {
    selectedBankAccount.value = bankAccount;
    showUpdateModal.value = true;
};

const openShowModal = (bankAccount: BankAccount) => {
    selectedBankAccount.value = bankAccount
    showShowModal.value = true
}

const closeModals = () => {
    showCreateModal.value = false;
    showUpdateModal.value = false;
    showShowModal.value = false;
    selectedBankAccount.value = null;
};

// CRUD operations
const deleteBankAccount = (bankAccount: BankAccount) => {
    bankAccountToDelete.value = bankAccount;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!bankAccountToDelete.value) return;

    const bankAccountName = bankAccountToDelete.value.name;
    isDeleting.value = true;

    router.delete(bankAccounts.destroy.url({ vessel: getCurrentVesselId(), bankAccount: bankAccountToDelete.value.id }), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            bankAccountToDelete.value = null;
            isDeleting.value = false;
            addNotification({
                type: 'success',
                message: `Bank account '${bankAccountName}' has been deleted successfully.`,
            });
        },
        onError: () => {
            isDeleting.value = false;
            addNotification({
                type: 'error',
                message: 'Failed to delete bank account. Please try again.',
            });
        },
    });
};

const cancelDelete = () => {
    showDeleteDialog.value = false;
    bankAccountToDelete.value = null;
    isDeleting.value = false;
};

// Sorting
const handleSort = (field: string) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }

    router.get(bankAccounts.index.url(), {
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Search and filters
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const countryFilter = ref(props.filters.country_id || '');

const applyFilters = () => {
    router.get(bankAccounts.index.url(), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        country_id: countryFilter.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    statusFilter.value = '';
    countryFilter.value = '';
    sortField.value = 'created_at';
    sortDirection.value = 'desc';

    router.get(bankAccounts.index.url({ vessel: getCurrentVesselId() }), {}, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <Head title="Bank Accounts" />

    <VesselLayout :breadcrumbs="[{ title: 'Bank Accounts', href: bankAccounts.index.url({ vessel: getCurrentVesselId() }) }]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">Bank Accounts</h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">Manage your bank accounts and their information</p>
                    </div>
                    <button
                        v-if="canCreate('bank-accounts')"
                        @click="openCreateModal"
                        class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
                    >
                        <Icon name="plus" class="w-4 h-4 mr-2" />
                        Add Bank Account
                    </button>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                            Search
                        </label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search bank accounts..."
                            class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                            Status
                        </label>
                        <select
                            v-model="statusFilter"
                            class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                        >
                            <option value="">All Statuses</option>
                            <option v-for="(label, value) in statuses" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <!-- Country Filter -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">
                            Country
                        </label>
                        <select
                            v-model="countryFilter"
                            class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                        >
                            <option value="">All Countries</option>
                            <option v-for="country in countries" :key="country.id" :value="country.id">
                                {{ country.name }} ({{ country.code }})
                            </option>
                        </select>
                    </div>

                    <!-- Apply Filters -->
                    <div class="flex items-end">
                        <button
                            @click="applyFilters"
                            class="w-full px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
                        >
                            Apply Filters
                        </button>
                    </div>

                    <!-- Clear Filters -->
                    <div class="flex items-end">
                        <button
                            @click="clearFilters"
                            class="w-full px-4 py-2 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground font-medium transition-colors"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Data Table Card -->
                <DataTable
                    :columns="columns"
                    :data="bankAccountsData"
                    :clickable="true"
                    :on-row-click="openShowModal"
                    :actions="actions"
                    :sort-field="sortField.value"
                    :sort-direction="sortDirection.value"
                    :on-sort="handleSort"
                    :loading="false"
                    empty-message="No bank accounts found"
                />

            <!-- Pagination -->
            <div v-if="bankAccounts.meta && bankAccounts.meta.last_page > 1" class="flex justify-center">
                <Pagination :links="bankAccounts.links" />
            </div>
        </div>

        <!-- Modals -->
        <BankAccountCreateModal
            :open="showCreateModal"
            :countries="countries"
            :currencies="currencies"
            :statuses="statuses"
            @close="closeModals"
            @success="closeModals"
        />

        <BankAccountUpdateModal
            v-if="selectedBankAccount"
            :open="showUpdateModal"
            :bank-account="selectedBankAccount"
            :countries="countries"
            :currencies="currencies"
            :statuses="statuses"
            @close="closeModals"
            @success="closeModals"
        />

        <BankAccountShowModal
            v-if="selectedBankAccount"
            :open="showShowModal"
            :bank-account="selectedBankAccount"
            @close="closeModals"
        />

        <!-- Confirmation Dialog -->
        <ConfirmationDialog
            v-model:open="showDeleteDialog"
            title="Delete Bank Account"
            description="This action cannot be undone."
            :message="`Are you sure you want to delete the bank account '${bankAccountToDelete?.name}'? This will permanently remove the bank account and all its data.`"
            confirm-text="Delete Bank Account"
            cancel-text="Cancel"
            variant="destructive"
            type="danger"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </VesselLayout>
</template>
