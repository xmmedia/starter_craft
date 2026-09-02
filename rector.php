<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withParallel()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        if: true,
        earlyReturn: true,
        carbon: true,
        // no privatization, naming, namedArgs, instanceOf, rectorPreset
    )
    ->withComposerBased(twig: true)
    ->withPhpSets()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/modules',
        __DIR__ . '/public/index.php',
        __DIR__ . '/public/editorcss.php',
    ])
    ->withRootFiles()
    ->withSkip([
        // don't remove useless variables inside event handler closures
        // it's nice to keep them for editing later
        Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector::class,
        // we may not want the property to have a default value
        Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector::class,
        Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector::class,
        Rector\CodeQuality\Rector\If_\ObjectExplicitBoolCompareRector::class,
        // keep @param tags even when redundant with type declarations
        Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector::class,
        Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector::class,
    ])
;
