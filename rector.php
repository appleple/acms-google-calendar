<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/GET',
        __DIR__ . '/POST',
        __DIR__ . '/test',
        __DIR__ . '/Api.php',
        __DIR__ . '/Engine.php',
        __DIR__ . '/Hook.php',
        __DIR__ . '/ServiceProvider.php',
    ])
    ->withPreparedSets(deadCode: true);

