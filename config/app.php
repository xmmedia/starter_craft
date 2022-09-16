<?php
/**
 * Yii Application Config
 *
 * Edit this file at your own risk!
 *
 * The array returned by this file will get merged with
 * vendor/craftcms/cms/src/config/app.php and app.[web|console].php, when
 * Craft's bootstrap script is defining the configuration for the entire
 * application.
 *
 * You can define custom modules and system components, and even override the
 * built-in system components.
 *
 * If you want to modify the application config for *only* web requests or
 * *only* console requests, create an app.web.php or app.console.php file in
 * your config/ folder, alongside this one.
 */

use craft\helpers\App;

return [
    'id' => App::env('APP_ID') ?: 'CraftCMS',
    'modules' => [
        'contact-form' => \modules\ContactFormModule::class,
        'image-module' => [
            'class' => \modules\imagemodule\ImageModule::class,
        ],
    ],
    'bootstrap' => ['contact-form', 'image-module'],
    'components' => [
        'session' => function () {
            $savePath = Craft::getAlias('@storage').'/sessions/';

            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }

            // Get the default component config
            $config = craft\helpers\App::sessionConfig();
            $config['savePath'] = $savePath;

            // Instantiate and return it
            return Craft::createObject($config);
        },
    ],
];
