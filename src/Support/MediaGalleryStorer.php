<?php

namespace Tbtop\SpatieMediaLibrary\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tbtop\Admin\Media\SvgSanitizer;
use Tbtop\Admin\Uploads\ImageEncoder;

/**
 * Persists an upload into a model's named spatie media collection, reusing
 * core's ImageEncoder for the same conversion step Upload fields get.
 */
final class MediaGalleryStorer
{
    /**
     * @param  array{format: string, quality?: int}|null  $conversion  Per-field override; falls back to config.
     */
    public static function store(
        UploadedFile $file,
        Model&HasMedia $model,
        string $collection,
        ?array $conversion = null,
    ): Media {
        // An unsaved model has no key, and spatie derives the storage path from
        // it — attaching here yields a media row whose path generator later
        // fails on null. Refuse up front instead of writing that record.
        if (! $model->exists) {
            throw new RuntimeException('Cannot attach media to an unsaved model.');
        }

        $conversion ??= (array) config('tbtop-spatie-media-library.conversion');
        $originalName = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);

        $converted = self::encode($file, $conversion, $originalName);
        $path = $converted[0] ?? (string) $file->getRealPath();
        $fileName = $converted[1] ?? (string) $file->getClientOriginalName();

        // addMedia() consumes (deletes) $path once copied to the media disk by
        // default, so the encoded temp file needs no separate cleanup here.
        $media = $model->addMedia($path)->usingName($originalName)->usingFileName($fileName)->toMediaCollection($collection);

        // Sniffed from the stored bytes, so a scriptful svg is stripped even
        // when the conversion above left the original untouched.
        SvgSanitizer::sanitizeStored($media->disk, $media->getPathRelativeToRoot(), $fileName);

        return $media;
    }

    /**
     * Encodes to the configured format when GD supports it; returns null when
     * the original upload should be kept untouched instead (unsupported
     * format, or GD could not decode the file).
     *
     * @param  array{format?: string, quality?: int}  $conversion
     * @return array{0: string, 1: string}|null
     */
    private static function encode(UploadedFile $file, array $conversion, string $originalName): ?array
    {
        $format = $conversion['format'] ?? null;
        if (! is_string($format) || ! ImageEncoder::supports($format)) {
            return null;
        }

        $img = ImageEncoder::fromUpload($file);
        if ($img === null) {
            return null;
        }

        $quality = isset($conversion['quality']) ? (int) $conversion['quality'] : null;
        $encoded = ImageEncoder::encode($img, $format, $quality);
        imagedestroy($img);
        if ($encoded === null) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'gallery-').'.'.$encoded['ext'];
        file_put_contents($tempPath, $encoded['blob']);

        return [$tempPath, "{$originalName}.{$encoded['ext']}"];
    }
}
