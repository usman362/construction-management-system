<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Resolves the company logo to an inline data: URI for printing.
 *
 * 2026-04-30 (Brenda): the logo on weekly timesheet prints kept showing as
 * a broken image. Tracing through the layers:
 *
 *   1) Setting::get('company_logo') returns a relative path like
 *      "/uploads/settings/abc.png"
 *   2) The blade was using asset() to turn it into an absolute URL
 *   3) But on production:
 *        - DomPDF can't always fetch HTTPS URLs without isRemoteEnabled
 *        - browser print loses the image during page-break-after rendering
 *        - the cached `app_settings` value sometimes lags behind the upload
 *
 * Solution: read the file from disk, base64-encode it, embed as a
 * `data:image/...;base64,...` URI directly in the <img src>. No external
 * URL resolution, no DomPDF remote-fetch dependency, no cache-staleness
 * window. If the file truly doesn't exist on disk we return null and the
 * blade falls back to typographic initials.
 *
 * Trade-off: each print page carries the encoded logo (~5–50 KB depending
 * on logo size). For weekly prints that produce ~50 pages this adds maybe
 * 1 MB to the response — fine for HTTP, fine for PDF download, totally
 * worth it for "the logo is reliably there every time".
 */
class BrandLogo
{
    /**
     * Get the current logo as a data URI suitable for <img src>.
     * Returns null if no logo is set or the file is missing.
     */
    public static function asDataUri(): ?string
    {
        // Read straight from the DB rather than going through Setting::get(),
        // which uses Cache::rememberForever('app_settings'). If that cache is
        // ever stale (e.g. logo uploaded but cache not invalidated yet, or
        // a permission issue prevented invalidation), Setting::get can return
        // null even when a logo is present. Bypassing the cache costs us one
        // tiny SELECT per print page, well worth the reliability.
        $stored = Setting::query()->where('key', 'company_logo')->value('value');
        if (empty($stored)) {
            return null;
        }

        // If the stored value is already a data: URI or http(s) URL, pass through.
        if (preg_match('#^(data:|https?:)#i', $stored)) {
            return $stored;
        }

        // Try the obvious public_path() location first; fall back to a
        // couple of variants in case the operator has a non-standard layout.
        $relative = ltrim($stored, '/');
        $candidates = [
            public_path($relative),
            // Some installs proxy /uploads/ from storage/app/public via symlink
            storage_path('app/public/' . preg_replace('#^uploads/#', '', $relative)),
            // Final fallback: try as-is in case the value is already absolute
            $stored,
        ];

        foreach ($candidates as $path) {
            if (is_file($path) && is_readable($path)) {
                $bytes = @file_get_contents($path);
                if ($bytes === false || $bytes === '') continue;
                $mime = self::guessMime($path);
                return 'data:' . $mime . ';base64,' . base64_encode($bytes);
            }
        }

        return null;
    }

    /**
     * Best-effort MIME guess from extension. Falls back to image/png which
     * every browser + DomPDF accepts even when the actual bytes are JPEG.
     */
    protected static function guessMime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'svg'         => 'image/svg+xml',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            default       => 'image/png',
        };
    }
}
