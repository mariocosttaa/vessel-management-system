<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/composables/useI18n';

interface Props {
    open: boolean;
    mareaStatus: string;
    preSelectSection?: 'expensesWithSalary' | 'expenses' | 'incomes' | 'crew' | 'quantity' | 'salary';
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
    'close': [];
    'download': (sections: {
        expensesWithSalary: boolean;
        expenses: boolean;
        incomes: boolean;
        crew: boolean;
        quantity: boolean;
        salary: boolean;
        enableColors: boolean;
    }) => void;
}>();

const { t } = useI18n();

// Expense type selection (mutually exclusive - only one can be selected)
const expensesWithSalary = ref<boolean>(true);
const expenses = ref<boolean>(false);

// Other sections (switches)
const includeIncomes = ref<boolean>(false);
const includeCrew = ref<boolean>(false);
const includeQuantity = ref<boolean>(false);
const includeSalary = ref<boolean>(false);

// Color toggle (enable/disable colors in PDF) - default to false (unchecked)
const enableColors = ref<boolean>(false);

// Check if marea is returned or closed (for quantity section)
const canShowQuantity = computed(() => {
    return props.mareaStatus === 'returned' || props.mareaStatus === 'closed';
});

// Disable salary switch when expenses with salary is selected (salary is included)
const isSalaryDisabled = computed(() => {
    return expensesWithSalary.value;
});

// Make expense selection mutually exclusive
watch(expensesWithSalary, (newValue) => {
    if (newValue) {
        expenses.value = false;
        // Disable salary when expenses with salary is selected
        if (includeSalary.value) {
            includeSalary.value = false;
        }
    }
});

watch(expenses, (newValue) => {
    if (newValue) {
        expensesWithSalary.value = false;
    }
});

// Validation - at least one section must be selected
const isValid = computed(() => {
    return expensesWithSalary.value || expenses.value || includeIncomes.value || includeCrew.value || includeQuantity.value || includeSalary.value;
});

const handleDownload = () => {
    if (!isValid.value) return;

    const sections = {
        expensesWithSalary: expensesWithSalary.value,
        expenses: expenses.value,
        incomes: includeIncomes.value,
        crew: includeCrew.value,
        quantity: includeQuantity.value && canShowQuantity.value,
        salary: includeSalary.value,
        enableColors: enableColors.value,
    };

    emit('download', sections);
};

const handleClose = () => {
    emit('close');
};

// Reset form when modal opens and handle pre-selection
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        // Pre-select section if provided
        if (props.preSelectSection) {
            // Reset all to default first
            expensesWithSalary.value = false;
            expenses.value = false;
            includeIncomes.value = false;
            includeCrew.value = false;
            includeQuantity.value = false;
            includeSalary.value = false;
            enableColors.value = false; // Reset colors to unchecked

            switch (props.preSelectSection) {
                case 'expensesWithSalary':
                    expensesWithSalary.value = true;
                    expenses.value = false;
                    break;
                case 'expenses':
                    expenses.value = true;
                    expensesWithSalary.value = false;
                    break;
                case 'incomes':
                    includeIncomes.value = true;
                    // Don't select expenses when incomes is pre-selected
                    expensesWithSalary.value = false;
                    expenses.value = false;
                    break;
                case 'crew':
                    includeCrew.value = true;
                    // Don't select expenses when crew is pre-selected
                    expensesWithSalary.value = false;
                    expenses.value = false;
                    break;
                case 'quantity':
                    includeQuantity.value = true;
                    // Don't select expenses when quantity is pre-selected
                    expensesWithSalary.value = false;
                    expenses.value = false;
                    break;
                case 'salary':
                    includeSalary.value = true;
                    // Don't select expenses when salary is pre-selected
                    expensesWithSalary.value = false;
                    expenses.value = false;
                    break;
            }
        } else {
            // Default behavior when clicking PDF button: activate all switches except "Expenses"
            // Choose "Expenses with Salary" (not regular "Expenses")
            // Disable "Salary Payments" because it's included in "Expenses with Salary"
            // Colors should NOT be enabled by default
            expensesWithSalary.value = true; // Choose expenses with salary
            expenses.value = false; // NOT regular expenses
            includeIncomes.value = true; // Activate incomes
            includeCrew.value = true; // Activate crew
            includeQuantity.value = canShowQuantity.value; // Activate quantity if available
            includeSalary.value = false; // Disable salary (included in expenses with salary)
            enableColors.value = false; // Colors NOT enabled by default
        }
    }
});
</script>

<template>
    <Dialog :open="open" @update:open="(val) => !val && handleClose()">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ t('Generate PDF') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Select sections to include in the PDF') }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-6 py-4">
                <!-- Always Included Section -->
                <div class="space-y-2 p-3 border rounded-md bg-muted/50">
                    <Label class="text-sm font-medium text-muted-foreground">
                        {{ t('Always included') }}
                    </Label>
                    <p class="text-xs text-muted-foreground mt-1">
                        {{ t('Marea Information') }} ({{ t('Marea Number') }}, {{ t('Status') }}, {{ t('Dates') }})
                    </p>
                </div>

                <!-- Expenses Selection (Mutually Exclusive Switches) -->
                <div class="space-y-3">
                    <Label class="text-sm font-medium">
                        {{ t('Expenses') }}
                    </Label>
                    <div class="space-y-3 p-3 border rounded-md bg-card">
                        <div class="flex items-center justify-between">
                            <Label :for="'expenses-with-salary'" class="text-sm font-medium cursor-pointer">
                                {{ t('Expenses with Salary') }}
                            </Label>
                            <Switch
                                :id="'expenses-with-salary'"
                                v-model:checked="expensesWithSalary"
                            />
                        </div>
                        <div class="flex items-center justify-between">
                            <Label :for="'expenses-only'" class="text-sm font-medium cursor-pointer">
                                {{ t('Expenses') }}
                            </Label>
                            <Switch
                                :id="'expenses-only'"
                                v-model:checked="expenses"
                            />
                        </div>
                    </div>
                </div>

                <!-- Other Sections (Switches) -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 border rounded-md bg-card">
                        <Label :for="'incomes'" class="text-sm font-medium cursor-pointer">
                            {{ t('Incomes') }}
                        </Label>
                        <Switch
                            :id="'incomes'"
                            v-model:checked="includeIncomes"
                        />
                    </div>

                    <div class="flex items-center justify-between p-3 border rounded-md bg-card">
                        <Label :for="'crew'" class="text-sm font-medium cursor-pointer">
                            {{ t('Crew Members') }}
                        </Label>
                        <Switch
                            :id="'crew'"
                            v-model:checked="includeCrew"
                        />
                    </div>

                    <div v-if="canShowQuantity" class="flex items-center justify-between p-3 border rounded-md bg-card">
                        <Label :for="'quantity'" class="text-sm font-medium cursor-pointer">
                            {{ t('Fishing Quantity Returns') }}
                        </Label>
                        <Switch
                            :id="'quantity'"
                            v-model:checked="includeQuantity"
                        />
                    </div>

                    <div class="flex items-center justify-between p-3 border rounded-md bg-card">
                        <Label :for="'salary'" class="text-sm font-medium" :class="{ 'cursor-pointer': !isSalaryDisabled, 'cursor-not-allowed opacity-50': isSalaryDisabled }">
                            {{ t('Salary Payments') }}
                        </Label>
                        <Switch
                            :id="'salary'"
                            v-model:checked="includeSalary"
                            :disabled="isSalaryDisabled"
                        />
                    </div>
                </div>

                <!-- Color Toggle -->
                <div class="flex items-center justify-between space-x-4 p-3 border rounded-md bg-card">
                    <div class="flex-1">
                        <Label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            {{ t('Enable Colors') }}
                        </Label>
                        <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                            {{ t('Use green for income and red for expenses') }}
                        </p>
                    </div>
                    <Switch
                        v-model:checked="enableColors"
                    />
                </div>

                <!-- Action Buttons -->
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="handleClose"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        @click="handleDownload"
                        :disabled="!isValid"
                    >
                        {{ t('Generate PDF') }}
                    </Button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>
</template>

