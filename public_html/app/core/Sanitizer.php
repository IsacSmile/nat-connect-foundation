<?php
declare(strict_types=1);

namespace App\Core;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * NatConnect Foundation — Content Sanitization Engine
 *
 * Wraps HTMLPurifier with a strict whitelist for rich-text editorial content.
 * Forces noopener and noreferrer on target=_blank links, strips malicious scripts,
 * event handlers, and unauthorized URI schemes. Permits YouTube embeds exclusively.
 */
class Sanitizer
{
    private static ?HTMLPurifier $purifier = null;

    /**
     * Purify user-submitted HTML content according to editorial safety rules.
     */
    public static function clean(string $dirtyHtml): string
    {
        if (trim($dirtyHtml) === '') {
            return '';
        }

        if (self::$purifier === null) {
            $config = HTMLPurifier_Config::createDefault();

            // Set HTMLPurifier cache directory to app/storage/cache/htmlpurifier (never inside vendor/)
            $cacheDir = APP_PATH . '/storage/cache/htmlpurifier';
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0775, true);
            }
            if (is_dir($cacheDir) && is_writable($cacheDir)) {
                $config->set('Cache.SerializerPath', $cacheDir);
            } else {
                $config->set('Cache.DefinitionImpl', null);
            }

            // Allowed HTML tags and attributes
            $config->set(
                'HTML.Allowed',
                'p,h2,h3,strong,em,a[href|title|target|rel],ul,ol,li,blockquote,img[src|alt|width|height],br,figure,figcaption,iframe[src|title|width|height|frameborder|allowfullscreen|class]'
            );

            // Safe iframe embedding: limited strictly to YouTube & YouTube NoCookie embed URLs
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', '%^https://(www\.)?(youtube\.com|youtube-nocookie\.com)/embed/%');

            $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
            $config->set('HTML.TargetNoreferrer', true);
            $config->set('HTML.TargetNoopener', true);
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);

            // Register HTML5 figure and figcaption elements, and iframe allowfullscreen
            $def = $config->getHTMLDefinition(true);
            if ($def !== null) {
                $def->addElement('figure', 'Block', 'Flow', 'Common');
                $def->addElement('figcaption', 'Inline', 'Flow', 'Common');
                $def->addAttribute('iframe', 'allowfullscreen', 'Bool');
            }

            self::$purifier = new HTMLPurifier($config);
        }

        $clean = self::$purifier->purify($dirtyHtml);

        // Strip any iframe that had its non-whitelisted src removed by SafeIframe
        return preg_replace('#<iframe(?![^>]*\bsrc=)[^>]*>.*?</iframe>#is', '', $clean);
    }
}
