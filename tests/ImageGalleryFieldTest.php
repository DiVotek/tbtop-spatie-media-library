<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tbtop\Admin\Dsl\S;
use Tbtop\ImageGallery\Support\MediaGalleryOptions;
use Tbtop\ImageGallery\Support\MediaGalleryStorer;
use Tbtop\ImageGallery\Tests\Fixtures\Product;

uses(RefreshDatabase::class);

it('serialises the media collection as value/label/display rows through findQueryableSelect', function (): void {
    Storage::fake('public');
    $product = Product::create(['name' => 'Chair']);
    $product->addMediaFromString('fake-bytes')->usingName('front')->usingFileName('front.jpg')->toMediaCollection('photos');

    $s = new S;
    $s->form('main', [
        $s->imageGallery('photos')->forCollection($product, 'photos'),
    ])->onSubmit(fn () => null);

    $field = $s->findQueryableSelect('photos');
    $rows = ($field->queryClosure())([], '');

    expect($rows)->toHaveCount(1)
        ->and($rows[0])->toHaveKeys(['value', 'label', 'display'])
        ->and($rows[0]['label'])->toBe('front')
        ->and($rows[0]['display'])->toHaveKeys(['image', 'subtitle'])
        ->and($rows[0])->not->toHaveKey('file_name');
});

it('saves an upload into the named media collection, not default', function (): void {
    Storage::fake('public');
    $product = Product::create(['name' => 'Table']);
    $file = UploadedFile::fake()->image('leg.png');

    MediaGalleryStorer::store($file, $product, 'gallery');

    expect($product->getMedia('gallery'))->toHaveCount(1)
        ->and($product->getMedia('default'))->toHaveCount(0);
});

it('returns an empty array for an empty collection instead of null', function (): void {
    $product = Product::create(['name' => 'Lamp']);

    $rows = MediaGalleryOptions::search($product, 'photos', '');

    expect($rows)->toBe([]);
});

it('applies a declared conversion: png in, webp on disk and in the returned url', function (): void {
    Storage::fake('public');
    $product = Product::create(['name' => 'Shelf']);
    $file = UploadedFile::fake()->image('side.png');

    $media = MediaGalleryStorer::store($file, $product, 'gallery', ['format' => 'webp']);

    expect($media->mime_type)->toBe('image/webp')
        ->and($media->file_name)->toEndWith('.webp')
        ->and($media->getUrl())->toEndWith('.webp');
    Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
});

it('refuses to attach media to an unsaved model instead of writing a keyless row', function (): void {
    Storage::fake('public');
    $unsaved = new Product(['name' => 'Not persisted']);

    expect(fn () => MediaGalleryStorer::store(
        UploadedFile::fake()->image('orphan.png'),
        $unsaved,
        'gallery',
    ))->toThrow(RuntimeException::class);

    // Spatie derives the storage path from the model key, so a row written here
    // would blow up later in DefaultPathGenerator rather than at the call site.
    expect(Media::count())->toBe(0);
});
