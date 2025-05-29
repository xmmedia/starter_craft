import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import mkcert from 'vite-plugin-mkcert';
import vuePlugin from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import dns from 'dns';

dns.setDefaultResultOrder('verbatim');

export default defineConfig(({ command }) => {
    return {
        appType: 'custom',
        base: '/build',
        build: {
            outDir: 'public/build',
            sourcemap: 'serve' === command,
            rollupOptions: {
                input: {
                    public: './public/js/src/public.js',
                    editor: './public/js/src/editor.js',
                },
            },
            copyPublicDir: false,
            assetsInlineLimit: 0,
            manifest: true,
        },
        clearScreen: false,
        css: {
            devSourcemap: true,
        },
        plugins: [
            mkcert(),
            vuePlugin(),
            tailwindcss(),
        ],
        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./public/js/src', import.meta.url)),
            },
        },
        server: {
            host: true,
            port: 9028,
            origin: 'https://localhost:9028',
            cors: {
                origin: 'https://craftstarter.lndo.site',
            },
            strictPort: true,
            https: true,
            watch: {
                ignored: ['**/vendor/**', '**/var/**'],
            },
        },
    };
});
