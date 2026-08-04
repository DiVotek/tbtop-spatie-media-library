<?php

namespace Tbtop\ImageGallery\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Reads a model's spatie media collection into the option rows the gallery
 * select's query() closure returns. Kept separate from the field builder so
 * it can be exercised without going through the DSL.
 */
final class MediaGalleryOptions
{
    /** @return list<array{value: string, label: string, url: string, mime: string}> */
    public static function search(Model&HasMedia $model, string $collection, string $search): array
    {
        $needle = mb_strtolower($search);

        return $model->getMedia($collection)
            ->filter(fn (Media $media): bool => $needle === '' || str_contains(mb_strtolower($media->name), $needle))
            ->take((int) config('tbtop-image-gallery.per_page'))
            ->values()
            ->map(fn (Media $media): array => [
                'value' => (string) $media->id,
                'label' => $media->name,
                'url' => $media->getUrl(),
                'mime' => $media->mime_type ?? '',
            ])
            ->all();
    }
}
