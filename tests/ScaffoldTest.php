<?php

use Tbtop\Admin\Dsl\S;

it('boots with the core admin package available', function (): void {
    expect(class_exists(S::class))->toBeTrue()
        ->and(config('tbtop-image-gallery.per_page'))->toBe(24);
});
