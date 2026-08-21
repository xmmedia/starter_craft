<?php

use craft\helpers\App;

return [
    // Path to the Vite manifest file, relative to the Craft base path.
    'manifestPath'               => '@webroot/build/.vite/manifest.json',

    // Dynamically enable dev server based on environment.
    'useDevServer'               => 'dev' === App::env('CRAFT_ENVIRONMENT'),

    // URL to the Vite dev server, proxied through Apache to be same-origin
    // (see lando_apache_vite.conf). Works for `lando vite` & a host-run `yarn dev`.
    // @todo-craft update to match your local dev URL
    'devServerPublic'            => 'https://craftstarter.lndo.site/vite-dev/',
    'checkDevServer'             => false,

    // Public URL for production assets.
    'serverPublic'               => '/build/',

    // Content-Security-Policy hash for inline tags.
    'includeIntegrityHashes'     => true,
    'includeModulePreloadShim'   => false,
    'includeScriptOnloadHandler' => false,
];
