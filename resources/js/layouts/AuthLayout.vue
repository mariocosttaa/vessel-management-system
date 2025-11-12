<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import ThemeDropdown from '@/components/ThemeDropdown.vue';
import { Link } from '@inertiajs/vue3';
import { landing } from '@/routes';
import { useI18n } from '@/composables/useI18n';
import { computed } from 'vue';

defineProps<{
    title?: string;
    description?: string;
}>();

const { t } = useI18n();
const appName = computed(() => import.meta.env.VITE_APP_NAME || 'Bindamy Mareas');
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-background px-4 py-12 sm:px-6 lg:px-8">
        <!-- Background gradient with subtle blue accent -->
        <div
            class="fixed inset-0 -z-10 overflow-hidden bg-gradient-to-br from-background via-background to-primary/5 dark:from-background dark:via-background dark:to-primary/10"
        >
            <!-- Subtle pattern overlay -->
            <div
                class="absolute inset-0 opacity-[0.015] dark:opacity-[0.03]"
                style="background-image: radial-gradient(circle at 1px 1px, currentColor 1px, transparent 0); background-size: 24px 24px;"
            ></div>
        </div>

        <!-- Language Switcher and Theme Switcher at top-right -->
        <div class="fixed top-4 right-4 z-50 flex items-center gap-2 sm:top-6 sm:right-6">
            <!-- Theme Dropdown -->
            <div class="rounded-lg border border-border/50 bg-card/80 backdrop-blur-md shadow-lg dark:border-border/30 dark:bg-card/60 p-0.5">
                <div class="rounded-md">
                    <ThemeDropdown />
                </div>
            </div>
            <!-- Language Switcher -->
            <div class="rounded-lg border border-border/50 bg-card/80 backdrop-blur-md shadow-lg dark:border-border/30 dark:bg-card/60 p-0.5">
                <div class="rounded-md">
                    <LanguageSwitcher />
                </div>
            </div>
        </div>

        <!-- Logo at top -->
        <div class="mb-8 flex w-full max-w-md items-center justify-center">
            <Link :href="landing.url()" class="flex items-center gap-2 transition-opacity hover:opacity-80">
                <AppLogo />
            </Link>
        </div>

        <!-- Main card with glass morphism effect -->
        <div class="w-full max-w-md">
            <div
                class="relative overflow-hidden rounded-2xl border border-border/50 bg-card/80 backdrop-blur-xl shadow-2xl dark:border-border/30 dark:bg-card/60"
            >
                <!-- Subtle blue accent glow -->
                <div
                    class="absolute -inset-0.5 bg-gradient-to-r from-primary/20 via-primary/10 to-primary/20 opacity-50 blur-xl dark:opacity-30"
                ></div>

                <!-- Content -->
                <div class="relative px-8 py-10 sm:px-10 sm:py-12">
                    <!-- Header -->
                    <div class="mb-8 text-center">
                        <h1
                            v-if="title"
                            class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl"
                        >
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            class="mt-3 text-sm text-muted-foreground sm:text-base"
                        >
                            {{ description }}
                        </p>
                    </div>

                    <!-- Form content -->
                    <div class="space-y-6">
                        <slot />
                    </div>
                </div>
            </div>

            <!-- Footer text -->
            <div class="mt-6 text-center text-xs text-muted-foreground">
                <p>
                    © {{ new Date().getFullYear() }} {{ appName }}. {{ t('All rights reserved.') }}
                </p>
            </div>
        </div>
    </div>
</template>
