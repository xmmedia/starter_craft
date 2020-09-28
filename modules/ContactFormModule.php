<?php
namespace modules;

use Craft;
use craft\contactform\models\Submission;
use yii\base\Event;
use craft\contactform\events\SendEvent;
use craft\contactform\Mailer;

/**
 * Custom module class.
 *
 * This class will be available throughout the system via:
 * `Craft::$app->getModule('my-module')`.
 *
 * You can change its module ID ("my-module") to something else from
 * config/app.php.
 *
 * If you want the module to get loaded on every request, uncomment this line
 * in config/app.php:
 *
 *     'bootstrap' => ['my-module']
 *
 * Learn more about Yii module development in Yii's documentation:
 * http://www.yiiframework.com/doc-2.0/guide-structure-modules.html
 */
class ContactFormModule extends \yii\base\Module
{
    /**
     * Initializes the module.
     */
    public function init()
    {
        // Set a @modules alias pointed to the modules/ directory
        Craft::setAlias('@modules', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'modules\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\controllers';
        }

        parent::init();

        Event::on(
            Mailer::class,
            Mailer::EVENT_BEFORE_SEND,
            function (SendEvent $e) {
                // set the from to the default mailer from
                // this is instead of "<prefix> <fromName>" which is confusing
                $e->message->setFrom(Craft::$app->getMailer()->from);
            }
        );

        // do some additional/different validation
        Event::on(
            Submission::class,
            Submission::EVENT_AFTER_VALIDATE,
            function (Event $e) {
                /** @var Submission $submission */
                $submission = $e->sender;

                if (empty($submission->fromName) || empty(trim($submission->fromName))) {
                    $submission->clearErrors('fromName');
                    $submission->addError(
                        'fromName',
                        'Please enter your name.'
                    );
                }

                if (empty($submission->fromEmail) || empty(trim($submission->fromEmail))) {
                    $submission->clearErrors('fromEmail');
                    $submission->addError(
                        'fromEmail',
                        'Please add your email address.'
                    );
                }

                if (empty($submission->message['body']) || empty(trim($submission->message['body']))) {
                    $submission->addError(
                        'message.body',
                        'Please add a message.'
                    );
                }
            }
        );
    }
}
