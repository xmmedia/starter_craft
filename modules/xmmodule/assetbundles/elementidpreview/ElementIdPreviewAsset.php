<?php

/**
 * XM module for Craft CMS 5.x
 *
 * @see      https://www.xmmedia.com
 *
 * @copyright Copyright (c) 2022 XM Media Inc.
 */

declare(strict_types=1);

namespace modules\xmmodule\assetbundles\elementidpreview;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Keeps the link preview under the Element ID input in step with what's typed.
 *
 * @see \modules\xmmodule\XmModule::previewElementIdUrls()
 */
class ElementIdPreviewAsset extends AssetBundle
{
    #[\Override]
    public function init(): void
    {
        $this->sourcePath = __DIR__.'/dist';
        $this->depends = [CpAsset::class];
        $this->js = ['element-id-preview.js'];
        $this->css = ['element-id-preview.css'];

        parent::init();
    }
}
