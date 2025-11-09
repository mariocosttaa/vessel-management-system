<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';
import { usePermissions } from '@/composables/usePermissions';

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

interface DistributionProfileItem {
    id: number;
    order_index: number;
    name: string;
    description: string | null;
    value_type: string;
    value_amount: number | null;
    reference_item_id: number | null;
    operation: string;
    reference_operation_item_id: number | null;
}

interface DistributionProfile {
    id: number;
    name: string;
    description: string | null;
    is_default: boolean;
    is_system: boolean;
    items: DistributionProfileItem[];
    created_by: {
        id: number;
        name: string;
    } | null;
    created_at: string | null;
}

interface Props {
    profile: DistributionProfile;
}

const props = defineProps<Props>();
const { canEdit, canDelete } = usePermissions();

// Sort items by order_index
const sortedItems = computed(() => {
    return [...props.profile.items].sort((a, b) => a.order_index - b.order_index);
});

const operations = [
    { value: 'set', symbol: '=', color: 'bg-blue-500' },
    { value: 'add', symbol: '+', color: 'bg-green-500' },
    { value: 'subtract', symbol: '-', color: 'bg-red-500' },
    { value: 'multiply', symbol: '×', color: 'bg-purple-500' },
    { value: 'divide', symbol: '÷', color: 'bg-orange-500' },
];

const getOperationInfo = (operation: string) => {
    return operations.find(op => op.value === operation) || operations[0];
};

const valueTypeLabels: Record<string, string> = {
    base_total_income: 'Total Income',
    base_total_expense: 'Total Expenses',
    fixed_amount: 'Fixed Amount',
    percentage_of_income: '% of Income',
    percentage_of_expense: '% of Expense',
    reference_item: 'Ref Step',
};

const getValueTypeLabel = (valueType: string) => {
    return valueTypeLabels[valueType] || valueType;
};

const formatValue = (item: DistributionProfileItem): string => {
    if (item.value_type === 'base_total_income' || item.value_type === 'base_total_expense') {
        return 'Auto';
    }

    if (item.value_type === 'reference_item' && item.reference_item_id) {
        const refItem = props.profile.items.find(i => i.id === item.reference_item_id);
        return refItem ? `Step ${refItem.order_index}` : 'N/A';
    }

    if (item.value_type === 'percentage_of_income' || item.value_type === 'percentage_of_expense') {
        if (item.value_amount === null || item.value_amount === undefined) {
            return 'N/A';
        }
        return `${item.value_amount}%`;
    }

    if (item.value_type === 'fixed_amount') {
        if (item.value_amount === null || item.value_amount === undefined) {
            return 'N/A';
        }
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(item.value_amount);
    }

    return 'N/A';
};

const getReferenceItemName = (referenceItemId: number | null): string => {
    if (!referenceItemId) return 'N/A';
    const refItem = props.profile.items.find(item => item.id === referenceItemId);
    return refItem ? refItem.name : 'Unknown';
};

const editProfile = () => {
    router.visit(`/panel/${getCurrentVesselId()}/marea-distribution-profiles/${props.profile.id}/edit`);
};

const backToList = () => {
    router.visit(`/panel/${getCurrentVesselId()}/marea-distribution-profiles`);
};
</script>

<template>
    <Head :title="`Distribution Profile: ${profile.name}`" />

    <VesselLayout :breadcrumbs="[
        { title: 'Distribution Profiles', href: `/panel/${getCurrentVesselId()}/marea-distribution-profiles` },
        { title: profile.name, href: `/panel/${getCurrentVesselId()}/marea-distribution-profiles/${profile.id}` }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="max-w-4xl mx-auto w-full">
                <!-- Header Card -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6 mb-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">
                                    {{ profile.name }}
                                </h1>
                                <span
                                    v-if="profile.is_default"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary"
                                >
                                    Default
                                </span>
                                <span
                                    v-if="profile.is_system"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-foreground"
                                >
                                    System
                                </span>
                            </div>
                            <p v-if="profile.description" class="text-muted-foreground dark:text-muted-foreground mb-2">
                                {{ profile.description }}
                            </p>
                            <div class="flex items-center gap-4 text-sm text-muted-foreground dark:text-muted-foreground">
                                <span v-if="profile.created_by">
                                    Created by {{ profile.created_by.name }}
                                </span>
                                <span v-if="profile.created_at">
                                    on {{ new Date(profile.created_at).toLocaleDateString() }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="backToList"
                                class="inline-flex items-center px-3 py-1.5 text-sm text-muted-foreground hover:text-card-foreground transition-colors"
                            >
                                <Icon name="arrow-left" class="w-4 h-4 mr-2" />
                                Back
                            </button>
                            <button
                                v-if="canEdit('distribution-profiles') && !profile.is_system"
                                @click="editProfile"
                                class="inline-flex items-center px-3 py-1.5 text-sm bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg transition-colors"
                            >
                                <Icon name="edit" class="w-4 h-4 mr-2" />
                                Edit
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Items Card -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <h2 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-6">
                        Calculation Steps ({{ sortedItems.length }})
                    </h2>

                    <div v-if="sortedItems.length === 0" class="text-center py-16 text-muted-foreground dark:text-muted-foreground border-2 border-dashed border-border dark:border-border rounded-lg">
                        <Icon name="layers" class="w-16 h-16 mx-auto mb-4 opacity-30" />
                        <p class="text-base font-medium mb-2">No calculation steps</p>
                        <p class="text-sm">This profile doesn't have any calculation steps yet.</p>
                    </div>

                    <!-- Visual Flow Display -->
                    <div v-else class="space-y-4">
                        <div
                            v-for="(item, index) in sortedItems"
                            :key="item.id"
                            class="relative"
                        >
                            <!-- Connecting Line -->
                            <div v-if="index > 0" class="absolute left-8 top-0 w-0.5 h-6 bg-border dark:bg-border -translate-y-full z-0"></div>

                            <!-- Step Card -->
                            <div class="flex items-start gap-4">
                                <!-- Step Number and Operation -->
                                <div class="flex flex-col items-center gap-2 flex-shrink-0">
                                    <div class="w-16 h-16 rounded-full border-2 border-border dark:border-border bg-card dark:bg-card flex items-center justify-center relative z-10">
                                        <span class="text-xs font-bold text-muted-foreground">#{{ item.order_index }}</span>
                                    </div>
                                    <div
                                        :class="[
                                            'w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-lg',
                                            getOperationInfo(item.operation).color
                                        ]"
                                    >
                                        {{ getOperationInfo(item.operation).symbol }}
                                    </div>
                                </div>

                                <!-- Step Content -->
                                <div class="flex-1 border border-border dark:border-border rounded-lg p-4 bg-card dark:bg-card">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-card-foreground dark:text-card-foreground mb-1">
                                                {{ item.name }}
                                            </h4>
                                            <p v-if="item.description" class="text-sm text-muted-foreground dark:text-muted-foreground">
                                                {{ item.description }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm mt-3">
                                        <span class="text-muted-foreground dark:text-muted-foreground">
                                            Type: <span class="font-medium text-card-foreground">{{ getValueTypeLabel(item.value_type) }}</span>
                                        </span>
                                        <span class="text-muted-foreground dark:text-muted-foreground">
                                            Value: <span class="font-medium text-card-foreground">{{ formatValue(item) }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </VesselLayout>
</template>

