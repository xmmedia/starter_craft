import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import mkcert from 'vite-plugin-mkcert';
import vuePlugin from '@vitejs/plugin-vue';
import dns from 'dns';

dns.setDefaultResultOrder('verbatim');

export default defineConfig({
    plugins: [
        mkcert(),
        vuePlugin(),
    ],
    base: '/build',
    build: {
        outDir: 'public/build',
        rollupOptions: {
            input: {
                public: './public/js/src/public.js',
                editor: './public/js/src/editor.js',
            },
        },
        sourcemap: true,
        copyPublicDir: false,
        assetsInlineLimit: 0,
        manifest: true,
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./public/js/src', import.meta.url)),
        },
    },
    css: {
        devSourcemap: true,
    },
    server: {
        host: true,
        port: 9028,
        origin: 'https://localhost:9028',
        strictPort: true,
        https: true,
        watch: {
            ignored: ['**/vendor/**', '**/var/**'],
        },
    },
    appType: 'custom',
    clearScreen: false,
});
