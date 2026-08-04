<?php

use Illuminate\Support\Facades\Route;
use Tbtop\Admin\Http\SetAdminLocale;
use Tbtop\Admin\Http\SetCurrentPanel;
use Tbtop\Admin\Panels\PanelRegistry;
use Tbtop\ImageGallery\Http\GalleryUploadController;

/**
 * Mirrors core's per-page endpoint registration: one upload route per page,
 * inside that panel's middleware stack, with tbtopPage baked into the route
 * defaults so the controller re-resolves the page — and therefore the bound
 * record — without trusting anything in the request.
 */
foreach (PanelRegistry::fromConfig()->all() as $panel) {
    Route::middleware([
        SetCurrentPanel::class.':'.$panel->getId(),
        ...$panel->authStack(),
        SetAdminLocale::class,
    ])
        ->prefix($panel->getPrefix())
        ->name('tbtop.'.$panel->getId().'.gallery.')
        ->group(function () use ($panel): void {
            foreach ($panel->getPages() as $class) {
                Route::post($class::path().'/gallery-upload/{tbtopField}', GalleryUploadController::class)
                    ->defaults('tbtopPage', $class)
                    ->name($class::slug().'.upload');
            }
        });
}
