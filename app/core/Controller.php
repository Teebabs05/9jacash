<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $name, array $data = [], ?string $layout = 'main'): void
    {
        view($name, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        redirect($path);
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function verifyCsrf(): void
    {
        if (!Security::verifyCsrf($this->post('csrf_token'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            http_response_code(419);
            $back = $_SERVER['HTTP_REFERER'] ?? base_url('/');
            header('Location: ' . $back);
            exit;
        }
    }

    protected function old(array $data): void
    {
        Session::set('_old', $data);
    }

    protected function json(array $data, int $status = 200): never
    {
        json_response($data, $status);
    }
}
