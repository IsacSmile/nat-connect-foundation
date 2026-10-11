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

if (!function_exists('formatNumberIN')) {
    /**
     * Format a number using standard Indian numbering system grouping (e.g. 1,00,000).
     */
    function formatNumberIN(int|float $number): string
    {
        $isNegative = $number < 0;
        $numStr = (string) abs((int) $number);
        if (strlen($numStr) <= 3) {
            return ($isNegative ? '-' : '') . $numStr;
        }

        $lastThree = substr($numStr, -3);
        $remaining = substr($numStr, 0, -3);

        $chunks = [];
        while (strlen($remaining) > 2) {
            $chunks[] = substr($remaining, -2);
            $remaining = substr($remaining, 0, -2);
        }
        if ($remaining !== '') {
            $chunks[] = $remaining;
        }

        $chunks = array_reverse($chunks);
        return ($isNegative ? '-' : '') . implode(',', $chunks) . ',' . $lastThree;
    }
}

if (!function_exists('isTrustedProxy')) {
    /**
     * Check if an IP matches a list of trusted proxy IPs or CIDR blocks.
     *
     * @param string $ip
     * @param array<string> $trustedProxies
     * @return bool
     */
    function isTrustedProxy(string $ip, array $trustedProxies): bool
    {
        if (empty($trustedProxies) || empty($ip)) {
            return false;
        }

        foreach ($trustedProxies as $proxy) {
            $proxy = trim((string) $proxy);
            if ($proxy === '') {
                continue;
            }

            // Exact IP match
            if ($ip === $proxy) {
                return true;
            }

            // CIDR notation check (e.g. 192.168.1.0/24 or 10.0.0.0/8)
            if (str_contains($proxy, '/')) {
                [$subnet, $maskBits] = explode('/', $proxy, 2);
                $maskBits = (int) $maskBits;

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $ipLong = ip2long($ip);
                    $subnetLong = ip2long($subnet);
                    if ($maskBits >= 0 && $maskBits <= 32) {
                        $mask = -1 << (32 - $maskBits);
                        $subnetLong &= $mask;
                        if (($ipLong & $mask) === $subnetLong) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}

if (!function_exists('clientIp')) {
    /**
     * Determine client IP address safely.
     * Uses REMOTE_ADDR ONLY by default.
     * Ignores X-Forwarded-For, CF-Connecting-IP, X-Real-IP unless
     * 'security.trusted_proxies' is configured and REMOTE_ADDR matches.
     */
    function clientIp(): string
    {
        $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $trustedProxies = (array) config('security.trusted_proxies', []);

        // Default behavior: REMOTE_ADDR ONLY
        if (empty($trustedProxies) || !isTrustedProxy($remoteAddr, $trustedProxies)) {
            return $remoteAddr;
        }

        // Only evaluate headers if REMOTE_ADDR is an explicitly trusted reverse proxy
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $cfIp = trim((string) $_SERVER['HTTP_CF_CONNECTING_IP']);
            if (filter_var($cfIp, FILTER_VALIDATE_IP)) {
                return $cfIp;
            }
        }

        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $realIp = trim((string) $_SERVER['HTTP_X_REAL_IP']);
            if (filter_var($realIp, FILTER_VALIDATE_IP)) {
                return $realIp;
            }
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
            $first = trim($ips[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }

        return $remoteAddr;
    }
}

if (!function_exists('setFlash')) {
    /**
     * Set a one-time flash message in session.
     */
    function setFlash(string $type, string $message): void
    {
        \App\Core\Auth::startSession();
        $_SESSION['flash'][$type] = $message;
    }
}

if (!function_exists('getFlash')) {
    /**
     * Retrieve and clear a specific flash message.
     */
    function getFlash(string $type): ?string
    {
        \App\Core\Auth::startSession();
        if (!empty($_SESSION['flash'][$type])) {
            $msg = (string) $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]);
            return $msg;
        }
        return null;
    }
}

if (!function_exists('getFlashes')) {
    /**
     * Retrieve all flash messages and clear them from session.
     *
     * @return array<string, string>
     */
    function getFlashes(): array
    {
        \App\Core\Auth::startSession();
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return is_array($flashes) ? $flashes : [];
    }
}

if (!function_exists('hasFlash')) {
    /**
     * Check if a flash message exists.
     */
    function hasFlash(string $type): bool
    {
        \App\Core\Auth::startSession();
        return !empty($_SESSION['flash'][$type]);
    }
}

