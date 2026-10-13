<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/**
 * NatConnect Foundation — Lightweight Router
 *
 * Supports GET/POST routes, parameterized path tokens ({slug}, {id}),
 * automatic 301 redirects verification from database, and fallback 404 handling.
 */
class Router
{
    /** @var array<string, array<int, array{pattern: string, regex: string, paramNames: string[], handler: mixed}>> */
    private array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    /** @var callable|null */
    private $notFoundHandler = null;

    /**
     * Register a GET route.
     */
    public function get(string $path, mixed $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, mixed $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Set a custom 404 Not Found callback.
     */
    public function setNotFound(callable $handler): self
    {
        $this->notFoundHandler = $handler;
        return $this;
    }

    /**
     * Parse path pattern and register route.
     */
    private function addRoute(string $method, string $path, mixed $handler): self
    {
        $path = '/' . trim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        // Extract parameter tokens like {slug}, {id}
        $paramNames = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];
            return '([^/]+)';
        }, $path);

        $regex = '#^' . $pattern . '$#u';

        $this->routes[$method][] = [
            'pattern'    => $path,
            'regex'      => $regex,
            'paramNames' => $paramNames,
            'handler'    => $handler,
        ];

        return $this;
    }

    /**
     * Dispatch the current HTTP request.
     */
    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        // Normalize path (strip query parameters)
        $parsedUrl = parse_url($uri, PHP_URL_PATH);
        $path = '/' . trim((string) $parsedUrl, '/');
        if ($path === '') {
            $path = '/';
        }

        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                array_shift($matches); // Remove full match
                $params = [];
                foreach ($route['paramNames'] as $index => $name) {
                    $params[$name] = isset($matches[$index]) ? urldecode($matches[$index]) : null;
                }

                $this->executeHandler($route['handler'], $params);
                return;
            }
        }

        // Route not found directly: check redirects table before 404
        $this->checkDatabaseRedirect($path);

        // If no redirect matched, trigger 404
        $this->handleNotFound($path);
    }

    /**
     * Execute a route handler (Closure, [Controller, 'method'], or 'Controller@method').
     */
    private function executeHandler(mixed $handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, [$params]);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            if (is_string($class)) {
                $class = new $class();
            }
            call_user_func_array([$class, $method], [$params]);
            return;
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $controller = new $class();
            call_user_func_array([$controller, $method], [$params]);
            return;
        }

        throw new \InvalidArgumentException("Invalid route handler supplied.");
    }

    /**
     * Check database redirects table for legacy/custom 301 paths.
     */
    private function checkDatabaseRedirect(string $path): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT `new_path` FROM `redirects` WHERE `old_path` = :p1 OR `old_path` = :p2 LIMIT 1");
            $trimmed = trim($path, '/');
            $stmt->execute([
                ':p1' => $path,
                ':p2' => $trimmed !== '' ? $trimmed : '/',
            ]);
            $row = $stmt->fetch();

            if ($row && !empty($row['new_path'])) {
                $newPath = $row['new_path'];
                // Prevent infinite redirect loop
                if ($newPath !== $path && $newPath !== '/' . $trimmed) {
                    header("Location: " . $newPath, true, 301);
                    exit;
                }
            }
        } catch (Throwable) {
            // Database might not be installed yet during initial scaffold check, continue gracefully
        }
    }

    /**
     * Render 404 Not Found response.
     */
    private function handleNotFound(string $path): void
    {
        http_response_code(404);

        if ($this->notFoundHandler !== null) {
            call_user_func($this->notFoundHandler, $path);
            return;
        }

        // Standard 404 display
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>404 Not Found — NatConnect Foundation</title>';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<link rel="stylesheet" href="/assets/css/app.css">';
        echo '</head><body class="bg-surface text-ink min-h-screen flex items-center justify-center p-6">';
        echo '<div class="text-center max-w-md bg-white p-8 rounded-xl border border-border shadow-soft-sm">';
        echo '<span class="inline-block px-3 py-1 bg-accent/10 text-accent-strong border border-accent/20 font-semibold text-xs rounded-full uppercase tracking-wider mb-3">Error 404</span>';
        echo '<h1 class="font-serif text-3xl font-bold text-primary mb-3">Page Not Found</h1>';
        echo '<p class="text-ink-muted text-sm mb-6">The page you requested (' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . ') does not exist or has been moved.</p>';
        echo '<a href="/" class="inline-flex items-center px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors">Return to Homepage</a>';
        echo '</div></body></html>';
    }
}
