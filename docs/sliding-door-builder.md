# Sliding door builder

## Local setup

Use the repository's normal Laravel setup with PHP 8.4+, Composer, Node and a local database configured in `.env`. For a fresh local database, `php artisan migrate --seed` installs settings, roles/permissions and the CSV-backed door/infill catalog through `DatabaseSeeder`. For an existing database, use the established catalog import/admin workflow instead of resetting it. `UserSeeder` defines the local sample accounts.

Run `php artisan serve` and `npm run dev` in separate terminals. Sign in at `/admin/login` using an admin or super admin account, then open `/admin/sliding-door-builder` or its navigation item. `View:SlidingDoorBuilder` is granted by the admin/super-admin permission seeders. Guests authenticate; users without permission receive 403. There is no public configurator or locale-prefixed builder route. Use the panel's English/Georgian language switch.

The catalog must contain an active usable model/variant and infills. Divided layouts require a partition component. Missing/unusable records are filtered by the catalog service; an empty catalog shows a localized empty state. Frame swatches and optional infill mappings live in `config/builder.php`; unmapped visuals remain neutral without inventing a catalog finish.

## Demonstration script

1. Enter opening width and height in millimetres, then click Next. The progress indicator is read-only. Each section must be complete before advancing, with one Previous/Next row in its footer. Incomplete text keeps the last valid preview and shows a red error under the field.
2. Choose a system, frame design, and actual colour/variant combination. Catalog ranges are available as references.
3. Choose full panels, two equal horizontal panels, or three equal horizontal panels. Both doors always share the layout.
4. Select a numbered door and panel in the preview; choose finishes independently. Tab and Enter/Space operate the same controls. Escape clears selection.
5. Try applying a finish to all panels, copying a door, or reducing the layout after customizing finishes. Confirm or cancel the replacement dialog. Surviving panel choices follow their top-to-bottom positions; new panels start empty and removed values do not return.
6. Change the system/variant/options and inspect compatibility messages. Existing finishes remain visible for correction. Click Next to validate the section. Errors appear only beneath their fields; an unfinished or incompatible panel is selected automatically for correction. A variant without partitions cannot introduce a divided layout.
7. Complete every panel and open review. Check the opening dimensions, system/variant/colour, shared layout, independent finishes and options. Edit links return to the appropriate step/door/panel.
8. Refresh to restore the last valid design on this browser/device. Print the summary or download its JSON configuration. Reset requires confirmation and clears the saved draft. Neither action submits a quote or order.

## Implemented rules and limitations

- Exactly two equal-width illustrative doors; one shared layout, independent finishes and stable instance IDs.
- Positive finite opening dimensions and representable geometry are enforced. Model width/height limits, variant height ranges and discouraged heights have **unconfirmed measurement scope**: one finished door versus an entire opening. They remain raw reference/advisory values. `sliding-door-rules.js` records the scopes as `unconfirmed`. Confirm scope and track/overlap deductions before applying product sizing limits. Estimated half-width geometry is never a cutting size.
- Recorded infill exclusions apply to all used frame components, partitions when divided, and soft-close components only when selected. An empty exclusion list means no recorded exclusions, not a complete certification of compatibility.
- Soft close is a preference available with the relevant component. Hardware quantities and installation eligibility remain unconfirmed.
- The last fully valid design is saved under a per-user browser-storage key. Partial/invalid edits do not overwrite it. Restore validates structure, IDs, dimensions and current catalog compatibility; outdated choices are preserved for correction. Storage failures allow continued editing. Drafts stay in this browser; there is no live cross-tab synchronization or server draft endpoint.
- Print and JSON export revalidate the design. JSON includes configuration IDs, readable current catalog details, millimetre units and recorded assumptions. Import, share links, pricing, manufacturing calculations and order/quote submission are deferred.
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

Verified on this branch:

- **352 PHP tests**, 3429 assertions; **101 individual Node tests**; **eight Chromium regression cases**; production Vite build and repository-wide Duster all pass.
- Catalog-backed journeys in English/Georgian, independent finishes, review, JSON download, refresh/restore and print PDF, without blocking console errors.
- 320/390/768/1440px widths and light/dark themes; Georgian/long names, no horizontal overflow, 44px controls and reduced-height focused inputs.
- Keyboard selection, context labels, pressed/live states, section/invalid-field focus, annotation contrast of at least 4.5:1 and focus accents of at least 3:1 against the preview surface.
- Empty/incomplete catalogs, removed/changed options, missing visuals, names rendered as text, malformed drafts and storage failures.
- No SVG node replacement for selection/nonvisual changes; correct resize redraw and two clean Filament SPA teardown/remount cycles.
- Print output uses white surfaces, actual frame colour and no selection markers. The dev server and its hot-file configuration remain available after the production-build check.
