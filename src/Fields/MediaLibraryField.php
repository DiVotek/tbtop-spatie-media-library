<?php

namespace Tbtop\SpatieMediaLibrary\Fields;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Tbtop\Admin\Dsl\Fields\Select;
use Tbtop\SpatieMediaLibrary\Support\MediaGalleryOptions;

/**
 * An image picker backed by a model's spatie media collection. Subclasses
 * Select rather than adding a new field kind at the core level: S::findQueryableSelect()
 * matches on `instanceof Select` plus a query closure, so async search,
 * per-request limit, resolve-by-value and multiple() all come from core.
 */
final class MediaLibraryField extends Select
{
    private ?Model $target = null;

    private string $collection = 'default';

    protected function kind(): string
    {
        return 'imageGallery';
    }

    /**
     * Wires this field's query() to the named collection on $model. Rows are
     * capped at config('tbtop-spatie-media-library.per_page') and filtered by name.
     */
    public function forCollection(Model&HasMedia $model, string $collection): static
    {
        $this->target = $model;
        $this->collection = $collection;

        return $this->query(
            fn (array $deps, string $search): array => MediaGalleryOptions::search($model, $collection, $search),
        );
    }

    /**
     * The bound record. Upload controllers read it off the resolved page rather
     * than trusting a model reference from the client.
     */
    public function target(): (Model&HasMedia)|null
    {
        return $this->target instanceof HasMedia ? $this->target : null;
    }

    public function collection(): string
    {
        return $this->collection;
    }
}
