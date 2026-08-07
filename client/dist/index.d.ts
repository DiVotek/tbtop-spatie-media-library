import * as react from 'react';
import { FieldCellProps, FieldFormProps } from '@tbtop/inertia-admin';

interface GalleryOption {
    value: string;
    label: string;
    /** Core strips unknown option keys; imagery rides in the allowlisted bag. */
    display: {
        image: string;
        subtitle?: string;
        mime?: string;
    };
}
interface GalleryOptions {
    endpoint?: string;
    uploadEndpoint?: string;
    multiple?: boolean;
}
/** What the field holds when something is picked; core adds the null itself. */
type GalleryPick = string | string[];
type GalleryValue = GalleryPick | null;

/**
 * Both modes render the same 96px tiles: selected images followed by a "+"
 * tile while the selection cap allows more (single caps at one). Clicking "+"
 * edits the whole set; clicking an existing tile replaces just that one.
 */
declare function GalleryForm({ name, value, onChange, options, disabled, }: FieldFormProps<GalleryPick, GalleryOptions>): react.JSX.Element;
declare function GalleryCell({ value }: FieldCellProps<GalleryPick, GalleryOptions>): react.JSX.Element;

declare const GALLERY_KIND = "imageGallery";
/**
 * Registers the client half. Options come from MediaLibraryField, and both
 * endpoints are derived from the page basePath here — the PHP side never
 * serializes a URL, so a page only declares forCollection().
 */
declare function registerMediaLibraryField(): void;

export { GALLERY_KIND, GalleryCell, GalleryForm, type GalleryOption, type GalleryOptions, type GalleryPick, type GalleryValue, registerMediaLibraryField };
