import { Button, Input, useClient } from "@tbtop/inertia-admin";
import { UploadIcon } from "lucide-react";
import { useRef, useState } from "react";
import { type GalleryOption, readUploadedOption } from "./types";

function errorMessage(e: unknown, fallback: string): string {
	if (e instanceof Error && e.message !== "") return e.message;
	return fallback;
}

/** Upload controls in the modal header: a file picker and a URL import. */
export function UploadBar({
	endpoint,
	busy,
	onUploaded,
	onBusyChange,
}: {
	endpoint: string;
	busy: boolean;
	onUploaded: (option: GalleryOption) => void;
	onBusyChange: (busy: boolean) => void;
}) {
	const client = useClient();
	const fileRef = useRef<HTMLInputElement>(null);
	const [url, setUrl] = useState("");
	const [error, setError] = useState<string | null>(null);

	const send = (body: FormData | { url: string }, fallback: string) => {
		onBusyChange(true);
		setError(null);
		const req =
			body instanceof FormData ? client.upload(endpoint, body) : client.post(endpoint, body);
		req
			.then((payload) => {
				const option = readUploadedOption(payload);
				if (option === null) {
					setError(fallback);
					return;
				}
				setUrl("");
				onUploaded(option);
			})
			.catch((e: unknown) => setError(errorMessage(e, fallback)))
			.finally(() => onBusyChange(false));
	};

	const pickFile = (file: File) => {
		const fd = new FormData();
		fd.append("file", file);
		send(fd, "Upload failed.");
	};

	return (
		<div className="flex flex-col gap-2">
			<div className="flex items-center gap-2">
				<Button
					type="button"
					variant="outline"
					size="sm"
					disabled={busy}
					onClick={() => fileRef.current?.click()}
					data-testid="gallery-upload-button"
				>
					<UploadIcon className="mr-1.5 h-4 w-4" />
					Upload
				</Button>
				<input
					ref={fileRef}
					type="file"
					accept="image/*"
					className="hidden"
					onChange={(e) => {
						const file = e.target.files?.[0];
						if (file) pickFile(file);
						e.target.value = "";
					}}
				/>
				<Input
					type="url"
					value={url}
					placeholder="…or paste an image URL"
					disabled={busy}
					onChange={(e) => setUrl(e.target.value)}
					onKeyDown={(e) => {
						if (e.key !== "Enter" || url.trim() === "") return;
						// Stop the dialog's Enter-confirms shortcut: here Enter imports.
						e.preventDefault();
						e.stopPropagation();
						send({ url: url.trim() }, "Could not import that URL.");
					}}
					data-testid="gallery-url-input"
				/>
				<Button
					type="button"
					size="sm"
					variant="secondary"
					disabled={busy || url.trim() === ""}
					onClick={() => send({ url: url.trim() }, "Could not import that URL.")}
					data-testid="gallery-import-button"
				>
					Import
				</Button>
			</div>
			{error !== null && <p className="text-sm text-destructive">{error}</p>}
		</div>
	);
}
