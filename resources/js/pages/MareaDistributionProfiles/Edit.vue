<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ItemModal from '@/components/modals/MareaDistributionProfile/ItemModal.vue';

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

interface DistributionProfileItem {
    id?: number;
    order_index: number;
    name: string;
    description: string;
    value_type: 'base_total_income' | 'base_total_expense' | 'fixed_amount' | 'percentage_of_income' | 'percentage_of_expense' | 'reference_item';
    value_amount: number | null;
    reference_item_id?: number | null;
    reference_item_order_index?: number | null;
    operation: 'set' | 'add' | 'subtract' | 'multiply' | 'divide';
    reference_operation_item_id?: number | null;
    reference_operation_item_order_index?: number | null;
}

interface DistributionProfile {
    id: number;
    name: string;
    description: string | null;
    is_default: boolean;
    is_system: boolean;
    items: DistributionProfileItem[];
}

interface Props {
    profile: DistributionProfile;
}

const props = defineProps<Props>();

// Initialize items and convert reference_item_id to reference_item_order_index
const initializeItems = (): DistributionProfileItem[] => {
    const items = props.profile.items.map((item) => ({ ...item }));

    // Create a map of item ID to order_index
    const idToOrderMap = new Map<number, number>();
    items.forEach(item => {
        if (item.id) {
            idToOrderMap.set(item.id, item.order_index);
        }
    });

    // Convert reference_item_id to reference_item_order_index
    return items.map(item => {
        if (item.reference_item_id && idToOrderMap.has(item.reference_item_id)) {
            item.reference_item_order_index = idToOrderMap.get(item.reference_item_id);
        }
        if (item.reference_operation_item_id && idToOrderMap.has(item.reference_operation_item_id)) {
            item.reference_operation_item_order_index = idToOrderMap.get(item.reference_operation_item_id);
        }
        return item;
    });
};

const form = useForm({
    name: props.profile.name,
    description: props.profile.description || '',
    is_default: props.profile.is_default,
    items: initializeItems(),
});

const showItemModal = ref(false);
const editingItemIndex = ref<number | null>(null);
const editingItem = ref<DistributionProfileItem | null>(null);

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

const getValueTypeLabel = (valueType: string) => {
    const types: Record<string, string> = {
        base_total_income: 'Total Income',
        base_total_expense: 'Total Expenses',
        fixed_amount: 'Fixed Amount',
        percentage_of_income: '% of Income',
        percentage_of_expense: '% of Expense',
        reference_item: 'Ref Step',
    };
    return types[valueType] || valueType;
};

const formatValue = (item: DistributionProfileItem) => {
    if (item.value_type === 'base_total_income' || item.value_type === 'base_total_expense') {
        return 'Auto';
    }
    if (item.value_type === 'reference_item') {
        const refIndex = item.reference_item_order_index ?? item.reference_item_id;
        return refIndex ? `Step ${refIndex}` : '—';
    }
    if (item.value_type.includes('percentage')) {
        return `${item.value_amount}%`;
    }
    return item.value_amount?.toLocaleString() || '—';
};

const openAddItemModal = () => {
    editingItemIndex.value = null;
    editingItem.value = null;
    showItemModal.value = true;
};

const openEditItemModal = (index: number) => {
    editingItemIndex.value = index;
    editingItem.value = { ...form.items[index] };
    showItemModal.value = true;
};

const handleSaveItem = (item: DistributionProfileItem) => {
    if (editingItemIndex.value !== null) {
        // Edit existing item - preserve order_index and id
        form.items[editingItemIndex.value] = {
            ...item,
            id: form.items[editingItemIndex.value].id, // Preserve ID
            order_index: form.items[editingItemIndex.value].order_index, // Preserve order_index
        };
    } else {
        // Add new item
        const newOrderIndex = form.items.length + 1;
        form.items.push({
            ...item,
            order_index: newOrderIndex,
        });
    }
    showItemModal.value = false;
    editingItemIndex.value = null;
    editingItem.value = null;
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
    // Reorder items
    form.items.forEach((item, idx) => {
        item.order_index = idx + 1;
    });
};

const moveItemUp = (index: number) => {
    if (index === 0) return;
    const item = form.items[index];
    form.items[index] = form.items[index - 1];
    form.items[index - 1] = item;
    // Reorder items
    form.items.forEach((item, idx) => {
        item.order_index = idx + 1;
    });
};

const moveItemDown = (index: number) => {
    if (index === form.items.length - 1) return;
    const item = form.items[index];
    form.items[index] = form.items[index + 1];
    form.items[index + 1] = item;
    // Reorder items
    form.items.forEach((item, idx) => {
        item.order_index = idx + 1;
    });
};

const handleSubmit = () => {
    form.put(`/panel/${getCurrentVesselId()}/marea-distribution-profiles/${props.profile.id}`, {
        onSuccess: () => {
            router.visit(`/panel/${getCurrentVesselId()}/marea-distribution-profiles`);
        },
    });
};

const handleCancel = () => {
    router.visit(`/panel/${getCurrentVesselId()}/marea-distribution-profiles`);
};
</script>

<template>
    <Head :title="`Edit Distribution Profile: ${profile.name}`" />

    <VesselLayout :breadcrumbs="[
        { title: 'Distribution Profiles', href: `/panel/${getCurrentVesselId()}/marea-distribution-profiles` },
        { title: profile.name, href: `/panel/${getCurrentVesselId()}/marea-distribution-profiles/${profile.id}` },
        { title: 'Edit', href: `/panel/${getCurrentVesselId()}/marea-distribution-profiles/${profile.id}/edit` }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="max-w-6xl mx-auto w-full">
                <!-- Header Card -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6 mb-6">
                    <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground mb-2">
                        Edit Distribution Profile: {{ profile.name }}
                    </h1>
                    <p class="text-muted-foreground dark:text-muted-foreground">
                        Update the distribution profile configuration
                    </p>
                </div>

                <!-- Form Card -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Profile Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <Label for="name" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                    Name *
                                </Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Enter profile name"
                                    :class="{ 'border-destructive dark:border-destructive': form.errors.name }"
                                />
                                <InputError :message="form.errors.name" class="mt-1" />
                            </div>

                            <!-- Is Default -->
                            <div class="flex items-center gap-2 pt-8">
                                <input
                                    id="is_default"
                                    v-model="form.is_default"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                                />
                                <Label for="is_default" class="text-sm font-medium text-card-foreground dark:text-card-foreground cursor-pointer">
                                    Set as default profile
                                </Label>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <Label for="description" class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                Description
                            </Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                placeholder="Enter profile description"
                                class="flex min-h-[80px] w-full rounded-md border border-input dark:border-input bg-background dark:bg-background px-3 py-2 text-sm text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive dark:border-destructive': form.errors.description }"
                            ></textarea>
                            <InputError :message="form.errors.description" class="mt-1" />
                        </div>

                        <!-- Items Section -->
                        <div class="border-t border-border dark:border-border pt-6">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground">
                                        Calculation Steps
                                    </h3>
                                    <p class="text-sm text-muted-foreground dark:text-muted-foreground mt-1">
                                        Define the steps for your distribution calculation
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    @click="openAddItemModal"
                                    class="inline-flex items-center px-4 py-2"
                                >
                                    <Icon name="plus" class="w-4 h-4 mr-2" />
                                    Add Step
                                </Button>
                            </div>

                            <InputError :message="form.errors.items" class="mb-4" />

                            <!-- Empty State -->
                            <div v-if="form.items.length === 0" class="text-center py-16 text-muted-foreground dark:text-muted-foreground border-2 border-dashed border-border dark:border-border rounded-lg">
                                <Icon name="layers" class="w-16 h-16 mx-auto mb-4 opacity-30" />
                                <p class="text-base font-medium mb-2">No calculation steps yet</p>
                                <p class="text-sm mb-4">Click "Add Step" to start building your distribution calculation.</p>
                                <Button type="button" variant="outline" @click="openAddItemModal">
                                    <Icon name="plus" class="w-4 h-4 mr-2" />
                                    Add First Step
                                </Button>
                            </div>

                            <!-- Visual Flow Display -->
                            <div v-else class="space-y-4">
                                <div
                                    v-for="(item, index) in form.items"
                                    :key="item.id || index"
                                    class="relative"
                                >
                                    <!-- Connecting Line -->
                                    <div v-if="index > 0" class="absolute left-8 top-0 w-0.5 h-6 bg-border dark:bg-border -translate-y-full"></div>

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
                                        <div
                                            class="flex-1 border border-border dark:border-border rounded-lg p-4 bg-card dark:bg-card hover:shadow-md transition-shadow cursor-pointer"
                                            @click="openEditItemModal(index)"
                                        >
                                            <div class="flex items-start justify-between mb-2">
                                                <div class="flex-1">
                                                    <h4 class="font-semibold text-card-foreground dark:text-card-foreground mb-1">
                                                        {{ item.name || 'Unnamed Step' }}
                                                    </h4>
                                                    <p v-if="item.description" class="text-sm text-muted-foreground dark:text-muted-foreground line-clamp-2">
                                                        {{ item.description }}
                                                    </p>
                                                </div>
                                                <div class="flex gap-1 ml-4">
                                                    <button
                                                        type="button"
                                                        @click.stop="moveItemUp(index)"
                                                        :disabled="index === 0"
                                                        class="p-1.5 rounded hover:bg-muted/50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                                                        title="Move up"
                                                    >
                                                        <Icon name="arrow-up" class="w-4 h-4" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click.stop="moveItemDown(index)"
                                                        :disabled="index === form.items.length - 1"
                                                        class="p-1.5 rounded hover:bg-muted/50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                                                        title="Move down"
                                                    >
                                                        <Icon name="arrow-down" class="w-4 h-4" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click.stop="removeItem(index)"
                                                        class="p-1.5 rounded hover:bg-destructive/10 text-destructive transition-colors"
                                                        title="Remove step"
                                                    >
                                                        <Icon name="trash" class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4 text-sm">
                                                <span class="text-muted-foreground dark:text-muted-foreground">
                                                    Type: <span class="font-medium">{{ getValueTypeLabel(item.value_type) }}</span>
                                                </span>
                                                <span class="text-muted-foreground dark:text-muted-foreground">
                                                    Value: <span class="font-medium">{{ formatValue(item) }}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-6 border-t border-border dark:border-border">
                            <Button
                                type="button"
                                variant="outline"
                                @click="handleCancel"
                            >
                                <Icon name="arrow-left" class="w-4 h-4 mr-2" />
                                Cancel
                            </Button>

                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                <Icon
                                    v-if="form.processing"
                                    name="loader-circle"
                                    class="w-4 h-4 mr-2 animate-spin"
                                />
                                <Icon
                                    v-else
                                    name="save"
                                    class="w-4 h-4 mr-2"
                                />
                                {{ form.processing ? 'Updating...' : 'Update Profile' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Item Modal -->
        <ItemModal
            :open="showItemModal"
            :item="editingItem"
            :existing-items="form.items"
            :order-index="editingItemIndex !== null && form.items[editingItemIndex] ? form.items[editingItemIndex].order_index : form.items.length + 1"
            @update:open="showItemModal = $event"
            @close="showItemModal = false"
            @save="handleSaveItem"
        />
    </VesselLayout>
</template>
