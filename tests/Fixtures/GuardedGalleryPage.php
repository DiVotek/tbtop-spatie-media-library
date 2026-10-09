<?php

namespace Tbtop\SpatieMediaLibrary\Tests\Fixtures;

use Tbtop\Admin\Panels\PanelConfig;

final class GuardedGalleryPage extends GalleryPage
{
    public static function path(): string
    {
        return 'gallery-guarded';
    }

    public static function middleware(PanelConfig $panel): ?array
    {
        return [...$panel->authStack(), RejectAll::class];
    }
}
