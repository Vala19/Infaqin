import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            // Listen on the LAN so a phone on the same Wi-Fi can load assets.
            // Set VITE_HMR_HOST in .env to this machine's LAN IP.
            host: '0.0.0.0',
            hmr: {
                host: env.VITE_HMR_HOST || 'localhost',
            },
            cors: true,
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
