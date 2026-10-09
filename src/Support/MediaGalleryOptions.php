<?php

namespace Tbtop\SpatieMediaLibrary\Support;

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
    /** @return list<array{value: string, label: string, display: array{image: string, subtitle: string, mime: string}}> */
    public static function search(Model&HasMedia $model, string $collection, string $search): array
    {
        $needle = mb_strtolower($search);

        return $model->getMedia($collection)
            ->filter(fn (Media $media): bool => $needle === '' || str_contains(mb_strtolower($media->name), $needle))
            ->take((int) config('tbtop-spatie-media-library.per_page'))
            ->values()
            ->map(fn (Media $media): array => self::toOption($media))
            ->all();
    }

    /** @return array{value: string, label: string, display: array{image: string, subtitle: string, mime: string}}|null */
    public static function find(Model&HasMedia $model, string $collection, string $id): ?array
    {
        $media = $model->getMedia($collection)->first(fn (Media $media): bool => (string) $media->getKey() === $id);

        return $media === null ? null : self::toOption($media);
    }

    /**
     * `display` is core's allowlisted channel for option imagery — arbitrary
     * row keys are stripped, since a query() row is often a whole model.
     * `mime` rides along so the tile can pick an icon for non-images.
     *
     * @return array{value: string, label: string, display: array{image: string, subtitle: string, mime: string}}
     */
    public static function toOption(Media $media): array
    {
        $mime = $media->mime_type ?? '';

        return [
            'value' => (string) $media->getKey(),
            'label' => $media->name,
            'display' => [
                'image' => $media->getUrl(),
                'subtitle' => $mime,
                'mime' => $mime,
            ],
        ];
    }

    /**
     * Persists a gallery's order for the consumer's save handler, so getMedia()
     * follows it: the given ids first, the collection's other media after them
     * in their previous order. Ids outside this record's collection are ignored.
     *
     * @param  list<string>  $ids
     */
    public static function saveOrder(Model&HasMedia $model, string $collection, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $existing = array_map(fn (Media $media): string => (string) $media->getKey(), self::fresh($model, $collection));
        $selected = array_values(array_intersect(array_unique($ids), $existing));
        $rest = array_values(array_diff($existing, $selected));

        // setNewOrder() renumbers only what it is given; passing the rest too
        // keeps unselected media from interleaving with the new numbers.
        /** @var class-string<Media> $mediaClass */
        $mediaClass = config('media-library.media_model');
        $mediaClass::setNewOrder([...$selected, ...$rest]);
    }

    /**
     * Deletes the collection's media whose id is not in $ids — rows and files.
     * Irreversible, and an empty list empties the collection: the consumer
     * normalises the field's null to [] only when that is what "cleared" means.
     *
     * @param  list<string>  $ids
     */
    public static function pruneUnselected(Model&HasMedia $model, string $collection, array $ids): void
    {
        $ids = array_map('strval', $ids);
        foreach (self::fresh($model, $collection) as $media) {
            if (! in_array((string) $media->getKey(), $ids, true)) {
                $media->delete();
            }
        }
    }

    /**
     * Queried rather than read off the loaded relation, which goes stale
     * between an upload and the form save.
     *
     * @return list<Media>
     */
    private static function fresh(Model&HasMedia $model, string $collection): array
    {
        $rows = $model->media()->where('collection_name', $collection)->orderBy('order_column')->get()->all();

        return array_values(array_filter($rows, fn (mixed $row): bool => $row instanceof Media));
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
