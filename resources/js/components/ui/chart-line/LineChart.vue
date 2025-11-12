<script setup lang="ts">
import { computed, ref } from 'vue';

interface LineChartProps {
    data: Record<string, any>[];
    index: string;
    categories: string[];
    colors?: string[];
    height?: number;
    yFormatter?: (value: number | Date, index: number) => string;
}

const props = withDefaults(defineProps<LineChartProps>(), {
    colors: () => [
        'hsl(var(--chart-1))',
        'hsl(var(--chart-2))',
        'hsl(var(--chart-3))',
        'hsl(var(--chart-4))',
        'hsl(var(--chart-5))',
    ],
    height: 300,
    yFormatter: () => (value: number | Date) => {
        if (typeof value === 'number') {
            return value >= 1000000
                ? `${(value / 1000000).toFixed(1)}M`
                : value >= 1000
                ? `${(value / 1000).toFixed(1)}K`
                : value.toLocaleString('en-US', { maximumFractionDigits: 0 });
        }
        return String(value);
    },
});

const margin = { top: 20, right: 30, bottom: 40, left: 60 };
const width = 800;
const innerWidth = width - margin.left - margin.right;
const innerHeight = props.height - margin.top - margin.bottom;

// Prepare data - values come in cents, keep them as is for formatter
const chartData = computed(() => {
    return props.data.map((item) => {
        const transformed: Record<string, any> = {
            [props.index]: item[props.index],
        };
        props.categories.forEach((category) => {
            const value = item[category] || 0;
            // Keep values in cents for proper formatting
            transformed[category] = typeof value === 'number' ? value : parseFloat(value) || 0;
        });
        return transformed;
    });
});

// Get max value for scaling - values are in cents
const maxValue = computed(() => {
    let max = 0;
    chartData.value.forEach((item) => {
        props.categories.forEach((category) => {
            const value = item[category] || 0;
            if (value > max) max = value;
        });
    });
    return max * 1.1; // Add 10% padding
});

// Scale functions
const xScale = computed(() => {
    if (chartData.value.length <= 1) return () => innerWidth / 2;
    return (index: number) => (innerWidth / (chartData.value.length - 1)) * index;
});

const yScale = (value: number) => {
    if (maxValue.value === 0) return innerHeight;
    return innerHeight - (value / maxValue.value) * innerHeight;
};

// Generate line path
const linePath = (category: string) => {
    if (chartData.value.length === 0) return '';

    const points = chartData.value
        .map((d, i) => `${xScale.value(i)},${yScale(d[category])}`)
        .join(' L ');

    return `M ${points}`;
};

// Y-axis ticks
const yTicks = computed(() => {
    const ticks = 5;
    const tickValues: number[] = [];
    for (let i = 0; i <= ticks; i++) {
        tickValues.push((maxValue.value / ticks) * i);
    }
    return tickValues;
});

// Hover state
const hoveredIndex = ref<number | null>(null);
const hoveredData = computed(() => {
    if (hoveredIndex.value === null || !chartData.value[hoveredIndex.value]) return null;
    return chartData.value[hoveredIndex.value];
});

const handleMouseMove = (event: MouseEvent) => {
    const target = event.currentTarget as HTMLElement;
    if (!target) return;
    const rect = target.getBoundingClientRect();
    const x = event.clientX - rect.left - margin.left;
    const index = Math.round((x / innerWidth) * (chartData.value.length - 1));
    hoveredIndex.value = Math.max(0, Math.min(index, chartData.value.length - 1));
};

const handleMouseLeave = () => {
    hoveredIndex.value = null;
};
</script>

<template>
    <div class="w-full">
        <svg
            :width="width"
            :height="props.height"
            class="w-full h-auto"
            :viewBox="`0 0 ${width} ${props.height}`"
            preserveAspectRatio="xMidYMid meet"
            @mousemove="handleMouseMove"
            @mouseleave="handleMouseLeave"
        >
            <g :transform="`translate(${margin.left}, ${margin.top})`">
                <!-- Grid lines -->
                <g v-for="(tick, tickIndex) in yTicks" :key="`grid-${tickIndex}`">
                    <line
                        :x1="0"
                        :y1="yScale(tick)"
                        :x2="innerWidth"
                        :y2="yScale(tick)"
                        stroke="currentColor"
                        stroke-width="1"
                        stroke-dasharray="4 4"
                        class="text-border opacity-30"
                    />
                </g>

                <!-- Line charts -->
                <template v-for="(category, catIndex) in props.categories" :key="`line-${category}`">
                    <path
                        :d="linePath(category)"
                        :stroke="props.colors[catIndex % props.colors.length]"
                        stroke-width="2.5"
                        fill="none"
                        class="transition-opacity duration-300"
                    />
                </template>

                <!-- Data points -->
                <template v-for="(category, catIndex) in props.categories" :key="`points-${category}`">
                    <circle
                        v-for="(item, itemIndex) in chartData"
                        :key="`point-${category}-${itemIndex}`"
                        :cx="xScale(itemIndex)"
                        :cy="yScale(item[category])"
                        r="4.5"
                        :fill="props.colors[catIndex % props.colors.length]"
                        stroke="white"
                        stroke-width="2"
                        class="transition-opacity duration-300 cursor-pointer"
                        :class="{ 'r-6': hoveredIndex === itemIndex }"
                    />
                </template>

                <!-- Hover line -->
                <line
                    v-if="hoveredIndex !== null"
                    :x1="xScale(hoveredIndex)"
                    :y1="0"
                    :x2="xScale(hoveredIndex)"
                    :y2="innerHeight"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-dasharray="4 4"
                    class="text-muted-foreground opacity-50"
                />
            </g>

            <!-- Y-axis labels -->
            <g :transform="`translate(${margin.left - 10}, ${margin.top})`">
                <text
                    v-for="(tick, tickIndex) in yTicks"
                    :key="`y-label-${tickIndex}`"
                    :x="0"
                    :y="yScale(tick)"
                    text-anchor="end"
                    dominant-baseline="middle"
                    class="fill-muted-foreground text-xs"
                    font-size="10"
                >
                    {{ props.yFormatter ? props.yFormatter(tick, tickIndex) : tick.toLocaleString() }}
                </text>
            </g>

            <!-- X-axis labels -->
            <g :transform="`translate(${margin.left}, ${props.height - margin.bottom + 25})`">
                <text
                    v-for="(item, itemIndex) in chartData"
                    :key="`x-label-${itemIndex}`"
                    :x="xScale(itemIndex)"
                    :y="0"
                    text-anchor="middle"
                    dominant-baseline="hanging"
                    class="fill-muted-foreground text-xs"
                    font-size="10"
                >
                    {{ item[props.index] }}
                </text>
            </g>

            <!-- Tooltip -->
            <g v-if="hoveredData && hoveredIndex !== null" :transform="`translate(${margin.left}, ${margin.top})`">
                <rect
                    :x="xScale(hoveredIndex) - 75"
                    :y="10"
                    width="150"
                    :height="20 + props.categories.length * 20"
                    rx="6"
                    fill="hsl(var(--popover))"
                    stroke="hsl(var(--border))"
                    stroke-width="1"
                    class="shadow-lg"
                />
                <text
                    :x="xScale(hoveredIndex)"
                    :y="25"
                    text-anchor="middle"
                    class="fill-popover-foreground text-xs font-semibold"
                    font-size="11"
                >
                    {{ hoveredData[props.index] }}
                </text>
                <text
                    v-for="(category, catIndex) in props.categories"
                    :key="`tooltip-${category}`"
                    :x="xScale(hoveredIndex)"
                    :y="35 + catIndex * 20"
                    text-anchor="middle"
                    class="text-xs font-medium"
                    :style="{ fill: props.colors[catIndex % props.colors.length] }"
                    font-size="10"
                >
                    {{ category }}: {{ props.yFormatter ? props.yFormatter(hoveredData[category], catIndex) : hoveredData[category].toLocaleString() }}
                </text>
            </g>
        </svg>

        <!-- Legend -->
        <div class="flex items-center justify-center gap-6 mt-4 pt-4 border-t border-border">
            <div
                v-for="(category, catIndex) in props.categories"
                :key="`legend-${category}`"
                class="flex items-center gap-2"
            >
                <div
                    class="w-3 h-3 rounded-full"
                    :style="{ backgroundColor: props.colors[catIndex % props.colors.length] }"
                ></div>
                <span class="text-xs font-medium text-muted-foreground capitalize">
                    {{ category }}
                </span>
            </div>
        </div>
    </div>
</template>

