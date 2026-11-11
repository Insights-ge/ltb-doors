# Sliding door builder

## Local setup

Use the repository's normal Laravel setup with PHP 8.4+, Composer, Node and a local database configured in `.env`. For a fresh local database, `php artisan migrate --seed` installs settings, roles/permissions and the CSV-backed door/infill catalog through `DatabaseSeeder`. For an existing database, use the established catalog import/admin workflow instead of resetting it. `UserSeeder` defines the local sample accounts.

Run `php artisan serve` and `npm run dev` in separate terminals. Sign in at `/admin/login` using an admin or super admin account, then open `/admin/sliding-door-builder` or its navigation item. `View:SlidingDoorBuilder` is granted by the admin/super-admin permission seeders. Guests authenticate; users without permission receive 403. There is no public configurator or locale-prefixed builder route. Use the panel's English/Georgian language switch.

The catalog must contain an active usable model/variant and infills. Divided layouts require a partition component. Missing/unusable records are filtered by the catalog service; an empty catalog shows a localized empty state. Frame swatches live in `config/builder.php`. Infill preview colours and textures are stored in `infills.visual_color` and `infills.visual_texture`, editable in the infill resource. The CSV seed records illustrative walnut/oak grain, mirror reflections, tinted glass and solid colours from the product names; unspecified paint colours use neutral shades. Missing/invalid visual values fall back to neutral solid rendering. SVG textures require no external images.

## Demonstration script

1. Choose a door model, then enter width and height in millimetres. Size fields stay disabled until a model is selected and enforce its inclusive minimum/maximum bounds. The progress indicator is read-only; each section must be complete before advancing, with one Previous/Next row and inline errors.
2. Choose a frame design and actual colour/variant combination. Frame and colour choices only include variants whose recorded height range contains the entered height.
3. Both doors inherit the model's partition count: 1–4 horizontal dividers create 2–5 equal panels on each door. There is no layout/count selector. All numbered panel selects are visible immediately; choosing an infill sets that panel on both doors. Each option includes an escaped label and database-backed colour/texture swatch. Preview controls select panel positions only and highlight both doors. Tab and Enter/Space operate these controls; Escape clears selection.
4. Changing to a model with fewer partitions asks for confirmation if filled panels would be removed. Cancel restores the previous model; surviving finishes follow their top-to-bottom positions, new panels start empty, and removed finishes do not return.
5. Change variants/options and inspect compatibility messages. Existing finishes remain available for correction. Next validates each section and selects an unfinished/incompatible panel automatically. Models without any partition-capable variants cannot be selected.
6. Complete every panel and open review. Check dimensions, model/variant/colour, model divisions, shared panel infills and all five component roles. Components show their recorded code, description and colour; absent optional hardware is marked as not included. Review is read-only; use Previous to return to configuration.
7. Refresh to restore the last valid design in this browser. Reset requires confirmation and clears the saved draft. Neither action submits a quote or order.

## Implemented rules and limitations

- Exactly two equal-width illustrative doors; one shared layout, shared panel infills and stable instance IDs.
- Size and system controls are PHP Filament `TextInput` and searchable `Select` schemas. Livewire owns their field state and renders native inline validation on Next. Dependent design/variant options come from the active catalog. Alpine observes the fields for the SVG preview, while the panel configuration stays in the browser and every panel has its own searchable Filament infill select, always visible. Draft restoration and reset refill the native schemas without discarding shared panel infills. Every configuration resolves to an existing variant under its model/design/height; its recorded components determine soft close and infill exclusions.
- Standard field errors come from the same `validation.php` translations and field-label casing used by Filament: missing values use `required`, nonnumeric values use `numeric`, zero/negative measurements use `gt.numeric`, and removed catalog choices use `exists` or `in`. Domain-specific compatibility messages remain separate.
- Positive finite dimensions, inclusive model width/height limits and representable geometry are enforced. Model width limits are per door: the total width field uses twice the minimum and maximum because the builder has two doors. Height limits apply directly. Variant height ranges filter selectable designs/variants and block unsupported heights. Discouraged height ranges remain advisory. Estimated half-width preview geometry is never a cutting size.
- `door_models.partition_count` stores each model's shared divider count (1–4). The catalog seeder assigns a random count per model on each run; run `php artisan db:seed --class=DoorCatalogSeeder` locally after migration to refresh the catalog without resetting users. The browser payload exposes `partitionCount`; visual metadata contains database-backed infill appearances, frame colour mappings and neutral fallback.

- Recorded infill exclusions apply to all used frame components, partitions when divided, and every recorded soft-close component. An empty exclusion list means no recorded exclusions, not a complete certification of compatibility.
- Soft close is determined by the selected variant, with no independent checkbox. Hardware quantities and installation eligibility remain unconfirmed.
- The last fully valid design is saved under a per-user browser-storage key. Partial/invalid edits do not overwrite it. Restore validates structure, IDs, dimensions and current catalog compatibility; outdated choices are preserved for correction. Drafts with outdated divisions or different infills on the two doors require explicit recovery; stored designs are preserved until the user discards or replaces them. Storage failures allow continued editing. Drafts stay in this browser; there is no live cross-tab synchronization or server draft endpoint.
- The review has no print, download or change buttons, and no uncertainty helper notes. The existing JSON export utility revalidates the design. JSON includes configuration IDs, readable current catalog details, millimetre units and recorded assumptions. Import, share links, pricing, manufacturing calculations and order/quote submission are deferred.
- A focused-input reduced viewport simulates available space with a mobile keyboard. Physical-device keyboard behaviour and a live screen-reader audit remain manual follow-ups; DOM/browser checks do not replace those audits.

## Checks

```sh
npm test
php artisan test --compact
vendor/bin/duster fix
npm run build
```

`npm test` runs the built-in Node runner; files can also be run directly with `node tests/JavaScript/<file>.test.js` to see each individual case. Production builds need not run after every edit while Vite dev is running.

The small Chromium suite uses Python Playwright and isolated browser contexts. It reads the catalog and changes only its own browser/session state. It authenticates once to avoid login throttling. Use a local authorized account and an active variant with a partition and at least two compatible infills:

```sh
python3 -m venv /tmp/ltb-browser-tests
/tmp/ltb-browser-tests/bin/pip install -r tests/Browser/requirements.txt
/tmp/ltb-browser-tests/bin/playwright install chromium
BUILDER_TEST_EMAIL=your-local-email BUILDER_TEST_PASSWORD=your-local-password /tmp/ltb-browser-tests/bin/python -m unittest discover -s tests/Browser -p 'test_*.py' -v
```

Set `BUILDER_TEST_URL` if the app is not at `http://localhost:8000`. Optional `BUILDER_TEST_ARTIFACTS` stores reduced-viewport screenshots in the chosen directory. Run against a local environment, not production.

Browser coverage includes English/Georgian journeys, model bounds and fixed divisions, shared panel infills, read-only component review, restoration/reset, browser print styles, keyboard selection, light/dark themes, 320/390/768/1440px layouts, reduced viewport focus, compatibility changes, neutral visual fallbacks, malformed drafts/storage failures, and Filament SPA teardown/remount.
