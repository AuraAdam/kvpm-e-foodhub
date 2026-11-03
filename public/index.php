<?php
/**
 * KVPM E-FoodHub — front controller.
 * Every page request is routed through this file (see public/.htaccess).
 */
declare(strict_types=1);

use App\Core\HttpException;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

// When run with PHP's built-in server, let it serve real static files directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

// Security headers for every response.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");

try {
    require dirname(__DIR__) . '/app/bootstrap.php';
    Session::start(config('session'));

    $router = new Router();
    require BASE_PATH . '/routes/web.php';
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', current_path());
} catch (HttpException $e) {
    http_response_code($e->status);
    View::render('errors/http', ['status' => $e->status, 'message' => $e->getMessage(), 'title' => 'Ralat ' . $e->status]);
} catch (Throwable $e) {
    // Never show stack traces or credentials to ordinary users.
    if (function_exists('log_error') && defined('BASE_PATH')) {
        log_error($e);
    }
    http_response_code(500);
    $debug = function_exists('config') && config('app.debug');
    if ($e instanceof PDOException && str_contains($e->getMessage(), 'SQLSTATE[HY000] [')) {
        $message = 'Tidak dapat menyambung ke pangkalan data. Pastikan MySQL di XAMPP sedang berjalan dan tetapan dalam fail .env betul.';
    } else {
        $message = 'Berlaku ralat pada pelayan. Sila cuba lagi sebentar lagi.';
    }
    if ($debug) {
        $message .= "\n\n" . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')';
    }
    try {
        View::render('errors/http', ['status' => 500, 'message' => $message, 'title' => 'Ralat pelayan'], 'bare');
    } catch (Throwable) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
    }
}
