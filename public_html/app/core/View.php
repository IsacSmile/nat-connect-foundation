<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * NatConnect Foundation — View Rendering Engine
 *
 * Renders views encapsulated in customizable layout shells, supports partials
 * and variable scoping.
 */
class View
{
    private static string $viewsPath = '';

    public static function getViewsPath(): string
    {
        if (self::$viewsPath === '') {
            self::$viewsPath = dirname(__DIR__) . '/views';
        }
        return self::$viewsPath;
    }

    /**
     * Render a view file inside an optional layout.
     *
     * @param string      $view   Relative path without .php (e.g. 'public/home')
     * @param array       $data   Variables passed to the view and layout
     * @param string|null $layout Relative layout path without .php (e.g. 'layouts/public'), or null for raw
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/public'): void
    {
        $baseDir = self::getViewsPath();
        $viewFile = $baseDir . '/' . ltrim($view, '/') . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View file not found: {$viewFile}");
        }

        // Render the view body into buffer
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // If no layout requested, output raw view content
        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = $baseDir . '/' . ltrim($layout, '/') . '.php';
        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Layout file not found: {$layoutFile}");
        }

        // Render layout with $content available
        require $layoutFile;
    }

    /**
     * Render a sub-template partial.
     *
     * @param string $partial Relative path without .php (e.g. 'partials/header')
     * @param array  $data    Variables passed to the partial
     */
    public static function partial(string $partial, array $data = []): void
    {
        $baseDir = self::getViewsPath();
        $partialFile = $baseDir . '/' . ltrim($partial, '/') . '.php';

        if (!file_exists($partialFile)) {
            throw new RuntimeException("Partial file not found: {$partialFile}");
        }

        extract($data, EXTR_SKIP);
        require $partialFile;
    }
}
