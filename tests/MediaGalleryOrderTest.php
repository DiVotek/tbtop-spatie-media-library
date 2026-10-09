<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tbtop\SpatieMediaLibrary\Support\MediaGalleryOptions;
use Tbtop\SpatieMediaLibrary\Tests\Fixtures\Product;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
});

function attach(Product $product, string $name, string $collection = 'photos'): string
{
    return (string) $product->addMedia(UploadedFile::fake()->image("{$name}.png"))
        ->usingName($name)->toMediaCollection($collection)->getKey();
}

function names(Product $product, string $collection = 'photos'): array
{
    return $product->fresh()->getMedia($collection)->pluck('name')->all();
}

it('orders the collection as passed, keeping unselected media after it in their old order', function (): void {
    $product = Product::create(['name' => 'Chair']);
    $a = attach($product, 'a');
    attach($product, 'b');
    $c = attach($product, 'c');
    attach($product, 'd');

    MediaGalleryOptions::saveOrder($product, 'photos', [$c, $a]);

    expect(names($product))->toBe(['c', 'a', 'b', 'd']);
});

it('leaves other records and collections untouched when ordering', function (): void {
    $product = Product::create(['name' => 'Chair']);
    $other = Product::create(['name' => 'Other']);
    $a = attach($product, 'a');
    $b = attach($product, 'b');
    $foreign = attach($other, 'x');
    attach($other, 'y');
    attach($product, 'p', 'pngonly');

    MediaGalleryOptions::saveOrder($product, 'photos', [$b, $foreign, $a]);

    expect(names($product))->toBe(['b', 'a'])
        ->and(names($other))->toBe(['x', 'y'])
        ->and(names($product, 'pngonly'))->toBe(['p']);
});

it('deletes exactly the collection media that is not selected', function (): void {
    $product = Product::create(['name' => 'Chair']);
    $other = Product::create(['name' => 'Other']);
    $a = attach($product, 'a');
    attach($product, 'b');
    attach($product, 'p', 'pngonly');
    attach($other, 'x');

    MediaGalleryOptions::pruneUnselected($product, 'photos', [$a]);

    expect(names($product))->toBe(['a'])
        ->and(names($product, 'pngonly'))->toBe(['p'])
        ->and(names($other))->toBe(['x'])
        ->and(Media::count())->toBe(3);
});
