<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import ItemModal from '@/components/modals/MareaDistributionProfile/ItemModal.vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';

interface DistributionItem {
    id?: number;
    profile_item_id?: number;
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

interface Props {
    open: boolean;
    mareaId: number;
    distributionItems: DistributionItem[];
    distributionProfileItems: DistributionItem[];
    vesselId: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
    'close': [];
    'success': [];
}>();

const { t } = useI18n();

const form = useForm<{ items: DistributionItem[] }>({
    items: [] as DistributionItem[],
});

// Initialize form with existing items or profile items
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        // Use marea-specific items if they exist, otherwise use profile items
        const itemsToUse = props.distributionItems.length > 0
            ? JSON.parse(JSON.stringify(props.distributionItems))
            : JSON.parse(JSON.stringify(props.distributionProfileItems));

        // Convert reference_item_id to reference_item_order_index for display
        // Create a map of item IDs to order_index for marea items
        const idToOrderMap = new Map<number, number>();
        itemsToUse.forEach((item: DistributionItem) => {
            if (item.id) {
                idToOrderMap.set(item.id, item.order_index);
            }
        });

        // Convert reference_item_id to reference_item_order_index
        form.items = itemsToUse.map((item: DistributionItem) => {
            if (item.reference_item_id && idToOrderMap.has(item.reference_item_id)) {
                item.reference_item_order_index = idToOrderMap.get(item.reference_item_id);
            }
            if (item.reference_operation_item_id && idToOrderMap.has(item.reference_operation_item_id)) {
                item.reference_operation_item_order_index = idToOrderMap.get(item.reference_operation_item_id);
            }
            return item;
        });
    }
});

const valueTypes = [
    { value: 'base_total_income', label: t('Total Income'), dynamic: true, description: t('Use the total income amount') },
    { value: 'base_total_expense', label: t('Total Expenses'), dynamic: true, description: t('Use the total expenses amount') },
    { value: 'fixed_amount', label: t('Fixed Amount'), dynamic: false, description: t('Enter a fixed monetary value') },
    { value: 'percentage_of_income', label: t('Percentage of Income'), dynamic: false, description: t('Calculate a percentage of total income') },
    { value: 'percentage_of_expense', label: t('Percentage of Expense'), dynamic: false, description: t('Calculate a percentage of total expenses') },
    { value: 'reference_item', label: t('Reference Step'), dynamic: false, description: t('Use the result from another step') },
];

const getValueTypeLabel = (valueType: string) => {
    const types: Record<string, string> = {
        base_total_income: t('Total Income'),
        base_total_expense: t('Total Expenses'),
        fixed_amount: t('Fixed Amount'),
        percentage_of_income: t('% of Income'),
        percentage_of_expense: t('% of Expense'),
        reference_item: t('Ref Step'),
    };
    return types[valueType] || valueType;
};

const formatValue = (item: DistributionItem) => {
    if (item.value_type === 'base_total_income' || item.value_type === 'base_total_expense') {
        return t('Auto');
    }
    if (item.value_type === 'reference_item') {
        const refIndex = item.reference_item_order_index ?? item.reference_item_id;
        return refIndex ? `${t('Step')} ${refIndex}` : '—';
    }
    if (item.value_type.includes('percentage')) {
        return `${item.value_amount}%`;
    }
    return item.value_amount?.toLocaleString() || '—';
};

const operations = [
    { value: 'set', label: t('Set (=)'), symbol: '=', color: 'bg-blue-500' },
    { value: 'add', label: t('Add (+)'), symbol: '+', color: 'bg-green-500' },
    { value: 'subtract', label: t('Subtract (-)'), symbol: '-', color: 'bg-red-500' },
    { value: 'multiply', label: t('Multiply (×)'), symbol: '×', color: 'bg-purple-500' },
    { value: 'divide', label: t('Divide (÷)'), symbol: '÷', color: 'bg-orange-500' },
];

const getOperationInfo = (operation: string) => {
    return operations.find(op => op.value === operation) || operations[0];
};

const isDynamicType = (valueType: string) => {
    return valueTypes.find(type => type.value === valueType)?.dynamic || false;
};

const handleClose = () => {
    emit('update:open', false);
    emit('close');
};

const handleSave = () => {
    // Ensure all items have the correct structure for the backend
    const itemsToSave = form.items.map(item => {
        const itemData: any = {
            order_index: item.order_index,
            name: item.name,
            description: item.description || '',
            value_type: item.value_type,
            value_amount: item.value_amount,
            operation: item.operation,
        };

        // Handle reference_item_order_index -> reference_item_id mapping will be done by backend
        if (item.value_type === 'reference_item' && item.reference_item_order_index) {
            itemData.reference_item_order_index = item.reference_item_order_index;
        }

        // Handle reference_operation_item_order_index
        if (item.reference_operation_item_order_index) {
            itemData.reference_operation_item_order_index = item.reference_operation_item_order_index;
        }

        // Preserve profile_item_id if it exists (for items that override profile items)
        if (item.profile_item_id) {
            itemData.profile_item_id = item.profile_item_id;
        }

        return itemData;
    });

    form.transform(() => ({ items: itemsToSave })).post(`/panel/${props.vesselId}/mareas/${props.mareaId}/distribution-items`, {
        onSuccess: () => {
            handleClose();
            emit('success');
        },
    });
};

const showItemEditModal = ref(false);
const editingItemIndex = ref<number | null>(null);
const editingItem = ref<DistributionItem | null>(null);

const openAddItemModal = () => {
    editingItemIndex.value = null;
    editingItem.value = null;
    showItemEditModal.value = true;
};

const openEditItemModal = (index: number) => {
    editingItemIndex.value = index;
    editingItem.value = { ...form.items[index] };
    showItemEditModal.value = true;
};

const handleSaveItem = (item: DistributionItem) => {
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
    showItemEditModal.value = false;
    editingItemIndex.value = null;
    editingItem.value = null;
};

const addItem = () => {
    openAddItemModal();
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
    form.items.forEach((item, idx) => {
        item.order_index = idx + 1;
    });
};

const moveItemUp = (index: number) => {
    if (index === 0) return;
    const item = form.items[index];
    form.items[index] = form.items[index - 1];
    form.items[index - 1] = item;
    form.items.forEach((item, idx) => {
        item.order_index = idx + 1;
    });
};

const moveItemDown = (index: number) => {
    if (index === form.items.length - 1) return;
    const item = form.items[index];
    form.items[index] = form.items[index + 1];
    form.items[index + 1] = item;
    form.items.forEach((item, idx) => {
        item.order_index = idx + 1;
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{{ t('Edit Calculation Override') }}</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    {{ t('Customize the calculation steps for this marea. These overrides will be used instead of the distribution profile.') }}
                </p>

                <!-- Visual Flow Display -->
                <div v-if="form.items.length > 0" class="space-y-4">
                    <div
                        v-for="(item, index) in form.items"
                        :key="item.id || index"
                        class="relative"
                    >
                        <!-- Connecting Line -->
                        <div v-if="index > 0" class="absolute left-8 top-0 w-0.5 h-6 bg-border dark:bg-border -translate-y-full z-0"></div>

                        <!-- Step Card -->
                        <div class="flex items-start gap-4">
                            <!-- Step Number and Operation -->
                            <div class="flex flex-col items-center gap-2 flex-shrink-0">
                                <div class="w-14 h-14 rounded-full border-2 border-border dark:border-border bg-card dark:bg-card flex items-center justify-center relative z-10">
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
                                            {{ item.name || t('Unnamed Step') }}
                                        </h4>
                                        <p v-if="item.description" class="text-sm text-muted-foreground dark:text-muted-foreground line-clamp-2 mb-2">
                                            {{ item.description }}
                                        </p>
                                        <div class="flex items-center gap-3 text-xs text-muted-foreground dark:text-muted-foreground">
                                            <span>{{ getValueTypeLabel(item.value_type) }}</span>
                                            <span v-if="item.value_type !== 'base_total_income' && item.value_type !== 'base_total_expense'">• {{ formatValue(item) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex gap-1 ml-4">
                                        <button
                                            type="button"
                                            @click.stop="moveItemUp(index)"
                                            :disabled="index === 0"
                                            class="p-1.5 rounded hover:bg-muted/50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                                            :title="t('Move up')"
                                        >
                                            <Icon name="arrow-up" class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click.stop="moveItemDown(index)"
                                            :disabled="index === form.items.length - 1"
                                            class="p-1.5 rounded hover:bg-muted/50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                                            :title="t('Move down')"
                                        >
                                            <Icon name="arrow-down" class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click.stop="removeItem(index)"
                                            class="p-1.5 rounded hover:bg-destructive/10 text-destructive transition-colors"
                                            :title="t('Remove step')"
                                        >
                                            <Icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-12 text-muted-foreground border-2 border-dashed border-border rounded-lg">
                    <Icon name="layers" class="w-12 h-12 mx-auto mb-4 opacity-30" />
                    <p class="text-sm font-medium mb-2">{{ t('No calculation steps') }}</p>
                    <p class="text-xs mb-4">{{ t('Click "Add Step" to start creating custom calculation steps.') }}</p>
                </div>

                <Button type="button" variant="outline" @click="addItem" class="w-full">
                    <Icon name="plus" class="w-4 h-4 mr-2" />
                    {{ t('Add Step') }}
                </Button>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="handleClose" :disabled="form.processing">
                    {{ t('Cancel') }}
                </Button>
                <Button @click="handleSave" :disabled="form.processing">
                    <Icon v-if="form.processing" name="loader-2" class="w-4 h-4 mr-2 animate-spin" />
                    {{ t('Save Override') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Item Edit Modal -->
    <ItemModal
        :open="showItemEditModal"
        :item="editingItem"
        :existing-items="form.items"
        :order-index="editingItemIndex !== null && form.items[editingItemIndex] ? form.items[editingItemIndex].order_index : form.items.length + 1"
        @update:open="showItemEditModal = $event"
        @close="showItemEditModal = false"
        @save="handleSaveItem"
    />
</template>

