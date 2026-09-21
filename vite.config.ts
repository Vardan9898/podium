import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [
        // Asset pipeline only; Vitest doesn't need it (and it refuses to boot when CI=true).
        ...(process.env.VITEST
            ? []
            : [
                  laravel({
                      input: ['resources/css/app.css', 'resources/js/app.ts'],
                      refresh: true,
                  }),
              ]),
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        host: '0.0.0.0',
        hmr: { host: 'localhost' },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/*.spec.ts'],
        restoreMocks: true,
    },
});
