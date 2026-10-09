<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tbtop\SpatieMediaLibrary\Tests\Fixtures\Product;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
    $this->product = Product::create(['name' => 'Chair']);
    $this->actingAs(new GenericUser(['id' => 1]));
});

function galleryUpload(string $page, string $field, UploadedFile $file): TestResponse
{
    return test()->post("/admin/{$page}/gallery-upload/{$field}", ['file' => $file], ['Accept' => 'application/json']);
}

it('runs the page middleware on its gallery upload, as core does for every page endpoint', function (): void {
    galleryUpload('gallery-guarded', 'photos', UploadedFile::fake()->image('a.png'))->assertForbidden();
    galleryUpload('gallery-open', 'photos', UploadedFile::fake()->image('a.png'))->assertCreated();

    expect(Media::count())->toBe(1);
});

it('answers 422 with a fixed text and writes nothing when the collection refuses the file', function (): void {
    galleryUpload('gallery-open', 'pngonly', UploadedFile::fake()->image('a.jpg'))
        ->assertStatus(422)
        ->assertExactJson(['message' => 'That file type is not allowed for this collection.']);

    config()->set('media-library.max_file_size', 10);
    galleryUpload('gallery-open', 'photos', UploadedFile::fake()->image('big.png', 50, 50))
        ->assertStatus(422)
        ->assertExactJson(['message' => 'The file is too large.']);

    expect(Media::count())->toBe(0);
});

it('stores the original when the collection would refuse the converted format', function (): void {
    $response = galleryUpload('gallery-open', 'jpegonly', UploadedFile::fake()->image('a.jpg'));

    $response->assertCreated();
    expect(Media::sole()->mime_type)->toBe('image/jpeg');
});

it('rejects a malformed svg without leaving a row or pruning the previous single file', function (): void {
    $this->product->addMedia(UploadedFile::fake()->image('old.png'))->toMediaCollection('single');
    $bad = UploadedFile::fake()->createWithContent('bad.svg', '<svg xmlns="http://www.w3.org/2000/svg"><rect></svg>');

    galleryUpload('gallery-open', 'single', $bad)
        ->assertStatus(422)
        ->assertExactJson(['message' => 'This file is corrupted or is not a valid SVG.']);

    expect(Media::pluck('file_name')->all())->toBe(['old.png']);
});

it('resolves selected ids only from the bound record and collection', function (): void {
    $own = $this->product->addMedia(UploadedFile::fake()->image('own.png'))->toMediaCollection('photos');
    $other = Product::create(['name' => 'Other'])
        ->addMedia(UploadedFile::fake()->image('foreign.png'))->toMediaCollection('photos');
    config()->set('tbtop-spatie-media-library.per_page', 0);

    $options = $this->postJson('/admin/gallery-open/select-options/photos', [
        'values' => [(string) $own->id, (string) $other->id],
        'deps' => [],
    ])->assertOk()->json('options');

    expect($options[0])->toHaveKey('display')
        ->and($options[0]['label'])->toBe('own')
        ->and($options[1])->toBe(['label' => (string) $other->id, 'value' => (string) $other->id]);
});
