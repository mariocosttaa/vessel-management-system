import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import i18n from './i18n';
import type { User } from './types';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer(
    (page) =>
        createInertiaApp({
            page,
            render: renderToString,
            title: (title) => (title ? `${title} - ${appName}` : appName),
            resolve: (name) =>
                resolvePageComponent(
                    `./pages/${name}.vue`,
                    import.meta.glob<DefineComponent>('./pages/**/*.vue'),
                ),
            setup: ({ App, props, plugin }) => {
                const app = createSSRApp({ render: () => h(App, props) });

                // Set locale from user preference or props (matching client-side logic)
                const user = props.initialPage?.props?.auth?.user as User | undefined;
                if (user?.language && ['en', 'pt', 'es', 'fr'].includes(user.language)) {
                    i18n.global.locale.value = user.language as any;
                } else if (props.initialPage?.props?.locale) {
                    i18n.global.locale.value = props.initialPage.props.locale as any;
                }

                return app.use(plugin).use(i18n);
            },
        }),
    { cluster: true },
);
