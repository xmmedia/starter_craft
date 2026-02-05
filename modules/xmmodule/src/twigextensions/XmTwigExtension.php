<?php
/**
 * XM module for Craft CMS 3.x
 *
 * XM module
 *
 * @link      https://www.xmmedia.com
 * @copyright Copyright (c) 2022 XM Media Inc.
 */

declare(strict_types=1);

namespace modules\xmmodule\twigextensions;

use craft\elements\Entry;
use modules\xmmodule\XmModule;
use Twig\Extension\AbstractExtension;
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
 * @package   XmModule
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
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('submenu', [$this, 'submenu']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('heading_striptags', [$this, 'headingStripTags'], ['is_safe' => ['html']]),
            new TwigFilter('phone_strip', [$this, 'phoneStrip']),
        ];
    }

    public function submenu(array $subpages): array
    {
        return array_map(
            function (Entry $page): array {
                return [
                    'id'    => $page->getId(),
                    'title' => $page->title,
                    'url'   => $page->getUrl(),
                ];
            },
            $subpages,
        );
    }

    public function headingStripTags(?string $heading): ?string
    {
        if (null === $heading) {
            return null;
        }

        return strip_tags($heading, '<strong><em><br><a><sup><sub>');
    }

    public function phoneStrip(string $phone): string
    {
        if (str_starts_with($phone, 'tel:+')) {
            $phone = substr($phone, strlen('tel:+'));
        }

        return 'tel:+'.preg_replace('/\D+/', '', $phone) ?? '';
    }
}
