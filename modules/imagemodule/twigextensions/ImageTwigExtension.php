<?php
/**
 * Image module for Craft CMS 5.x
 *
 * @link      https://www.xmmedia.com
 * @copyright Copyright (c) 2022 XM Media Inc.
 */

declare(strict_types=1);

namespace modules\imagemodule\twigextensions;

use Craft;
use craft\elements\Asset;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\web\twig\Extension;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

/**
 * Twig can be extended in many ways; you can add extra tags, filters, tests, operators,
 * global variables, and functions. You can even extend the parser itself with
 * node visitors.
 *
 * http://twig.sensiolabs.org/doc/advanced.html
 *
 * @author    XM Media Inc.
 * @package   ImageModule
 * @since     1.0.0
 */
class ImageTwigExtension extends AbstractExtension
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
        return 'ImageFunction';
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
            new TwigFunction('image', [$this, 'getImage'], ['is_safe' => ['html']]),
            new TwigFunction('imageOrSvg', [$this, 'getImageOrSvg'], ['is_safe' => ['html']]),
        ];
    }

    public function getImage(?Asset $image, string|array|null $transform = null, array $attributes = []): Markup
    {
        if (null === $image) {
            return new Markup('', Craft::$app->charset);
        }

        if (!array_key_exists('alt', $attributes)) {
            $attributes['alt'] = $this->resolveAlt($image);
        }

        $html = Html::tag(
            'img',
            '',
            array_merge([
                'src'      => $image->getUrl($transform),
                // craft returns false (bool) when the image is an SVG, so srcset won't be added to the img tag
                'srcset'   => $image->getSrcset(['1.5x', '2x', '3x'], $transform),
                'width'    => $image->getWidth($transform),
                'height'   => $image->getHeight($transform),
                'loading'  => 'lazy',
                'decoding' => 'async',
            ], $attributes),
        );

        return Template::raw($html);
    }

    public function getImageOrSvg(
        ?Asset $image,
        string|array|null $transform = null,
        array $attributes = [],
    ): Markup|string {
        if (null === $image) {
            return new Markup('', Craft::$app->charset);
        }

        if ('image/svg+xml' === $image->getMimeType()) {
            $twig = Craft::$app->getView()->getTwig();
            /** @var Extension $craftExtension */
            $craftExtension = $twig->getExtension(Extension::class);

            if (!array_key_exists('aria-label', $attributes) && !array_key_exists('role', $attributes)) {
                $attributes['role'] = 'img';
                $attributes['aria-label'] = $this->resolveAlt($image);
            }

            return $craftExtension->attrFilter($craftExtension->svgFunction($image), $attributes);
        }

        return $this->getImage($image, $transform, $attributes);
    }

    private function resolveAlt(Asset $image): string
    {
        if (!empty(trim((string) $image->alt))) {
            return $image->alt;
        }

        return $image->title;
    }
}
