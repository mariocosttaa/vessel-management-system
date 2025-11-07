<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ref, watch } from 'vue';
import bankAccountsApi from '@/routes/panel/api/bank-accounts';

interface Country {
    id: number;
    name: string;
    code: string;
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
    notes: string | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    open: boolean;
    bankAccount: BankAccount;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
}>();

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

// State for detailed bank account data
const detailedBankAccount = ref<BankAccount | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);


const fetchBankAccountDetails = async () => {
    loading.value = true;
    error.value = null;

    const vesselId = getCurrentVesselId();
    const url = bankAccountsApi.details.url({ vessel: vesselId, bankAccount: props.bankAccount.id });

    try {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Failed to fetch bank account details');
        }

        const data = await response.json();
        detailedBankAccount.value = data.bankAccount;
    } catch (err) {
        error.value = 'Failed to load bank account details';
    } finally {
        loading.value = false;
    }
};

// Watch for modal open to fetch details
watch(() => props.open, async (isOpen) => {
    if (isOpen && props.bankAccount) {
        await fetchBankAccountDetails();
    } else {
        // Reset state when modal closes
        detailedBankAccount.value = null;
        error.value = null;
    }
}, { immediate: true });

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'default';
        case 'inactive':
            return 'secondary';
        default:
            return 'outline';
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Bank Account Details</DialogTitle>
                <DialogDescription class="sr-only">
                    View detailed information about the bank account
                </DialogDescription>
            </DialogHeader>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="flex items-center space-x-2">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                    <span class="text-muted-foreground">Loading bank account details...</span>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="flex items-center justify-center py-8">
                <div class="text-center">
                    <p class="text-red-600 mb-4">{{ error }}</p>
                    <Button @click="fetchBankAccountDetails" variant="outline">
                        Try Again
                    </Button>
                </div>
            </div>

            <!-- Content -->
            <div v-else-if="detailedBankAccount" class="space-y-6">
                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Account Name</label>
                            <p class="text-lg font-semibold text-foreground">{{ detailedBankAccount.name }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Bank Name</label>
                            <p class="text-lg text-foreground">{{ detailedBankAccount.bank_name }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Account Number</label>
                            <p class="text-lg text-foreground">{{ detailedBankAccount.account_number || 'Not provided' }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-muted-foreground">IBAN</label>
                            <p class="text-lg font-mono text-foreground">{{ detailedBankAccount.iban || 'Not provided' }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Country</label>
                            <p class="text-lg font-semibold text-foreground">{{ detailedBankAccount.country ? `${detailedBankAccount.country.name} (${detailedBankAccount.country.code})` : 'Not provided' }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Initial Balance</label>
                            <p class="text-lg font-semibold text-green-600">{{ detailedBankAccount.formatted_initial_balance }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Current Balance</label>
                            <p class="text-lg font-semibold text-blue-600">{{ detailedBankAccount.formatted_current_balance }}</p>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Status</label>
                    <div class="mt-1">
                        <Badge :variant="getStatusVariant(detailedBankAccount.status)">
                            {{ detailedBankAccount.status_label }}
                        </Badge>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="detailedBankAccount.notes">
                    <label class="text-sm font-medium text-muted-foreground">Notes</label>
                    <p class="mt-1 text-foreground whitespace-pre-wrap">{{ detailedBankAccount.notes }}</p>
                </div>

                <!-- Timestamps -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t">
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Created</label>
                        <p class="text-sm text-foreground">{{ detailedBankAccount.created_at }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Last Updated</label>
                        <p class="text-sm text-foreground">{{ detailedBankAccount.updated_at }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end pt-4">
                    <Button variant="outline" @click="emit('close')">
                        Close
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
