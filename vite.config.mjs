import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import mkcert from'vite-plugin-mkcert';
import vuePlugin from '@vitejs/plugin-vue';
import manifestSRIPlugin from 'vite-plugin-manifest-sri';
import symfonyPlugin from 'vite-plugin-symfony';
import dns from 'dns';

dns.setDefaultResultOrder('verbatim');

export default defineConfig({
    plugins: [
        mkcert(),
        vuePlugin(),
        manifestSRIPlugin(),
        symfonyPlugin({
            refresh: true,
            sriAlgorithm: 'sha384',
        }),
    ],
    build: {
        outDir: 'public/build',
        rollupOptions: {
            input: {
                public: './public/js/src/public.js',
            },
        },
        sourcemap: true,
        // don't inline assets
        assetsInlineLimit: 0,
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./public/js/src', import.meta.url)),
        },
        // the default plus .vue
        extensions: ['.vue', '.mjs', '.js', '.mts', '.ts', '.jsx', '.tsx', '.json'],
    },
    css: {
        devSourcemap: true,
    },
    server: {
        host: true,
        // @todo-craft change port number
        port: 9028,
        origin: 'https://localhost:9028',
        strictPort: true,
        https: true,
        watch: {
            // this is in part needed because the symfony plugin ignores the public dir completely
            ignored: ['**/vendor/**', '**/var/**'],
        },
    },
    appType: 'custom',
    clearScreen: false,
});
