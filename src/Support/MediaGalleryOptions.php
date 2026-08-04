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
    /** @return list<array{value: string, label: string, display: array{image: string, subtitle: string}}> */
    public static function search(Model&HasMedia $model, string $collection, string $search): array
    {
        $needle = mb_strtolower($search);

        return $model->getMedia($collection)
            ->filter(fn (Media $media): bool => $needle === '' || str_contains(mb_strtolower($media->name), $needle))
            ->take((int) config('tbtop-image-gallery.per_page'))
            ->values()
            ->map(fn (Media $media): array => self::toOption($media))
            ->all();
    }

    /**
     * `display` is core's allowlisted channel for option imagery — arbitrary
     * row keys are stripped, since a query() row is often a whole model.
     *
     * @return array{value: string, label: string, display: array{image: string, subtitle: string}}
     */
    public static function toOption(Media $media): array
    {
        return [
            'value' => (string) $media->getKey(),
            'label' => $media->name,
            'display' => [
                'image' => $media->getUrl(),
                'subtitle' => $media->mime_type ?? '',
            ],
        ];
    }

    /**
     * Current ids in a collection, shaped for a form record().
     *
     * @return list<string>
     */
    public static function ids(Model&HasMedia $model, string $collection): array
    {
        return $model->getMedia($collection)
            ->map(fn (Media $media): string => (string) $media->getKey())
            ->values()
            ->all();
    }
}
