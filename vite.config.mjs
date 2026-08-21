import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import { existsSync, readFileSync } from 'node:fs';
import mkcert from 'vite-plugin-mkcert';
import vuePlugin from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import dns from 'dns';

dns.setDefaultResultOrder('verbatim');

// inside the Lando node service (`lando vite`), use the certificate Lando issues it:
// signed by the CA Lando installs on the host, with localhost & 127.0.0.1 in the SANs.
// mkcert can't run there — it needs root, & a CA made in the container isn't trusted by
// the host browser. Outside (host `yarn dev`, builds, CI), mkcert handles the cert.
const certPath = '/certs/cert.crt';
const keyPath = '/certs/cert.key';
const inLando = existsSync(certPath) && existsSync(keyPath);
const https = inLando ? {
    cert: readFileSync(certPath),
    key: readFileSync(keyPath),
} : true;

export default defineConfig(({ command }) => {
    return {
        plugins: [
            ...(inLando ? [] : [mkcert()]),
            vuePlugin(),
            tailwindcss(),
        ],
        base: command === 'build' ? '/build/' : '/',
        build: {
            outDir: 'public/build',
            rolldownOptions: {
                input: {
                    public: './public/js/src/public.js',
                    editor: './public/js/src/editor.js',
                },
            },
            sourcemap: 'serve' === command,
            copyPublicDir: false,
            // don't inline assets
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
            // @todo-craft change port number 2x (must match .lando.yml)
            port: 9028,
            origin: 'https://localhost:9028',
            cors: {
                // @todo-craft update to match your local dev URL
                origin: 'https://craftstarter.lndo.site',
            },
            strictPort: true,
            https,
            watch: {
                ignored: ['**/vendor/**', '**/storage/**'],
            },
        },
        appType: 'custom',
        clearScreen: false,
    };
});
