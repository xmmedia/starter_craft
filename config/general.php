<?php
/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here. You can see a
 * list of the available settings in vendor/craftcms/cms/src/config/GeneralConfig.php.
 *
 * @see \craft\config\GeneralConfig
 */

use craft\config\GeneralConfig;
use craft\helpers\App;

return GeneralConfig::create()
    // Set the default week start day for date pickers (0 = Sunday, 1 = Monday, etc.)
    ->defaultWeekStartDay(1)
    // Prevent generated URLs from including "index.php"
    ->omitScriptNameInUrls()
    // Enable Dev Mode (see https://craftcms.com/guides/what-dev-mode-does)
    ->devMode(App::env('DEV_MODE') ?? false)
    // Allow administrative changes
    ->allowAdminChanges(App::env('ALLOW_ADMIN_CHANGES') ?? false)
    // Disallow robots
    ->disallowRobots(App::env('DISALLOW_ROBOTS') ?? false)
    // disable the "X-Powered-By: Craft" header
    ->sendPoweredByHeader(false)
    // @todo-craft
    ->timezone('America/Edmonton')
    ->aliases([
        '@web'     => App::env('PRIMARY_SITE_URL'),
        '@webroot' => dirname(__DIR__).'/public',
    ])
    ->convertFilenamesToAscii()
    ->maxUploadFileSize('50M')
    ->cpHeadTags([
        // Traditional favicon
        ['link', ['rel' => 'icon', 'href' => '/icons/favicon.ico']],
        // Scalable favicon for browsers that support them
        ['link', ['rel' => 'icon', 'type' => 'image/svg+xml', 'sizes' => 'any', 'href' => '/icons/favicon.svg']],
        // Touch icon for mobile devices
        ['link', ['rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href' => '/icons/touch-icon.svg']],
        // Pinned tab icon for Safari
        ['link', ['rel' => 'mask-icon', 'href' => '/icons/mask-icon.svg', 'color' => '#663399']],
    ])
;
