import { FileIcon, PlusIcon, XIcon } from "lucide-react";
import type { GalleryOption } from "./types";

// block on the button: an inline-level button adds a baseline gap under the
// image, making the filled tile taller than the empty one.
const TILE = "block h-24 w-24 overflow-hidden rounded-md border transition-colors";

/**
 * A collection can hold documents, not only images. Rendering those through
 * <img> yields a broken-image glyph, so anything non-image gets an icon plus
 * its extension.
 */
export function OptionPreview({ item, className }: { item: GalleryOption; className: string }) {
	const mime = item.display.mime ?? "";
	if (mime === "" || mime.startsWith("image/")) {
		return <img src={item.display.image} alt={item.label} className={className} />;
	}
	return (
		<span
			className={`${className} flex flex-col items-center justify-center gap-1 bg-muted text-muted-foreground`}
		>
			<FileIcon className="h-6 w-6" />
			<span className="text-[10px] font-medium uppercase">{extensionOf(item.label, mime)}</span>
		</span>
	);
}

function extensionOf(label: string, mime: string): string {
	const dot = label.lastIndexOf(".");
	if (dot > 0 && dot < label.length - 1) {
		return label.slice(dot + 1);
	}
	const slash = mime.indexOf("/");
	return slash === -1 ? "file" : mime.slice(slash + 1);
}

export function ImageTile({
	item,
	onOpen,
	onRemove,
	disabled,
}: {
	item: GalleryOption;
	onOpen: () => void;
	onRemove: () => void;
	disabled?: boolean;
}) {
	return (
		<div className="relative w-fit">
			<button
				type="button"
				onClick={onOpen}
				disabled={disabled}
				title={`${item.label} — click to replace`}
				className={`${TILE} bg-muted hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50`}
			>
				<OptionPreview item={item} className="h-full w-full object-cover" />
			</button>
			{!disabled && (
				<button
					type="button"
					onClick={onRemove}
					aria-label={`Remove ${item.label}`}
					className="absolute right-0.5 top-0.5 rounded-full bg-background/85 p-0.5 text-foreground shadow-sm hover:bg-background"
				>
					<XIcon className="h-3.5 w-3.5" />
				</button>
			)}
		</div>
	);
}

export function AddTile({ onOpen, disabled }: { onOpen: () => void; disabled?: boolean }) {
	return (
		<div className="relative w-fit">
			<button
				type="button"
				onClick={onOpen}
				disabled={disabled}
				aria-label="Choose image"
				title="Choose image"
				className={`${TILE} flex items-center justify-center border-dashed text-muted-foreground hover:bg-muted/50 disabled:cursor-not-allowed disabled:opacity-50`}
			>
				<PlusIcon className="h-6 w-6" />
			</button>
		</div>
	);
}
