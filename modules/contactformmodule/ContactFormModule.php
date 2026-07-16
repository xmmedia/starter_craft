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

                $formName = self::messageValue($e->submission, 'formName');
                $e->message->setSubject(
                    ($formName ? "$formName form" : 'Website form').' submission from '.$e->submission->fromName
                );

                $e->message->setHtmlBody(self::renderNotificationHtml($e->submission));
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

    /**
     * Renders the styled HTML notification email for a contact form submission.
     * Any message fields beyond the standard body/formName/page context are
     * included automatically, so forms can submit extra fields without needing
     * template changes here.
     */
    private static function renderNotificationHtml(Submission $submission): string
    {
        $excludedFields = ['body', 'formName', 'Page Name', 'Page URL'];
        $fields = [];

        if (\is_array($submission->message)) {
            foreach ($submission->message as $key => $value) {
                if (\in_array($key, $excludedFields, true)) {
                    continue;
                }
                $fields[$key] = \is_array($value) ? implode(', ', $value) : (string) $value;
            }
        }

        $body = \is_array($submission->message)
            ? (string) ($submission->message['body'] ?? '')
            : (string) $submission->message;

        return \Craft::$app->getView()->renderTemplate('_emails/contact-form-notification', [
            'siteName'  => \Craft::$app->getSystemName(),
            'formName'  => self::messageValue($submission, 'formName'),
            'fromName'  => $submission->fromName,
            'fromEmail' => $submission->fromEmail,
            'fields'    => $fields,
            'body'      => $body,
            'pageName'  => self::messageValue($submission, 'Page Name'),
            'pageUrl'   => self::messageValue($submission, 'Page URL'),
        ]);
    }

    private static function messageValue(Submission $submission, string $key): ?string
    {
        if (!\is_array($submission->message) || !\array_key_exists($key, $submission->message)) {
            return null;
        }

        $value = $submission->message[$key];

        return \is_array($value) ? implode(', ', $value) : $value;
    }
}
