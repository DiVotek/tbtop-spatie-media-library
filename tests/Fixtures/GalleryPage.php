<?php

namespace Tbtop\SpatieMediaLibrary\Tests\Fixtures;

use Tbtop\Admin\Dsl\Node;
use Tbtop\Admin\Dsl\S;
use Tbtop\Admin\Pages\Page;

/** Binds one imageGallery per collection on the first Product, the way a record edit page would. */
abstract class GalleryPage extends Page
{
    public function view(S $s): Node
    {
        $product = Product::query()->firstOrFail();

        return $s->form('product', [
            $s->imageGallery('photos')->forCollection($product, 'photos')->multiple(),
            $s->imageGallery('single')->forCollection($product, 'single'),
            $s->imageGallery('pngonly')->forCollection($product, 'pngonly'),
            $s->imageGallery('jpegonly')->forCollection($product, 'jpegonly'),
        ])->onSubmit(fn () => null)->toNode();
    }
}
