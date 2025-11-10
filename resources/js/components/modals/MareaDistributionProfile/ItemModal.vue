<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';

interface DistributionItem {
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
    item: DistributionItem | null;
    existingItems: DistributionItem[];
    orderIndex: number;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
    'close': [];
    'save': [item: DistributionItem];
}>();

const form = ref<DistributionItem>({
    order_index: props.orderIndex,
    name: '',
    description: '',
    value_type: 'base_total_income',
    value_amount: null,
    reference_item_order_index: null,
    operation: 'set',
    reference_operation_item_order_index: null,
});

const errors = ref<Record<string, string>>({});

// Initialize form when modal opens or item changes
watch(() => [props.open, props.item], ([isOpen, item]) => {
    if (isOpen) {
        if (item) {
            // When editing, preserve reference_item_id if it exists, but prefer reference_item_order_index
            form.value = {
                ...item,
                // If we have reference_item_id but no reference_item_order_index, try to find it
                reference_item_order_index: item.reference_item_order_index ??
                    (item.reference_item_id && props.existingItems.find(i => i.order_index === item.reference_item_id)?.order_index) ??
                    item.reference_item_id ??
                    null,
            };
        } else {
            form.value = {
                order_index: props.orderIndex,
                name: '',
                description: '',
                value_type: 'base_total_income',
                value_amount: null,
                reference_item_order_index: null,
                operation: 'set',
                reference_operation_item_order_index: null,
            };
        }
        errors.value = {};
    }
}, { immediate: true });

const valueTypes = [
    { value: 'base_total_income', label: 'Total Income', dynamic: true, description: 'Use the total income amount' },
    { value: 'base_total_expense', label: 'Total Expenses', dynamic: true, description: 'Use the total expenses amount' },
    { value: 'fixed_amount', label: 'Fixed Amount', dynamic: false, description: 'Enter a fixed monetary value' },
    { value: 'percentage_of_income', label: 'Percentage of Income', dynamic: false, description: 'Calculate a percentage of total income' },
    { value: 'percentage_of_expense', label: 'Percentage of Expense', dynamic: false, description: 'Calculate a percentage of total expenses' },
    { value: 'reference_item', label: 'Reference Step', dynamic: false, description: 'Use the result from another step' },
];

const operations = [
    { value: 'set', label: 'Set (=)', symbol: '=', color: 'bg-blue-500 text-white' },
    { value: 'add', label: 'Add (+)', symbol: '+', color: 'bg-green-500 text-white' },
    { value: 'subtract', label: 'Subtract (-)', symbol: '-', color: 'bg-red-500 text-white' },
    { value: 'multiply', label: 'Multiply (×)', symbol: '×', color: 'bg-purple-500 text-white' },
    { value: 'divide', label: 'Divide (÷)', symbol: '÷', color: 'bg-orange-500 text-white' },
];

const selectedValueType = computed(() => {
    return valueTypes.find(type => type.value === form.value.value_type);
});

const isDynamicType = computed(() => {
    return selectedValueType.value?.dynamic || false;
});

const availableReferenceItems = computed(() => {
    return props.existingItems.filter((item, index) => item.order_index < props.orderIndex);
});

// Convert to Select component options format
const valueTypeOptions = computed(() => {
    return valueTypes.map(type => ({
        value: type.value,
        label: `${type.label} - ${type.description}`
    }));
});

const referenceItemOptions = computed(() => {
    const options = [{ value: null, label: 'Select a step' }];
    availableReferenceItems.value.forEach(item => {
        options.push({ value: item.order_index, label: `Step ${item.order_index}: ${item.name}` });
    });
    return options;
});

const getOperationInfo = (operation: string) => {
    return operations.find(op => op.value === operation) || operations[0];
};

const handleClose = () => {
    emit('update:open', false);
    emit('close');
};

const validate = () => {
    errors.value = {};

    if (!form.value.name || form.value.name.trim() === '') {
        errors.value.name = 'Name is required';
    } else if (form.value.name.length > 100) {
        errors.value.name = 'Name must be less than 100 characters';
    }

    if (form.value.description && form.value.description.length > 255) {
        errors.value.description = 'Description must be less than 255 characters';
    }

    if (!isDynamicType.value) {
        if (form.value.value_type === 'fixed_amount' || form.value.value_type.includes('percentage')) {
            if (form.value.value_amount === null || form.value.value_amount === undefined) {
                errors.value.value_amount = 'Value is required';
            } else if (form.value.value_amount < 0) {
                errors.value.value_amount = 'Value must be positive';
            }
        }

        if (form.value.value_type === 'reference_item') {
            if (form.value.reference_item_order_index === null || form.value.reference_item_order_index === undefined) {
                errors.value.reference_item_order_index = 'Please select a reference step';
            }
        }
    }

    return Object.keys(errors.value).length === 0;
};

const handleSave = () => {
    if (!validate()) {
        return;
    }

    emit('save', { ...form.value });
    handleClose();
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>
                    {{ item ? 'Edit Step' : 'Add Step' }} #{{ orderIndex }}
                </DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Name -->
                <div>
                    <Label for="item_name">Name *</Label>
                    <Input
                        id="item_name"
                        v-model="form.name"
                        placeholder="e.g., Calculate Commission"
                        maxlength="100"
                        :class="{ 'border-destructive': errors.name }"
                    />
                    <InputError v-if="errors.name" :message="errors.name" class="mt-1" />
                    <p class="text-xs text-muted-foreground mt-1">Brief name for this step (max 100 characters)</p>
                </div>

                <!-- Description -->
                <div>
                    <Label for="item_description">Description</Label>
                    <textarea
                        id="item_description"
                        v-model="form.description"
                        placeholder="Optional description of what this step does"
                        rows="2"
                        maxlength="255"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        :class="{ 'border-destructive': errors.description }"
                    />
                    <InputError v-if="errors.description" :message="errors.description" class="mt-1" />
                    <p class="text-xs text-muted-foreground mt-1">Optional description (max 255 characters)</p>
                </div>

                <!-- Value Type -->
                <div>
                    <Label for="value_type">Value Type *</Label>
                    <Select
                        id="value_type"
                        v-model="form.value_type"
                        :options="valueTypeOptions"
                        :error="!!errors.value_type"
                    />
                    <p class="text-xs text-muted-foreground mt-1">{{ selectedValueType?.description }}</p>
                </div>

                <!-- Value Amount (only for non-dynamic types) -->
                <div v-if="!isDynamicType">
                    <Label v-if="form.value_type === 'fixed_amount'" for="value_amount">Amount *</Label>
                    <Label v-else-if="form.value_type.includes('percentage')" for="value_amount">Percentage *</Label>
                    <Input
                        id="value_amount"
                        v-model.number="form.value_amount"
                        type="number"
                        :step="form.value_type.includes('percentage') ? 0.01 : 1"
                        :min="0"
                        :placeholder="form.value_type.includes('percentage') ? 'e.g., 10 for 10%' : 'Enter amount'"
                        :class="{ 'border-destructive': errors.value_amount }"
                    />
                    <InputError v-if="errors.value_amount" :message="errors.value_amount" class="mt-1" />
                    <p v-if="form.value_type.includes('percentage')" class="text-xs text-muted-foreground mt-1">
                        Enter percentage as a number (e.g., 10 for 10%, 5.5 for 5.5%)
                    </p>
                </div>

                <!-- Reference Item (for reference_item type) -->
                <div v-if="form.value_type === 'reference_item'">
                    <Label for="reference_item_order_index">Reference Step *</Label>
                    <Select
                        id="reference_item_order_index"
                        v-model="form.reference_item_order_index"
                        :options="referenceItemOptions"
                        placeholder="Select a step"
                        :error="!!errors.reference_item_order_index"
                    />
                    <InputError v-if="errors.reference_item_order_index" :message="errors.reference_item_order_index" class="mt-1" />
                    <p class="text-xs text-muted-foreground mt-1">
                        Select a previous step to use its result as the value
                    </p>
                </div>

                <!-- Operation -->
                <div>
                    <Label for="operation">Operation *</Label>
                    <div class="grid grid-cols-5 gap-2">
                        <button
                            v-for="op in operations"
                            :key="op.value"
                            type="button"
                            @click="form.operation = op.value"
                            :class="[
                                'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                                form.operation === op.value
                                    ? op.color
                                    : 'bg-muted text-muted-foreground hover:bg-muted/80'
                            ]"
                        >
                            {{ op.symbol }}
                        </button>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        Selected: <span :class="getOperationInfo(form.operation).color" class="px-2 py-1 rounded text-xs font-medium">
                            {{ getOperationInfo(form.operation).label }}
                        </span>
                    </p>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="handleClose">
                    Cancel
                </Button>
                <Button @click="handleSave" :class="getOperationInfo(form.operation).color">
                    <Icon name="save" class="w-4 h-4 mr-2" />
                    Save Step
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

