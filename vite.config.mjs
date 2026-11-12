import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import vuePlugin from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import dns from 'dns';

dns.setDefaultResultOrder('verbatim');

// runs in the DDEV web container, behind https it doesn't serve itself: the dev server through
// Apache's proxy at /vite-dev/ (.ddev/web-build/apache-vite.conf), so dev assets are same-origin
// with the site (browsers won't load an SVG <use> from another origin), & the preview through
// DDEV's router (web_extra_exposed_ports in .ddev/config.yaml). Both use Vite's default ports
// (5173 & 4173), set explicitly as apache-vite.conf & .ddev/config.yaml rely on them: they're per
// container & the router routes by hostname, so every project can use the same ones
// includes the router's port, when it isn't 443; only the dev server uses it
const siteUrl = new URL(process.env.DDEV_PRIMARY_URL || 'https://localhost');

export default defineConfig(({ command, isPreview }) => {
    if ('serve' === command && !isPreview && !process.env.DDEV_PRIMARY_URL) {
        throw new Error('The dev server only runs in DDEV, behind its Apache proxy: use `ddev yarn dev`');
    }

    return {
        plugins: [
            vuePlugin({
                // leave absolute URLs (/images/…) in templates as-is: they're served from
                // the webroot, not resolved as imports (which fail without a publicDir)
                template: { transformAssetUrls: { includeAbsolute: false } },
            }),
            tailwindcss(),
        ],
        // the dev base must match the proxied path in apache-vite.conf; preview runs as `serve`,
        // but should mirror the production paths
        base: command === 'build' || isPreview ? '/build/' : '/vite-dev/',
        // Craft's webroot is public/, not a Vite static dir: without this Vite treats
        // every source file under it as a public asset & warns on each import
        publicDir: false,
        build: {
            outDir: 'public/build',
            rolldownOptions: {
                input: {
                    public: './public/js/src/public.js',
                    editor: './public/js/src/editor.js',
                },
            },
            sourcemap: 'serve' === command,
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
            // Vite's default
            port: 5173,
            strictPort: true,
            origin: siteUrl.origin,
            // the proxy passes the site's Host through; Vite 400s unknown hosts
            allowedHosts: [siteUrl.hostname],
            // the HMR socket rides the proxied path, not the Vite port
            hmr: {
                protocol: 'wss',
                host: siteUrl.hostname,
                clientPort: Number(siteUrl.port || 443),
            },
            watch: {
                // matched against the absolute path, so they must start with **/
                // node_modules, .git, the cache dir & build.outDir are already ignored by vite
                ignored: [
                    '**/.ddev/**',
                    '**/.idea/**',
                    '**/bin/**',
                    '**/docs/**',
                    '**/public/assets/**',
                    '**/public/cpresources/**',
                    '**/storage/**',
                    '**/vendor/**',
                ],
            },
        },
        preview: {
            // Vite's default
            port: 4173,
            strictPort: true,
        },
        appType: 'custom',
        clearScreen: false,
    };
});
