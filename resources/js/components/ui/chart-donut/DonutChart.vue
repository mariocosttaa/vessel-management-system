<script setup lang="ts">
import { computed } from 'vue';

interface DonutChartProps {
    data: Record<string, any>[];
    index: string;
    category: string;
    colors?: string[];
    height?: number;
    valueFormatter?: (value: number | Date) => string;
}

const props = withDefaults(defineProps<DonutChartProps>(), {
    colors: () => [
        'hsl(var(--chart-1))',
        'hsl(var(--chart-2))',
        'hsl(var(--chart-3))',
        'hsl(var(--chart-4))',
        'hsl(var(--chart-5))',
    ],
    height: 300,
    valueFormatter: () => (value: number | Date) => {
        if (typeof value === 'number') {
            return value >= 1000000
                ? `$${(value / 1000000).toFixed(1)}M`
                : value >= 1000
                ? `$${(value / 1000).toFixed(1)}K`
                : `$${value.toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
        }
        return String(value);
    },
});

// Use height to determine width for better responsiveness
const width = computed(() => Math.min(props.height || 200, 200));
const radius = computed(() => Math.min(width.value, props.height) / 2 - 8);
const innerRadius = computed(() => radius.value * 0.6);
const centerX = computed(() => width.value / 2);
const centerY = computed(() => props.height / 2);

// Prepare data - values come in cents, keep them as is for formatter
const chartData = computed(() => {
    return props.data.map((item) => {
        const value = item[props.category] || 0;
        return {
            [props.index]: item[props.index],
            value: typeof value === 'number' ? value : parseFloat(value) || 0,
        };
    });
});

// Calculate total
const total = computed(() => {
    return chartData.value.reduce((sum, item) => sum + item.value, 0);
});

// Calculate angles and paths for donut segments
const segments = computed(() => {
    let currentAngle = -90; // Start from top
    return chartData.value.map((item, index) => {
        const percentage = item.value / total.value;
        const angle = percentage * 360;
        const startAngle = currentAngle;
        const endAngle = currentAngle + angle;
        currentAngle = endAngle;

        const startAngleRad = (startAngle * Math.PI) / 180;
        const endAngleRad = (endAngle * Math.PI) / 180;

        const x1 = centerX.value + radius.value * Math.cos(startAngleRad);
        const y1 = centerY.value + radius.value * Math.sin(startAngleRad);
        const x2 = centerX.value + radius.value * Math.cos(endAngleRad);
        const y2 = centerY.value + radius.value * Math.sin(endAngleRad);

        const x3 = centerX.value + innerRadius.value * Math.cos(endAngleRad);
        const y3 = centerY.value + innerRadius.value * Math.sin(endAngleRad);
        const x4 = centerX.value + innerRadius.value * Math.cos(startAngleRad);
        const y4 = centerY.value + innerRadius.value * Math.sin(startAngleRad);

        const largeArcFlag = angle > 180 ? 1 : 0;

        // Create the donut path
        const path = `
            M ${x1} ${y1}
            A ${radius} ${radius} 0 ${largeArcFlag} 1 ${x2} ${y2}
            L ${x3} ${y3}
            A ${innerRadius} ${innerRadius} 0 ${largeArcFlag} 0 ${x4} ${y4}
            Z
        `.trim();

        // Calculate label position (middle of arc)
        const labelAngle = (startAngle + endAngle) / 2;
        const labelAngleRad = (labelAngle * Math.PI) / 180;
        const labelRadius = (radius.value + innerRadius.value) / 2;
        const labelX = centerX.value + labelRadius * Math.cos(labelAngleRad);
        const labelY = centerY.value + labelRadius * Math.sin(labelAngleRad);

        return {
            ...item,
            index,
            percentage,
            path,
            color: props.colors[index % props.colors.length],
            startAngle,
            endAngle,
            labelX,
            labelY,
            labelAngle,
        };
    });
});
</script>

<template>
    <div class="w-full">
        <svg
            :width="width"
            :height="props.height"
            class="w-full h-auto mx-auto"
            :viewBox="`0 0 ${width} ${props.height}`"
            preserveAspectRatio="xMidYMid meet"
        >
            <!-- Donut segments -->
            <g>
                <path
                    v-for="(segment, index) in segments"
                    :key="`segment-${index}`"
                    :d="segment.path"
                    :fill="segment.color"
                    :opacity="0.8"
                    class="transition-opacity duration-200 cursor-pointer hover:opacity-100"
                    stroke="white"
                    stroke-width="2"
                />
            </g>

            <!-- Labels (only show if segment is large enough) -->
            <g>
                <text
                    v-for="(segment, index) in segments.filter(s => s.percentage > 0.08)"
                    :key="`label-${index}`"
                    :x="segment.labelX"
                    :y="segment.labelY"
                    text-anchor="middle"
                    dominant-baseline="middle"
                    class="fill-white text-[9px] font-semibold pointer-events-none"
                    font-size="9"
                >
                    {{ (segment.percentage * 100).toFixed(0) }}%
                </text>
            </g>

            <!-- Center text -->
            <text
                :x="centerX"
                :y="centerY - 4"
                text-anchor="middle"
                dominant-baseline="middle"
                class="fill-muted-foreground text-[10px] font-medium"
                font-size="10"
            >
                Total
            </text>
            <text
                :x="centerX"
                :y="centerY + 8"
                text-anchor="middle"
                dominant-baseline="middle"
                class="fill-card-foreground text-xs font-bold"
                font-size="11"
            >
                {{ props.valueFormatter(total) }}
            </text>
        </svg>

        <!-- Legend -->
        <div class="flex flex-col gap-1.5 mt-3 pt-3 border-t border-border">
            <div
                v-for="(segment, index) in segments"
                :key="`legend-${index}`"
                class="flex items-center justify-between gap-2"
            >
                <div class="flex items-center gap-1.5 min-w-0 flex-1">
                    <div
                        class="w-2.5 h-2.5 rounded flex-shrink-0"
                        :style="{ backgroundColor: segment.color }"
                    ></div>
                    <span class="text-[10px] font-medium text-muted-foreground truncate">
                        {{ segment[props.index] }}
                    </span>
                </div>
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <span class="text-[10px] font-semibold text-card-foreground">
                        {{ props.valueFormatter(segment.value) }}
                    </span>
                    <span class="text-[10px] text-muted-foreground">
                        ({{ (segment.percentage * 100).toFixed(0) }}%)
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

