<?php

namespace Tbtop\ImageGallery\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tbtop\Admin\Media\MediaUploadLimit;
use Tbtop\Admin\Media\SsrfGuard;
use Throwable;

/**
 * Downloads a user-supplied URL into a temporary UploadedFile.
 *
 * Fetching an arbitrary URL server-side is an SSRF sink, so every guard here is
 * load-bearing: core's SsrfGuard blocks private ranges and pins DNS (a bare
 * pre-flight check is defeated by a rebind between check and request),
 * redirects are refused because a 302 escapes the check, the size cap aborts
 * mid-transfer, and the mime is verified from disk content rather than the
 * Content-Type header alone.
 */
final class UrlImporter
{
    /** Caller is responsible for @unlink()ing the returned file's path. */
    public static function fetch(string $url): UploadedFile
    {
        if (! self::hostAllowed($url) || SsrfGuard::isBlocked($url)) {
            throw new RuntimeException('This URL cannot be imported.');
        }

        $maxBytes = MediaUploadLimit::bytes();
        $tmpPath = self::tempPath($url);
        $oversized = false;

        try {
            $response = Http::timeout((int) config('tbtop-image-gallery.url_import.timeout', 30))
                ->withOptions([
                    'allow_redirects' => false,
                    'progress' => static function (int $dlTotal, int $dlNow) use ($maxBytes, &$oversized): void {
                        if ($dlNow > $maxBytes) {
                            $oversized = true;
                            throw new RuntimeException('Download too large');
                        }
                    },
                    ...SsrfGuard::pinnedDnsCurlOption($url),
                ])
                ->sink($tmpPath)
                ->get($url);
        } catch (Throwable $e) {
            @unlink($tmpPath);
            throw new RuntimeException(
                $oversized || self::causedByOversize($e) ? 'The file is too large.' : 'Could not download that URL.',
            );
        }

        if (! $response->successful()) {
            @unlink($tmpPath);
            throw new RuntimeException('Could not download that URL.');
        }

        return self::verified($tmpPath, $url, $maxBytes);
    }

    private static function verified(string $tmpPath, string $url, int $maxBytes): UploadedFile
    {
        if (! is_file($tmpPath) || filesize($tmpPath) > $maxBytes) {
            @unlink($tmpPath);
            throw new RuntimeException('The file is too large.');
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmpPath);
        if (! MimeGuard::allows($mime)) {
            @unlink($tmpPath);
            throw new RuntimeException('That file type is not allowed.');
        }

        return new UploadedFile($tmpPath, self::filenameFromUrl($url), $mime, test: true);
    }

    private static function hostAllowed(string $url): bool
    {
        /** @var list<string> $allowed */
        $allowed = (array) config('tbtop-image-gallery.url_import.allowed_hosts', []);
        if ($allowed === []) {
            return true;
        }

        $host = (string) parse_url($url, PHP_URL_HOST);

        foreach ($allowed as $pattern) {
            if (fnmatch((string) $pattern, $host)) {
                return true;
            }
        }

        return false;
    }

    private static function tempPath(string $url): string
    {
        $descriptor = tempnam(sys_get_temp_dir(), 'tbtop_gallery_');
        if ($descriptor === false) {
            throw new RuntimeException('Could not download that URL.');
        }

        return $descriptor.'_'.self::filenameFromUrl($url);
    }

    private static function causedByOversize(Throwable $e): bool
    {
        for ($cur = $e; $cur !== null; $cur = $cur->getPrevious()) {
            if (str_contains($cur->getMessage(), 'Download too large')) {
                return true;
            }
        }

        return false;
    }

    private static function filenameFromUrl(string $url): string
    {
        $basename = basename((string) parse_url($url, PHP_URL_PATH));
        $sanitized = preg_replace('/[^A-Za-z0-9._-]/', '_', $basename) ?? '';

        return $sanitized !== '' && str_contains($sanitized, '.')
            ? $sanitized
            : 'download_'.time().'.bin';
    }
}
