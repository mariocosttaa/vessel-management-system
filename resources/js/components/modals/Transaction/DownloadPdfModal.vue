<script setup lang="ts">
import { ref, computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { DateInput } from '@/components/ui/date-input';
import { Switch } from '@/components/ui/switch';
import { useI18n } from '@/composables/useI18n';

interface Props {
    open: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    download: (options: { type: 'month' | 'range'; month?: number; year?: number; startDate?: string; endDate?: string; transactionType?: string; enableColors?: boolean }) => void;
}>();

const { t } = useI18n();

// Download type selection
const downloadType = ref<'month' | 'range'>('month');

// Transaction type filter (income, expense, or all)
const transactionType = ref<string>('all');

// Color toggle (enable/disable colors in PDF) - default to false (unchecked)
const enableColors = ref<boolean>(false);

// Month/Year selection
const selectedMonth = ref<number>(new Date().getMonth() + 1);
const selectedYear = ref<number>(new Date().getFullYear());

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
            enableColors: enableColors.value,
        });
    } else {
        emit('download', {
            type: 'range' as const,
            startDate: startDate.value,
            endDate: endDate.value,
            transactionType: transactionType.value,
            enableColors: enableColors.value,
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
                <DialogTitle>{{ t('Download Transaction Report') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Choose how you want to download the transaction report') }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-6 py-4">
                <!-- Download Type Selection -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                        {{ t('Download Type') }}
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

                <!-- Color Toggle -->
                <div class="flex items-center justify-between space-x-4 p-3 border rounded-md bg-card">
                    <div class="flex-1">
                        <label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            {{ t('Enable Colors') }}
                        </label>
                        <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                            {{ t('Use green for income and red for expenses') }}
                        </p>
                    </div>
                    <Switch
                        v-model:checked="enableColors"
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
                        {{ t('Download PDF') }}
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

