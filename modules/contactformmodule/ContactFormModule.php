<?php

declare(strict_types=1);

namespace modules\contactformmodule;

use craft\base\Element;
use craft\base\Model;
use craft\contactform\events\SendEvent;
use craft\contactform\Mailer;
use craft\contactform\models\Submission;
use craft\events\DefineAttributeHtmlEvent;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\mail\Mailer as CraftMailer;
use hybridinteractive\contactformextensions\elements\Submission as SubmissionElement;
use modules\contactformmodule\jobs\SendEmail;
use yii\base\Event;
use yii\base\Module as BaseModule;
use yii\mail\MailEvent;

/**
 * @method static ContactFormModule getInstance()
 */
class ContactFormModule extends BaseModule
{
    /**
     * Whether emails being sent for the current request are contact form emails,
     * and so should be pushed to the queue rather than sent inline.
     */
    private bool $queueSends = false;

    /**
     * Message keys the Contact Form Extensions plugin leaves out of its submission view.
     */
    private const array SUBMISSION_SKIP_KEYS = [
        'formName',
        'toEmail',
        'confirmationSubject',
        'confirmationTemplate',
        'notificationTemplate',
        'disableRecaptcha',
        'disableConfirmation',
        'disableSaveSubmission',
    ];

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

    public function setQueueSends(bool $value): void
    {
        $this->queueSends = $value;
    }

    private function attachEventHandlers(): void
    {
        // flag every email sent for the rest of this request as a contact form email:
        // the submission action doesn't send anything else, and the Contact Form
        // Extensions confirmation email should be queued too
        Event::on(
            Mailer::class,
            Mailer::EVENT_BEFORE_SEND,
            function (SendEvent $e): void {
                $this->setQueueSends(true);
            }
        );

        // push the email onto the queue instead of sending it inline, so that a
        // transport outage (eg Postmark being down) is retried rather than lost.
        // this runs after every handler that builds the message, including the
        // notification template rendered by the Contact Form Extensions plugin
        Event::on(
            CraftMailer::class,
            CraftMailer::EVENT_BEFORE_SEND,
            function (MailEvent $e): void {
                if (!$this->queueSends) {
                    return;
                }

                \Craft::$app->getQueue()->push(SendEmail::fromMessage($e->message));

                // cancel the inline send; the queued job will do it
                $e->isValid = false;
            }
        );

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

        // the plugin's submissions index shows the raw message keys (eg "body"),
        // so label them the same way as the submission view: capitalised & translated
        Event::on(
            SubmissionElement::class,
            Element::EVENT_DEFINE_ATTRIBUTE_HTML,
            static function (DefineAttributeHtmlEvent $e): void {
                if ('message' !== $e->attribute) {
                    return;
                }

                /** @var SubmissionElement $submission */
                $submission = $e->sender;
                $e->html = self::messagePreviewHtml($submission->message);
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

                if (empty(trim((string) self::messageValue($submission, 'body')))) {
                    $submission->clearErrors('message');
                    $submission->addError(
                        'message.body',
                        'Please add a message.'
                    );
                }
            }
        );
    }

    private static function messagePreviewHtml(mixed $message): string
    {
        if (\is_string($message)) {
            $message = json_decode($message, true);
        }

        if (!\is_array($message)) {
            return '';
        }

        $html = '';
        foreach ($message as $key => $value) {
            if (!\is_string($value) || \in_array($key, self::SUBMISSION_SKIP_KEYS, true)) {
                continue;
            }

            $key = (string) $key;
            // matches Twig's capitalize filter, used by the submission view
            $label = mb_strtoupper(mb_substr($key, 0, 1)).mb_strtolower(mb_substr($key, 1));

            $html .= Html::tag(
                'li',
                Html::tag('em', Html::encode(\Craft::t('contact-form-extensions', $label)))
                .': '.Html::encode(StringHelper::truncate(trim($value), 30)),
            );
        }

        return Html::tag('ul', $html);
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
