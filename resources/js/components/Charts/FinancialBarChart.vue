<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue';

interface ChartData {
    month_label: string;
    month: number;
    year: number;
    income: number;
    expenses: number;
    net: number;
}

interface Props {
    data: ChartData[];
    currency: string;
    currencyData: {
        code: string;
        symbol: string;
        decimal_separator: number;
    };
}

const props = defineProps<Props>();

const animated = ref(false);

onMounted(() => {
    setTimeout(() => {
        animated.value = true;
    }, 150);
});

// Calculate max value for scaling
const maxValue = computed(() => {
    const allValues = props.data.flatMap((d) => [d.income, d.expenses]);
    return Math.max(...allValues, 1);
});

// Get bar width percentage
const getBarWidth = (value: number) => {
    if (maxValue.value === 0) return 0;
    return Math.max((Math.abs(value) / maxValue.value) * 100, 2);
};

// Format currency for display
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: props.currencyData.decimal_separator,
        maximumFractionDigits: props.currencyData.decimal_separator,
    }).format(value / 100);
};
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="(month, index) in data"
            :key="`${month.year}-${month.month}`"
            class="space-y-2.5 group"
        >
            <!-- Month Label -->
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 min-w-[80px]">
                    {{ month.month_label }}
                </span>
                <div class="flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-medium">Net:</span>
                    <MoneyDisplay
                        :value="month.net"
                        :currency="currency"
                        :decimals="currencyData.decimal_separator"
                        :variant="month.net >= 0 ? 'positive' : 'negative'"
                        size="xs"
                        :show-symbol="true"
                    />
                </div>
            </div>

            <!-- Bars Container -->
            <div class="flex items-center gap-3">
                <!-- Income Bar -->
                <div class="flex-1 relative">
                    <div class="h-8 bg-slate-100 dark:bg-slate-800/50 rounded-lg overflow-hidden shadow-inner">
                        <div
                            class="h-full bg-gradient-to-r from-emerald-500 via-emerald-400 to-emerald-500 rounded-lg transition-all duration-700 ease-out relative overflow-hidden group-hover:shadow-lg group-hover:shadow-emerald-500/20"
                            :style="{
                                width: animated ? `${getBarWidth(month.income)}%` : '0%',
                                transitionDelay: `${index * 50}ms`,
                            }"
                        >
                            <!-- Shine effect -->
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer"></div>
                            <!-- Value Label -->
                            <div
                                v-if="getBarWidth(month.income) > 20"
                                class="absolute inset-0 flex items-center justify-start pl-2 text-xs font-semibold text-emerald-50 drop-shadow-sm"
                            >
                                {{ formatCurrency(month.income) }}
                            </div>
                        </div>
                    </div>
                    <!-- Outside Label for small bars -->
                    <div
                        v-if="getBarWidth(month.income) <= 20 && month.income > 0"
                        class="absolute left-2 top-1/2 -translate-y-1/2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 whitespace-nowrap"
                    >
                        {{ formatCurrency(month.income) }}
                    </div>
                </div>

                <!-- Expenses Bar -->
                <div class="flex-1 relative">
                    <div class="h-8 bg-slate-100 dark:bg-slate-800/50 rounded-lg overflow-hidden shadow-inner">
                        <div
                            class="h-full bg-gradient-to-r from-red-500 via-red-400 to-red-500 rounded-lg transition-all duration-700 ease-out relative overflow-hidden group-hover:shadow-lg group-hover:shadow-red-500/20"
                            :style="{
                                width: animated ? `${getBarWidth(month.expenses)}%` : '0%',
                                transitionDelay: `${index * 50}ms`,
                            }"
                        >
                            <!-- Shine effect -->
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer"></div>
                            <!-- Value Label -->
                            <div
                                v-if="getBarWidth(month.expenses) > 20"
                                class="absolute inset-0 flex items-center justify-start pl-2 text-xs font-semibold text-red-50 drop-shadow-sm"
                            >
                                {{ formatCurrency(month.expenses) }}
                            </div>
                        </div>
                    </div>
                    <!-- Outside Label for small bars -->
                    <div
                        v-if="getBarWidth(month.expenses) <= 20 && month.expenses > 0"
                        class="absolute left-2 top-1/2 -translate-y-1/2 text-xs font-semibold text-red-600 dark:text-red-400 whitespace-nowrap"
                    >
                        {{ formatCurrency(month.expenses) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex items-center justify-center gap-6 pt-3 border-t border-slate-200/60 dark:border-slate-800/60">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded bg-gradient-to-r from-emerald-500 to-emerald-400 shadow-sm"></div>
                <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Income</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded bg-gradient-to-r from-red-500 to-red-400 shadow-sm"></div>
                <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Expenses</span>
            </div>
        </div>
    </div>
</template>

