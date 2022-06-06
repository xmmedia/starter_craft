<?php
/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here. You can see a
 * list of the available settings in vendor/craftcms/cms/src/config/GeneralConfig.php.
 *
 * @see \craft\config\GeneralConfig
 */

use craft\helpers\App;

return [
    // Global settings
    '*' => [
        // Default Week Start Day (0 = Sunday, 1 = Monday...)
        'defaultWeekStartDay' => 1,

        // Whether generated URLs should omit "index.php"
        'omitScriptNameInUrls' => true,

        // Disable GraphQL
        'enableGql' => false,

        // Control panel trigger word
        'cpTrigger' => 'admin',

        // The secure key Craft will use for hashing and encrypting data
        'securityKey' => App::env('SECURITY_KEY'),

        // @todo-craft
        'timezone' => 'America/Edmonton',

        'ga_tracking_id' => App::env('GA_TRACKING_ID'),

        'sendPoweredByHeader' => false,

        'aliases' => [
            '@webroot' => dirname(__DIR__) . '/public',
        ],

        'cpHeadTags' => [
            // Traditional favicon
            ['link', ['rel' => 'icon', 'href' => '/icons/favicon.ico']],
            // Scalable favicon for browsers that support them
            ['link', ['rel' => 'icon', 'type' => 'image/svg+xml', 'sizes' => 'any', 'href' => '/icons/favicon.svg']],
            // Touch icon for mobile devices
            ['link', ['rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href' => '/icons/touch-icon.svg']],
            // Pinned tab icon for Safari
            ['link', ['rel' => 'mask-icon', 'href' => '/icons/mask-icon.svg', 'color' => '#663399']],
        ],
    ],

    // Dev environment settings
    'dev' => [
        // Dev Mode (see https://craftcms.com/guides/what-dev-mode-does)
        'devMode' => true,

        // Prevent crawlers from indexing pages and following links
        'disallowRobots' => true,
    ],

    // Staging environment settings
    'staging' => [
        // Set this to `false` to prevent administrative changes from being made on staging
        'allowAdminChanges' => false,

        // Prevent crawlers from indexing pages and following links
        'disallowRobots' => true,
    ],

    // Production environment settings
    'production' => [
        // Set this to `false` to prevent administrative changes from being made on production
        'allowAdminChanges' => false,
    ],
];
