import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { createHash } from 'node:crypto';
import { defineConfig } from 'vite';

const generateComponentId = (filepath: string, source: string, isProduction?: boolean) => {
    const token = filepath + (isProduction ? source : '');

    return createHash('sha256').update(token).digest('hex').substring(0, 8);
};

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
            features: {
                componentIdGenerator: (filepath, source, isProduction) =>
                    generateComponentId(filepath, source, isProduction),
            },
        }),
    ],
});
