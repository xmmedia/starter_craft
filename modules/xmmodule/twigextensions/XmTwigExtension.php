<?php

/**
 * XM module for Craft CMS 5.x
 *
 * @see      https://www.xmmedia.com
 *
 * @copyright Copyright (c) 2022 XM Media Inc.
 */

declare(strict_types=1);

namespace modules\xmmodule\twigextensions;

use craft\elements\Entry;
use craft\helpers\Html;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig can be extended in many ways; you can add extra tags, filters, tests, operators,
 * global variables, and functions. You can even extend the parser itself with
 * node visitors.
 *
 * http://twig.sensiolabs.org/doc/advanced.html
 *
 * @author    XM Media Inc.
 *
 * @since     1.0.0
 */
class XmTwigExtension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'XmFunction';
    }

    /**
     * Returns an array of Twig functions, used in Twig templates via:
     *
     *      {% set this = someFunction('something') %}
     */
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('blockWidth', $this->blockWidth(...)),
            new TwigFunction('blockId', $this->blockId(...), ['is_safe' => ['html']]),
            new TwigFunction('menu', $this->menu(...)),
            new TwigFunction('submenu', $this->submenu(...)),
        ];
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('heading_striptags', $this->headingStripTags(...), ['is_safe' => ['html']]),
            new TwigFilter('phone_strip', $this->phoneStrip(...)),
            new TwigFilter('address_format', $this->addressFormat(...), ['is_safe' => ['html']]),
            new TwigFilter('json_ld', $this->jsonLd(...), ['is_safe' => ['html']]),
        ];
    }

    public function blockWidth(Entry $block): ?string
    {
        if (null === $block->blockWidth) {
            return null;
        }

        return 'blocks-wrap:col-span-'.($block->blockWidth ?? 12);
    }

    /**
     * The block's `id` attribute, ready to drop into a tag, or an empty string when it has none.
     *
     * Includes the leading space, so it goes straight after the previous attribute:
     * `<div class="{{ classes }}"{{ blockId(block) }}>`.
     *
     * No trimming needed — PlainText::normalizeValue() already trims and turns an empty
     * value into null.
     */
    public function blockId(Entry $block): string
    {
        if (null === $block->elementId) {
            return '';
        }

        return Html::renderTagAttributes(['id' => $block->elementId]);
    }

    public function menu(array $items): array
    {
        return array_map(
            static fn (Entry $item): array => [
                'url'   => $item->menuLink->url,
                'label' => $item->menuLabel,
            ],
            $items,
        );
    }

    public function submenu(array $subpages): array
    {
        return array_map(
            static fn (Entry $page): array => [
                'id'    => $page->getId(),
                'title' => $page->menuLabel ?? $page->title,
                'url'   => $page->getUrl(),
            ],
            $subpages,
        );
    }

    /**
     * Strips all but inline formatting tags from a heading.
     *
     * Returns Markup so the value survives a `{% set %}` unescaped — `is_safe` only
     * covers the expression it's used in. Empty results are null, not empty Markup,
     * which is always truthy in Twig.
     */
    public function headingStripTags(?string $heading): ?Markup
    {
        if (null === $heading) {
            return null;
        }

        // b & i are because CKeditor doesn't use strong or em
        $stripped = strip_tags($heading, '<strong><b><em><i><br><a><sup><sub><span>');

        if ('' === trim($stripped)) {
            return null;
        }

        return new Markup($stripped, \Craft::$app->charset);
    }

    /**
     * Formats a phone number as E.164 (`+14035555555`), prefixed with `tel:` by default.
     * Pass an empty prefix for the bare number.
     *
     * A 10 digit number without a leading `+` is assumed to be North American and gets
     * the default country code; anything else is assumed to already include one.
     */
    public function phoneStrip(string $phone, ?string $prefix = 'tel', string $defaultCountryCode = '1'): string
    {
        $phone = preg_replace('/^[a-z]+:/i', '', trim($phone));
        $digits = preg_replace('/\D+/', '', $phone);

        if (!str_starts_with($phone, '+') && 10 === \strlen($digits)) {
            $digits = $defaultCountryCode.$digits;
        }

        return ($prefix ? $prefix.':' : '').'+'.$digits;
    }

    /**
     * Returns Markup for the same reason as {@see self::headingStripTags()}.
     */
    public function addressFormat(string $address): Markup
    {
        return new Markup(
            nl2br(str_replace('  ', ' &MediumSpace;', e($address))),
            \Craft::$app->charset,
        );
    }

    /**
     * Renders schema.org nodes as a JSON-LD script tag, dropping each node's empty values.
     *
     * @param array<array<string, mixed>> $nodes
     */
    public function jsonLd(array $nodes): Markup
    {
        $graph = array_map(
            static fn (array $node): array => array_filter(
                $node,
                static fn (mixed $value): bool => !\in_array($value, [null, '', false, []], true),
            ),
            array_values($nodes),
        );

        // the HEX flags escape < > & and quotes, so the content can't close the tag
        $json = json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
        );

        return new Markup(
            "<script type=\"application/ld+json\">\n{$json}\n</script>",
            \Craft::$app->charset,
        );
    }
}
