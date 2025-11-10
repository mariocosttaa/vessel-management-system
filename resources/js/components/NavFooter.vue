<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { urlIsActive } from '@/lib/utils';
import { usePage } from '@inertiajs/vue3';

interface Props {
    items: NavItem[];
    class?: string;
}

const props = defineProps<Props>();
const page = usePage();
</script>

<template>
    <SidebarGroup
        :class="`group-data-[collapsible=icon]:p-0 ${props.class || ''}`"
    >
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in items" :key="item.title">
                    <SidebarMenuButton
                        class="text-muted-foreground hover:text-foreground hover:bg-muted/50 dark:hover:bg-muted/30 font-medium transition-all duration-200"
                        as-child
                        :is-active="urlIsActive(item.href, page.url)"
                    >
                        <Link
                            :href="item.href"
                            class="flex items-center gap-2"
                        >
                            <component :is="item.icon" class="w-4 h-4" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
