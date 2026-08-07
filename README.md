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

The package is private and ships from git — no registry on either side. Both halves are read from
the same tag, so they cannot drift apart.

Point composer at the repository and require the package:

```json
{
  "repositories": [
    { "type": "vcs", "url": "git@github.com:DiVotek/tbtop-spatie-media-library.git" }
  ],
  "require": {
    "tbtop/spatie-media-library": "^0.1"
  }
}
```

Add the client with the matching tag:

```json
{
  "dependencies": {
    "@tbtop/spatie-media-library": "github:DiVotek/tbtop-spatie-media-library#v0.1.0"
  }
}
```

Both resolve over your existing git credentials — an SSH key, `gh auth login`, or a credential
helper — the same way composer's `auth.json` covers private VCS repositories. Nothing extra to
configure.

Upgrading means bumping the tag in the npm dependency and the constraint in composer. The npm side
pins an exact tag: git dependencies have no version ranges.

Register the client field once, wherever your admin entrypoint registers its other fields:

```ts
import { registerMediaLibraryField } from "@tbtop/spatie-media-library";

registerMediaLibraryField();
```

**Register the package for class detection**, or its utilities are never generated and the field
renders unstyled. Tailwind skips `node_modules` unless a source is declared explicitly:

```css
@import "tailwindcss";
@source "../../node_modules/@tbtop/spatie-media-library/client/dist";
```

The path is relative to the stylesheet.

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
bun install
bun run typecheck
bun run build      # writes client/dist
```

`client/dist` is committed, because npm installs a git checkout as-is and never builds it. The
release workflow rebuilds and commits it before tagging, so a release always ships a dist that
matches its sources — but a branch can carry a stale one between releases.

The manifests are split by role: the repository root holds the published npm manifest (version,
entry points, peers), while `client/package.json` exists only to run the build.

## Releasing

Bump the version in the root `package.json` in your PR — merging it to `main` is the release. The
workflow tags `vX.Y.Z` and creates a GitHub Release with notes generated from the merged pull
requests. Composer reads that tag through the VCS repository; npm reads the same tag.

A merge that does not change the version is not a release, and the workflow exits quietly. Rebuild
and commit `client/dist` alongside any change to `client/src`: CI fails a pull request whose dist is
stale, and so does the release.
