<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

abstract class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        View::render($view, $data, $layout);
    }

    /** Render inside the seller/admin dashboard layout. */
    protected function dashboard(string $view, array $data = []): void
    {
        View::render($view, $data, 'dashboard');
    }
}
