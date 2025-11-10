<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Bell, Globe } from 'lucide-vue-next';
import { ref } from 'vue';
import type { BreadcrumbItemType } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const showNotifications = ref(false);
const showLanguageMenu = ref(false);

const handleNotificationClick = () => {
    showNotifications.value = !showNotifications.value;
    // TODO: Implement notification dropdown/modal
};

const handleLanguageClick = () => {
    showLanguageMenu.value = !showLanguageMenu.value;
    // TODO: Implement language dropdown/modal
};
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

        <div class="flex items-center gap-1.5 shrink-0">
            <!-- Notification Icon -->
            <button
                @click="handleNotificationClick"
                class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-muted/40 hover:bg-muted/70 dark:bg-muted/20 dark:hover:bg-muted/40 transition-all duration-200 relative group"
                title="Notifications"
            >
                <Bell class="w-4 h-4 text-muted-foreground group-hover:text-foreground transition-colors" />
                <!-- Notification badge placeholder - can be made dynamic later -->
                <!-- <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-primary rounded-full ring-2 ring-background"></span> -->
            </button>

            <!-- Language Change Icon -->
            <button
                @click="handleLanguageClick"
                class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-muted/40 hover:bg-muted/70 dark:bg-muted/20 dark:hover:bg-muted/40 transition-all duration-200 group"
                title="Change Language"
            >
                <Globe class="w-4 h-4 text-muted-foreground group-hover:text-foreground transition-colors" />
            </button>

            <!-- Dark Mode Toggle -->
            <ThemeToggle />
        </div>
    </header>
</template>
