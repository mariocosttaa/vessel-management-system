<script setup lang="ts">
import { computed } from 'vue';
import { AreaChart } from '@/components/ui/chart-area';

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
    height?: number;
}

const props = withDefaults(defineProps<Props>(), {
    height: 280,
});

// Transform data for AreaChart component
// The data comes in cents, but AreaChart expects regular numbers
// We'll keep it in cents since AreaChart will format it
const chartData = computed(() => {
    return props.data.map((item: ChartData) => ({
        name: item.month_label,
        income: item.income, // Keep in cents
        expenses: item.expenses, // Keep in cents
    }));
});

// Custom colors for income (green) and expenses (red)
const colors = computed(() => [
    'hsl(142 76% 36%)', // emerald-600 for income
    'hsl(0 84% 60%)', // red-500 for expenses
]);
</script>

<template>
    <div class="w-full">
        <AreaChart
            :data="chartData"
            index="name"
            :categories="['income', 'expenses']"
            :colors="colors"
            :height="props.height"
        />
    </div>
</template>
