<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { computed } from 'vue';

interface Transaction {
    id: number;
    transaction_number: string;
    type: string;
    type_label: string;
    amount: number;
    formatted_amount: string;
    amount_per_unit: number | null;
    price_per_unit?: number | null; // Backward compatibility
    formatted_amount_per_unit?: string | null;
    formatted_price_per_unit: string | null;
    quantity: number | null;
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
    files?: {
        id: number;
        src: string;
        name: string;
        size: number;
        type: string;
        size_human: string;
    }[];
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

const getFileIcon = (type: string): string => {
    const extension = type.toLowerCase();
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
        return 'image';
    }
    if (extension === 'pdf') {
        return 'file-text';
    }
    if (['doc', 'docx'].includes(extension)) {
        return 'file-text';
    }
    if (['xls', 'xlsx'].includes(extension)) {
        return 'file-text';
    }
    if (['txt', 'csv'].includes(extension)) {
        return 'file-text';
    }
    return 'file';
};

const openFile = (src: string) => {
    // Ensure the URL starts with / for relative paths
    const url = src.startsWith('/') ? src : `/${src}`;
    window.open(url, '_blank');
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="max-h-[90vh] overflow-y-auto" :style="{ maxWidth: '75vw', width: '100%' }">
            <DialogHeader>
                <DialogTitle>
                    Transaction #{{ detailedTransaction?.transaction_number || props.transaction?.transaction_number || '' }}
                </DialogTitle>
                <DialogDescription class="sr-only">
                    View detailed information about the transaction
                </DialogDescription>
            </DialogHeader>

            <!-- Content -->
            <div v-if="detailedTransaction" class="space-y-6">
                <!-- Header with Date and Status -->
                <div class="flex items-center justify-between pb-4 border-b border-border">
                    <div>
                        <p class="text-sm text-muted-foreground">
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

                <!-- Two Column Layout: Left (Details) | Right (Files) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column: Transaction Details -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Financial Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <!-- Price Per Unit and Quantity (if available) -->
                                <div v-if="(detailedTransaction.amount_per_unit ?? detailedTransaction.price_per_unit) != null && detailedTransaction.quantity != null && (detailedTransaction.amount_per_unit ?? detailedTransaction.price_per_unit)! > 0 && detailedTransaction.quantity > 0" class="space-y-3 p-4 border rounded-lg bg-muted/30">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground">Quantity</label>
                                        <p class="text-lg font-semibold text-card-foreground">
                                            {{ Math.round(detailedTransaction.quantity ?? 0) }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground">Price Per Unit</label>
                                        <p class="text-lg font-semibold text-card-foreground">
                                            {{ detailedTransaction.formatted_amount_per_unit || detailedTransaction.formatted_price_per_unit || detailedTransaction.formatted_amount }}
                                        </p>
                                    </div>

                                    <div v-if="detailedTransaction.vat_profile" class="pt-2 border-t space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground">VAT Profile</label>
                                        <p class="text-lg text-card-foreground">
                                            {{ detailedTransaction.vat_profile.name }} ({{ detailedTransaction.vat_profile.percentage }}%)
                                        </p>
                                    </div>

                                    <div v-if="detailedTransaction.vat_amount > 0" class="pt-2 border-t space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground">VAT Amount</label>
                                        <p class="text-lg text-card-foreground">
                                            {{ detailedTransaction.formatted_vat_amount }}
                                        </p>
                                    </div>

                                    <div class="pt-2 border-t-2 border-primary/50 space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground">Total Amount</label>
                                        <p class="text-2xl font-bold text-card-foreground text-primary">
                                            {{ detailedTransaction.formatted_total_amount }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Direct Amount (if no price per unit/quantity) -->
                                <div v-else class="space-y-4">
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

                                    <!-- Only show Total Amount if it's different from Amount (i.e., VAT exists) -->
                                    <div v-if="detailedTransaction.vat_amount > 0" class="pt-2 border-t">
                                        <label class="text-sm font-medium text-muted-foreground">Total Amount</label>
                                        <p class="text-xl font-semibold text-card-foreground text-primary">
                                            {{ detailedTransaction.formatted_total_amount }}
                                        </p>
                                    </div>
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

                    <!-- Right Column: Files Section -->
                    <div class="lg:col-span-1 space-y-4 border-l-0 lg:border-l lg:pl-6 pt-0 lg:pt-0">
                        <div class="space-y-3">
                            <label class="text-base font-semibold text-foreground">Attached Files</label>

                            <!-- Files List -->
                            <div v-if="detailedTransaction.files && detailedTransaction.files.length > 0" class="space-y-2 max-h-[500px] overflow-y-auto">
                                <div
                                    v-for="file in detailedTransaction.files"
                                    :key="file.id"
                                    class="flex flex-col gap-2 p-3 rounded-lg border border-border bg-card hover:bg-muted/50 transition-colors"
                                >
                                    <div class="flex items-start gap-2">
                                        <Icon
                                            :name="getFileIcon(file.type)"
                                            class="w-5 h-5 text-muted-foreground flex-shrink-0 mt-0.5"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-foreground truncate">
                                                {{ file.name }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ file.size_human }} · {{ file.type.toUpperCase() }}
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="openFile(file.src)"
                                        class="w-full text-xs"
                                    >
                                        <Icon name="external-link" class="w-3 h-3 mr-1" />
                                        View
                                    </Button>
                                </div>
                            </div>

                            <!-- No Files Message -->
                            <div v-else class="flex flex-col items-center justify-center p-8 rounded-lg border border-dashed border-border bg-muted/30">
                                <Icon name="file" class="w-12 h-12 text-muted-foreground mb-3" />
                                <p class="text-sm font-medium text-muted-foreground text-center">
                                    No files attached
                                </p>
                                <p class="text-xs text-muted-foreground text-center mt-1">
                                    This transaction has no attached documents
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fallback to props transaction if API call fails -->
            <div v-else-if="props.transaction" class="space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-border">
                    <div>
                        <p class="text-sm text-muted-foreground">
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

                <!-- Two Column Layout: Left (Details) | Right (Files) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column: Transaction Details -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <!-- Price Per Unit and Quantity (if available) -->
                                <div v-if="(props.transaction.amount_per_unit ?? props.transaction.price_per_unit) != null && props.transaction.quantity != null && (props.transaction.amount_per_unit ?? props.transaction.price_per_unit)! > 0 && props.transaction.quantity > 0" class="space-y-3 p-4 border rounded-lg bg-muted/30">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground">Quantity</label>
                                        <p class="text-lg font-semibold text-card-foreground">
                                            {{ Math.round(props.transaction.quantity ?? 0) }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground">Price Per Unit</label>
                                        <p class="text-lg font-semibold text-card-foreground">
                                            {{ (props.transaction as any).formatted_amount_per_unit || props.transaction.formatted_price_per_unit || props.transaction.formatted_amount }}
                                        </p>
                                    </div>

                                    <div v-if="props.transaction.vat_profile" class="pt-2 border-t space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground">VAT Profile</label>
                                        <p class="text-lg text-card-foreground">
                                            {{ props.transaction.vat_profile.name }} ({{ props.transaction.vat_profile.percentage }}%)
                                        </p>
                                    </div>

                                    <div v-if="props.transaction.vat_amount > 0" class="pt-2 border-t space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground">VAT Amount</label>
                                        <p class="text-lg text-card-foreground">
                                            {{ props.transaction.formatted_vat_amount }}
                                        </p>
                                    </div>

                                    <div class="pt-2 border-t-2 border-primary/50 space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground">Total Amount</label>
                                        <p class="text-2xl font-bold text-card-foreground text-primary">
                                            {{ props.transaction.formatted_total_amount }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Direct Amount (if no price per unit/quantity) -->
                                <div v-else class="space-y-4">
                                    <div>
                                        <label class="text-sm font-medium text-muted-foreground">Amount</label>
                                        <p class="text-xl font-semibold text-card-foreground">
                                            {{ props.transaction.formatted_amount }}
                                        </p>
                                    </div>

                                    <div v-if="props.transaction.vat_profile">
                                        <label class="text-sm font-medium text-muted-foreground">VAT Profile</label>
                                        <p class="text-lg text-card-foreground">
                                            {{ props.transaction.vat_profile.name }} ({{ props.transaction.vat_profile.percentage }}%)
                                        </p>
                                    </div>

                                    <div v-if="props.transaction.vat_amount > 0">
                                        <label class="text-sm font-medium text-muted-foreground">VAT Amount</label>
                                        <p class="text-lg text-card-foreground">
                                            {{ props.transaction.formatted_vat_amount }}
                                        </p>
                                    </div>

                                    <!-- Only show Total Amount if it's different from Amount (i.e., VAT exists) -->
                                    <div v-if="props.transaction.vat_amount > 0" class="pt-2 border-t">
                                        <label class="text-sm font-medium text-muted-foreground">Total Amount</label>
                                        <p class="text-xl font-semibold text-card-foreground text-primary">
                                            {{ props.transaction.formatted_total_amount }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Currency</label>
                                    <p class="text-lg text-card-foreground">{{ props.transaction.currency }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Category</label>
                                    <p class="text-lg text-card-foreground">
                                        {{ props.transaction.category?.name || 'N/A' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Files Section -->
                    <div class="lg:col-span-1 space-y-4 border-l-0 lg:border-l lg:pl-6 pt-0 lg:pt-0">
                        <div class="space-y-3">
                            <label class="text-base font-semibold text-foreground">Attached Files</label>

                            <!-- Files List -->
                            <div v-if="props.transaction.files && props.transaction.files.length > 0" class="space-y-2 max-h-[500px] overflow-y-auto">
                                <div
                                    v-for="file in props.transaction.files"
                                    :key="file.id"
                                    class="flex flex-col gap-2 p-3 rounded-lg border border-border bg-card hover:bg-muted/50 transition-colors"
                                >
                                    <div class="flex items-start gap-2">
                                        <Icon
                                            :name="getFileIcon(file.type)"
                                            class="w-5 h-5 text-muted-foreground flex-shrink-0 mt-0.5"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-foreground truncate">
                                                {{ file.name }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ file.size_human }} · {{ file.type.toUpperCase() }}
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="openFile(file.src)"
                                        class="w-full text-xs"
                                    >
                                        <Icon name="external-link" class="w-3 h-3 mr-1" />
                                        View
                                    </Button>
                                </div>
                            </div>

                            <!-- No Files Message -->
                            <div v-else class="flex flex-col items-center justify-center p-8 rounded-lg border border-dashed border-border bg-muted/30">
                                <Icon name="file" class="w-12 h-12 text-muted-foreground mb-3" />
                                <p class="text-sm font-medium text-muted-foreground text-center">
                                    No files attached
                                </p>
                                <p class="text-xs text-muted-foreground text-center mt-1">
                                    This transaction has no attached documents
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-4 border-t border-border">
                <Button type="button" variant="outline" @click="emit('close')">
                    Close
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

