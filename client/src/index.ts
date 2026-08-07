import { defineFieldClient } from "@tbtop/inertia-admin";
import { GalleryCell, GalleryForm } from "./galleryField";

export { GalleryCell, GalleryForm } from "./galleryField";
export type { GalleryOption, GalleryOptions, GalleryPick, GalleryValue } from "./types";

export const GALLERY_KIND = "imageGallery";

/**
 * Registers the client half. Options come from ImageGalleryField, and both
 * endpoints are derived from the page basePath here — the PHP side never
 * serializes a URL, so a page only declares forCollection().
 */
export function registerImageGalleryField(): void {
	defineFieldClient(GALLERY_KIND, {
		form: GalleryForm,
		cell: GalleryCell,
		materialize: (node, basePath) => {
			const options = node.options !== null && typeof node.options === "object" ? node.options : {};
			return {
				...node,
				options: {
					...options,
					endpoint: `${basePath}/select-options/${node.name}`,
					uploadEndpoint: `${basePath}/gallery-upload/${node.name}`,
				},
			};
		},
	});
}
