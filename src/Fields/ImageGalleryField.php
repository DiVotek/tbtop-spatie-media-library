<?php

namespace Tbtop\ImageGallery\Fields;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Tbtop\Admin\Dsl\Fields\Select;
use Tbtop\ImageGallery\Support\MediaGalleryOptions;

/**
 * An image picker backed by a model's spatie media collection. Subclasses
 * Select rather than adding a new field kind at the core level: S::findQueryableSelect()
 * matches on `instanceof Select` plus a query closure, so async search,
 * per-request limit, resolve-by-value and multiple() all come from core.
 */
final class ImageGalleryField extends Select
{
    protected function kind(): string
    {
        return 'imageGallery';
    }

    /**
     * Wires this field's query() to the named collection on $model. Rows are
     * capped at config('tbtop-image-gallery.per_page') and filtered by name.
     */
    public function forCollection(Model&HasMedia $model, string $collection): static
    {
        return $this->query(
            fn (array $deps, string $search): array => MediaGalleryOptions::search($model, $collection, $search),
        );
    }
}
