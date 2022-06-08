<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\Php56\Rector\FunctionLike\AddDefaultValueForUndefinedVariableRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Rector\Php80\Rector\FunctionLike\UnionTypesRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src'
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81
    ]);

    // https://github.com/rectorphp/rector/blob/main/docs/how_to_ignore_rule_or_paths.md
    $rectorConfig->skip(
      [
        __DIR__ . '/cache',
        __DIR__ . '/logs',
        __DIR__ . '/node_modules',
        __DIR__ . '/OtraStandard',
        __DIR__ . '/phpdoc',
        __DIR__ . '/reports',
        __DIR__ . '/sassdoc',
        __DIR__ . '/tmp',
        __DIR__ . '/vendor',

        // Those rules on `null` values consequences are complex, can be false positives
        AddDefaultValueForUndefinedVariableRector::class,
        RestoreDefaultNullToNullableTypePropertyRector::class,

        NullToStrictStringFuncCallArgRector::class,

        // Rector does not understand the way my controllers are made
        RemoveParentCallWithoutParentRector::class,
        UnionTypesRector::class
      ]
    );
};
