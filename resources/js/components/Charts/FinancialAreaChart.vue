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
    height?: number;
}

const props = withDefaults(defineProps<Props>(), {
    height: 320,
});

const chartRef = ref<SVGElement | null>(null);
const animated = ref(false);

onMounted(() => {
    // Trigger animation after mount
    setTimeout(() => {
        animated.value = true;
    }, 100);
});

// Chart dimensions
const chartPadding = { top: 30, right: 40, bottom: 50, left: 70 };
const chartWidth = computed(() => 800);
const chartHeight = computed(() => props.height);
const innerWidth = computed(() => chartWidth.value - chartPadding.left - chartPadding.right);
const innerHeight = computed(() => chartHeight.value - chartPadding.top - chartPadding.bottom);

// Calculate scales
const maxValue = computed(() => {
    const allValues = props.data.flatMap((d) => [d.income, d.expenses]);
    return Math.max(...allValues, 1) * 1.15; // Add 15% padding
});

// Scale functions (methods instead of computed)
const getXScale = (index: number) => {
    if (props.data.length === 1) return innerWidth.value / 2;
    return (innerWidth.value / (props.data.length - 1)) * index;
};

const getYScale = (value: number) => {
    if (maxValue.value === 0) return innerHeight.value;
    return innerHeight.value - (value / maxValue.value) * innerHeight.value;
};

// Generate area path for income
const incomeAreaPath = computed(() => {
    if (props.data.length === 0) return '';
    const points = props.data.map((d, i) => `${getXScale(i)},${getYScale(d.income)}`).join(' ');
    return `M ${chartPadding.left},${chartPadding.top + innerHeight.value} L ${points} L ${chartPadding.left + innerWidth.value},${chartPadding.top + innerHeight.value} Z`;
});

// Generate area path for expenses
const expensesAreaPath = computed(() => {
    if (props.data.length === 0) return '';
    const points = props.data.map((d, i) => `${getXScale(i)},${getYScale(d.expenses)}`).join(' ');
    return `M ${chartPadding.left},${chartPadding.top + innerHeight.value} L ${points} L ${chartPadding.left + innerWidth.value},${chartPadding.top + innerHeight.value} Z`;
});


// Y-axis ticks
const yAxisTicks = computed(() => {
    const ticks = 5;
    const tickValues: number[] = [];
    for (let i = 0; i <= ticks; i++) {
        tickValues.push((maxValue.value / ticks) * i);
    }
    return tickValues;
});

// Hover state
const hoveredIndex = ref<number | null>(null);

const handleMouseMove = (event: MouseEvent) => {
    if (!chartRef.value) return;
    const rect = chartRef.value.getBoundingClientRect();
    const x = event.clientX - rect.left - chartPadding.left;
    const index = Math.round((x / innerWidth.value) * (props.data.length - 1));
    hoveredIndex.value = Math.max(0, Math.min(index, props.data.length - 1));
};

const handleMouseLeave = () => {
    hoveredIndex.value = null;
};

// Format currency for display
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: props.currencyData.decimal_separator,
        maximumFractionDigits: props.currencyData.decimal_separator,
    }).format(value / 100);
};

// Tooltip positioning (computed to stay within bounds)
const tooltipPosition = computed(() => {
    if (hoveredIndex.value === null || !props.data[hoveredIndex.value]) {
        return null;
    }
    const data = props.data[hoveredIndex.value];
    const tooltipWidth = 150;
    const tooltipHeight = 58;
    const padding = 5;

    // Calculate X position (center on point, but clamp to edges)
    let tooltipX = getXScale(hoveredIndex.value);
    const minX = padding;
    const maxX = innerWidth.value - tooltipWidth - padding;
    tooltipX = Math.max(minX + tooltipWidth / 2, Math.min(tooltipX, maxX + tooltipWidth / 2));

    // Calculate Y position (above the higher value point)
    const higherValue = Math.max(data.income, data.expenses);
    let tooltipY = getYScale(higherValue) - tooltipHeight - 10;
    const minY = padding;
    const maxY = innerHeight.value - tooltipHeight - padding;
    tooltipY = Math.max(minY, Math.min(tooltipY, maxY));

    return {
        x: tooltipX - tooltipWidth / 2,
        y: tooltipY,
        textX: tooltipX,
        textY: tooltipY + 15,
    };
});
</script>

<template>
    <div class="relative w-full">
        <!-- Chart Container -->
        <div
            class="relative overflow-hidden rounded-lg bg-card dark:bg-card border border-border dark:border-border"
            @mousemove="handleMouseMove"
            @mouseleave="handleMouseLeave"
        >
            <!-- SVG Chart -->
            <svg
                ref="chartRef"
                :width="chartWidth.value"
                :height="chartHeight.value"
                class="w-full h-auto block"
                :viewBox="`0 0 ${chartWidth.value} ${chartHeight.value}`"
                preserveAspectRatio="xMidYMid meet"
                style="overflow: hidden;"
            >
                <!-- Gradient Definitions -->
                <defs>
                    <!-- Clip path to prevent overflow -->
                    <clipPath id="chartClip">
                        <rect
                            :x="chartPadding.left"
                            :y="chartPadding.top"
                            :width="innerWidth.value"
                            :height="innerHeight.value"
                        />
                    </clipPath>
                    <!-- Income Gradient -->
                    <linearGradient id="incomeGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" :style="{ stopColor: '#10b981', stopOpacity: 0.8 }" />
                        <stop offset="100%" :style="{ stopColor: '#059669', stopOpacity: 0.3 }" />
                    </linearGradient>
                    <!-- Expenses Gradient -->
                    <linearGradient id="expensesGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" :style="{ stopColor: '#ef4444', stopOpacity: 0.8 }" />
                        <stop offset="100%" :style="{ stopColor: '#dc2626', stopOpacity: 0.3 }" />
                    </linearGradient>
                    <!-- Glow Filter -->
                    <filter id="glow">
                        <feGaussianBlur stdDeviation="3" result="coloredBlur" />
                        <feMerge>
                            <feMergeNode in="coloredBlur" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>
                </defs>

                <!-- Grid Lines -->
                <g :transform="`translate(${chartPadding.left}, ${chartPadding.top})`">
                    <g v-for="(tick, index) in yAxisTicks" :key="`grid-${index}`" class="text-slate-300 dark:text-slate-700">
                        <line
                            :x1="0"
                            :y1="getYScale(tick)"
                            :x2="innerWidth.value"
                            :y2="getYScale(tick)"
                            stroke="currentColor"
                            stroke-width="1"
                            stroke-dasharray="4 4"
                            opacity="0.3"
                        />
                    </g>
                </g>

                <!-- Area Charts -->
                <g :transform="`translate(${chartPadding.left}, ${chartPadding.top})`" clip-path="url(#chartClip)">
                    <!-- Expenses Area (behind) -->
                    <path
                        :d="expensesAreaPath"
                        fill="url(#expensesGradient)"
                        :opacity="animated ? 0.6 : 0"
                        class="transition-opacity duration-1000 ease-out"
                    />
                    <!-- Income Area (on top) -->
                    <path
                        :d="incomeAreaPath"
                        fill="url(#incomeGradient)"
                        :opacity="animated ? 0.7 : 0"
                        class="transition-opacity duration-1000 ease-out"
                    />
                </g>

                <!-- Data Points -->
                <g :transform="`translate(${chartPadding.left}, ${chartPadding.top})`">
                    <!-- Income Points -->
                    <circle
                        v-for="(item, index) in props.data"
                        :key="`income-point-${index}`"
                        :cx="getXScale(index)"
                        :cy="getYScale(item.income)"
                        r="4"
                        fill="#10b981"
                        stroke="#fff"
                        stroke-width="2"
                        :opacity="animated ? 1 : 0"
                        class="transition-opacity duration-1000 ease-out"
                        :class="{ 'scale-150': hoveredIndex === index }"
                    />
                    <!-- Expenses Points -->
                    <circle
                        v-for="(item, index) in props.data"
                        :key="`expenses-point-${index}`"
                        :cx="getXScale(index)"
                        :cy="getYScale(item.expenses)"
                        r="4"
                        fill="#ef4444"
                        stroke="#fff"
                        stroke-width="2"
                        :opacity="animated ? 1 : 0"
                        class="transition-opacity duration-1000 ease-out"
                        :class="{ 'scale-150': hoveredIndex === index }"
                    />
                </g>

                <!-- Y-Axis Labels -->
                <g :transform="`translate(${chartPadding.left - 8}, ${chartPadding.top})`">
                    <text
                        v-for="(tick, index) in yAxisTicks"
                        :key="`y-label-${index}`"
                        :x="0"
                        :y="getYScale(tick)"
                        text-anchor="end"
                        dominant-baseline="middle"
                        class="fill-slate-600 dark:fill-slate-400 text-xs font-medium"
                        font-size="10"
                    >
                        {{ formatCurrency(tick) }}
                    </text>
                </g>

                <!-- X-Axis Labels -->
                <g :transform="`translate(${chartPadding.left}, ${chartPadding.top + innerHeight.value + 25})`">
                    <text
                        v-for="(item, index) in props.data"
                        :key="`x-label-${index}`"
                        :x="getXScale(index)"
                        :y="0"
                        text-anchor="middle"
                        dominant-baseline="hanging"
                        class="fill-slate-600 dark:fill-slate-400 text-xs font-medium"
                        font-size="10"
                    >
                        {{ item.month_label }}
                    </text>
                </g>

                <!-- Hover Tooltip -->
                <g v-if="hoveredIndex !== null && props.data[hoveredIndex] && tooltipPosition" :transform="`translate(${chartPadding.left}, ${chartPadding.top})`">
                    <!-- Vertical Line -->
                    <line
                        :x1="getXScale(hoveredIndex)"
                        :y1="0"
                        :x2="getXScale(hoveredIndex)"
                        :y2="innerHeight.value"
                        stroke="#64748b"
                        stroke-width="1.5"
                        stroke-dasharray="4 4"
                        opacity="0.5"
                    />
                    <!-- Tooltip Background -->
                    <rect
                        :x="tooltipPosition.x"
                        :y="tooltipPosition.y"
                        width="150"
                        height="58"
                        rx="6"
                        fill="rgba(15, 23, 42, 0.95)"
                        class="dark:fill-slate-800/95"
                        stroke="#334155"
                        stroke-width="1"
                    />
                    <!-- Tooltip Text -->
                    <text
                        :x="tooltipPosition.textX"
                        :y="tooltipPosition.textY"
                        text-anchor="middle"
                        class="fill-slate-200 text-xs font-semibold"
                        font-size="10"
                    >
                        {{ props.data[hoveredIndex].month_label }}
                    </text>
                    <text
                        :x="tooltipPosition.textX"
                        :y="tooltipPosition.textY + 14"
                        text-anchor="middle"
                        class="fill-emerald-400 text-xs font-medium"
                        font-size="9"
                    >
                        Income: {{ formatCurrency(props.data[hoveredIndex].income) }}
                    </text>
                    <text
                        :x="tooltipPosition.textX"
                        :y="tooltipPosition.textY + 26"
                        text-anchor="middle"
                        class="fill-red-400 text-xs font-medium"
                        font-size="9"
                    >
                        Expenses: {{ formatCurrency(props.data[hoveredIndex].expenses) }}
                    </text>
                </g>
            </svg>
        </div>

        <!-- Legend -->
        <div class="flex items-center justify-center gap-6 mt-3 pt-3 border-t border-border dark:border-border">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded bg-emerald-500"></div>
                <span class="text-xs font-medium text-muted-foreground dark:text-muted-foreground">Income</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded bg-red-500"></div>
                <span class="text-xs font-medium text-muted-foreground dark:text-muted-foreground">Expenses</span>
            </div>
        </div>
    </div>
</template>

