<?php

declare(strict_types=1);

// Craft's `Craft` and `Yii` global classes aren't part of Composer's autoloader —
// they're require()'d directly by vendor/craftcms/cms/bootstrap/bootstrap.php at runtime.
require_once __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require_once __DIR__ . '/vendor/craftcms/cms/src/Craft.php';
