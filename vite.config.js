import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import { fileURLToPath, URL } from 'node:url';

const inertiaSsrEnabled = process.env.INERTIA_SSR_ENABLED === 'true';
const vitePort = Number(process.env.VITE_PORT || 5173);
const usePolling = process.env.VITE_USE_POLLING !== 'false';
const pollInterval = Number(process.env.VITE_POLL_INTERVAL || 3000);

export default defineConfig({
    resolve: { alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) } },
    plugins: [
        vue(),
        laravel({
            input: ['src/assets/styles/global.css', 'src/app.js', 'resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            server: {
                host: '0.0.0.0',
                port: vitePort,
                origin: `http://localhost:${vitePort}`,
            },
        }),
        tailwindcss(),
        inertia({ ssr: inertiaSsrEnabled ? undefined : false }),
        process.env.WAYFINDER_SKIP_GENERATION !== 'true' && wayfinder({ formVariants: true }),
    ],
    server: {
        port: vitePort,
        strictPort: true,
        host: '0.0.0.0',
        origin: `http://localhost:${vitePort}`,
        cors: {
            origin: ['http://localhost:8000', 'http://localhost:8010'],
        },
        hmr: {
            host: 'localhost',
            clientPort: vitePort,
        },
        watch: {
            usePolling,
            interval: pollInterval,
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
