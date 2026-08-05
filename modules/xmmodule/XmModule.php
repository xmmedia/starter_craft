<?php

/**
 * XM module for Craft CMS 5.x
 *
 * @see      https://www.xmmedia.com
 *
 * @copyright Copyright (c) 2022 XM Media Inc.
 */

declare(strict_types=1);

namespace modules\xmmodule;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\events\AssetEvent;
use craft\events\DefineRulesEvent;
use craft\events\SetAssetFilenameEvent;
use craft\helpers\Assets as AssetsHelper;
use craft\mail\Mailer;
use modules\xmmodule\twigextensions\XmTwigExtension;
use yii\base\Event;
use yii\base\Module as BaseModule;
use yii\mail\BaseMailer;
use yii\mail\MailEvent;

/**
 * @author  XM Media Inc.
 *
 * @since   1.0.0
 *
 * @method static XmModule getInstance()
 */
class XmModule extends BaseModule
{
    /**
     * Cased asset basenames, keyed by the lowercase filename Craft is about to store.
     *
     * @var string[]
     *
     * @see forceLowercaseFilenames()
     */
    private array $assetBasenames = [];

    #[\Override]
    public function init(): void
    {
        \Craft::setAlias('@modules/xmmodule', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (\Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'modules\\xmmodule\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\xmmodule\\controllers';
        }

        parent::init();

        $this->attachEventHandlers();
        $this->forceLowercaseFilenames();
        $this->validateElementIds();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        \Craft::$app->onInit(static function () {
            // Add in our Twig extensions
            \Craft::$app->getView()->registerTwigExtension(new XmTwigExtension());
        });

        /*
         * Logging in Craft involves using one of the following methods:
         *
         * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
         * Craft::info(): record a message that conveys some useful information.
         * Craft::warning(): record a warning message that indicates something unexpected has happened.
         * Craft::error(): record a fatal error that should be investigated as soon as possible.
         *
         * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
         *
         * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
         * the category to the method (prefixed with the fully qualified class name) where the constant appears.
         *
         * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
         * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
         *
         * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
         */
        \Craft::info('XM module loaded', __METHOD__);
    }

    private function attachEventHandlers(): void
    {
        // Append site name to system email subjects
        // EVENT_BEFORE_SEND is defined on \yii\mail\BaseMailer (parent of craft\mail\Mailer)
        Event::on(Mailer::class, BaseMailer::EVENT_BEFORE_SEND, static function (MailEvent $event) {
            $key = $event->message->key ?? null;

            if (!\in_array($key, ['account_activation', 'forgot_password'], true)) {
                return;
            }

            $siteName = \Craft::$app->getSystemName();
            $currentSubject = $event->message->getSubject();
            $event->message->setSubject($currentSubject.' on '.$siteName);
        });
    }

    /**
     * Rejects Element IDs that can't be used as an HTML `id`.
     *
     * HTML requires the value to be non-empty and free of whitespace, so `id="id id"` is
     * invalid — and since the field exists to make `#anchor` links scroll to a block, an
     * ID a fragment or CSS selector can't address just silently doesn't work. This is
     * stricter than the spec (letter first, then letters/numbers/hyphens/underscores) so
     * the value is always usable in a URL and a selector without escaping.
     *
     * Applied to any entry whose field layout has the field, so every block type — and
     * any added later — is covered without listing them here.
     */
    private function validateElementIds(): void
    {
        Event::on(Entry::class, Model::EVENT_DEFINE_RULES, static function (DefineRulesEvent $event): void {
            $entry = $event->sender;

            if (!$entry instanceof Entry) {
                return;
            }

            // an entry mid-construction may not have a field layout yet
            try {
                $hasField = null !== $entry->getFieldLayout()?->getFieldByHandle('elementId');
            } catch (\Throwable) {
                return;
            }

            if (!$hasField) {
                return;
            }

            $event->rules[] = [
                ['elementId'],
                'match',
                'pattern' => '/^[A-Za-z][A-Za-z0-9_-]*$/',
                'skipOnEmpty' => true,
                // Not SCENARIO_ESSENTIALS: that's what the CP autosaves drafts under
                // (ElementsController::actionCreate()), and Matrix passes the owner's scenario
                // down to its nested entries. Validating there fails the autosave of a
                // half-typed ID, and the CP can only report a generic "Couldn't save draft".
                'on' => [Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE],
                'message' => 'The Element ID must start with a letter and contain only letters, '
                    .'numbers, hyphens and underscores — no spaces.',
            ];
        });
    }

    /**
     * Forces all asset filenames to lowercase. Asset will keep their original title casing.
     */
    private function forceLowercaseFilenames(): void
    {
        // Fires from Assets::prepareAssetName(), which covers uploads, renames, moves and file replacements
        Event::on(AssetsHelper::class, AssetsHelper::EVENT_SET_FILENAME, function (SetAssetFilenameEvent $event) {
            $lowercased = mb_strtolower($event->filename);
            // the extension includes the leading dot, e.g. ".JPG"
            $extension = mb_strtolower($event->extension);

            // Craft derives a new asset's default title from the sanitized filename, which is
            // about to lose its casing. Remember the cased name against the lowercase filename
            // Craft will store, so beforeHandleFile() can build the title from it instead.
            $this->assetBasenames[$lowercased.$extension] = $event->filename;

            $event->filename = $lowercased;
            $event->extension = $extension;
        });

        // Restore the original casing of a new asset's title, just before Asset::beforeSave()
        // falls back to a title generated from the (now lowercase) filename.
        Event::on(Asset::class, Asset::EVENT_BEFORE_HANDLE_FILE, function (AssetEvent $event) {
            $asset = $event->asset;

            // A title the editor typed, or one an existing asset already has, is left alone.
            if (!$event->isNew || $asset->title) {
                return;
            }

            $filename = mb_strtolower($asset->getFilename());
            $basename = $this->assetBasenames[$filename] ?? pathinfo($filename, \PATHINFO_FILENAME);

            $asset->title = AssetsHelper::filename2Title($basename);
        });
    }
}
