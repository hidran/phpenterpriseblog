<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class View
{
    public function __construct(private readonly string $viewsDir)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        $file = $this->viewsDir . '/' . $template . '.tpl.php';
        if (!is_file($file)) {
            throw new RuntimeException("View not found: {$file}");
        }
        extract($data, EXTR_OVERWRITE);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
