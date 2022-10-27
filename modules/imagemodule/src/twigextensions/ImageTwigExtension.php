<?php
/**
 * Iamge module for Craft CMS 3.x
 *
 * Iamge module
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
        ];
    }

    public function getImage(
        ?Asset $image,
        string|array $transform = null,
        array $attributes = []
    ): Markup {
        if (null === $image) {
            return new Markup('', Craft::$app->charset);
        }

        if (!array_key_exists('alt', $attributes)) {
            $attributes['alt'] = $image->title;
        }

        if ('svg' === $image->extension) {
            $html = Html::tag(
                'img',
                '',
                array_merge([
                    'src'     => $image->getUrl(),
                    'width'   => $image->getWidth($transform),
                    'height'  => $image->getHeight($transform),
                    'loading' => 'lazy',
                ], $attributes),
            );
        } else {
            $html = Html::tag(
                'img',
                '',
                array_merge([
                    'src'     => $image->getUrl($transform),
                    'srcset'  => $image->getSrcset(['1.5x', '2x', '3x'], $transform),
                    'width'   => $image->getWidth($transform),
                    'height'  => $image->getHeight($transform),
                    'loading' => 'lazy',
                ], $attributes),
            );
        }

        return Template::raw($html);
    }
}
