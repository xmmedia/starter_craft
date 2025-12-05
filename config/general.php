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
    ->defaultWeekStartDay(0)
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
    // disable graphql
    ->enableGql(false)
    // @todo-craft
    ->timezone('America/Edmonton')
    ->aliases([
        '@web'     => App::env('PRIMARY_SITE_URL'),
        '@webroot' => dirname(__DIR__).'/public',
    ])
    ->convertFilenamesToAscii()
    // disable garbage collection for unsaved drafts and soft-deleted elements
    ->purgeUnsavedDraftsDuration(0)
    ->softDeleteDuration(0)
    ->maxUploadFileSize('50M')
    // in prod/staging, don't run the queue automatically, instead use cron job (every minute)
    ->runQueueAutomatically(App::env('DEV_MODE') ?? false)
    ->cpHeadTags([
        ['link', ['rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href', '/apple-touch-icon.png']],
        ['link', ['rel' => 'icon', 'type' => 'image/png', 'sizes' => '32x32', 'href' => '/favicon-32x32.png']],
        ['link', ['rel' => 'icon', 'type' => 'image/png', 'sizes' => '16x16', 'href' => '/favicon-16x16.png']],
        ['link', ['rel' => 'manifest', 'href' => '/site.webmanifest']],
        ['link', ['rel' => 'mask-icon', 'href' => '/safari-pinned-tab.svg', 'color' => '#5bbad5']],
        ['meta', ['name' => 'msapplication-TileColor', 'content' => '#ffffff']],
        ['meta', ['name' => 'theme-color', 'content' => '#ffffff']],
    ])
;
