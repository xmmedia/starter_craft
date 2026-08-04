<?php

/**
 * SEO module for Craft CMS 5.x
 *
 * @see      https://www.xmmedia.com
 *
 * @copyright Copyright (c) 2026 XM Media Inc.
 */

declare(strict_types=1);

namespace modules\seomodule;

use craft\elements\Entry;
use ether\seo\controllers\sitemap\XmlController;
use yii\base\ActionEvent;
use yii\base\Event;
use yii\base\Module as BaseModule;
use yii\web\Controller;
use yii\web\Response;

/**
 * @author  XM Media Inc.
 *
 * @since   1.0.0
 *
 * @method static SeoModule getInstance()
 */
class SeoModule extends BaseModule
{
    #[\Override]
    public function init(): void
    {
        \Craft::setAlias('@modules/seomodule', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (\Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'modules\\seomodule\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\seomodule\\controllers';
        }

        parent::init();

        $this->filterSitemap();

        \Craft::info('SEO module loaded', __METHOD__);
    }

    /**
     * Drops entries flagged "Hide from Search Engines" out of the SEO plugin's sitemap.
     *
     * Edits the finished XML rather than generating our own, so the plugin keeps control of
     * pagination, lastmod dates, priorities and section settings.
     *
     * Hooks afterAction rather than adding a URL rule — the plugin registers `sitemap.xml`
     * on EVENT_REGISTER_SITE_URL_RULES and would overwrite a route under the same key.
     */
    private function filterSitemap(): void
    {
        // makes the dependency explicit; `::class` alone would just never fire if it went away
        if (!class_exists(XmlController::class)) {
            return;
        }

        Event::on(XmlController::class, Controller::EVENT_AFTER_ACTION, static function (ActionEvent $event): void {
            // only 'core' holds entry URLs — 'index' lists sub-sitemaps, 'custom' hand-entered ones
            if ('core' !== $event->action->id || !$event->result instanceof Response) {
                return;
            }

            $hidden = self::hiddenEntryUrls();

            if ([] === $hidden) {
                return;
            }

            $event->result->content = self::removeUrlsFromSitemap((string) $event->result->content, $hidden);
        });
    }

    /**
     * URLs of every entry whose SEO block has "Hide from Search Engines" turned on.
     *
     * @return string[]
     */
    private static function hiddenEntryUrls(): array
    {
        // SEO blocks are nested entries, so find those first, then walk up to their owners
        $blocks = Entry::find()
            ->field('seo')
            ->seoNoindex(true)
            ->status(null)
            ->siteId(\Craft::$app->getSites()->getCurrentSite()->id)
            ->all();

        $urls = [];

        foreach ($blocks as $block) {
            $url = $block->getOwner()?->getUrl();

            if (null !== $url) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * Removes the `<url>` elements whose `<loc>` matches one of the given URLs.
     *
     * @param string[] $urls
     */
    private static function removeUrlsFromSitemap(string $xml, array $urls): string
    {
        if ('' === trim($xml)) {
            return $xml;
        }

        $document = new \DOMDocument();

        // malformed XML is the plugin's problem — leave it untouched
        if (!@$document->loadXML($xml)) {
            return $xml;
        }

        $urls = array_flip($urls);

        /** @var \DOMElement $url */
        foreach (iterator_to_array($document->getElementsByTagName('url')) as $url) {
            $loc = $url->getElementsByTagName('loc')->item(0)?->nodeValue;

            if (null !== $loc && isset($urls[$loc])) {
                $url->parentNode?->removeChild($url);
            }
        }

        return (string) $document->saveXML();
    }
}
