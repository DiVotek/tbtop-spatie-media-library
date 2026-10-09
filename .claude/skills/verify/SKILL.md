---
name: verify
description: Run the gallery field in a real admin to check a change in the browser — host recipe on core's demo app.
---

# Verify the gallery field live

No host app ships with this repo. Use core's demo in a throwaway worktree.

1. `git -C ../tbtop worktree add --detach /tmp/tbtop-verify origin/main`
2. `apps/demo`: `cp .env.example .env`, `touch database/database.sqlite`; add a path repository
   to this checkout (`symlink: true`) and `composer require -W spatie/laravel-medialibrary:^11 tbtop/spatie-media-library:@dev`.
3. JS: `bun install` in the worktree root, in `packages/client` (then `bun run build`) and in `apps/demo`.
   Copy this repo's `client/package.json` + `client/dist` into `apps/demo/node_modules/@tbtop/spatie-media-library/`
   (the demo's Vite alias already maps `@tbtop/inertia-admin` to core src, so there is one React).
   Register in `resources/js/admin.tsx` (`registerMediaLibraryField()`), add
   `@source '../../node_modules/@tbtop/spatie-media-library/dist';` to `resources/css/app.css`.
4. **Gotcha — `media` table clash:** the demo's `media` table is core's media library. Copy spatie's
   `create_media_table.php.stub` with the table renamed to `spatie_media`, add a `Media` subclass with
   `$table = 'spatie_media'`, publish `medialibrary-config` and point `media_model` at it.
5. A `HasMedia` model + a page in `app/Admin/Pages/` (auto-discovered; `view()` must return
   `->toNode()`). Publish `tbtop-spatie-media-library-config`, set `per_page` to 2 to exercise hidden ids.
6. `php artisan key:generate && php artisan migrate:fresh --seed && php artisan storage:link && bun run build`,
   `php artisan serve --port=8765`. Seed login: `admin@admin.com` / `password` (DatabaseSeeder).

Driving: Chrome extension file uploads only accept files inside this checkout — put fixtures in a
temporary dot-dir here and delete it after. Pest browser in the demo cannot attach files.
Inertia's error page and the login unsaved-guard block navigation; navigate with force.
To fail the `values` request, patch `fetch`/`XMLHttpRequest.send` in the page for bodies containing `"values"`.
