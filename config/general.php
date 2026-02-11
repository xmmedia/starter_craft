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
    // @todo-craft update with new favicons
    ->cpHeadTags([
        ['link', ['rel' => 'icon', 'type' => 'image/png', 'href' => '/favicon-96x96.png', 'sizes' => '96x96']],
        ['link', ['rel' => 'icon', 'type' => 'image/svg+xml', 'href' => '/favicon.svg']],
        ['link', ['rel' => 'shortcut icon', 'href' => '/favicon.ico']],
        ['link', ['rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href' => '/apple-touch-icon.png']],
        ['meta', ['name' => 'apple-mobile-web-app-title', 'content' => '#ffffff']],
        ['link', ['rel' => 'manifest', 'href' => '/site.webmanifest']],
    ])
;
