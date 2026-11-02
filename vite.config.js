import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    resolve: { alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) } },
    plugins: [
        vue(),
        laravel({
            input: ['src/assets/styles/global.css', 'src/app.js', 'resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        tailwindcss(),
        inertia(),
        process.env.WAYFINDER_SKIP_GENERATION !== 'true' && wayfinder({ formVariants: true }),
    ],
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
        watch: {
            usePolling: true,
            interval: 1000,
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
