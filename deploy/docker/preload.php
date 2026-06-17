<?php

declare(strict_types=1);

// Preload application classes through Composer's authoritative classmap so that
// each class is loaded *with* its dependencies (e.g. vendor PSR interfaces)
// resolved in the correct order. Using the autoloader instead of bare
// opcache_compile_file() avoids "Can't preload unlinked class" warnings.
$loader = require __DIR__ . '/../../vendor/autoload.php';

foreach (array_keys($loader->getClassMap()) as $class) {
    if (str_starts_with($class, 'App\\')) {
        class_exists($class);
    }
}
