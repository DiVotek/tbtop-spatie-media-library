<?php

namespace Tbtop\ImageGallery;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tbtop\Admin\Dsl\S;
use Tbtop\ImageGallery\Fields\ImageGalleryField;

final class ImageGalleryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tbtop-image-gallery')
            ->hasConfigFile('tbtop-image-gallery')
            ->hasRoute('gallery');
    }

    public function packageBooted(): void
    {
        S::register('imageGallery', ImageGalleryField::class);
    }
}
