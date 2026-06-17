<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/config', __DIR__ . '/public', __DIR__ . '/tests'])
    ->append([__DIR__ . '/bin/console'])
    ->notPath('resources/views');

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
    ])
    ->setFinder($finder);
