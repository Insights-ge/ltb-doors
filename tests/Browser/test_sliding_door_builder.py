"""Catalog-backed Chromium regressions. Requires a running local app and Vite."""
import json
import os
from pathlib import Path
import unittest
from playwright.sync_api import sync_playwright

ROOT = "Alpine.$data(document.querySelector('.sliding-door-builder'))"


def contrast(first, second):
    def luminance(rgb):
        values = [value / 255 / 12.92 if value / 255 <= 0.04045 else ((value / 255 + 0.055) / 1.055) ** 2.4 for value in rgb[:3]]
        return sum(value * weight for value, weight in zip(values, [0.2126, 0.7152, 0.0722]))
    low, high = sorted([luminance(first), luminance(second)])
    return (high + 0.05) / (low + 0.05)


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
        current = self.page.evaluate(f'{ROOT}.activeStep')
        while current != number:
            direction = 'next' if number > current else 'previous'
            self.page.locator(f'#builder-step-{current} [data-builder-{direction}]').click()
            next_step = self.page.evaluate(f'{ROOT}.activeStep')
            if next_step == current:
                break
            self.page.locator(f'#builder-step-{current}').wait_for(state='hidden')
            self.page.locator(f'#builder-step-{next_step}').wait_for(state='visible')
            current = next_step

    def catalog_choices(self):
        choices = self.page.evaluate(f'''() => {{
            const b={ROOT};
            for(const m of b.catalog.models) for(const v of m.variants) {{
                const excluded=new Set(v.components.filter(c=>c.type!=='soft_close_mechanism').flatMap(c=>c.incompatibleInfillIds));
                const infills=b.catalog.infills.filter(i=>!excluded.has(i.id));
                if(v.hasPartition && infills.length>1) return {{model:m.id,variant:v.id,design:v.frameDesignCode,infills:infills.slice(0,2).map(i=>i.id)}};
            }}
        }}''')
        self.assertIsNotNone(choices, 'Seed an active variant with a partition and two compatible infills.')
        return choices

    def configure(self):
        page = self.page
        page.locator('#builder-openingWidth').fill('3000')
        page.locator('#builder-openingHeight').fill('2400')
        self.step(2)
        choices = self.catalog_choices()
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
                self.assertEqual(page.evaluate('getComputedStyle(document.body).backgroundColor'), 'rgb(255, 255, 255)')
                self.assertFalse(page.locator('.builder-preview-panel-highlight.is-selected').is_visible())
                page.emulate_media(media='screen')

    def test_responsive_layout_and_focused_measurement_with_reduced_viewport(self):
        self.locale('ka')
        self.configure()
        page = self.page
        for width in [320, 390, 768, 1440]:
            for mode in ['light', 'dark']:
                with self.subTest(width=width, theme=mode):
                    page.set_viewport_size({'width': width, 'height': 900})
                    page.evaluate("Alpine.store('sidebar').close()")
                    page.evaluate('(mode)=>window.dispatchEvent(new CustomEvent("theme-changed",{detail:mode}))', mode)
                    self.step(3)
                    page.locator('[data-layout-preset=two_horizontal_panels]').wait_for(state='visible')
                    self.assertTrue(page.evaluate('document.documentElement.scrollWidth <= innerWidth'))
                    svg = page.locator('.builder-preview-svg').bounding_box()
                    self.assertGreater(svg['width'], 96)
                    for button in page.locator('[data-preview-door-control]').all():
                        self.assertGreaterEqual(button.bounding_box()['height'], 44)
                    self.step(1)
                    # Models the usable viewport shrinking when a mobile keyboard opens.
                    page.set_viewport_size({'width': width, 'height': 420})
                    field = page.locator('#builder-openingWidth')
                    field.scroll_into_view_if_needed()
                    field.focus()
                    box = field.bounding_box()
                    self.assertGreaterEqual(box['x'], 0)
                    self.assertLessEqual(box['x'] + box['width'], width)
                    self.assertGreaterEqual(box['y'], 0)
                    self.assertLessEqual(box['y'] + box['height'], 420)
                    self.assertEqual(field.input_value(), '3000')
                    colors = page.evaluate("""() => {
                        const rgb = value => { const canvas=document.createElement('canvas'); canvas.width=canvas.height=1; const ctx=canvas.getContext('2d'); ctx.fillStyle=value; ctx.fillRect(0,0,1,1); return Array.from(ctx.getImageData(0,0,1,1).data); };
                        return {
                            text:rgb(getComputedStyle(document.querySelector('.builder-preview-svg')).color),
                            surface:rgb(getComputedStyle(document.querySelector('.builder-preview .fi-section-content')).backgroundColor),
                            focus:rgb(getComputedStyle(document.activeElement).outlineColor)
                        };
                    }""")
                    self.assertGreaterEqual(contrast(colors['text'], colors['surface']), 4.5)
                    self.assertGreaterEqual(contrast(colors['focus'], colors['surface']), 3)

                    if os.environ.get('BUILDER_TEST_ARTIFACTS'):
                        directory = Path(os.environ['BUILDER_TEST_ARTIFACTS'])
                        directory.mkdir(parents=True, exist_ok=True)
                        page.screenshot(path=str(directory / f'keyboard-{width}-{mode}.png'))
                    page.set_viewport_size({'width': width, 'height': 900})

    def test_keyboard_selection_focus_and_inline_validation(self):
        self.configure()
        page = self.page
        doors = page.locator('[data-preview-door-control]')
        doors.first.focus()
        page.keyboard.press('Tab')
        self.assertEqual(page.evaluate('document.activeElement.dataset.previewDoorControl'), doors.nth(1).get_attribute('data-preview-door-control'))
        page.keyboard.press('Space')
        page.locator('[data-preview-panel-control]').first.wait_for(state='visible')
        page.keyboard.press('Tab')
        self.assertTrue(page.evaluate('document.activeElement.hasAttribute("data-preview-panel-control")'))
        page.keyboard.press('Enter')
        page.wait_for_function(f'{ROOT}.previewSelection.panelId !== null')
        label = page.evaluate(f'{ROOT}.previewPanelAccessibleLabel(0)')
        self.assertEqual(page.get_by_role('button', name=label, exact=True).get_attribute('aria-pressed'), 'true')
        self.assertTrue(page.evaluate('document.activeElement.matches(":focus-visible")'))
        self.assertNotEqual(page.evaluate('getComputedStyle(document.activeElement).outlineStyle'), 'none')
        self.assertEqual(page.locator('.builder-preview [role=status]').inner_text(), page.evaluate(f'{ROOT}.previewSelectionAnnouncement'))
        page.keyboard.press('Escape')
        self.assertEqual(page.evaluate('document.activeElement.dataset.previewDoorControl'), doors.nth(1).get_attribute('data-preview-door-control'))
        self.step(1)
        page.wait_for_function('document.activeElement.id === "builder-step-1"')
        width = page.locator('#builder-openingWidth')
        width.fill('invalid')
        self.step(4)
        page.wait_for_function(f'{ROOT}.sizeErrors.openingWidth')
        self.assertEqual(width.get_attribute('aria-invalid'), 'true')
        page.locator('#builder-openingWidth-error').wait_for(state='visible')
        self.assertEqual(page.locator('#builder-validation').count(), 0)
        page.wait_for_function('document.activeElement.id === "builder-openingWidth"')
        self.assertNotEqual(page.evaluate(f'{ROOT}.activeStep'), 4)

    def test_long_catalog_names_are_text_and_missing_visuals_use_neutral_fills(self):
        self.configure()
        page = self.page
        page.set_viewport_size({'width': 320, 'height': 900})
        page.evaluate("Alpine.store('sidebar').close()")
        page.evaluate(f"""() => {{
            const b={ROOT};
            const text='<img data-untrusted onerror=alert(1)> ძალიან გრძელი დასახელება '.repeat(6);
            b.selectedModel.name=text;
            b.selectedVariant.name=text;
            b.catalog.infills.forEach(infill=>infill.description=text);
            b.metadata.infillVisuals=[];
            b.metadata.frameColors=[];
            b.updatePreview();
        }}""")
        self.assertEqual(page.locator('[data-untrusted]').count(), 0)
        self.assertTrue(page.evaluate('document.documentElement.scrollWidth <= innerWidth'))
        self.assertTrue(page.evaluate(f"Array.from(document.querySelectorAll('.builder-preview-panel')).every(panel=>panel.getAttribute('fill')==={ROOT}.metadata.neutralVisual.fill)"))
        self.step(4)
        page.locator('.builder-review').wait_for(state='visible')
        self.assertIn('<img data-untrusted', page.locator('.builder-review').inner_text())
        self.assertEqual(page.locator('[data-untrusted]').count(), 0)
        self.assertTrue(page.evaluate('document.documentElement.scrollWidth <= innerWidth'))

    def test_preview_avoids_replacing_nodes_for_selection_and_nonvisual_changes(self):
        self.configure()
        page = self.page
        page.evaluate("window.__builderPanel=document.querySelector('.builder-preview-panel')")
        page.locator('[data-preview-door-control]').first.click()
        page.locator('[data-preview-panel-control]').first.click()
        self.assertTrue(page.evaluate("window.__builderPanel===document.querySelector('.builder-preview-panel')"))
        page.evaluate(f'{ROOT}.previewConfiguration={{...{ROOT}.previewConfiguration,softClose:!{ROOT}.previewConfiguration.softClose}}')
        page.evaluate(f'{ROOT}.updatePreview()')
        self.assertTrue(page.evaluate("window.__builderPanel===document.querySelector('.builder-preview-panel')"))
        # Repeated public render calls and ResizeObserver notifications must be idempotent.
        page.evaluate(f'{ROOT}.updatePreview()')
        self.assertTrue(page.evaluate("window.__builderPanel===document.querySelector('.builder-preview-panel')"))
        page.set_viewport_size({'width': 390, 'height': 900})
        page.wait_for_function("window.__builderPanel!==document.querySelector('.builder-preview-panel')")
        self.assertEqual(page.locator('.builder-preview-door').count(), 2)
        self.assertEqual(page.locator('.builder-preview-panel').count(), 4)
        # Exercise Filament's SPA teardown/remount instead of only a full page refresh.
        for _ in range(2):
            page.evaluate("Livewire.navigate('/admin/my-profile')")
            page.wait_for_function("!document.querySelector('.sliding-door-builder')")
            page.evaluate("Livewire.navigate('/admin/sliding-door-builder')")
            self.ready()
            page.wait_for_function(f'{ROOT}.hasPreview')
            self.assertEqual(page.locator('.builder-preview-door').count(), 2)
            self.assertEqual(page.locator('.builder-preview-panel').count(), 4)
            page.locator('[data-preview-door-control]').first.click()
            self.assertEqual(page.locator('.builder-preview-door.is-selected').count(), 1)

    def test_sequential_sections_have_single_footer_and_only_inline_errors(self):
        page = self.page
        self.assertEqual(page.locator('.builder-step-navigation button, .builder-step-navigation a').count(), 0)
        self.assertEqual(page.locator('.builder-step-navigation [role=tab]').count(), 0)
        self.assertEqual(page.locator('[data-builder-next]:visible').count(), 1)
        self.assertEqual(page.locator('[data-builder-previous]:visible').count(), 0)
        self.assertTrue(page.locator('[data-builder-next]:visible').evaluate("el=>Boolean(el.closest('.fi-section'))"))
        self.step(2)
        self.assertEqual(page.evaluate(f'{ROOT}.activeStep'), 1)
        page.locator('#builder-openingWidth-error').wait_for(state='visible')
        page.locator('#builder-openingHeight-error').wait_for(state='visible')
        self.assertFalse(page.locator('#builder-model-error').is_visible())
        self.assertEqual(page.locator('#builder-validation').count(), 0)
        page.locator('#builder-openingWidth').fill('3000')
        page.locator('#builder-openingHeight').fill('2400')
        self.step(2)
        self.assertEqual(page.evaluate(f'{ROOT}.activeStep'), 2)
        self.assertFalse(page.locator('#builder-model-error').is_visible())
        self.assertEqual(page.locator('[data-builder-next]:visible').count(), 1)
        self.assertEqual(page.locator('[data-builder-previous]:visible').count(), 1)
        self.step(3)
        page.locator('#builder-model-error').wait_for(state='visible')
        self.assertEqual(page.evaluate('document.activeElement.id'), 'builder-model')
        choices = self.catalog_choices()
        page.locator('#builder-model').select_option(str(choices['model']))
        page.locator('#builder-model-error').wait_for(state='hidden')
        page.locator('#builder-design-error').wait_for(state='visible')
        self.assertFalse(page.locator('#builder-variant-error').is_visible())
        self.step(3)
        self.assertEqual(page.evaluate(f'{ROOT}.activeStep'), 2)
        page.locator('#builder-design').select_option(choices['design'])
        page.locator('#builder-variant-error').wait_for(state='visible')
        self.step(3)
        self.assertEqual(page.evaluate(f'{ROOT}.activeStep'), 2)
        page.locator('#builder-variant').select_option(str(choices['variant']))
        self.step(3)
        self.assertFalse(page.locator('#builder-finishes-error').is_visible())
        self.step(4)
        page.locator('#builder-finishes-error').wait_for(state='visible')
        self.assertEqual(page.evaluate(f'{ROOT}.selectedPreviewDoor.index'), 0)
        self.assertTrue(page.locator('#builder-finishes-error').evaluate("el=>el.previousElementSibling.id==='builder-finishes'"))
        page.locator(f'[data-infill-id="{choices["infills"][0]}"]').click()
        page.locator('#builder-finishes-error').wait_for(state='hidden')
        self.step(4)
        self.assertEqual(page.evaluate(f'{ROOT}.selectedPreviewDoor.index'), 1)
        page.locator(f'[data-infill-id="{choices["infills"][1]}"]').click()
        self.step(4)
        self.assertEqual(page.evaluate(f'{ROOT}.activeStep'), 4)
        self.assertEqual(page.locator('[data-builder-next]:visible').count(), 0)
        self.assertEqual(page.locator('[data-builder-previous]:visible').count(), 1)
        self.assertEqual(page.locator('.builder-summary button').count(), 0)

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
