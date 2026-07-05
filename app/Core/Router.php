<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal front-controller router. Supports {param} placeholders and
 * per-route middleware callbacks (e.g. auth guards).
 */
class Router
{
    private array $routes = [];
    private array $groupMiddleware = [];

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function any(string $path, string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, string $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => trim($path, '/'),
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    public function group(array $middleware, callable $callback): void
    {
        $previous = $this->groupMiddleware;
        $this->groupMiddleware = array_merge($previous, $middleware);
        $callback($this);
        $this->groupMiddleware = $previous;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = trim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $pattern = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);

                foreach ($route['middleware'] as $middleware) {
                    $middleware();
                }

                [$controllerName, $action] = explode('@', $route['handler']);
                $class = "App\\Controllers\\{$controllerName}";

                if (!class_exists($class)) {
                    $this->abort(500, "Controller {$class} not found");
                    return;
                }

                $controller = new $class();
                if (!method_exists($controller, $action)) {
                    $this->abort(500, "Action {$action} not found on {$class}");
                    return;
                }

                call_user_func_array([$controller, $action], $matches);
                return;
            }
        }

        $this->abort(404);
    }

    private function abort(int $code, string $message = ''): void
    {
        http_response_code($code);
        $view = $code === 404 ? 'pages/404' : 'pages/error';
        view($view, ['message' => $message, 'title' => $code === 404 ? 'Page Not Found' : 'Error']);
    }
}
