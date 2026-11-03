<?php
declare(strict_types=1);

namespace App\Core;

/** Renders PHP templates from resources/views inside a layout. */
final class View
{
    public static function render(string $template, array $data = [], string $layout = 'main'): void
    {
        $content = self::capture($template, $data);
        if ($layout === '') {
            echo $content;
            return;
        }
        echo self::capture('layouts/' . $layout, $data + ['content' => $content]);
    }

    public static function capture(string $template, array $data = []): string
    {
        $file = BASE_PATH . '/resources/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $template");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
