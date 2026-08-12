<?php

namespace App\Support;

class EmailTracking
{
    public static function pixelUrl(string $trackingId): string
    {
        $trackingId = trim($trackingId);

        return route('tracking.open', ['tracking_id' => $trackingId], true);
    }

    /**
     * Outlook/Gmail-safe 1×1 pixel. Avoid display:none — desktop Outlook strips those images.
     */
    public static function pixelHtml(string $trackingId): string
    {
        $src = e(self::pixelUrl($trackingId));

        return '<img src="'.$src.'" width="1" height="1" border="0" alt=""'
            .' style="width:1px;height:1px;border:0;overflow:hidden;line-height:1px;" />';
    }

    public static function rewriteLinks(string $html, string $trackingId): string
    {
        return (string) preg_replace_callback(
            '/<a\s+([^>]*\s)?href=(["\'])([^"\']+)\2/i',
            function (array $matches) use ($trackingId): string {
                $prefix = $matches[1] ?? '';
                $targetUrl = $matches[3];

                if ($targetUrl === '' || str_starts_with(strtolower($targetUrl), 'mailto:') || str_starts_with($targetUrl, '#')) {
                    return $matches[0];
                }

                if (str_contains($targetUrl, '/t/c/')) {
                    return $matches[0];
                }

                $trackingUrl = url('/t/c/'.$trackingId).'?url='.urlencode($targetUrl);

                return '<a '.$prefix.'href="'.$trackingUrl.'"';
            },
            $html
        );
    }

    public static function appendToHtml(string $html, string $trackingId): string
    {
        $html = self::rewriteLinks($html, $trackingId);

        if (str_contains($html, '/t/o/'.$trackingId)) {
            return $html;
        }

        return $html.self::pixelHtml($trackingId);
    }

    public static function normalizeTrackingId(string $trackingId): string
    {
        $trackingId = trim($trackingId);

        if (str_ends_with(strtolower($trackingId), '.gif')) {
            $trackingId = substr($trackingId, 0, -4);
        }

        return strtolower($trackingId);
    }
}
