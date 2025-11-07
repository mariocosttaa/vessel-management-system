<script setup lang="ts">
import BaseModal from '../BaseModal.vue';
import { Badge } from '@/components/ui/badge';

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

// Helper for status badge variant
const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active': return 'success';
        case 'inactive': return 'destructive';
        default: return 'default';
    }
};
</script>

<template>
    <BaseModal
        :open="open"
        title="Bank Account Details"
        size="2xl"
        :api-url="`/api/bank-accounts/${bankAccount.id}/details`"
        :enable-lazy-loading="true"
        :show-cancel-button="false"
        :show-confirm-button="false"
        @close="emit('close')"
        @data-loaded="(data) => console.log('Data loaded:', data)"
        @error="(error) => console.error('Error:', error)"
    >
        <template #default="{ data }">
            <div v-if="data?.bankAccount" class="space-y-6">
                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Account Name</label>
                            <p class="text-lg font-semibold">{{ data.bankAccount.name }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">Bank Name</label>
                            <p class="text-lg">{{ data.bankAccount.bank_name }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">Account Number</label>
                            <p class="text-lg">{{ data.bankAccount.account_number || 'Not provided' }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">IBAN</label>
                            <p class="text-lg">{{ data.bankAccount.iban || 'Not provided' }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Country</label>
                            <p class="text-lg">{{ data.bankAccount.country ? `${data.bankAccount.country.name} (${data.bankAccount.country.code})` : 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">Initial Balance</label>
                            <p class="text-lg">{{ data.bankAccount.formatted_initial_balance }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">Current Balance</label>
                            <p class="text-lg">{{ data.bankAccount.formatted_current_balance }}</p>
                        </div>
                    </div>
                </div>

                <!-- Status and Notes -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Status</label>
                        <Badge :variant="getStatusVariant(data.bankAccount.status)">
                            {{ data.bankAccount.status_label }}
                        </Badge>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Notes</label>
                        <p class="text-lg">{{ data.bankAccount.notes || 'No notes' }}</p>
                    </div>
                </div>

                <!-- Timestamps -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Created</label>
                        <p class="text-lg">{{ new Date(data.bankAccount.created_at).toLocaleDateString() }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Last Updated</label>
                        <p class="text-lg">{{ new Date(data.bankAccount.updated_at).toLocaleDateString() }}</p>
                    </div>
                </div>
            </div>
        </template>
    </BaseModal>
</template>
