"""Catalog-backed Chromium regressions. Requires a running local app and Vite."""
import json
import os
import unittest
from playwright.sync_api import sync_playwright

ROOT = "Alpine.$data(document.querySelector('.sliding-door-builder'))"


class SlidingDoorBuilderBrowserTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.email = os.environ['BUILDER_TEST_EMAIL']
        cls.password = os.environ['BUILDER_TEST_PASSWORD']
        cls.base_url = os.environ.get('BUILDER_TEST_URL', 'http://localhost:8000').rstrip('/')
        cls.playwright = sync_playwright().start()
        cls.browser = cls.playwright.chromium.launch(headless=True)
        context = cls.browser.new_context()
        page = context.new_page()
        page.goto(cls.base_url + '/admin/login')
        page.locator('input[type=email]').fill(cls.email)
        page.locator('input[type=password]').fill(cls.password)
        page.locator('button[type=submit]').click()
        page.wait_for_url(lambda url: '/login' not in url)
        cls.cookies = context.cookies()
        context.close()

    @classmethod
    def tearDownClass(cls):
        cls.browser.close()
        cls.playwright.stop()

    def setUp(self):
        self.context = self.browser.new_context(viewport={'width': 1440, 'height': 1000}, storage_state={'cookies': self.cookies, 'origins': []})
        self.page = self.context.new_page()
        self.page.set_default_timeout(10000)
        self.errors = []
        self.page.on('pageerror', lambda error: self.errors.append(str(error)))
        self.page.goto(self.base_url + '/admin/sliding-door-builder')
        self.ready()

    def tearDown(self):
        self.context.close()
        self.assertEqual(self.errors, [])

    def ready(self):
        self.page.wait_for_function(f"window.Alpine && document.querySelector('.sliding-door-builder') && {ROOT}.catalog")

    def locale(self, locale):
        self.page.locator('.fi-ls [wire\\:click]').evaluate_all(
            "(els, locale) => { const item=els.find(el=>el.getAttribute('wire:click') === `changeLocale('${locale}')`); if(item)item.click(); }", locale)
        self.page.wait_for_function('(locale) => document.documentElement.lang === locale', arg=locale)
        self.ready()

    def step(self, number):
        self.page.locator(f'[aria-controls=builder-step-{number}]').click()

    def configure(self):
        page = self.page
        page.locator('#builder-openingWidth').fill('3000')
        page.locator('#builder-openingHeight').fill('2400')
        self.step(2)
        choices = page.evaluate(f'''() => {{
            const b={ROOT};
            for(const m of b.catalog.models) for(const v of m.variants) {{
                const excluded=new Set(v.components.filter(c=>c.type!=='soft_close_mechanism').flatMap(c=>c.incompatibleInfillIds));
                const infills=b.catalog.infills.filter(i=>!excluded.has(i.id));
                if(v.hasPartition && infills.length>1) return {{model:m.id,variant:v.id,design:v.frameDesignCode,infills:infills.slice(0,2).map(i=>i.id)}};
            }}
        }}''')
        self.assertIsNotNone(choices, 'Seed an active variant with a partition and two compatible infills.')
        page.locator('#builder-model').select_option(str(choices['model']))
        page.locator('#builder-design').select_option(choices['design'])
        page.locator('#builder-variant').select_option(str(choices['variant']))
        self.step(3)
        page.locator('[data-layout-preset=two_horizontal_panels]').click()
        page.wait_for_function('document.querySelectorAll(".builder-preview-panel").length===4')
        for door in range(2):
            page.locator('[data-preview-door-control]').nth(door).click()
            for panel in range(2):
                page.locator('[data-preview-panel-control]').nth(panel).click()
                page.locator(f'[data-infill-id="{choices["infills"][(door+panel)%2]}"]').click()
        page.wait_for_function(f'{ROOT}.canReview')
        return choices

    def test_configuration_export_and_restoration_in_both_locales(self):
        page = self.page
        for locale in ['en', 'ka']:
            with self.subTest(locale=locale):
                self.locale(locale)
                # Each run has an isolated browser context; this clears only its test draft.
                page.evaluate("Object.keys(localStorage).filter(k=>k.startsWith('sliding-door-builder:')).forEach(k=>localStorage.removeItem(k))")
                page.reload()
                self.ready()
                choices = self.configure()
                self.step(4)
                page.wait_for_function(f'{ROOT}.activeStep===4')
                with page.expect_download() as event:
                    page.locator('[data-export-design]').click()
                with open(event.value.path()) as handle:
                    exported = json.load(handle)
                self.assertEqual(exported['configuration']['infillIdsByDoor'], [choices['infills'], list(reversed(choices['infills']))])
                self.assertEqual(exported['units'], 'mm')
                page.reload()
                self.ready()
                page.wait_for_function(f"{ROOT}.draftStatus==='restored'")
                self.assertEqual(page.locator('#builder-model').input_value(), str(choices['model']))
                self.assertEqual(page.locator('#builder-design').input_value(), choices['design'])
                self.assertEqual(page.locator('#builder-variant').input_value(), str(choices['variant']))
                self.step(4)
                self.assertIn(str(3000), page.locator('.builder-review').inner_text())
                page.evaluate('window.__builderPrinted=false; window.print=()=>window.__builderPrinted=true')
                page.locator('[data-print-design]').click()
                page.wait_for_function('window.__builderPrinted')
                page.emulate_media(media='print')
                self.assertTrue(page.locator('.builder-preview-svg').is_visible())
                self.assertTrue(page.locator('.builder-review').is_visible())
                self.assertFalse(page.locator('.builder-step-navigation').is_visible())
                page.emulate_media(media='screen')

    def test_upstream_invalidation_retains_finishes_and_blocks_review(self):
        choices = self.configure()
        page = self.page
        before = page.evaluate(f'JSON.stringify({ROOT}.previewConfiguration.infillIdsByDoor)')
        page.evaluate(f'{ROOT}.selectedVariant.hasPartition=false')
        page.wait_for_function(f'!{ROOT}.canReview')
        self.assertTrue(page.locator('[data-layout-preset=two_horizontal_panels]').is_disabled())
        self.step(4)
        self.assertNotEqual(page.evaluate(f'{ROOT}.activeStep'), 4)
        self.assertEqual(page.evaluate(f'JSON.stringify({ROOT}.previewConfiguration.infillIdsByDoor)'), before)
        page.evaluate(f'{ROOT}.selectedVariant.hasPartition=true')
        page.evaluate(f"{ROOT}.selectedVariant.components.find(c=>c.type==='top_rail').incompatibleInfillIds.push({choices['infills'][0]})")
        page.wait_for_function(f"{ROOT}.validation.errors.some(issue=>issue.code==='finish_incompatible')")
        self.assertFalse(page.evaluate(f'{ROOT}.canReview'))
        self.assertEqual(page.evaluate(f'JSON.stringify({ROOT}.previewConfiguration.infillIdsByDoor)'), before)
        page.reload()
        self.ready()
        page.wait_for_function(f"{ROOT}.draftStatus==='restored'")

    def test_invalid_draft_can_be_discarded_and_storage_failure_does_not_block_editing(self):
        page = self.page
        page.evaluate("localStorage.setItem(Object.keys(localStorage).find(k=>k.startsWith('sliding-door-builder:')) ?? document.querySelector('.sliding-door-builder').getAttribute('x-data').match(/sliding-door-builder:\\d+/)[0], '{')")
        page.reload()
        self.ready()
        page.wait_for_function(f"{ROOT}.draftStatus==='invalid'")
        page.locator('.builder-draft-recovery button').click()
        self.assertIsNone(page.evaluate(f'{ROOT}.draftStatus'))
        # Only builder writes fail; Filament's unrelated theme/sidebar storage stays available.
        page.evaluate("() => { const original=Storage.prototype.setItem; Storage.prototype.setItem=function(key,value){if(key.startsWith('sliding-door-builder:'))throw new DOMException('Quota exceeded','QuotaExceededError');return original.call(this,key,value)}; }")
        self.configure()
        page.wait_for_function(f"{ROOT}.draftStatus==='unavailable'")
        self.assertTrue(page.evaluate(f'{ROOT}.canReview'))


if __name__ == '__main__':
    unittest.main()
