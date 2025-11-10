<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Ship } from 'lucide-vue-next';

const page = usePage();

const vesselName = computed(() => {
    const auth = page.props.auth as any;
    return auth?.current_vessel?.name || 'Vessel Management';
});
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground shadow-sm"
    >
        <Ship class="size-4 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-2 grid flex-1 text-left text-sm min-w-0">
        <span class="truncate leading-tight font-semibold text-sidebar-foreground">{{ vesselName }}</span>
        <span class="truncate text-xs text-sidebar-foreground/60">Bindamy Mareas</span>
    </div>
</template>
