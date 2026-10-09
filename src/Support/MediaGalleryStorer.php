<?php

namespace Tbtop\SpatieMediaLibrary\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
    private const SCRATCH_DISK = 'tbtop-gallery-scratch';

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

        $converted = self::encode($file, $conversion, $originalName, self::acceptedMimes($model, $collection));
        $path = $converted[0] ?? (string) $file->getRealPath();
        $fileName = $converted[1] ?? (string) $file->getClientOriginalName();

        try {
            // Before addMedia(): spatie writes the row and, for singleFile() /
            // onlyKeepLatest() collections, prunes older media inside that call,
            // so rejecting afterwards would leave an orphan row and a lost image.
            self::sanitizeLocal($path, $fileName);

            // addMedia() consumes (deletes) $path only after a successful copy.
            return $model->addMedia($path)->usingName($originalName)->usingFileName($fileName)->toMediaCollection($collection);
        } finally {
            if ($converted !== null && is_file($converted[0])) {
                @unlink($converted[0]);
            }
        }
    }

    /**
     * sanitizeStored() works on a named disk, so the temp file's directory is
     * mounted as a scratch disk; forgetDisk() drops the root cached by the
     * previous call.
     */
    private static function sanitizeLocal(string $path, string $fileName): void
    {
        config(['filesystems.disks.'.self::SCRATCH_DISK => ['driver' => 'local', 'root' => dirname($path)]]);
        Storage::forgetDisk(self::SCRATCH_DISK);

        SvgSanitizer::sanitizeStored(self::SCRATCH_DISK, basename($path), $fileName);
    }

    /** @return list<string> empty when the collection accepts any mime */
    private static function acceptedMimes(Model&HasMedia $model, string $collection): array
    {
        return array_values($model->getMediaCollection($collection)->acceptsMimeTypes ?? []);
    }

    /**
     * Encodes to the configured format when GD supports it and the collection
     * would accept the result; returns null when the original upload should be
     * kept untouched instead.
     *
     * @param  array{format?: string, quality?: int}  $conversion
     * @param  list<string>  $acceptedMimes
     * @return array{0: string, 1: string}|null
     */
    private static function encode(UploadedFile $file, array $conversion, string $originalName, array $acceptedMimes): ?array
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

        if ($acceptedMimes !== [] && ! in_array($encoded['mimeType'], $acceptedMimes, true)) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'gallery-');
        if ($tempPath === false) {
            return null;
        }
        // tempnam() creates the placeholder; rename keeps the unique name while
        // giving spatie the extension it derives the mime from.
        $target = $tempPath.'.'.$encoded['ext'];
        rename($tempPath, $target);
        file_put_contents($target, $encoded['blob']);

        return [$target, "{$originalName}.{$encoded['ext']}"];
    }
}
