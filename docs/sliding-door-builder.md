# Sliding door builder

Catalog sizing scope remains unconfirmed: model width/height limits, variant height ranges and discouraged heights may describe one finished door or a complete opening. The builder displays raw catalog ranges for reference and does not apply them to the opening or to estimated half-width door geometry. `sliding-door-rules.js` records each scope as `unconfirmed`. Only positive finite opening dimensions and representable geometry are currently enforced. Confirm the measurement scope and any track/overlap deductions before introducing product sizing limits.

The preview always uses two equal-width doors with a shared panel layout. It provides opening dimensions, not fabrication or cutting sizes. Soft close is a preference; hardware quantity and installation eligibility are unconfirmed. Recorded component–infill exclusions are enforced only for components actually used.

## Verification record

- Complete catalog-backed journey checked in English and Georgian: size → model/design/colour → shared two-panel layout → independently assigned finishes → review → JSON download → refresh/restore → print PDF. Selected values and finish IDs survived restoration. No browser errors after correcting the review edit-label expression.


## Browser regression checks

The small Chromium suite uses Python Playwright (pinned in `tests/Browser/requirements.txt`) and isolated browser contexts. It does not modify catalog records or create test users. Run it against a running local Laravel/Vite setup with an existing authorized account and an active variant with a partition and at least two compatible infills:

```sh
python3 -m venv /tmp/ltb-browser-tests
/tmp/ltb-browser-tests/bin/pip install -r tests/Browser/requirements.txt
/tmp/ltb-browser-tests/bin/playwright install chromium
BUILDER_TEST_EMAIL=your-local-email BUILDER_TEST_PASSWORD=your-local-password /tmp/ltb-browser-tests/bin/python -m unittest discover -s tests/Browser -p 'test_*.py' -v
```

Set `BUILDER_TEST_URL` if the app is not at `http://localhost:8000`. Tests cover both locales, JSON/print, draft restoration, catalog invalidation, malformed drafts and storage write failures. They operate only on their isolated session/browser storage. Use a local environment, not production.
