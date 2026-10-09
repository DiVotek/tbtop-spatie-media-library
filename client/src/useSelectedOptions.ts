import { useClient } from "@tbtop/inertia-admin";
import { useEffect, useState } from "react";
import { type GalleryOption, readResolved } from "./types";

/** What a tile knows about its id: still loading, resolved, gone, or unknown after a failed request. */
export type TileSource = GalleryOption | "loading" | "missing" | "unresolved";

interface SelectedOptions {
	sourceOf: (id: string) => TileSource;
	error: string | null;
}

/**
 * Resolves the selected ids through core's `values` branch of the options
 * endpoint, because the browse rows are capped at per_page and miss anything
 * past it. Earlier answers are kept, so a tile does not flash while the next
 * selection loads.
 */
export function useSelectedOptions(endpoint: string, ids: string[]): SelectedOptions {
	const client = useClient();
	const [known, setKnown] = useState<Map<string, GalleryOption | "missing">>(() => new Map());
	const [error, setError] = useState<string | null>(null);
	const key = ids.join(",");

	useEffect(() => {
		const values = key === "" ? [] : key.split(",");
		if (endpoint === "" || values.length === 0) return;
		let alive = true;
		client
			.post(endpoint, { values, deps: {} })
			.then((payload) => {
				if (!alive) return;
				const resolved = readResolved(payload);
				setKnown((prev) => new Map([...prev, ...resolved]));
				setError(null);
			})
			.catch(() => {
				if (alive) setError("Could not load images.");
			});
		return () => {
			alive = false;
		};
	}, [client, endpoint, key]);

	const sourceOf = (id: string): TileSource => known.get(id) ?? (error === null ? "loading" : "unresolved");

	return { sourceOf, error };
}
