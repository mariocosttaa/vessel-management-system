<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();

// Define the order of groups for consistent display
const groupOrder = ['Platform', 'Crew Management', 'Financial'];

// Group items by their group property, or use 'Platform' as default
const groupedItems = computed(() => {
    const groups: Record<string, NavItem[]> = {};

    props.items.forEach((item) => {
        const group = item.group || 'Platform';
        if (!groups[group]) {
            groups[group] = [];
        }
        groups[group].push(item);
    });

    // Convert to array of { label, items } for easier iteration
    const orderedGroups: Array<{ label: string; items: NavItem[] }> = [];

    // Add groups in the defined order
    groupOrder.forEach((groupName) => {
        if (groups[groupName] && groups[groupName].length > 0) {
            orderedGroups.push({ label: groupName, items: groups[groupName] });
        }
    });

    // Add any remaining groups that weren't in the order list
    Object.keys(groups).forEach((groupName) => {
        if (!groupOrder.includes(groupName) && groups[groupName].length > 0) {
            orderedGroups.push({ label: groupName, items: groups[groupName] });
        }
    });

    return orderedGroups;
});
</script>

<template>
    <template v-for="(group, index) in groupedItems" :key="group.label">
        <SidebarGroup class="px-2 py-0">
            <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in group.items" :key="item.title">
                    <SidebarMenuButton
                        as-child
                        :is-active="urlIsActive(item.href, page.url)"
                        :tooltip="item.title"
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
        <!-- Add separator between groups (except after the last one) -->
        <SidebarSeparator v-if="index < groupedItems.length - 1" />
    </template>
</template>
