import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';
import i18n from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Set locale from user preference, localStorage, or props
        if (typeof window !== 'undefined') {
            // First try to get from user's saved preference (from Inertia props)
            const userLanguage = props.initialPage?.props?.auth?.user?.language;
            if (userLanguage && ['en', 'pt', 'es', 'fr'].includes(userLanguage)) {
                i18n.global.locale.value = userLanguage as any;
                localStorage.setItem('locale', userLanguage);
            } else {
                // Fallback to localStorage
                const savedLocale = localStorage.getItem('locale');
                if (savedLocale && ['en', 'pt', 'es', 'fr'].includes(savedLocale)) {
                    i18n.global.locale.value = savedLocale as any;
                } else if (props.initialPage?.props?.locale) {
                    i18n.global.locale.value = props.initialPage.props.locale as any;
                    localStorage.setItem('locale', props.initialPage.props.locale);
                }
            }
        } else if (props.initialPage?.props?.locale) {
            i18n.global.locale.value = props.initialPage.props.locale as any;
        } else if (props.initialPage?.props?.auth?.user?.language) {
            i18n.global.locale.value = props.initialPage.props.auth.user.language as any;
        }

        app.use(plugin).use(i18n).mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
