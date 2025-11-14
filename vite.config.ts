import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import * as nodeCrypto from 'node:crypto';
import { defineConfig } from 'vite';

type ExtendedNodeCrypto = typeof nodeCrypto & {
    hash?: (algorithm: string, data: nodeCrypto.BinaryLike, encoding: nodeCrypto.BinaryToTextEncoding) => string;
};

const cryptoModule = nodeCrypto as ExtendedNodeCrypto;

if (typeof cryptoModule.hash !== 'function') {
    cryptoModule.hash = (algorithm, data, encoding) => {
        return cryptoModule.createHash(algorithm).update(data).digest(encoding);
    };
}

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
        }),
    ],
});
