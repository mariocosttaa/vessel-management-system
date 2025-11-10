<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import { Globe } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { SupportedLocale } from '@/i18n';

const { setLocale, getCurrentLocale, supportedLocales, localeNames } = useI18n();
const page = usePage();

const currentLocale = ref<SupportedLocale>(getCurrentLocale());

// Check if user is authenticated
const isAuthenticated = computed(() => !!page.props.auth?.user);

const setCookie = (name: string, value: string, days = 365) => {
    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const changeLocale = (locale: SupportedLocale) => {
    // Update locally first for immediate feedback
    currentLocale.value = locale;
    setLocale(locale);
    setCookie('locale', locale);

    // If user is not authenticated, reload the page to apply the new locale from cookie
    if (!isAuthenticated.value) {
        // Reload the page so the backend can read the cookie and send it in props
        // The cookie is set synchronously above, so it will be available in the request
        router.reload({
            only: [],
        });
        return;
    }

    // If user is authenticated, update in backend using Inertia API call
    router.put('/panel/language', { language: locale }, {
        preserveState: true,
        preserveScroll: true,
        only: [],
        onSuccess: () => {
            // Language updated, page will refresh with new locale
        },
        onError: () => {
            console.error('Failed to update language');
            // Revert local changes on error
            currentLocale.value = getCurrentLocale();
            setLocale(getCurrentLocale());
        },
    });
};

onMounted(() => {
    // Priority order:
    // 1. User's saved language preference (for authenticated users)
    // 2. Locale from props (which comes from backend cookie)
    // 3. Current locale from i18n system

    const userLanguage = (page.props.auth?.user as any)?.language;
    const propsLocale = (page.props as any)?.locale;

    if (userLanguage && supportedLocales.includes(userLanguage as SupportedLocale)) {
        currentLocale.value = userLanguage as SupportedLocale;
    } else if (propsLocale && supportedLocales.includes(propsLocale as SupportedLocale)) {
        currentLocale.value = propsLocale as SupportedLocale;
    } else {
        currentLocale.value = getCurrentLocale();
    }
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger
            class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-muted-foreground hover:text-card-foreground hover:bg-muted transition-colors"
        >
            <Globe class="w-4 h-4" />
            <span class="hidden sm:inline">{{ localeNames[currentLocale] }}</span>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-40">
            <DropdownMenuItem
                v-for="locale in supportedLocales"
                :key="locale"
                @click="changeLocale(locale)"
                :class="currentLocale === locale ? 'bg-muted' : ''"
            >
                <span class="flex items-center justify-between w-full">
                    <span>{{ localeNames[locale] }}</span>
                    <span
                        v-if="currentLocale === locale"
                        class="text-primary text-xs"
                    >
                        ✓
                    </span>
                </span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

