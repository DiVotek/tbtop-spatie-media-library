<?php

namespace Tbtop\SpatieMediaLibrary\Tests\Fixtures;

use Tbtop\Admin\Panels\Panel;
use Tbtop\Admin\Panels\PanelConfig;

final class GalleryPanel extends Panel
{
    public function configure(PanelConfig $panel): PanelConfig
    {
        return $panel->id('admin')->prefix('admin')->middleware(['web'])
            ->pages([OpenGalleryPage::class, GuardedGalleryPage::class]);
    }
}
