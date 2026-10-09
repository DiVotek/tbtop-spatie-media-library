import {
	Button,
	type FieldCellProps,
	type FieldFormProps,
	Input,
	ModalShell,
} from "@tbtop/inertia-admin";
import { type KeyboardEvent as ReactKeyboardEvent, useCallback, useMemo, useState } from "react";
import { AddTile, ImageTile, OptionPreview, PlaceholderTile } from "./tiles";
import type { GalleryOption, GalleryOptions, GalleryPick, ModalTarget } from "./types";
import { toIds, uniqueIds } from "./types";
import { UploadBar } from "./uploadBar";
import { useGalleryRows } from "./useGalleryRows";
import { useSelectedOptions } from "./useSelectedOptions";

/**
 * Both modes render the same 96px tiles: selected images followed by a "+"
 * tile while the selection cap allows more (single caps at one). Clicking "+"
 * edits the whole set; clicking an existing tile replaces just that one.
 */
export function GalleryForm({
	name,
	value,
	onChange,
	options,
	disabled,
}: FieldFormProps<GalleryPick, GalleryOptions>) {
	const endpoint = options?.endpoint ?? "";
	const uploadEndpoint = options?.uploadEndpoint ?? "";
	const multiple = options?.multiple === true;

	const [target, setTarget] = useState<ModalTarget | null>(null);
	const [search, setSearch] = useState("");
	const [draft, setDraft] = useState<string[]>([]);
	const [uploading, setUploading] = useState(false);
	const [uploaded, setUploaded] = useState<GalleryOption[]>([]);

	const ids = useMemo(() => toIds(value), [value]);
	const open = target !== null;

	const { rows, loading, error, refetch } = useGalleryRows(endpoint, search, open);
	// Tiles resolve by id, not from the browse rows: those stop at per_page.
	const selected = useSelectedOptions(endpoint, ids);

	// Uploads from this dialog session stay on top: the refetch that follows an
	// upload returns the first per_page rows, which a new image is not among.
	const visibleRows = useMemo(() => {
		const needle = search.toLowerCase();
		const pinned = uploaded.filter((o) => o.label.toLowerCase().includes(needle));
		const pinnedIds = new Set(pinned.map((o) => o.value));
		return [...pinned, ...rows.filter((r) => !pinnedIds.has(r.value))];
	}, [uploaded, rows, search]);

	// Replacing one tile is a single-pick interaction even in multiple mode.
	const picksOne = !multiple || target?.kind === "replace";

	const openFor = useCallback(
		(next: ModalTarget) => {
			setSearch("");
			setUploaded([]);
			setDraft(next.kind === "replace" ? [ids[next.index] ?? ""].filter(Boolean) : ids);
			setTarget(next);
		},
		[ids],
	);

	// Duplicates would let one image occupy two tiles; the first position wins.
	const emit = (picked: string[]) => {
		const next = uniqueIds(picked);
		if (next.length === 0) {
			onChange(null);
			return;
		}
		onChange(multiple ? next : (next[0] ?? null));
	};

	const toggleDraft = (id: string) => {
		if (picksOne) {
			setDraft([id]);
			return;
		}
		setDraft((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
	};

	// A freshly uploaded image is what the user came for: pre-select it so the
	// only remaining step is confirming.
	const handleUploaded = (option: GalleryOption) => {
		setSearch("");
		setUploaded((prev) => [option, ...prev.filter((o) => o.value !== option.value)]);
		refetch();
		setDraft((prev) => (picksOne ? [option.value] : [...prev, option.value]));
	};

	const confirm = () => {
		if (target?.kind === "replace") {
			const picked = draft[0];
			if (picked !== undefined) {
				const next = [...ids];
				next[target.index] = picked;
				emit(next);
			}
		} else {
			emit(draft);
		}
		setTarget(null);
	};

	const removeAt = (index: number) => emit(ids.filter((_, i) => i !== index));

	// Enter confirms the dialog, except while a tile has focus — there Enter is
	// the button's own activation, and swallowing it would break multi-select.
	const handleKeyDown = (e: ReactKeyboardEvent<HTMLDivElement>) => {
		if (e.key !== "Enter" || e.defaultPrevented) return;
		if (e.target instanceof HTMLButtonElement) return;
		e.preventDefault();
		confirm();
	};

	const canAddMore = multiple || ids.length === 0;

	return (
		<div className="flex flex-col gap-2" data-testid={`gallery-picker-${name}`}>
			<div className="flex flex-wrap gap-2">
				{ids.map((id, index) => {
					const source = selected.sourceOf(id);
					const actions = {
						disabled,
						onOpen: () => openFor({ kind: "replace", index }),
						onRemove: () => removeAt(index),
					};
					return typeof source === "string" ? (
						<PlaceholderTile key={`${id}-${index}`} id={id} kind={source} {...actions} />
					) : (
						<ImageTile key={`${id}-${index}`} item={source} {...actions} />
					);
				})}
				{canAddMore && <AddTile onOpen={() => openFor({ kind: "set" })} disabled={disabled} />}
			</div>

			{selected.error !== null && <p className="text-sm text-destructive">{selected.error}</p>}

			{multiple && ids.length > 0 && (
				<span className="text-xs text-muted-foreground">{ids.length} selected</span>
			)}

			<ModalShell
				open={open}
				onOpenChange={(next) => !next && setTarget(null)}
				title={target?.kind === "replace" ? "Replace image" : "Choose images"}
				description="Images from the model's spatie collection."
				size="lg"
				footer={
					<div className="flex justify-end gap-2">
						<Button type="button" variant="ghost" size="sm" onClick={() => setTarget(null)}>
							Cancel
						</Button>
						<Button type="button" size="sm" onClick={confirm} data-testid={`gallery-confirm-${name}`}>
							Select
						</Button>
					</div>
				}
			>
				{/* biome-ignore lint/a11y/noStaticElementInteractions: keyboard-only
				    shortcut on a dialog body; every control inside stays focusable. */}
				<div className="flex flex-col gap-3" onKeyDown={handleKeyDown}>
					{uploadEndpoint !== "" && (
						<UploadBar
							endpoint={uploadEndpoint}
							busy={uploading}
							onBusyChange={setUploading}
							onUploaded={handleUploaded}
						/>
					)}

					<Input
						type="search"
						value={search}
						placeholder="Search images…"
						onChange={(e) => setSearch(e.target.value)}
						data-testid={`gallery-search-${name}`}
					/>

					{error !== null && <p className="text-sm text-destructive">{error}</p>}
					{error === null && visibleRows.length === 0 && !loading && !uploading && (
						<p className="text-sm text-muted-foreground">No images in this collection.</p>
					)}

					<div className="grid max-h-[50vh] grid-cols-2 gap-3 overflow-y-auto sm:grid-cols-4">
						{visibleRows.map((row) => {
							const isSelected = draft.includes(row.value);
							return (
								<button
									type="button"
									key={row.value}
									onClick={() => toggleDraft(row.value)}
									aria-pressed={isSelected}
									className={`overflow-hidden rounded-lg border-2 text-left transition ${
										isSelected
											? "border-primary ring-2 ring-primary/30"
											: "border-transparent hover:border-muted-foreground/30"
									}`}
								>
									<OptionPreview item={row} className="aspect-square w-full bg-muted object-cover" />
									<span className="block truncate px-2 py-1.5 text-xs text-muted-foreground">
										{row.label}
									</span>
								</button>
							);
						})}
					</div>
				</div>
			</ModalShell>
		</div>
	);
}

export function GalleryCell({ value }: FieldCellProps<GalleryPick, GalleryOptions>) {
	const count = toIds(value).length;
	return <span>{count === 0 ? "–" : `${count} image${count === 1 ? "" : "s"}`}</span>;
}
