import { useClient } from "@tbtop/inertia-admin";
import { useCallback, useEffect, useState } from "react";
import { type GalleryOption, readOptions } from "./types";

const DEBOUNCE_MS = 200;

interface GalleryRows {
	rows: GalleryOption[];
	loading: boolean;
	error: string | null;
	refetch: () => void;
}

/** Loads collection rows from the field's own endpoint, debounced on search. */
export function useGalleryRows(endpoint: string, search: string, active: boolean): GalleryRows {
	const client = useClient();
	const [rows, setRows] = useState<GalleryOption[]>([]);
	const [error, setError] = useState<string | null>(null);
	const [loading, setLoading] = useState(false);
	const [tick, setTick] = useState(0);
	const refetch = useCallback(() => setTick((t) => t + 1), []);

	useEffect(() => {
		if (!active || endpoint === "") return;
		let alive = true;
		setLoading(true);
		const timer = setTimeout(() => {
			client
				.post(endpoint, { search, deps: {} })
				.then((payload) => {
					if (!alive) return;
					setRows(readOptions(payload));
					setError(null);
				})
				.catch((e: unknown) => {
					if (!alive) return;
					setError(e instanceof Error ? e.message : "Failed to load images");
				})
				.finally(() => {
					if (alive) setLoading(false);
				});
		}, DEBOUNCE_MS);
		return () => {
			alive = false;
			clearTimeout(timer);
		};
	}, [client, endpoint, search, active, tick]);

	return { rows, loading, error, refetch };
}
