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
            static function (SendEvent $e): void {
                // set the from to the default mailer from
                // this is instead of "<prefix> <fromName>" which is confusing
                $e->message->setFrom(\Craft::$app->getMailer()->from);

                // the formName field is submitted by the form & so can't be trusted:
                // only use it if it's one of the names we know about
                $formName = self::messageValue($e->submission, 'formName');
                if (!\in_array($formName, self::formNames(), true)) {
                    $formName = null;
                }

                $e->message->setSubject(
                    ($formName ? "$formName form" : 'Website form').' submission from '.$e->submission->fromName
                );

                // the HTML body comes from the Contact Form Extensions plugin's
                // notification template setting (_emails/contact-form-notification)
            }
        );

        // do some additional/different validation
        Event::on(
            Submission::class,
            Model::EVENT_AFTER_VALIDATE,
            static function (Event $e): void {
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

    private static function messageValue(Submission $submission, string $key): ?string
    {
        if (!\is_array($submission->message) || !\array_key_exists($key, $submission->message)) {
            return null;
        }

        $value = $submission->message[$key];

        return \is_array($value) ? implode(', ', $value) : $value;
    }

    /**
     * The form names, keyed by handle, from config/custom.php.
     * The form templates use the same list for their hidden formName field.
     *
     * @return string[]
     */
    private static function formNames(): array
    {
        return \Craft::$app->getConfig()->getCustom()->formNames;
    }
}
