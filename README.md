# tbtop/spatie-media-library

An image picker for [tbtop/admin](https://github.com/DiVotek/tbtop) forms, backed by
[spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary). The field lists one
record's media collection, uploads into it, and imports images by URL.

It is a field, not a media library. Files belong to the record they are attached to — there is no
shared pool, no folders, no cross-entity browsing. For files reused between entities, use the media
library that ships with tbtop/admin.

The package is two halves in one repository: the PHP field (`src/`) and its React client
(`client/`). Both are required.

## Requirements

- PHP 8.4, Laravel 11–13
- `tbtop/admin` and `@tbtop/inertia-admin` (see peer ranges in `composer.json` / `client/package.json`)
- `spatie/laravel-medialibrary` ^11

## Install

```bash
composer require tbtop/spatie-media-library
npm install @tbtop/spatie-media-library
```

Register the client field once, wherever your admin entrypoint registers its other fields:

```ts
import { registerMediaLibraryField } from "@tbtop/spatie-media-library";

registerMediaLibraryField();
```

**Register the package for class detection**, or its utilities are never generated and the field
renders unstyled. Tailwind skips `node_modules` unless a source is declared explicitly:

```css
@import "tailwindcss";
@source "../../node_modules/@tbtop/spatie-media-library/dist";
```

The path is relative to the stylesheet, and points at `dist` because that is what npm ships.

## Usage

The model needs spatie's contract and a declared collection:

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Post extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banners');
    }
}
```

Then bind the field to that record and collection:

```php
use Tbtop\SpatieMediaLibrary\Support\MediaGalleryOptions;

$s->form('post', [
    $s->imageGallery('banners')
        ->label('Post banners')
        ->forCollection($post, 'banners')
        ->multiple()
        ->rules('nullable|array'),
])->record(['banners' => MediaGalleryOptions::ids($post, 'banners')]);
```

`forCollection()` is the whole configuration. Endpoints are derived from the page path on the
client — nothing to declare, nothing to keep in sync.

Without `->multiple()` the field holds a single media id; with it, a list. `MediaGalleryOptions::ids()`
shapes a collection for the form's `record()`.

The record must be persisted. Spatie derives the storage path from the model key, so attaching to an
unsaved model is refused with a 422 rather than writing a row whose path cannot be built.

## Uploading and URL import

The field posts to `{page-path}/gallery-upload/{field}`, registered per panel page. The target record
and collection are read off the field on the re-resolved page and never from the request, so a caller
cannot redirect an upload into another model. The page's own gate applies.

URL import reuses core's guards: private ranges and non-http schemes are blocked with DNS pinning
against rebinding, redirects are refused, the transfer aborts when it exceeds the size ceiling, and
the mime is verified from the downloaded bytes rather than the `Content-Type` header. Imports run
synchronously and can take as long as the configured timeout.

## Configuration

```bash
php artisan vendor:publish --tag=tbtop-spatie-media-library-config
```

| Key | Default | Meaning |
| --- | --- | --- |
| `per_page` | `24` | Rows returned per options request |
| `conversion.format` | `webp` | Re-encode format: `webp`, `jpeg`, `png`, or `null` to keep the original |
| `conversion.quality` | `80` | Encoder quality |
| `accept` | `['image/*']` | fnmatch patterns; `text/html` is refused regardless |
| `url_import.enabled` | `true` | Whether the URL import input is accepted |

The import timeout, size ceiling and host allowlist are read from `tbtop-admin.media` — one setting
for every ingestion path, rather than a second place to configure the same thing.

## Development

```bash
composer test      # pest
composer analyse   # phpstan
composer format    # pint

cd client
bun run typecheck
bun run build
```
