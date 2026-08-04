<?php

namespace Tbtop\ImageGallery\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/** Minimal spatie-backed model for exercising the gallery field against a real media collection. */
final class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];
}
