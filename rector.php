<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withParallel()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/modules',
        __DIR__ . '/public/index.php',
        __DIR__ . '/public/editorcss.php',
    ])
    ->withRootFiles()
    ->withPhpSets()
    ->withSets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
    ])
    ->withSkip([
        // don't remove useless variables inside event handler closures
        // it's nice to keep them for editing later
        Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector::class,
        // we may not want the property to have a default value
        Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector::class,
        Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector::class,
        Rector\CodeQuality\Rector\If_\CombineIfRector::class,
        Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector::class,
        Rector\CodeQuality\Rector\If_\ObjectExplicitBoolCompareRector::class,
        // keep @param tags even when redundant with type declarations
        Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector::class,
        Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector::class,
        // keep empty() as-is; don't rewrite to in_array($x, ['', '0'], true)
        Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector::class,
    ])
;
