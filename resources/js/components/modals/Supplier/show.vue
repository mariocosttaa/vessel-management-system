<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

interface Supplier {
    id: number;
    company_name: string;
    description?: string;
    email?: string;
    phone?: string;
    address?: string;
    notes?: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    open: boolean;
    supplier?: Supplier | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const handleClose = () => {
    emit('update:open', false);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>Supplier Details</DialogTitle>
                <DialogDescription>
                    View detailed information about this supplier
                </DialogDescription>
            </DialogHeader>

            <div v-if="supplier" class="py-4">
                <div class="space-y-6">
                    <!-- Basic Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">
                            Company Information
                        </h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Company Name</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground">{{ supplier.company_name }}</dd>
                            </div>
                            <div v-if="supplier.description">
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Description</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground whitespace-pre-line">{{ supplier.description }}</dd>
                            </div>
                            <div v-if="supplier.email">
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Email</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground">{{ supplier.email }}</dd>
                            </div>
                            <div v-if="supplier.phone">
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Phone</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground">{{ supplier.phone }}</dd>
                            </div>
                            <div v-if="supplier.address">
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Address</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground whitespace-pre-line">{{ supplier.address }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Notes -->
                    <div v-if="supplier.notes">
                        <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">
                            Notes
                        </h3>
                        <p class="text-sm text-card-foreground dark:text-card-foreground bg-muted/50 dark:bg-muted/50 p-4 rounded-lg">
                            {{ supplier.notes }}
                        </p>
                    </div>

                    <!-- System Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">
                            System Information
                        </h3>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Created</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground">
                                    {{ formatDate(supplier.created_at) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Last Updated</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground">
                                    {{ formatDate(supplier.updated_at) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4">
                <Button
                    variant="outline"
                    @click="handleClose"
                >
                    Close
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
