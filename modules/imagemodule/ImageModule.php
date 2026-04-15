<?php
/**
 * Image module for Craft CMS 5.x
 *
 * @link      https://www.xmmedia.com
 * @copyright Copyright (c) 2022 XM Media Inc.
 */

declare(strict_types=1);

namespace modules\imagemodule;

use modules\imagemodule\twigextensions\ImageTwigExtension;
use Craft;
use yii\base\Module as BaseModule;

/**
 * @author  XM Media Inc.
 * @package ImageModule
 * @since   1.0.0
 *
 * @method static ImageModule getInstance()
 */
class ImageModule extends BaseModule
{
    public function init(): void
    {
        Craft::setAlias('@modules/imagemodule', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'modules\\imagemodule\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\imagemodule\\controllers';
        }

        parent::init();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function() {
            // Add in our Twig extensions
            Craft::$app->getView()->registerTwigExtension(new ImageTwigExtension());
        });

        /**
         * Logging in Craft involves using one of the following methods:
         *
         * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
         * Craft::info(): record a message that conveys some useful information.
         * Craft::warning(): record a warning message that indicates something unexpected has happened.
         * Craft::error(): record a fatal error that should be investigated as soon as possible.
         *
         * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
         *
         * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
         * the category to the method (prefixed with the fully qualified class name) where the constant appears.
         *
         * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
         * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
         *
         * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
         */
        Craft::info('Image module loaded', __METHOD__);
    }
}
