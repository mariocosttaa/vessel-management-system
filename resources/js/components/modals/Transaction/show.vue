<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';

interface Transaction {
    id: number;
    transaction_number: string;
    type: string;
    type_label: string;
    amount: number;
    formatted_amount: string;
    vat_amount: number;
    formatted_vat_amount: string;
    total_amount: number;
    formatted_total_amount: string;
    currency: string;
    house_of_zeros: number;
    transaction_date: string;
    formatted_transaction_date: string;
    description: string | null;
    notes: string | null;
    reference: string | null;
    status: string;
    status_label: string;
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
    crew_member: {
        id: number;
        name: string;
        email: string;
    } | null;
    vat_profile: {
        id: number;
        name: string;
        percentage: number;
    } | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    open: boolean;
    transaction: Transaction;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
}>();


// Use transaction data from props directly - it already has all the necessary information
// No need for API call since the data is already loaded in the table
const detailedTransaction = computed(() => props.transaction);

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'completed':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
};

const getTypeVariant = (type: string) => {
    switch (type) {
        case 'income':
            return 'default';
        case 'expense':
            return 'destructive';
        case 'transfer':
            return 'secondary';
        default:
            return 'outline';
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="max-w-3xl">
            <DialogHeader>
                <DialogTitle>Transaction Details</DialogTitle>
                <DialogDescription class="sr-only">
                    View detailed information about the transaction
                </DialogDescription>
            </DialogHeader>

            <!-- Content -->
            <div v-if="detailedTransaction" class="space-y-6">
                <!-- Header with Transaction Number and Status -->
                <div class="flex items-center justify-between pb-4 border-b border-border">
                    <div>
                        <h3 class="text-lg font-semibold text-card-foreground">
                            Transaction #{{ detailedTransaction.transaction_number }}
                        </h3>
                        <p class="text-sm text-muted-foreground mt-1">
                            {{ detailedTransaction.formatted_transaction_date }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <Badge :variant="getTypeVariant(detailedTransaction.type)">
                            {{ detailedTransaction.type_label }}
                        </Badge>
                        <Badge :variant="getStatusVariant(detailedTransaction.status)">
                            {{ detailedTransaction.status_label }}
                        </Badge>
                    </div>
                </div>

                <!-- Financial Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Amount</label>
                            <p class="text-xl font-semibold text-card-foreground">
                                {{ detailedTransaction.formatted_amount }}
                            </p>
                        </div>

                        <div v-if="detailedTransaction.vat_profile">
                            <label class="text-sm font-medium text-muted-foreground">VAT Profile</label>
                            <p class="text-lg text-card-foreground">
                                {{ detailedTransaction.vat_profile.name }} ({{ detailedTransaction.vat_profile.percentage }}%)
                            </p>
                        </div>

                        <div v-if="detailedTransaction.vat_amount > 0">
                            <label class="text-sm font-medium text-muted-foreground">VAT Amount</label>
                            <p class="text-lg text-card-foreground">
                                {{ detailedTransaction.formatted_vat_amount }}
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Total Amount</label>
                            <p class="text-xl font-semibold text-card-foreground text-primary">
                                {{ detailedTransaction.formatted_total_amount }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Currency</label>
                            <p class="text-lg text-card-foreground">{{ detailedTransaction.currency }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Category</label>
                            <p class="text-lg text-card-foreground">
                                {{ detailedTransaction.category?.name || 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Related Information -->
                <div v-if="detailedTransaction.supplier || detailedTransaction.crew_member" class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-border">
                    <div v-if="detailedTransaction.supplier">
                        <label class="text-sm font-medium text-muted-foreground">Supplier</label>
                        <p class="text-lg text-card-foreground">
                            {{ detailedTransaction.supplier.company_name }}
                        </p>
                    </div>

                    <div v-if="detailedTransaction.crew_member">
                        <label class="text-sm font-medium text-muted-foreground">Crew Member</label>
                        <p class="text-lg text-card-foreground">
                            {{ detailedTransaction.crew_member.name }}
                            <span class="text-sm text-muted-foreground">
                                ({{ detailedTransaction.crew_member.email }})
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Description -->
                <div v-if="detailedTransaction.description" class="pt-4 border-t border-border">
                    <label class="text-sm font-medium text-muted-foreground">Description</label>
                    <p class="text-base text-card-foreground mt-1">{{ detailedTransaction.description }}</p>
                </div>

                <!-- Reference -->
                <div v-if="detailedTransaction.reference" class="pt-4 border-t border-border">
                    <label class="text-sm font-medium text-muted-foreground">Reference</label>
                    <p class="text-base text-card-foreground mt-1">{{ detailedTransaction.reference }}</p>
                </div>

                <!-- Notes -->
                <div v-if="detailedTransaction.notes" class="pt-4 border-t border-border">
                    <label class="text-sm font-medium text-muted-foreground">Notes</label>
                    <p class="text-base text-card-foreground mt-1 whitespace-pre-wrap">{{ detailedTransaction.notes }}</p>
                </div>

                <!-- Timestamps -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-border">
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Created At</label>
                        <p class="text-sm text-card-foreground">{{ detailedTransaction.created_at }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Updated At</label>
                        <p class="text-sm text-card-foreground">{{ detailedTransaction.updated_at }}</p>
                    </div>
                </div>
            </div>

            <!-- Fallback to props transaction if API call fails -->
            <div v-else-if="props.transaction" class="space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-border">
                    <div>
                        <h3 class="text-lg font-semibold text-card-foreground">
                            Transaction #{{ props.transaction.transaction_number }}
                        </h3>
                        <p class="text-sm text-muted-foreground mt-1">
                            {{ props.transaction.formatted_transaction_date }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <Badge :variant="getTypeVariant(props.transaction.type)">
                            {{ props.transaction.type_label }}
                        </Badge>
                        <Badge :variant="getStatusVariant(props.transaction.status)">
                            {{ props.transaction.status_label }}
                        </Badge>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Amount</label>
                        <p class="text-xl font-semibold text-card-foreground">
                            {{ props.transaction.formatted_amount }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Category</label>
                        <p class="text-lg text-card-foreground">
                            {{ props.transaction.category?.name || 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-4 border-t border-border">
                <Button variant="outline" @click="emit('close')">
                    Close
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

