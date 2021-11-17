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
            ['link', ['rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href' => '/apple-touch-icon.png']],
            ['link', ['rel' => 'icon', 'type' => 'image/png', 'sizes' => '32x32', 'href' => '/favicon-32x32.png']],
            ['link', ['rel' => 'icon', 'type' => 'image/png','sizes' => '16x16', 'href' => '/favicon-16x16.png']],
            ['link', ['rel' => 'manifest', 'href' => '/site.webmanifest']],
            // @todo-craft update the next two based on colours for the favicon
            ['link', ['rel' => 'mask-icon', 'href' => '/safari-pinned-tab.svg', 'color' => '#603cba']],
            ['meta', ['name' => 'msapplication-TileColor', 'content' => '#603cba']],
            ['meta', ['name' => 'theme-color', 'content' => '#ffffff']],
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
