import { ModalShell, Input, Button, defineFieldClient, useClient } from '@tbtop/inertia-admin';
import { useState, useMemo, useCallback, useEffect, useRef } from 'react';
import { XIcon, PlusIcon, UploadIcon, FileIcon } from 'lucide-react';
import { jsxs, jsx } from 'react/jsx-runtime';

// src/index.ts
var TILE = "block h-24 w-24 overflow-hidden rounded-md border transition-colors";
function OptionPreview({ item, className }) {
  const mime = item.display.mime ?? "";
  if (mime === "" || mime.startsWith("image/")) {
    return /* @__PURE__ */ jsx("img", { src: item.display.image, alt: item.label, className });
  }
  return /* @__PURE__ */ jsxs(
    "span",
    {
      className: `${className} flex flex-col items-center justify-center gap-1 bg-muted text-muted-foreground`,
      children: [
        /* @__PURE__ */ jsx(FileIcon, { className: "h-6 w-6" }),
        /* @__PURE__ */ jsx("span", { className: "text-[10px] font-medium uppercase", children: extensionOf(item.label, mime) })
      ]
    }
  );
}
function extensionOf(label, mime) {
  const dot = label.lastIndexOf(".");
  if (dot > 0 && dot < label.length - 1) {
    return label.slice(dot + 1);
  }
  const slash = mime.indexOf("/");
  return slash === -1 ? "file" : mime.slice(slash + 1);
}
function ImageTile({
  item,
  onOpen,
  onRemove,
  disabled
}) {
  return /* @__PURE__ */ jsxs("div", { className: "relative w-fit", children: [
    /* @__PURE__ */ jsx(
      "button",
      {
        type: "button",
        onClick: onOpen,
        disabled,
        title: `${item.label} \u2014 click to replace`,
        className: `${TILE} bg-muted hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50`,
        children: /* @__PURE__ */ jsx(OptionPreview, { item, className: "h-full w-full object-cover" })
      }
    ),
    !disabled && /* @__PURE__ */ jsx(
      "button",
      {
        type: "button",
        onClick: onRemove,
        "aria-label": `Remove ${item.label}`,
        className: "absolute right-0.5 top-0.5 rounded-full bg-background/85 p-0.5 text-foreground shadow-sm hover:bg-background",
        children: /* @__PURE__ */ jsx(XIcon, { className: "h-3.5 w-3.5" })
      }
    )
  ] });
}
function AddTile({ onOpen, disabled }) {
  return /* @__PURE__ */ jsx("div", { className: "relative w-fit", children: /* @__PURE__ */ jsx(
    "button",
    {
      type: "button",
      onClick: onOpen,
      disabled,
      "aria-label": "Choose image",
      title: "Choose image",
      className: `${TILE} flex items-center justify-center border-dashed text-muted-foreground hover:bg-muted/50 disabled:cursor-not-allowed disabled:opacity-50`,
      children: /* @__PURE__ */ jsx(PlusIcon, { className: "h-6 w-6" })
    }
  ) });
}

// src/types.ts
function isOption(row) {
  if (typeof row !== "object" || row === null) return false;
  if (!("value" in row) || !("label" in row) || !("display" in row)) return false;
  if (typeof row.value !== "string" || typeof row.label !== "string") return false;
  const display = row.display;
  return typeof display === "object" && display !== null && "image" in display && typeof display.image === "string";
}
function readOptions(payload) {
  if (typeof payload !== "object" || payload === null || !("options" in payload)) return [];
  const rows = payload.options;
  return Array.isArray(rows) ? rows.filter(isOption) : [];
}
function readUploadedOption(payload) {
  if (typeof payload !== "object" || payload === null || !("option" in payload)) return null;
  return isOption(payload.option) ? payload.option : null;
}
function toIds(value) {
  if (value === null) return [];
  return Array.isArray(value) ? value.map(String) : [String(value)];
}
function errorMessage(e, fallback) {
  if (e instanceof Error && e.message !== "") return e.message;
  return fallback;
}
function UploadBar({
  endpoint,
  busy,
  onUploaded,
  onBusyChange
}) {
  const client = useClient();
  const fileRef = useRef(null);
  const [url, setUrl] = useState("");
  const [error, setError] = useState(null);
  const send = (body, fallback) => {
    onBusyChange(true);
    setError(null);
    const req = body instanceof FormData ? client.upload(endpoint, body) : client.post(endpoint, body);
    req.then((payload) => {
      const option = readUploadedOption(payload);
      if (option === null) {
        setError(fallback);
        return;
      }
      setUrl("");
      onUploaded(option);
    }).catch((e) => setError(errorMessage(e, fallback))).finally(() => onBusyChange(false));
  };
  const pickFile = (file) => {
    const fd = new FormData();
    fd.append("file", file);
    send(fd, "Upload failed.");
  };
  return /* @__PURE__ */ jsxs("div", { className: "flex flex-col gap-2", children: [
    /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
      /* @__PURE__ */ jsxs(
        Button,
        {
          type: "button",
          variant: "outline",
          size: "sm",
          disabled: busy,
          onClick: () => fileRef.current?.click(),
          "data-testid": "gallery-upload-button",
          children: [
            /* @__PURE__ */ jsx(UploadIcon, { className: "mr-1.5 h-4 w-4" }),
            "Upload"
          ]
        }
      ),
      /* @__PURE__ */ jsx(
        "input",
        {
          ref: fileRef,
          type: "file",
          accept: "image/*",
          className: "hidden",
          onChange: (e) => {
            const file = e.target.files?.[0];
            if (file) pickFile(file);
            e.target.value = "";
          }
        }
      ),
      /* @__PURE__ */ jsx(
        Input,
        {
          type: "url",
          value: url,
          placeholder: "\u2026or paste an image URL",
          disabled: busy,
          onChange: (e) => setUrl(e.target.value),
          onKeyDown: (e) => {
            if (e.key !== "Enter" || url.trim() === "") return;
            e.preventDefault();
            e.stopPropagation();
            send({ url: url.trim() }, "Could not import that URL.");
          },
          "data-testid": "gallery-url-input"
        }
      ),
      /* @__PURE__ */ jsx(
        Button,
        {
          type: "button",
          size: "sm",
          variant: "secondary",
          disabled: busy || url.trim() === "",
          onClick: () => send({ url: url.trim() }, "Could not import that URL."),
          "data-testid": "gallery-import-button",
          children: "Import"
        }
      )
    ] }),
    error !== null && /* @__PURE__ */ jsx("p", { className: "text-sm text-destructive", children: error })
  ] });
}
var DEBOUNCE_MS = 200;
function useGalleryRows(endpoint, search, active) {
  const client = useClient();
  const [rows, setRows] = useState([]);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);
  const [tick, setTick] = useState(0);
  const refetch = useCallback(() => setTick((t) => t + 1), []);
  useEffect(() => {
    if (!active || endpoint === "") return;
    let alive = true;
    setLoading(true);
    const timer = setTimeout(() => {
      client.post(endpoint, { search, deps: {} }).then((payload) => {
        if (!alive) return;
        setRows(readOptions(payload));
        setError(null);
      }).catch((e) => {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load images");
      }).finally(() => {
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
function GalleryForm({
  name,
  value,
  onChange,
  options,
  disabled
}) {
  const endpoint = options?.endpoint ?? "";
  const uploadEndpoint = options?.uploadEndpoint ?? "";
  const multiple = options?.multiple === true;
  const [target, setTarget] = useState(null);
  const [search, setSearch] = useState("");
  const [draft, setDraft] = useState([]);
  const [uploading, setUploading] = useState(false);
  const ids = useMemo(() => toIds(value), [value]);
  const open = target !== null;
  const { rows, loading, error, refetch } = useGalleryRows(
    endpoint,
    open ? search : "",
    open || ids.length > 0
  );
  const byId = useMemo(() => new Map(rows.map((r) => [r.value, r])), [rows]);
  const selected = ids.map((id) => byId.get(id)).filter((r) => r !== void 0);
  const picksOne = !multiple || target?.kind === "replace";
  const openFor = useCallback(
    (next) => {
      setSearch("");
      setDraft(next.kind === "replace" ? [ids[next.index] ?? ""].filter(Boolean) : ids);
      setTarget(next);
    },
    [ids]
  );
  const emit = (next) => {
    if (next.length === 0) {
      onChange(null);
      return;
    }
    onChange(multiple ? next : next[0] ?? null);
  };
  const toggleDraft = (id) => {
    if (picksOne) {
      setDraft([id]);
      return;
    }
    setDraft((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
  };
  const handleUploaded = (option) => {
    setSearch("");
    refetch();
    setDraft((prev) => picksOne ? [option.value] : [...prev, option.value]);
  };
  const confirm = () => {
    if (target?.kind === "replace") {
      const picked = draft[0];
      if (picked !== void 0) {
        const next = [...ids];
        next[target.index] = picked;
        emit(next);
      }
    } else {
      emit(draft);
    }
    setTarget(null);
  };
  const removeAt = (index) => emit(ids.filter((_, i) => i !== index));
  const handleKeyDown = (e) => {
    if (e.key !== "Enter" || e.defaultPrevented) return;
    if (e.target instanceof HTMLButtonElement) return;
    e.preventDefault();
    confirm();
  };
  const canAddMore = multiple || ids.length === 0;
  return /* @__PURE__ */ jsxs("div", { className: "flex flex-col gap-2", "data-testid": `gallery-picker-${name}`, children: [
    /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap gap-2", children: [
      selected.map((item, index) => /* @__PURE__ */ jsx(
        ImageTile,
        {
          item,
          disabled,
          onOpen: () => openFor({ kind: "replace", index }),
          onRemove: () => removeAt(index)
        },
        `${item.value}-${index}`
      )),
      canAddMore && /* @__PURE__ */ jsx(AddTile, { onOpen: () => openFor({ kind: "set" }), disabled })
    ] }),
    multiple && selected.length > 0 && /* @__PURE__ */ jsxs("span", { className: "text-xs text-muted-foreground", children: [
      selected.length,
      " selected"
    ] }),
    /* @__PURE__ */ jsx(
      ModalShell,
      {
        open,
        onOpenChange: (next) => !next && setTarget(null),
        title: target?.kind === "replace" ? "Replace image" : "Choose images",
        description: "Images from the model's spatie collection.",
        size: "lg",
        footer: /* @__PURE__ */ jsxs("div", { className: "flex justify-end gap-2", children: [
          /* @__PURE__ */ jsx(Button, { type: "button", variant: "ghost", size: "sm", onClick: () => setTarget(null), children: "Cancel" }),
          /* @__PURE__ */ jsx(Button, { type: "button", size: "sm", onClick: confirm, "data-testid": `gallery-confirm-${name}`, children: "Select" })
        ] }),
        children: /* @__PURE__ */ jsxs("div", { className: "flex flex-col gap-3", onKeyDown: handleKeyDown, children: [
          uploadEndpoint !== "" && /* @__PURE__ */ jsx(
            UploadBar,
            {
              endpoint: uploadEndpoint,
              busy: uploading,
              onBusyChange: setUploading,
              onUploaded: handleUploaded
            }
          ),
          /* @__PURE__ */ jsx(
            Input,
            {
              type: "search",
              value: search,
              placeholder: "Search images\u2026",
              onChange: (e) => setSearch(e.target.value),
              "data-testid": `gallery-search-${name}`
            }
          ),
          error !== null && /* @__PURE__ */ jsx("p", { className: "text-sm text-destructive", children: error }),
          error === null && rows.length === 0 && !loading && !uploading && /* @__PURE__ */ jsx("p", { className: "text-sm text-muted-foreground", children: "No images in this collection." }),
          /* @__PURE__ */ jsx("div", { className: "grid max-h-[50vh] grid-cols-2 gap-3 overflow-y-auto sm:grid-cols-4", children: rows.map((row) => {
            const isSelected = draft.includes(row.value);
            return /* @__PURE__ */ jsxs(
              "button",
              {
                type: "button",
                onClick: () => toggleDraft(row.value),
                "aria-pressed": isSelected,
                className: `overflow-hidden rounded-lg border-2 text-left transition ${isSelected ? "border-primary ring-2 ring-primary/30" : "border-transparent hover:border-muted-foreground/30"}`,
                children: [
                  /* @__PURE__ */ jsx(OptionPreview, { item: row, className: "aspect-square w-full bg-muted object-cover" }),
                  /* @__PURE__ */ jsx("span", { className: "block truncate px-2 py-1.5 text-xs text-muted-foreground", children: row.label })
                ]
              },
              row.value
            );
          }) })
        ] })
      }
    )
  ] });
}
function GalleryCell({ value }) {
  const count = toIds(value).length;
  return /* @__PURE__ */ jsx("span", { children: count === 0 ? "\u2013" : `${count} image${count === 1 ? "" : "s"}` });
}

// src/index.ts
var GALLERY_KIND = "imageGallery";
function registerMediaLibraryField() {
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
          uploadEndpoint: `${basePath}/gallery-upload/${node.name}`
        }
      };
    }
  });
}

export { GALLERY_KIND, GalleryCell, GalleryForm, registerMediaLibraryField };
//# sourceMappingURL=index.js.map
//# sourceMappingURL=index.js.map