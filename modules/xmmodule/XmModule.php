<?php
/**
 * XM module for Craft CMS 5.x
 *
 * @link      https://www.xmmedia.com
 * @copyright Copyright (c) 2022 XM Media Inc.
 */

declare(strict_types=1);

namespace modules\xmmodule;

use Craft;
use craft\mail\Mailer;
use modules\xmmodule\twigextensions\XmTwigExtension;
use yii\base\Module as BaseModule;
use yii\mail\BaseMailer;
use yii\mail\MailEvent;

/**
 * @author  XM Media Inc.
 * @package XmModule
 * @since   1.0.0
 *
 * @method static XmModule getInstance()
 */
class
XmModule extends BaseModule
{
    public function init(): void
    {
        Craft::setAlias('@modules/xmmodule', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'modules\\xmmodule\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\xmmodule\\controllers';
        }

        parent::init();

        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function() {
            // Add in our Twig extensions
            Craft::$app->getView()->registerTwigExtension(new XmTwigExtension());
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
        Craft::info('XM module loaded', __METHOD__);
    }

    private function attachEventHandlers(): void
    {
        // Append site name to system email subjects
        // EVENT_BEFORE_SEND is defined on \yii\mail\BaseMailer (parent of craft\mail\Mailer)
        \yii\base\Event::on(Mailer::class, BaseMailer::EVENT_BEFORE_SEND, function(MailEvent $event) {
            $key = $event->message->key ?? null;

            if (!in_array($key, ['account_activation', 'forgot_password'], true)) {
                return;
            }

            $siteName = Craft::$app->getSystemName();
            $currentSubject = $event->message->getSubject();
            $event->message->setSubject($currentSubject . ' on ' . $siteName);
        });
    }
}
