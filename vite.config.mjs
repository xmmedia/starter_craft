import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import mkcert from 'vite-plugin-mkcert';
import vuePlugin from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import dns from 'dns';

dns.setDefaultResultOrder('verbatim');

export default defineConfig(({ command }) => {
    return {
        plugins: [
            mkcert(),
            vuePlugin(),
            tailwindcss(),
        ],
        build: {
            outDir: 'public/build',
            rollupOptions: {
                input: {
                    public: './public/js/src/public.js',
                    editor: './public/js/src/editor.js',
                },
            },
            sourcemap: 'serve' === command,
            // don't inline assets
            copyPublicDir: false,
            assetsInlineLimit: 0,
            manifest: true,
        },
        css: {
            devSourcemap: true,
        },
        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./public/js/src', import.meta.url)),
            },
        },
        server: {
            host: true,
            // @todo-craft change port number 2x
            port: 9028,
            origin: 'https://localhost:9028',
            cors: {
                // @todo-craft update to match your local dev URL
                origin: 'https://craftstarter.lndo.site',
            },
            strictPort: true,
            https: true,
            watch: {
                ignored: ['**/vendor/**', '**/var/**'],
            },
        },
        appType: 'custom',
        clearScreen: false,
    };
});
