<?php

declare(strict_types=1);

namespace modules\contactformmodule;

use Automattic\Akismet\Akismet;
use Automattic\Akismet\DTO\CheckResult;
use Automattic\Akismet\DTO\Content;
use Automattic\Akismet\Enum\ContentType;
use craft\contactform\honeypot\Plugin as HoneypotPlugin;
use craft\contactform\models\Submission;
use craft\helpers\App;
use craft\helpers\UrlHelper;

final class SpamCheck
{
    public const string MESSAGE_KEY = 'Spam check';

    /**
     * Only these are sent, as $_SERVER also holds the .env values
     * and a proxy can add credential headers.
     */
    private const array SENT_HEADERS = [
        'HTTP_ACCEPT',
        'HTTP_ACCEPT_ENCODING',
        'HTTP_ACCEPT_LANGUAGE',
        'HTTP_ORIGIN',
        'HTTP_SEC_FETCH_DEST',
        'HTTP_SEC_FETCH_MODE',
        'HTTP_SEC_FETCH_SITE',
    ];

    /**
     * Message keys that aren't the visitor's own text.
     */
    private const array NOT_CONTENT_KEYS = [
        'body',
        'Page Name',
        'Page URL',
        self::MESSAGE_KEY,
    ];

    /**
     * Returns whether Akismet considers the submission spam, and records the
     * result on the submission. Fails open: if the check fails for any reason,
     * the submission goes through.
     */
    public static function check(Submission $submission): bool
    {
        $request = \Craft::$app->getRequest();
        $honeypotParam = self::honeypotParam();
        $honeypotValue = $honeypotParam ? $request->getBodyParam($honeypotParam) : null;
        $emailIsValid = (bool) filter_var((string) $submission->fromEmail, \FILTER_VALIDATE_EMAIL);

        $lines = [];
        $isSpam = false;

        $apiKey = App::env('AKISMET_API_KEY');
        if (!$apiKey) {
            $lines[] = 'Akismet: not checked (no API key)';
        } else {
            try {
                $isTest = \Craft::$app->getConfig()->getGeneral()->devMode;
                $content = self::content($submission, $emailIsValid, $honeypotParam, $honeypotValue);
                $result = self::client($apiKey, $isTest)->check($content);
                $isSpam = $result->isSpam();
                $lines = self::resultLines($result, $isTest);
            } catch (\Throwable $e) {
                \Craft::error('Akismet check failed: '.$e->getMessage(), __METHOD__);
                $lines[] = 'Akismet: not checked ('.$e->getMessage().')';
            }
        }

        if (!$emailIsValid) {
            $lines[] = 'Email: not sent to Akismet (not a valid address)';
        }

        if (\is_string($honeypotValue)) {
            $lines[] = 'Honeypot: '.('' === $honeypotValue ? 'empty' : 'filled in (spam)');
        }

        $lines[] = 'IP: '.($request->getRemoteIP() ?? 'unknown');
        $lines[] = 'User agent: '.($request->getUserAgent() ?? 'none');
        $lines[] = 'Referrer: '.($request->getReferrer() ?? 'none');

        if (\is_array($submission->message)) {
            $submission->message[self::MESSAGE_KEY] = implode("\n", $lines);
        }

        return $isSpam;
    }

    private static function client(string $apiKey, bool $isTest): Akismet
    {
        return Akismet::create(
            apiKey: $apiKey,
            site: UrlHelper::siteUrl(),
            isTest: $isTest,
            applicationUserAgent: 'Craft CMS/'.\Craft::$app->getVersion(),
            httpClient: \Craft::createGuzzleClient(['timeout' => 3, 'connect_timeout' => 2]),
        );
    }

    private static function content(
        Submission $submission,
        bool $emailIsValid,
        ?string $honeypotParam,
        mixed $honeypotValue,
    ): Content {
        $request = \Craft::$app->getRequest();

        // submitted by the form, so only trusted as the permalink if it's on this site
        $pageUrl = ContactFormModule::messageValue($submission, 'Page URL');
        if (null !== $pageUrl && !str_starts_with($pageUrl, UrlHelper::siteUrl())) {
            $pageUrl = null;
        }

        $headers = array_filter(
            array_intersect_key($_SERVER, array_flip(self::SENT_HEADERS)),
            \is_string(...),
        );

        $hasHoneypot = $honeypotParam && \is_string($honeypotValue);

        return new Content(
            userIp: (string) $request->getRemoteIP(),
            userAgent: $request->getUserAgent(),
            body: self::body($submission),
            authorName: $submission->fromName,
            authorEmail: $emailIsValid ? $submission->fromEmail : null,
            type: ContentType::ContactForm,
            permalink: $pageUrl,
            referrer: $request->getReferrer(),
            honeypotFieldName: $hasHoneypot ? $honeypotParam : null,
            honeypotFieldValue: $hasHoneypot ? $honeypotValue : null,
            serverVariables: $headers,
            blogLang: \Craft::$app->getSites()->getCurrentSite()->language,
            blogCharset: 'UTF-8',
        );
    }

    /**
     * The message body, followed by the other fields the visitor filled in.
     */
    private static function body(Submission $submission): ?string
    {
        if (!\is_array($submission->message)) {
            return \is_string($submission->message) ? $submission->message : null;
        }

        $skipKeys = [...ContactFormModule::SUBMISSION_SKIP_KEYS, ...self::NOT_CONTENT_KEYS];
        $keys = ['body', ...array_diff(array_map(strval(...), array_keys($submission->message)), $skipKeys)];

        $parts = [];
        foreach ($keys as $key) {
            $value = ContactFormModule::messageValue($submission, $key);
            if (null !== $value && '' !== trim($value)) {
                $parts[] = 'body' === $key ? $value : $key.': '.$value;
            }
        }

        return [] !== $parts ? implode("\n\n", $parts) : null;
    }

    /**
     * @return string[]
     */
    private static function resultLines(CheckResult $result, bool $isTest): array
    {
        $lines = [
            'Akismet: '.match (true) {
                $result->shouldDiscard() => 'spam (blatant: discard)',
                $result->isSpam()        => 'spam',
                default                  => 'not spam',
            }.($isTest ? ' [test mode]' : ''),
        ];

        $details = [
            'Recheck after' => null !== $result->recheckAfter ? $result->recheckAfter.' seconds' : null,
            'Classification' => $result->classification,
            'Debug help' => $result->debugHelp,
            'Error' => $result->error,
            'Alert' => $result->alertCode ? trim($result->alertCode.' '.$result->alertMessage) : null,
            'GUID' => $result->guid,
        ];
        foreach ($details as $label => $value) {
            if (null !== $value) {
                $lines[] = $label.': '.$value;
            }
        }

        return $lines;
    }

    private static function honeypotParam(): ?string
    {
        $plugin = \Craft::$app->getPlugins()->getPlugin('contact-form-honeypot');
        if (!$plugin instanceof HoneypotPlugin) {
            return null;
        }

        /** @var \craft\contactform\honeypot\Settings $settings */
        $settings = $plugin->getSettings();

        return $settings->honeypotParam ?: null;
    }
}
