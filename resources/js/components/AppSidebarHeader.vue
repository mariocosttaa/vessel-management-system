<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import ThemeDropdown from '@/components/ThemeDropdown.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { BreadcrumbItemType } from '@/types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const user = computed(() => page.props.auth?.user);
const { getInitials } = useInitials();
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-4 border-b border-border/40 bg-background/50 backdrop-blur-sm px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-5"
    >
        <div class="flex items-center gap-3 min-w-0">
            <SidebarTrigger class="-ml-1.5 hover:bg-muted/60 transition-colors" />
            <div class="h-6 w-px bg-border/40" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
            <template v-else>
                <h1 class="text-base font-semibold text-foreground truncate">Dashboard</h1>
            </template>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <!-- Notification Dropdown -->
            <NotificationDropdown />

            <!-- Language Switcher -->
            <LanguageSwitcher />

            <!-- Theme Dropdown -->
            <ThemeDropdown />

            <!-- User Menu -->
            <DropdownMenu v-if="user">
                <DropdownMenuTrigger as-child>
                    <button
                        class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-muted/40 hover:bg-muted/70 dark:bg-muted/20 dark:hover:bg-muted/40 transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                        title="User Menu"
                    >
                        <Avatar class="h-8 w-8 overflow-hidden rounded-full">
                            <AvatarImage
                                v-if="user.avatar"
                                :src="user.avatar"
                                :alt="user.name"
                            />
                            <AvatarFallback
                                class="rounded-full bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                            >
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-56 rounded-lg"
                    align="end"
                    :side-offset="8"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
