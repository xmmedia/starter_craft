<?php

use craft\helpers\App;

return [
    // Path to the Vite manifest file, relative to the Craft base path.
    'manifestPath'               => '@webroot/build/.vite/manifest.json',

    // Dynamically enable dev server based on environment.
    'useDevServer'               => App::env('ENVIRONMENT') === 'dev' || App::env('CRAFT_ENVIRONMENT') === 'dev',

    // URL to the Vite development server.
    'devServerPublic'            => 'https://localhost:9028/',
    'checkDevServer'             => false,

    // Public URL for production assets.
    'serverPublic'               => '/build/',

    // Content-Security-Policy hash for inline tags.
    'includeIntegrityHashes'     => true,
    'includeModulePreloadShim'   => false,
    'includeScriptOnloadHandler' => false,
];
