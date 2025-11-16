<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { DateInput } from '@/components/ui/date-input';
import { useI18n } from '@/composables/useI18n';

interface Props {
    open: boolean;
    initialMonth?: number;
    initialYear?: number;
    lockMonthYear?: boolean; // If true, disable month/year selection (for history month page)
}

const props = withDefaults(defineProps<Props>(), {
    initialMonth: undefined,
    initialYear: undefined,
    lockMonthYear: false,
});
const emit = defineEmits<{
    close: [];
    download: (options: { type: 'month' | 'range'; month?: number; year?: number; startDate?: string; endDate?: string; transactionType?: string }) => void;
}>();

const { t } = useI18n();

// Download type selection
const downloadType = ref<'month' | 'range'>('month');

// Transaction type filter (income, expense, or all)
const transactionType = ref<string>('all');

// Month/Year selection - use initial values if provided, otherwise default to current month/year
const selectedMonth = ref<number>(props.initialMonth ?? new Date().getMonth() + 1);
const selectedYear = ref<number>(props.initialYear ?? new Date().getFullYear());

// Watch for prop changes to update values when modal opens
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        if (props.initialMonth && props.initialYear) {
            selectedMonth.value = props.initialMonth;
            selectedYear.value = props.initialYear;
        }
        if (props.lockMonthYear) {
            downloadType.value = 'month';
        }
    }
});

watch(() => [props.initialMonth, props.initialYear], ([newMonth, newYear]) => {
    if (newMonth && newYear) {
        selectedMonth.value = newMonth;
        selectedYear.value = newYear;
    }
});

// Force download type to 'month' when month/year is locked
watch(() => props.lockMonthYear, (isLocked) => {
    if (isLocked) {
        downloadType.value = 'month';
    }
});

// Date range selection
const startDate = ref<string>('');
const endDate = ref<string>('');

// Month options
const monthOptions = computed(() => {
    const months = [
        { value: 1, label: t('January') },
        { value: 2, label: t('February') },
        { value: 3, label: t('March') },
        { value: 4, label: t('April') },
        { value: 5, label: t('May') },
        { value: 6, label: t('June') },
        { value: 7, label: t('July') },
        { value: 8, label: t('August') },
        { value: 9, label: t('September') },
        { value: 10, label: t('October') },
        { value: 11, label: t('November') },
        { value: 12, label: t('December') },
    ];
    return months;
});

// Year options (current year and 5 years back)
const yearOptions = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let i = 0; i < 6; i++) {
        const year = currentYear - i;
        years.push({ value: year, label: year.toString() });
    }
    return years;
});

// Validation
const isValid = computed(() => {
    if (downloadType.value === 'month') {
        return selectedMonth.value > 0 && selectedMonth.value <= 12 && selectedYear.value > 2000;
    } else {
        return startDate.value && endDate.value && new Date(startDate.value) <= new Date(endDate.value);
    }
});

const handleDownload = () => {
    if (!isValid.value) return;

    if (downloadType.value === 'month') {
        emit('download', {
            type: 'month' as const,
            month: selectedMonth.value,
            year: selectedYear.value,
            transactionType: transactionType.value,
        });
    } else {
        emit('download', {
            type: 'range' as const,
            startDate: startDate.value,
            endDate: endDate.value,
            transactionType: transactionType.value,
        });
    }
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => !val && handleClose()">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('Export Transactions to Excel') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Choose how you want to export the transactions to Excel') }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-6 py-4">
                <!-- Download Type Selection -->
                <div v-if="!lockMonthYear" class="space-y-2">
                    <label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                        {{ t('Export Type') }}
                    </label>
                    <div class="flex gap-4">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input
                                type="radio"
                                v-model="downloadType"
                                value="month"
                                class="w-4 h-4 text-primary focus:ring-primary"
                            />
                            <span class="text-sm text-card-foreground dark:text-card-foreground">{{ t('By Month') }}</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input
                                type="radio"
                                v-model="downloadType"
                                value="range"
                                class="w-4 h-4 text-primary focus:ring-primary"
                            />
                            <span class="text-sm text-card-foreground dark:text-card-foreground">{{ t('By Date Range') }}</span>
                        </label>
                    </div>
                </div>

                <!-- Month/Year Selection -->
                <div v-if="downloadType === 'month'" class="space-y-4">
                    <div v-if="lockMonthYear" class="space-y-2">
                        <label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            {{ t('Month') }}
                        </label>
                        <div class="px-3 py-2 bg-muted/50 dark:bg-muted/30 rounded-md text-sm text-muted-foreground">
                            {{ monthOptions.find(m => m.value === selectedMonth)?.label }} {{ selectedYear }}
                        </div>
                        <p class="text-xs text-muted-foreground">{{ t('Exporting transactions for the current month being viewed') }}</p>
                    </div>
                    <template v-else>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Month') }}
                            </label>
                            <Select
                                v-model="selectedMonth"
                                :options="monthOptions"
                                :placeholder="t('Select month')"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                                {{ t('Year') }}
                            </label>
                            <Select
                                v-model="selectedYear"
                                :options="yearOptions"
                                :placeholder="t('Select year')"
                            />
                        </div>
                    </template>
                </div>

                <!-- Date Range Selection -->
                <div v-else class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            {{ t('Start Date') }}
                        </label>
                        <DateInput v-model="startDate" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            {{ t('End Date') }}
                        </label>
                        <DateInput v-model="endDate" />
                    </div>
                </div>

                <!-- Transaction Type Filter -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                        {{ t('Transaction Type') }}
                    </label>
                    <Select
                        v-model="transactionType"
                        :options="[
                            { value: 'all', label: t('All Transactions') },
                            { value: 'income', label: t('Income Only') },
                            { value: 'expense', label: t('Expenses Only') }
                        ]"
                        :placeholder="t('Select transaction type')"
                    />
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 pt-4">
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
                        {{ t('Export Excel') }}
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

