export interface GalleryOption {
	value: string;
	label: string;
	/** Core strips unknown option keys; imagery rides in the allowlisted bag. */
	display: { image: string; subtitle?: string; mime?: string };
}

export interface GalleryOptions {
	endpoint?: string;
	uploadEndpoint?: string;
	multiple?: boolean;
}

/** What the field holds when something is picked; core adds the null itself. */
export type GalleryPick = string | string[];

export type GalleryValue = GalleryPick | null;

/** Which tile the modal is editing: the whole set, or one position in it. */
export type ModalTarget = { kind: "set" } | { kind: "replace"; index: number };

export function isOption(row: unknown): row is GalleryOption {
	if (typeof row !== "object" || row === null) return false;
	if (!("value" in row) || !("label" in row) || !("display" in row)) return false;
	if (typeof row.value !== "string" || typeof row.label !== "string") return false;
	const display = row.display;
	return (
		typeof display === "object" &&
		display !== null &&
		"image" in display &&
		typeof display.image === "string"
	);
}

export function readOptions(payload: unknown): GalleryOption[] {
	if (typeof payload !== "object" || payload === null || !("options" in payload)) return [];
	const rows = payload.options;
	return Array.isArray(rows) ? rows.filter(isOption) : [];
}

/** The upload endpoint answers {option}; anything else is a failure. */
export function readUploadedOption(payload: unknown): GalleryOption | null {
	if (typeof payload !== "object" || payload === null || !("option" in payload)) return null;
	return isOption(payload.option) ? payload.option : null;
}

export function toIds(value: GalleryValue): string[] {
	if (value === null) return [];
	return Array.isArray(value) ? value.map(String) : [String(value)];
}
