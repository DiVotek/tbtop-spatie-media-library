<?php

use Illuminate\Support\Facades\Route;
use Tbtop\Admin\Http\SetAdminLocale;
use Tbtop\Admin\Http\SetCurrentPanel;
use Tbtop\Admin\Panels\PanelRegistry;
use Tbtop\SpatieMediaLibrary\Http\GalleryUploadController;

/**
 * Mirrors core's per-page endpoint registration (tbtop/admin routes/admin.php):
 * one upload route per page, under the page's own middleware() override or the
 * panel's auth stack, with tbtopPage baked into the route defaults so the
 * controller re-resolves the page — and therefore the bound record — without
 * trusting anything in the request. Core exposes no hook for plugin endpoints,
 * so this copy of its stack rule must follow core if that rule changes.
 */
foreach (PanelRegistry::fromConfig()->all() as $panel) {
    foreach ($panel->getPages() as $class) {
        Route::middleware([
            SetCurrentPanel::class.':'.$panel->getId(),
            ...($class::middleware($panel) ?? $panel->authStack()),
            SetAdminLocale::class,
        ])
            ->prefix($panel->getPrefix())
            ->name('tbtop.'.$panel->getId().'.gallery.')
            ->post($class::path().'/gallery-upload/{tbtopField}', GalleryUploadController::class)
            ->defaults('tbtopPage', $class)
            ->name($class::slug().'.upload');
    }
}
