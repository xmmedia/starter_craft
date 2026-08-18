<?php

declare(strict_types=1);

namespace modules\contactformmodule\jobs;

use craft\mail\Message;
use craft\queue\BaseJob;
use modules\contactformmodule\ContactFormModule;
use yii\mail\MessageInterface;
use yii\queue\RetryableJobInterface;

/**
 * Sends a contact form email through the queue so that a mail transport outage
 * (eg Postmark being down) is retried rather than silently dropping the message.
 *
 * The message is stored as plain values rather than a serialised Symfony email, to
 * keep the payload inspectable. Attachments are inlined because the uploaded temp
 * file is gone by the time the queue runs.
 */
class SendEmail extends BaseJob implements RetryableJobInterface
{
    /**
     * Seconds the job may run for — and, since the queue re-reserves timed out jobs,
     * how long before a failed attempt is retried.
     */
    private const int TTR = 300;

    /**
     * Give up after this many attempts (~50 minutes at the TTR above).
     */
    private const int MAX_ATTEMPTS = 10;

    /**
     * Headers set from the properties below or by the transport, so not copied over
     * as custom headers.
     */
    private const array STANDARD_HEADERS = [
        'bcc',
        'cc',
        'content-disposition',
        'content-id',
        'content-transfer-encoding',
        'content-type',
        'date',
        'from',
        'message-id',
        'mime-version',
        'reply-to',
        'return-path',
        'sender',
        'subject',
        'to',
    ];

    /** @var array<string,string>|string */
    public array|string $from = [];

    /** @var array<string,string>|string */
    public array|string $to = [];

    /** @var array<string,string>|string|null */
    public array|string|null $cc = null;

    /** @var array<string,string>|string|null */
    public array|string|null $bcc = null;

    /** @var array<string,string>|string|null */
    public array|string|null $replyTo = null;

    public string $subject = '';

    public ?string $charset = null;

    public ?string $textBody = null;

    public ?string $htmlBody = null;

    /**
     * Non-standard headers on the message, eg X-Priority.
     *
     * @var list<array{name: string, value: string}>
     */
    public array $headers = [];

    /** @var list<array{content: string, fileName: string|null, contentType: string, inline: bool}> */
    public array $attachments = [];

    /**
     * Creates a job from a message that's about to be sent.
     */
    public static function fromMessage(MessageInterface $message): self
    {
        $job = new self([
            'from' => $message->getFrom() ?: [],
            'to' => $message->getTo() ?: [],
            'cc' => $message->getCc() ?: null,
            'bcc' => $message->getBcc() ?: null,
            'replyTo' => $message->getReplyTo() ?: null,
            'subject' => $message->getSubject(),
            'charset' => $message->getCharset() ?: null,
        ]);

        if ($message instanceof Message) {
            $email = $message->getSymfonyEmail();

            $job->textBody = self::bodyToString($email->getTextBody());
            $job->htmlBody = self::bodyToString($email->getHtmlBody());

            foreach ($email->getAttachments() as $attachment) {
                $job->attachments[] = [
                    'content' => $attachment->getBody(),
                    'fileName' => $attachment->getFilename(),
                    'contentType' => $attachment->getContentType(),
                    // the content ID isn't assigned until send time, so the disposition
                    // is what identifies an embedded part; re-embedding needs a file name
                    'inline' => 'inline' === $attachment->getDisposition()
                        && null !== $attachment->getFilename(),
                ];
            }

            foreach ($email->getHeaders()->all() as $header) {
                if (\in_array(strtolower($header->getName()), self::STANDARD_HEADERS, true)) {
                    continue;
                }

                $job->headers[] = [
                    'name' => $header->getName(),
                    'value' => $header->getBodyAsString(),
                ];
            }
        }

        return $job;
    }

    #[\Override]
    public function execute($queue): void
    {
        $message = new Message()
            ->setFrom($this->from)
            ->setTo($this->to)
            ->setSubject($this->subject)
        ;

        if (null !== $this->cc) {
            $message->setCc($this->cc);
        }

        if (null !== $this->bcc) {
            $message->setBcc($this->bcc);
        }

        if (null !== $this->replyTo) {
            $message->setReplyTo($this->replyTo);
        }

        if (null !== $this->charset) {
            $message->setCharset($this->charset);
        }

        if (null !== $this->textBody) {
            $message->setTextBody($this->textBody);
        }

        if (null !== $this->htmlBody) {
            $message->setHtmlBody($this->htmlBody);
        }

        foreach ($this->headers as $header) {
            $message->addHeader($header['name'], $header['value']);
        }

        foreach ($this->attachments as $attachment) {
            if ($attachment['inline']) {
                // Symfony re-points the body's `cid:<fileName>` references at the
                // regenerated content ID, so only the file name needs to survive
                $message->embedContent($attachment['content'], [
                    'fileName' => $attachment['fileName'],
                    'contentType' => $attachment['contentType'],
                ]);

                continue;
            }

            $message->attachContent($attachment['content'], [
                'fileName' => $attachment['fileName'],
                'contentType' => $attachment['contentType'],
            ]);
        }

        // belt & braces: don't let the mailer queue this again
        ContactFormModule::getInstance()->setQueueSends(false);

        if (!\Craft::$app->getMailer()->send($message)) {
            // throwing is what triggers the retry; craft\mail\Mailer has already logged
            // the transport error
            throw new \RuntimeException('Failed to send the contact form email.');
        }
    }

    /**
     * @return int
     */
    #[\Override]
    public function getTtr()
    {
        return self::TTR;
    }

    /**
     * @param int                   $attempt
     * @param \Exception|\Throwable $error
     *
     * @return bool
     */
    #[\Override]
    public function canRetry($attempt, $error)
    {
        return $attempt < self::MAX_ATTEMPTS;
    }

    #[\Override]
    protected function defaultDescription(): ?string
    {
        return 'Sending contact form email';
    }

    /**
     * Symfony email bodies can be a string, a stream or null.
     *
     * @param resource|string|null $body
     */
    private static function bodyToString($body): ?string
    {
        if (\is_resource($body)) {
            $contents = stream_get_contents($body);

            return false === $contents ? null : $contents;
        }

        return $body;
    }
}
