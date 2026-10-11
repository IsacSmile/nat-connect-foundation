<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Global Helper Utilities
 */

if (!function_exists('e')) {
    /**
     * Escape HTML output for XSS protection.
     */
    function e(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('slugify')) {
    /**
     * Generate an SEO-friendly URL slug.
     */
    function slugify(string $text): string
    {
        // Replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', (string) $text);
        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', (string) $text);
        // Trim
        $text = trim((string) $text, '-');
        // Remove duplicate dividers
        $text = preg_replace('~-+~', '-', (string) $text);
        // Lowercase
        $text = strtolower((string) $text);

        return !empty($text) ? $text : 'post-' . bin2hex(random_bytes(3));
    }
}

if (!function_exists('readTime')) {
    /**
     * Calculate article reading time at 200 words per minute.
     * Guaranteed minimum of 1 minute.
     */
    function readTime(?string $html): int
    {
        if (empty($html)) {
            return 1;
        }

        $plainText = strip_tags($html);
        $wordCount = str_word_count($plainText);
        $minutes = (int) ceil($wordCount / 200);

        return max(1, $minutes);
    }
}

if (!function_exists('formatDate')) {
    /**
     * Format a date string in standard Indian format (e.g. 12 Oct 2026).
     */
    function formatDate(?string $datetime, string $format = 'd M Y'): string
    {
        if (empty($datetime)) {
            return '';
        }

        try {
            $date = new DateTime($datetime);
            return $date->format($format);
        } catch (Throwable) {
            return '';
        }
    }
}

if (!function_exists('url')) {
    /**
     * Generate a fully qualified URL for the application.
     */
    function url(string $path = ''): string
    {
        $baseUrl = rtrim((string) config('app.url', 'http://127.0.0.1:8000'), '/');
        $path = ltrim($path, '/');

        return $path !== '' ? "{$baseUrl}/{$path}" : $baseUrl;
    }
}

if (!function_exists('asset')) {
    /**
     * Generate an asset URL with automated cache-busting timestamp query parameter.
     * Example: asset('assets/css/app.css') -> /assets/css/app.css?v=1733829100
     */
    function asset(string $path): string
    {
        $cleanPath = '/' . ltrim($path, '/');
        $publicRoot = dirname(__DIR__, 2) . '/public_html';
        $diskPath = $publicRoot . $cleanPath;

        $version = '';
        if (file_exists($diskPath)) {
            $version = '?v=' . filemtime($diskPath);
        }

        return $cleanPath . $version;
    }
}

if (!function_exists('youtubeId')) {
    /**
     * Extract 11-character YouTube video ID from various standard YouTube URL formats.
     */
    function youtubeId(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i';
        if (preg_match($pattern, $url, $match)) {
            return $match[1];
        }

        return null;
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to another URL and terminate execution.
     */
    function redirect(string $path, int $status = 302): void
    {
        $target = str_starts_with($path, 'http') ? $path : url($path);
        header("Location: {$target}", true, $status);
        exit;
    }
}
