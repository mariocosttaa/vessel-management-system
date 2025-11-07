<script setup lang="ts">
import { computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';

interface CrewMember {
    id: number;
    name: string;
    email?: string;
    phone?: string;
    date_of_birth?: string;
    formatted_date_of_birth?: string;
    hire_date: string;
    formatted_hire_date: string;
    position_id: number;
    position_name: string;
    salary_compensation?: {
        compensation_type: string;
        fixed_amount: number | null;
        percentage: number | null;
        currency: string;
        payment_frequency: string;
    };
    status: string;
    status_label: string;
    status_color: string;
    notes?: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    open: boolean;
    crewMember?: CrewMember | null;
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

const getStatusBadgeClass = (status: string) => {
    const baseClass = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium';

    switch (status) {
        case 'active':
            return `${baseClass} bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400`;
        case 'inactive':
            return `${baseClass} bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400`;
        case 'on_leave':
            return `${baseClass} bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400`;
        default:
            return `${baseClass} bg-muted text-muted-foreground dark:bg-muted dark:text-muted-foreground`;
    }
};

const formatSalaryAmount = (compensation: any) => {
    if (!compensation) return 'Not specified';

    if (compensation.compensation_type === 'fixed' && compensation.fixed_amount) {
        const amount = (compensation.fixed_amount / 100).toFixed(2);
        return `${amount} ${compensation.currency}`;
    } else if (compensation.compensation_type === 'percentage' && compensation.percentage) {
        return `${compensation.percentage}% of revenue`;
    }

    return 'Not specified';
};

const formatPaymentFrequency = (frequency: string) => {
    const frequencies: Record<string, string> = {
        'weekly': 'Weekly',
        'bi_weekly': 'Bi-weekly',
        'monthly': 'Monthly',
        'quarterly': 'Quarterly',
        'annually': 'Annually'
    };
    return frequencies[frequency] || frequency;
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Crew Member Details</DialogTitle>
                <DialogDescription>
                    View detailed information about this crew member
                </DialogDescription>
            </DialogHeader>

            <div v-if="crewMember" class="py-4">
                <div class="space-y-6">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">
                                Basic Information
                            </h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Full Name</dt>
                                    <dd class="text-sm text-card-foreground dark:text-card-foreground">{{ crewMember.name }}</dd>
                                </div>
                                <div v-if="crewMember.email">
                                    <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Email</dt>
                                    <dd class="text-sm text-card-foreground dark:text-card-foreground">{{ crewMember.email }}</dd>
                                </div>
                                <div v-if="crewMember.phone">
                                    <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Phone</dt>
                                    <dd class="text-sm text-card-foreground dark:text-card-foreground">{{ crewMember.phone }}</dd>
                                </div>
                                <div v-if="crewMember.date_of_birth">
                                    <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Date of Birth</dt>
                                    <dd class="text-sm text-card-foreground dark:text-card-foreground">
                                        {{ crewMember.formatted_date_of_birth || formatDate(crewMember.date_of_birth) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">
                                Employment Information
                            </h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Position</dt>
                                    <dd class="text-sm text-card-foreground dark:text-card-foreground">{{ crewMember.position_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Hire Date</dt>
                                    <dd class="text-sm text-card-foreground dark:text-card-foreground">
                                        {{ crewMember.formatted_hire_date || formatDate(crewMember.hire_date) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Status</dt>
                                    <dd class="text-sm">
                                        <span :class="getStatusBadgeClass(crewMember.status)">
                                            {{ crewMember.status_label }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Salary Information -->
                    <div v-if="crewMember.salary_compensation">
                        <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">
                            Salary Information
                        </h3>
                        <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Compensation Type</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground">
                                    {{ crewMember.salary_compensation.compensation_type === 'fixed' ? 'Fixed Salary' : 'Percentage of Revenue' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Amount</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground">
                                    {{ formatSalaryAmount(crewMember.salary_compensation) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Payment Frequency</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground">
                                    {{ formatPaymentFrequency(crewMember.salary_compensation.payment_frequency) }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Notes -->
                    <div v-if="crewMember.notes">
                        <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-4">
                            Notes
                        </h3>
                        <p class="text-sm text-card-foreground dark:text-card-foreground bg-muted/50 dark:bg-muted/50 p-4 rounded-lg">
                            {{ crewMember.notes }}
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
                                    {{ formatDate(crewMember.created_at) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Last Updated</dt>
                                <dd class="text-sm text-card-foreground dark:text-card-foreground">
                                    {{ formatDate(crewMember.updated_at) }}
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
