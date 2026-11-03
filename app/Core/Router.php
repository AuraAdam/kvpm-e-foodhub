<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Small front-controller router.
 *
 * Route patterns use {name} placeholders, e.g. "/orders/{number}".
 * Every POST request must carry a valid CSRF token; this is checked here,
 * once, so no state-changing route can forget it.
 *
 * Middleware names:
 *   guest        – only visitors who are not signed in
 *   auth         – any signed-in user
 *   shopper      – signed-in customer or seller (administrators do not shop)
 *   seller       – signed-in seller whose shop is approved
 *   admin        – signed-in administrator
 */
final class Router
{
    private array $routes = [];

    public function get(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    private function add(string $method, string $pattern, array $handler, array $middleware): void
    {
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[A-Za-z0-9_-]+)', $pattern);
        $this->routes[] = [
            'method'     => $method,
            'regex'      => '#^' . $regex . '$#',
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $method) {
                continue;
            }

            if ($method === 'POST') {
                Csrf::verifyRequest();
            }
            foreach ($route['middleware'] as $name) {
                Middleware::run($name);
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = $route['handler'];
            (new $class())->$action(...array_values($params));
            return;
        }

        if ($pathMatched) {
            throw new HttpException(405, 'Kaedah permintaan tidak dibenarkan untuk halaman ini.');
        }
        throw new HttpException(404, 'Halaman yang anda cari tidak wujud.');
    }
}
