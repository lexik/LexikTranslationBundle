<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/Command',
        __DIR__ . '/Controller',
        __DIR__ . '/DependencyInjection',
        __DIR__ . '/Document',
        __DIR__ . '/Entity',
        __DIR__ . '/EventDispatcher',
        __DIR__ . '/Form',
        __DIR__ . '/Manager',
        __DIR__ . '/Model',
        __DIR__ . '/Propel',
        __DIR__ . '/Storage',
        __DIR__ . '/Tests',
        __DIR__ . '/Translation',
        __DIR__ . '/Util',
    ]);

    // register a single rule
    //$rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81
    ]);
};
