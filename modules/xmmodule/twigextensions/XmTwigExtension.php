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

use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\fields\data\LinkData;
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
            new TwigFunction('linkUrl', $this->linkUrl(...)),
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

        // column spans only apply in the page level .blocks-wrap grid: a block nested
        // within another block (eg. a column) isn't one of its grid items
        if (null !== $block->getOwner()?->getField()) {
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

    /**
     * @param Entry[] $items
     */
    public function menu(array $items): array
    {
        $menu = [];
        $current = $this->currentElement();

        foreach ($items as $item) {
            $url = $this->linkUrl($item->menuLink);

            if ('' === $url) {
                continue;
            }

            $menu[] = [
                'url'         => $url,
                'label'       => $item->menuLabel,
                'ariaCurrent' => $this->ariaCurrent($item->menuLink->getElement(), $current),
            ];
        }

        return $menu;
    }

    /**
     * Uses the canonical element, so a draft in live preview matches the entry the menu
     * links to. Nothing is current on an error page, even when the request matched an
     * element (eg. its template is missing).
     */
    private function currentElement(): ?ElementInterface
    {
        $element = \Craft::$app->getUrlManager()->getMatchedElement();

        if (!$element) {
            return null;
        }

        $response = \Craft::$app->getResponse();

        if ($response->getIsClientError() || $response->getIsServerError()) {
            return null;
        }

        return $element->getCanonical();
    }

    /**
     * A link to an ancestor gets `true`: `page` would announce the section as the current page.
     */
    private function ariaCurrent(?ElementInterface $linked, ?ElementInterface $current): ?string
    {
        if (null === $linked || null === $current) {
            return null;
        }

        if ($linked->getCanonicalId() === $current->id) {
            return 'page';
        }

        return $linked->isAncestorOf($current) ? 'true' : null;
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
     * A Link field's URL for an href: `getUrl(false)`, with phone links through phoneStrip(),
     * keeping links to the previewed page in live preview. Empty when there's no link.
     */
    public function linkUrl(?LinkData $link): string
    {
        $url = $link?->getUrl(false) ?? '';

        if ('' === $url && $this->isPreviewPlaceholderOfLiveEntry($link?->getElement())) {
            $url = $link->getUrl();
        }

        if ('' !== $url && 'tel' === $link->getType()) {
            return $this->phoneStrip($url);
        }

        return $url;
    }

    /**
     * Live preview registers the previewed element as a placeholder, which element queries
     * (links included) return in place of its entry, and getUrl(false) drops a saved draft.
     */
    private function isPreviewPlaceholderOfLiveEntry(?ElementInterface $element): bool
    {
        if (!$element instanceof Element || !$element->previewing) {
            return false;
        }

        return \in_array($element->getCanonical()->getStatus(), [Element::STATUS_ENABLED, Entry::STATUS_LIVE], true);
    }

    /**
     * Formats a phone number as E.164 (`+14035555555`), prefixed with `tel:` by default.
     * Pass an empty prefix for the bare number.
     *
     * A 10 digit number without a leading `+` is assumed to be North American and gets
     * the default country code. Numbers that can't be made E.164 (short codes, local
     * numbers, pauses or extensions with `,` or `;`) are returned as they are.
     */
    public function phoneStrip(string $phone, ?string $prefix = 'tel', string $defaultCountryCode = '1'): string
    {
        $phone = preg_replace('/^[a-z]+:/i', '', trim($phone));
        $digits = preg_replace('/\D+/', '', $phone);
        $prefix = $prefix ? $prefix.':' : '';

        if (!str_contains($phone, ',') && !str_contains($phone, ';')) {
            if (str_starts_with($phone, '+')) {
                return $prefix.'+'.$digits;
            }

            if (10 === \strlen($digits)) {
                return $prefix.'+'.$defaultCountryCode.$digits;
            }

            if (\strlen($digits) === 10 + \strlen($defaultCountryCode) && str_starts_with($digits, $defaultCountryCode)) {
                return $prefix.'+'.$digits;
            }
        }

        return $prefix.$phone;
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
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE
                | \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_QUOT | \JSON_THROW_ON_ERROR,
        );

        return new Markup(
            "<script type=\"application/ld+json\">\n{$json}\n</script>",
            \Craft::$app->charset,
        );
    }
}
