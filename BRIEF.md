# Brief — spatie gallery field (spike)

Implement `tbtop/spatie-media-library`: a **PHP-only** facade over
`spatie/laravel-medialibrary` that gives a tbtop admin form an image field backed
by a model's media collection. The scaffold, the gates and the core-side seams
are already in place — this brief is the implementation scope on top of them.

This is a **spike to test an idea**, not a product. It is not published, not
versioned, and may be thrown away. Prefer the smallest thing that proves the
concept.

## Hard boundaries

- **Never edit `../tbtop`.** The core is linked by a composer `path` repository
  and already carries two deliberate local modifications (see below). If
  something in core looks wrong or missing, **stop and report it** — do not fix it.
- **No npm package, no client build.** The React component is a separate,
  later step and is not part of this task.
- **No new HTTP endpoint.** Option fetching goes through core's existing
  `SelectOptionsController`. If you find yourself writing a controller or a
  route, you have taken a wrong turn — re-read "How the field works".
- `routes/gallery.php` stays empty. The provider needs the file to boot.

## Core modifications already made (uncommitted, on purpose)

Both live in the working tree of `../tbtop` and must **not** be committed by you:

1. `packages/php/src/Dsl/Fields/Select.php` — `final` removed so the field can
   subclass `Select`.
2. `packages/php/src/Http/SelectOptionsController.php` — `rowOption()` now
   passes extra row keys through instead of dropping everything but
   `value`/`label`. This is how a preview url reaches the client.

Verified: core is green with both (1063 Pest tests, phpstan L5, pint).

## How the field works

The field is a **subclass of `Select`** with its own wire kind. This was proven
against the real seam before writing this brief:

- `S::findQueryableSelect($name)` matches on `$field instanceof Select` plus a
  non-null `queryClosure()` — a subclass passes, and the wire `kind` is
  irrelevant to the match.
- Therefore async search, per-request limit, resolve-by-value and multi-select
  all come from core for free.

So the field must:

- extend `Select`, override `kind()` to return `imageGallery`;
- register a `query(fn (array $deps, string $search): array)` closure that reads
  the model's spatie collection, applies `$search` to the file name, and caps the
  page size at `config('tbtop-spatie-media-library.per_page')`;
- return rows shaped `['value' => (string) $media->id, 'label' => $media->name,
  'url' => $media->getUrl(), 'mime' => $media->mime_type]` — `value` and `label`
  are required by core, the rest rides along thanks to core modification (2).

## Persistence

Uploads reuse core rather than reimplementing storage:

- `Tbtop\Admin\Uploads\UploadStorer::store()` and
  `Tbtop\Admin\Uploads\ImageEncoder` handle conversion (`webp|jpeg|png`).
- The facade wires an `Upload`-style save path that ends in
  `$model->addMedia(...)->toMediaCollection($collection)`.
- The collection name is whatever the consumer passed; never fall back to
  `default` silently.
- Conversion defaults come from `config('tbtop-spatie-media-library.conversion')` and
  a per-field override wins over the config.

## Tests (exactly these four, Pest)

Each was agreed with a behavior rule and the mutation it catches. Do not add
others without asking; do not add tests for fluent setters (pass-through) or for
markup.

| # | Rule | Mutation it must catch |
|---|---|---|
| 1 | The query closure serialises collection items as `{value, label, url, mime}` | Returning spatie models raw, so the wire carries `file_name` instead of the contract |
| 2 | Saving puts the file in the **named** collection | Collection name ignored, everything lands in `default` |
| 3 | An empty collection yields `[]`, not an error or `null` | `null` returned, client crashes on `.map` |
| 4 | A declared conversion is applied: png in, webp on disk and in the returned url | Conversion declared but the original is stored |

Test 1 must assert through `findQueryableSelect()` + the closure's return value —
that is the seam a real request uses. Do not assert builder internals.

Use `Storage::fake()` for 2 and 4. Tests must be deterministic and
order-independent (`executionOrder="random"` is on).

`tests/ScaffoldTest.php` is a placeholder that exists only because
`failOnEmptyTestSuite` is enabled — delete it once test 1 lands.

## Gates (all currently green — keep them that way)

```
composer test      # pest
composer analyse   # phpstan level 5, empty baseline
composer format    # pint
```

phpstan's baseline is empty by design. Do not add entries to it; fix the code.

## Style

- File ≤200 lines, function ≤50, guard clauses first, ≤2 nesting levels.
- Comments only for constraints the code cannot state. No `@param`/`@return`
  blocks that restate declared types.
- Parse data crossing a boundary, don't cast it.
- English only in code, comments and commit messages.
- Commits: `type(scope): subject`, scope `gallery`.

## Reporting

State plainly what is done, what is not, and what you could not verify. Paste
real command output for the gates. If a test cannot be written at the right
seam, say so and explain why — do not move it to a weaker seam to make it pass.
