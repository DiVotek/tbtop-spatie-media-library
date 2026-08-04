<?php

namespace Tbtop\ImageGallery;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class ImageGalleryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tbtop-image-gallery')
            ->hasConfigFile('tbtop-image-gallery')
            ->hasRoute('gallery');
    }
}
