<?php

namespace Tbtop\ImageGallery\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tbtop\Admin\Http\AuthorizesPage;
use Tbtop\Admin\Http\ResolvedPage;
use Tbtop\Admin\Media\MediaUploadLimit;
use Tbtop\ImageGallery\Fields\ImageGalleryField;
use Tbtop\ImageGallery\Support\MediaGalleryOptions;
use Tbtop\ImageGallery\Support\MediaGalleryStorer;
use Tbtop\ImageGallery\Support\MimeGuard;
use Tbtop\ImageGallery\Support\UrlImporter;

/**
 * POST {page-path}/gallery-upload/{tbtopField}
 *
 * Two inputs, one destination: a multipart `file`, or a `url` the server
 * downloads. The target record and collection are read off the field on the
 * re-resolved page — never from the request — so a caller cannot redirect an
 * upload into another model. Inherits the page gate via AuthorizesPage.
 */
final class GalleryUploadController
{
    use AuthorizesPage;

    public function __invoke(Request $request): JsonResponse
    {
        $this->authorizePageGate($request);

        $field = $this->resolveField($request);
        $target = $field->target();

        if ($target === null) {
            throw new NotFoundHttpException('This gallery field is not bound to a record.');
        }

        try {
            [$file, $isTemp] = $this->readFile($request);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        try {
            $media = MediaGalleryStorer::store($file, $target, $field->collection());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } finally {
            if ($isTemp) {
                @unlink($file->getPathname());
            }
        }

        return response()->json(['option' => MediaGalleryOptions::toOption($media)], 201);
    }

    private function resolveField(Request $request): ImageGalleryField
    {
        $fieldName = (string) $request->route('tbtopField');
        $field = ResolvedPage::fromRequest($request)->s->findQueryableSelect($fieldName);

        if (! $field instanceof ImageGalleryField) {
            throw new NotFoundHttpException("Gallery field \"{$fieldName}\" is not defined on this page.");
        }

        return $field;
    }

    /**
     * @return array{0: UploadedFile, 1: bool} the file and whether it is a
     *                                         temp download this controller must clean up
     */
    private function readFile(Request $request): array
    {
        if ($request->filled('url')) {
            if (config('tbtop-image-gallery.url_import.enabled') !== true) {
                throw new RuntimeException('URL import is disabled.');
            }
            $request->validate(['url' => 'required|url']);

            return [UrlImporter::fetch((string) $request->input('url')), true];
        }

        $request->validate(['file' => 'required|file|max:'.MediaUploadLimit::kilobytes()]);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        if (! MimeGuard::allows((string) $file->getMimeType())) {
            throw new RuntimeException('That file type is not allowed.');
        }

        return [$file, false];
    }
}
