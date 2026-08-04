<?php

namespace Tbtop\ImageGallery\Support;

final class MimeGuard
{
    public static function allows(string $mime): bool
    {
        // Active content: refused whatever the accept list says, since an
        // image/* pattern would otherwise let text/html through as SVG.
        if (str_starts_with($mime, 'text/html')) {
            return false;
        }

        /** @var list<string> $accept */
        $accept = (array) config('tbtop-image-gallery.accept', ['image/*']);
        if ($accept === []) {
            return true;
        }

        foreach ($accept as $pattern) {
            if (fnmatch((string) $pattern, $mime)) {
                return true;
            }
        }

        return false;
    }
}
