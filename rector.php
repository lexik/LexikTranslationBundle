<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Command',
        __DIR__ . '/Controller',
        __DIR__ . '/DependencyInjection',
        __DIR__ . '/Document',
        __DIR__ . '/Entity',
        __DIR__ . '/EventDispatcher',
        __DIR__ . '/Form',
        __DIR__ . '/Manager',
        __DIR__ . '/Model',
        __DIR__ . '/Storage',
        __DIR__ . '/Tests',
        __DIR__ . '/Translation',
        __DIR__ . '/Util',
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_81,
    ]);
