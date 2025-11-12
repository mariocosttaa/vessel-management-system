<script setup lang="ts">
import { computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { useI18n } from '@/composables/useI18n';

interface Props {
    open: boolean;
    vessel?: {
        id: number;
        name: string;
        registration_number: string;
        vessel_type: string;
        capacity?: number;
        year_built?: number;
        status: string;
        notes?: string;
        crew_members_count: number;
        transactions_count: number;
        created_at: string;
        updated_at: string;
    } | null;
}

const props = defineProps<Props>();
const { t } = useI18n();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const statusColor = computed(() => {
    switch (props.vessel?.status) {
        case 'active':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'maintenance':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        case 'inactive':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
});

const statusLabel = computed(() => {
    return props.vessel?.status ? props.vessel.status.charAt(0).toUpperCase() + props.vessel.status.slice(1) : '';
});

const vesselTypeLabel = computed(() => {
    return props.vessel?.vessel_type ? props.vessel.vessel_type.charAt(0).toUpperCase() + props.vessel.vessel_type.slice(1) : '';
});

const handleClose = () => {
    emit('update:open', false);
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ vessel?.name }}</DialogTitle>
                <DialogDescription>
                    {{ t('Vessel details and information') }}
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
                <div v-if="vessel" class="space-y-6">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Registration Number') }}</label>
                            <p class="text-sm text-card-foreground dark:text-card-foreground">{{ vessel.registration_number }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Vessel Type') }}</label>
                            <p class="text-sm text-card-foreground dark:text-card-foreground">{{ vesselTypeLabel }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground ">{{ t('Status') }}</label> <br>
                            <span :class="statusColor" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                {{ statusLabel }}
                            </span>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Capacity') }}</label>
                            <p class="text-sm text-card-foreground dark:text-card-foreground">
                                {{ vessel.capacity ? vessel.capacity.toLocaleString() : t('Not specified') }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Year Built') }}</label>
                            <p class="text-sm text-card-foreground dark:text-card-foreground">
                                {{ vessel.year_built || t('Not specified') }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Crew Members') }}</label>
                            <p class="text-sm text-card-foreground dark:text-card-foreground">{{ vessel.crew_members_count }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Transactions') }}</label>
                            <p class="text-sm text-card-foreground dark:text-card-foreground">{{ vessel.transactions_count }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Created') }}</label>
                            <p class="text-sm text-card-foreground dark:text-card-foreground">
                                {{ new Date(vessel.created_at).toLocaleDateString() }}
                            </p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="vessel.notes">
                        <label class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('Notes') }}</label>
                        <p class="text-sm text-card-foreground dark:text-card-foreground mt-1 p-3 bg-muted dark:bg-muted rounded-md">
                            {{ vessel.notes }}
                        </p>
                    </div>

                    <!-- Statistics -->
                    <div class="border-t border-border dark:border-border pt-6">
                        <h3 class="text-lg font-medium text-card-foreground dark:text-card-foreground mb-4">{{ t('Statistics') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="text-center p-4 bg-muted dark:bg-muted rounded-lg">
                                <div class="text-2xl font-bold text-card-foreground dark:text-card-foreground">{{ vessel.crew_members_count }}</div>
                                <div class="text-sm text-muted-foreground dark:text-muted-foreground">{{ t('Crew Members') }}</div>
                            </div>
                            <div class="text-center p-4 bg-muted dark:bg-muted rounded-lg">
                                <div class="text-2xl font-bold text-card-foreground dark:text-card-foreground">{{ vessel.transactions_count }}</div>
                                <div class="text-sm text-muted-foreground dark:text-muted-foreground">{{ t('Transactions') }}</div>
                            </div>
                            <div class="text-center p-4 bg-muted dark:bg-muted rounded-lg">
                                <div class="text-2xl font-bold text-card-foreground dark:text-card-foreground">
                                    {{ vessel.year_built ? new Date().getFullYear() - vessel.year_built : t('N/A') }}
                                </div>
                                <div class="text-sm text-muted-foreground dark:text-muted-foreground">{{ t('Years Old') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4">
                <Button
                    variant="outline"
                    @click="handleClose"
                >
                    {{ t('Close') }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
