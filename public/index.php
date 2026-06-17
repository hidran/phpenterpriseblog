<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

(new App\Kernel(dirname(__DIR__)))->handle();
