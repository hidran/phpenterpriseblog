<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\View;

abstract class BaseController
{
    protected string $content = '';

    public function __construct(protected readonly View $view)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function display(string $layout = 'layouts/default', array $data = []): void
    {
        $data['content'] = $this->content;
        echo $this->view->render($layout, $data);
    }
}
