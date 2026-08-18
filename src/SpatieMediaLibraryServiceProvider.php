<?php

namespace Tbtop\SpatieMediaLibrary;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tbtop\Admin\Dsl\S;
use Tbtop\SpatieMediaLibrary\Fields\MediaLibraryField;

final class SpatieMediaLibraryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tbtop-spatie-media-library')
            ->hasConfigFile('tbtop-spatie-media-library')
            ->hasRoute('gallery');
    }

    public function packageBooted(): void
    {
        S::register('imageGallery', MediaLibraryField::class);
    }
}
