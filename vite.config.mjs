import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import { existsSync, readFileSync } from 'node:fs';
import mkcert from 'vite-plugin-mkcert';
import vuePlugin from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import dns from 'dns';

dns.setDefaultResultOrder('verbatim');

// in the Lando node service use the cert Lando issues it; mkcert can't run there
// (needs root, & a CA made in the container isn't trusted by the host browser)
const certPath = '/certs/cert.crt';
const keyPath = '/certs/cert.key';
const inLando = existsSync(certPath) && existsSync(keyPath);
// @todo-craft update to match your local dev URL
const siteOrigin = 'https://craftstarter.lndo.site';
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
        // must match the proxied path in lando_apache_vite.conf
        base: command === 'build' ? '/build/' : '/vite-dev/',
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
            // @todo-craft change port number 2x (must match lando_apache_vite.conf)
            port: 9028,
            // always reached through the appserver proxy, in the container or on the host
            origin: siteOrigin,
            // the proxy passes the site's Host through; Vite 400s unknown hosts
            allowedHosts: [new URL(siteOrigin).hostname],
            // the HMR socket rides the proxied path, not the Vite port
            hmr: {
                protocol: 'wss',
                host: new URL(siteOrigin).hostname,
                clientPort: 443,
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
