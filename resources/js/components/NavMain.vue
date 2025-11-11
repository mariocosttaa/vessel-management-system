<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubItem,
    SidebarMenuSubButton,
} from '@/components/ui/sidebar';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ChevronRight } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();
const { t } = useI18n();

// Define the order of groups for consistent display - Core items first
// Note: We use translated values since items have translated group names
const groupOrder = computed(() => [
    t('Core'),
    t('Crew Management'),
    t('Financial'),
    t('Reports'),
    t('Settings'),
    t('Others'),
]);

// Check if any child item is active
const hasActiveChild = (item: NavItem): boolean => {
    if (!item.children) return false;
    return item.children.some(
        (child) => child.href && urlIsActive(child.href, page.url)
    );
};

// Initialize open states for items with active children
const getInitialOpenState = (item: NavItem): boolean => {
    return hasActiveChild(item);
};

// Check if item or any of its children is active
const isItemActive = (item: NavItem): boolean => {
    if (item.href && urlIsActive(item.href, page.url)) return true;
    return hasActiveChild(item);
};

// Group items by their group property, or use 'Core' as default
const groupedItems = computed(() => {
    const groups: Record<string, NavItem[]> = {};

    props.items.forEach((item) => {
        const group = item.group || t('Core');
        if (!groups[group]) {
            groups[group] = [];
        }
        groups[group].push(item);
    });

    // Convert to array of { label, items } for easier iteration
    const orderedGroups: Array<{ label: string; items: NavItem[]; isCore: boolean }> = [];

    // Add groups in the defined order
    groupOrder.value.forEach((groupName) => {
        if (groups[groupName] && groups[groupName].length > 0) {
            orderedGroups.push({
                label: groupName,
                items: groups[groupName],
                isCore: groupName === t('Core'),
            });
        }
    });

    // Add any remaining groups that weren't in the order list
    Object.keys(groups).forEach((groupName) => {
        if (!groupOrder.value.includes(groupName) && groups[groupName].length > 0) {
            orderedGroups.push({
                label: groupName,
                items: groups[groupName],
                isCore: false,
            });
        }
    });

    return orderedGroups;
});
</script>

<template>
    <template v-for="group in groupedItems" :key="group.label">
        <SidebarGroup class="px-2 py-1 first:pt-1">
            <!-- Hide label for "Core" group since vessel name is shown in header -->
            <SidebarGroupLabel v-if="!group.isCore">{{
                group.label
            }}</SidebarGroupLabel>
            <SidebarMenu :class="group.isCore ? 'space-y-0.5' : 'mt-1 space-y-0.5'">
                <SidebarMenuItem v-for="item in group.items" :key="item.title">
                    <!-- Collapsible menu item with children -->
                    <Collapsible
                        v-if="item.children && item.children.length > 0"
                        :default-open="getInitialOpenState(item)"
                    >
                        <template #default="{ open }">
                            <div class="flex items-center">
                                <!-- Parent link (if href exists) -->
                                <SidebarMenuButton
                                    v-if="item.href"
                                    as-child
                                    :is-active="
                                        item.href &&
                                        urlIsActive(item.href, page.url)
                                    "
                                    :tooltip="item.title"
                                    class="flex-1"
                                >
                                    <Link :href="item.href">
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                        />
                                        <span>{{ item.title }}</span>
                                    </Link>
                                </SidebarMenuButton>
                                <!-- Parent button (if no href) -->
                                <SidebarMenuButton
                                    v-else
                                    :is-active="isItemActive(item)"
                                    :tooltip="item.title"
                                    class="flex-1"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                    />
                                    <span>{{ item.title }}</span>
                                </SidebarMenuButton>
                                <!-- Chevron button for expand/collapse -->
                                <CollapsibleTrigger as-child>
                                    <button
                                        type="button"
                                        class="ml-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-sidebar-foreground/70 transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                                    >
                                        <ChevronRight
                                            class="h-4 w-4 transition-transform duration-200"
                                            :class="{
                                                'rotate-90': open,
                                            }"
                                        />
                                    </button>
                                </CollapsibleTrigger>
                            </div>
                            <CollapsibleContent>
                                <SidebarMenuSub>
                                    <SidebarMenuSubItem
                                        v-for="child in item.children"
                                        :key="child.title"
                                    >
                                        <SidebarMenuSubButton
                                            as-child
                                            :is-active="
                                                child.href &&
                                                urlIsActive(child.href, page.url)
                                            "
                                        >
                                            <Link
                                                v-if="child.href"
                                                :href="child.href"
                                            >
                                                <component
                                                    v-if="child.icon"
                                                    :is="child.icon"
                                                />
                                                <span>{{ child.title }}</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </template>
                    </Collapsible>

                    <!-- Regular menu item without children -->
                    <SidebarMenuButton
                        v-else
                        as-child
                        :is-active="
                            item.href && urlIsActive(item.href, page.url)
                        "
                        :tooltip="item.title"
                    >
                        <Link v-if="item.href" :href="item.href">
                            <component v-if="item.icon" :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    </template>
</template>
