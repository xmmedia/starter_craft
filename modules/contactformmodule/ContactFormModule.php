<?php

declare(strict_types=1);

namespace modules\contactformmodule;

use craft\base\Model;
use craft\contactform\events\SendEvent;
use craft\contactform\Mailer;
use craft\contactform\models\Submission;
use yii\base\Event;
use yii\base\Module as BaseModule;

/**
 * @method static ContactFormModule getInstance()
 */
class ContactFormModule extends BaseModule
{
    #[\Override]
    public function init(): void
    {
        \Craft::setAlias('@modules/contactformmodule', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (\Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'modules\\contactformmodule\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\contactformmodule\\controllers';
        }

        parent::init();

        $this->attachEventHandlers();
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            Mailer::class,
            Mailer::EVENT_BEFORE_SEND,
            static function (SendEvent $e) {
                // set the from to the default mailer from
                // this is instead of "<prefix> <fromName>" which is confusing
                $e->message->setFrom(\Craft::$app->getMailer()->from);
                $e->message->setSubject(
                    'Website form submission from '.$e->submission->fromName
                );
            }
        );

        // do some additional/different validation
        Event::on(
            Submission::class,
            Model::EVENT_AFTER_VALIDATE,
            static function (Event $e) {
                /** @var Submission $submission */
                $submission = $e->sender;

                if (empty(trim((string) $submission->fromName))) {
                    $submission->clearErrors('fromName');
                    $submission->addError(
                        'fromName',
                        'Please enter your name.'
                    );
                }

                if (empty(trim((string) $submission->fromEmail))) {
                    $submission->clearErrors('fromEmail');
                    $submission->addError(
                        'fromEmail',
                        'Please add your email address.'
                    );
                }

                if (empty(trim((string) $submission->message['body']))) {
                    $submission->addError(
                        'message.body',
                        'Please add a message.'
                    );
                }
            }
        );
    }
}
