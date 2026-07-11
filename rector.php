<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/bootstrap.php',
        __DIR__ . '/config',
        __DIR__ . '/modules',
        __DIR__ . '/public/index.php',
        __DIR__ . '/public/editorcss.php',
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_84,
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
    ])
;
