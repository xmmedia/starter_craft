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
    build: {
        outDir: 'public/build', // Ensure the output directory is correct
        rollupOptions: {
            input: {
                public: './public/js/src/public.js', // Your entry file
                editor: './public/js/src/editor.js',
            },
        },
        sourcemap: true,
        assetsInlineLimit: 0,
        manifest: true, // Enable manifest generation
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./public/js/src', import.meta.url)),
        },
        extensions: ['.vue', '.mjs', '.js', '.mts', '.ts', '.jsx', '.tsx', '.json'],
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
