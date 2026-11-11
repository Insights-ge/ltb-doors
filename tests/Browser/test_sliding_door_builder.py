"""Catalog-backed Chromium regressions. Requires a running local app and Vite."""
import json
import html
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

    def field_error(self, field):
        if field.startswith('builder-panel-'):
            return self.page.locator(f'#{field}-error')
        return self.page.locator(f'[data-field-wrapper]:has(#{field}) [data-validation-error]')

    def step(self, number):
        current = self.page.evaluate(f'{ROOT}.activeStep')
        while current != number:
            direction = 'next' if number > current else 'previous'
            self.page.locator(f'#builder-step-{current} [data-builder-{direction}]').click()
            self.page.wait_for_function(f'!{ROOT}.formBusy')
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
                const excluded=new Set(v.components.flatMap(c=>c.incompatibleInfillIds));
                const infills=b.catalog.infills.filter(i=>!excluded.has(i.id));
                if(v.hasPartition && Math.max(m.minHeight,v.heightRangeMin)<=Math.min(m.maxHeight,v.heightRangeMax) && infills.length>1) return {{model:m.id,variant:v.id,design:v.frameDesignCode,panels:m.partitionCount+1,width:m.minWidth+m.maxWidth,height:Math.floor((Math.max(m.minHeight,v.heightRangeMin)+Math.min(m.maxHeight,v.heightRangeMax))/2),infills:infills.slice(0,2).map(i=>i.id)}};
            }}
        }}''')
        self.assertIsNotNone(choices, 'Seed an active variant with a partition and two compatible infills.')
        return choices

    def select(self, field, value):
        self.page.locator('#'+field).click()
        self.page.locator(f'[role=option][data-value="{value}"]:visible').click()
        if field.startswith('builder-panel-'):
            self.page.wait_for_function(f'{ROOT}.previewConfiguration.infillIdsByDoor[0][{int(field.rsplit("-",1)[1])}] === {int(value)}')
            return
        key = {'builder-model':'modelId', 'builder-design':'frameDesignCode', 'builder-variant':'variantId'}[field]
        self.page.wait_for_function(f'String({ROOT}.formState.{key}) === {json.dumps(str(value))}')

    def selected_value(self, field):
        key = {'builder-model':'modelId', 'builder-design':'frameDesignCode', 'builder-variant':'variantId'}[field]
        return self.page.evaluate(f'String({ROOT}.formState.{key} ?? "")')

    def choose_model_and_size(self):
        choices = self.catalog_choices()
        self.select('builder-model', str(choices['model']))
        self.page.wait_for_function("!document.querySelector('#builder-openingWidth').disabled")
        self.page.locator('#builder-openingWidth').fill(str(choices['width']))
        self.page.locator('#builder-openingHeight').fill(str(choices['height']))
        return choices

    def configure(self):
        page = self.page
        choices = self.choose_model_and_size()
        self.step(2)
        self.select('builder-design', choices['design'])
        self.select('builder-variant', str(choices['variant']))
        self.step(3)
        page.wait_for_function(f'document.querySelectorAll(".builder-preview-panel").length==={2*choices["panels"]}')
        self.assertEqual(page.locator('[data-preview-door-control]').count(), 0)
        self.assertEqual(page.locator('[id^=builder-panel-][role=combobox]:visible').count(), choices['panels'])
        for panel in range(choices['panels']):
            self.select(f'builder-panel-{panel}', choices['infills'][panel%2])
        page.wait_for_function(f'{ROOT}.canReview')
        return choices

    def test_configuration_review_and_restoration_in_both_locales(self):
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
                self.assertEqual(page.locator('.builder-review button').count(), 0)
                self.assertEqual(page.locator('[data-review-component]').count(), 5)
                components = page.evaluate(f'{ROOT}.selectedVariant.components')
                for component in components:
                    row = page.locator(f'[data-review-component="{component["type"]}"]')
                    self.assertIn(component['code'], row.inner_text())
                    self.assertIn(component['description'], row.inner_text())
                self.assertFalse(page.locator('.builder-review ul').count())
                self.assertEqual(page.evaluate(f'{ROOT}.previewConfiguration.infillIdsByDoor'), [[choices['infills'][panel%2] for panel in range(choices['panels'])] for door in range(2)])
                page.reload()
                self.ready()
                page.wait_for_function(f"{ROOT}.draftStatus==='restored'")
                self.assertEqual(self.selected_value('builder-model'), str(choices['model']))
                self.assertEqual(self.selected_value('builder-design'), choices['design'])
                self.assertEqual(self.selected_value('builder-variant'), str(choices['variant']))
                self.step(4)
                self.assertIn(str(choices['width']), page.locator('.builder-review').inner_text())
                page.emulate_media(media='print')
                self.assertTrue(page.locator('.builder-preview-svg').is_visible())
                self.assertTrue(page.locator('.builder-review').is_visible())
                self.assertFalse(page.locator('.builder-step-navigation').is_visible())
                self.assertEqual(page.evaluate('getComputedStyle(document.body).backgroundColor'), 'rgb(255, 255, 255)')
                self.assertFalse(page.locator('.builder-preview-panel-highlight.is-selected').is_visible())
                page.emulate_media(media='screen')

    def test_responsive_layout_and_focused_measurement_with_reduced_viewport(self):
        self.locale('ka')
        choices = self.configure()
        page = self.page
        for width in [320, 390, 768, 1440]:
            for mode in ['light', 'dark']:
                with self.subTest(width=width, theme=mode):
                    page.set_viewport_size({'width': width, 'height': 900})
                    page.evaluate("Alpine.store('sidebar').close()")
                    page.evaluate('(mode)=>window.dispatchEvent(new CustomEvent("theme-changed",{detail:mode}))', mode)
                    self.step(3)
                    page.locator('#builder-panel-0').wait_for(state='visible')
                    self.assertTrue(page.evaluate('document.documentElement.scrollWidth <= innerWidth'))
                    svg = page.locator('.builder-preview-svg').bounding_box()
                    self.assertGreater(svg['width'], 96)
                    for button in page.locator('[data-preview-panel-control]').all():
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
                    self.assertEqual(field.input_value(), str(choices['width']))
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
        choices = self.configure()
        page = self.page
        panels = page.locator('[data-preview-panel-control]')
        panels.first.focus()
        page.keyboard.press('Space')
        page.wait_for_function(f'{ROOT}.selectedPreviewPanel?.index===0')
        self.assertEqual(page.locator('.builder-preview-panel-highlight.is-selected').count(), 2)
        page.keyboard.press('Tab')
        page.keyboard.press('Enter')
        page.wait_for_function(f'{ROOT}.selectedPreviewPanel?.index===1')
        label = page.evaluate(f'{ROOT}.previewPanelAccessibleLabel(1)')
        self.assertEqual(page.get_by_role('button', name=label, exact=True).get_attribute('aria-pressed'), 'true')
        self.assertTrue(page.evaluate('document.activeElement.matches(":focus-visible")'))
        self.assertEqual(page.locator('.builder-preview [role=status]').inner_text(), page.evaluate(f'{ROOT}.previewSelectionAnnouncement'))
        page.keyboard.press('Escape')
        self.assertIsNone(page.evaluate(f'{ROOT}.previewSelection.panelId'))
        self.step(1)
        page.wait_for_function('document.activeElement.id === "builder-step-1"')
        width = page.locator('#builder-openingWidth')
        width.fill('invalid')
        self.step(4)
        page.wait_for_function(f'{ROOT}.sizeErrors.openingWidth')
        self.assertEqual(width.get_attribute('aria-invalid'), 'true')
        self.field_error('builder-openingWidth').wait_for(state='visible')
        self.assertEqual(page.locator('#builder-validation').count(), 0)
        page.wait_for_function('document.activeElement.id === "builder-openingWidth"')
        self.assertNotEqual(page.evaluate(f'{ROOT}.activeStep'), 4)

    def test_long_catalog_names_are_text_and_missing_visuals_use_neutral_fills(self):
        choices = self.configure()
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
        choices = self.configure()
        page = self.page
        page.evaluate("window.__builderPanel=document.querySelector('.builder-preview-panel')")
        page.locator('[data-preview-panel-control]').first.click()
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
        self.assertEqual(page.locator('.builder-preview-panel').count(), 2*choices['panels'])
        # Exercise Filament's SPA teardown/remount instead of only a full page refresh.
        for _ in range(2):
            page.evaluate("Livewire.navigate('/admin/my-profile')")
            page.wait_for_function("!document.querySelector('.sliding-door-builder')")
            page.evaluate("Livewire.navigate('/admin/sliding-door-builder')")
            self.ready()
            page.wait_for_function(f'{ROOT}.hasPreview')
            self.assertEqual(page.locator('.builder-preview-door').count(), 2)
            self.assertEqual(page.locator('.builder-preview-panel').count(), 2*choices['panels'])
            page.locator('[data-preview-panel-control]').first.click()
            self.assertEqual(page.locator('.builder-preview-panel-highlight.is-selected').count(), 2)

    def test_sequential_sections_have_single_footer_and_only_inline_errors(self):
        page = self.page
        self.assertEqual(page.locator('.builder-step-navigation button, .builder-step-navigation a').count(), 0)
        self.assertEqual(page.locator('.builder-step-navigation [role=tab]').count(), 0)
        self.assertEqual(page.locator('[data-builder-next]:visible').count(), 1)
        self.assertEqual(page.locator('[data-builder-previous]:visible').count(), 0)
        self.assertTrue(page.locator('[data-builder-next]:visible').evaluate("el=>Boolean(el.closest('.fi-section'))"))
        self.step(2)
        self.assertEqual(page.evaluate(f'{ROOT}.activeStep'), 1)
        self.field_error('builder-model').wait_for(state='visible')
        self.assertTrue(page.locator('#builder-openingWidth').is_disabled())
        self.assertTrue(page.locator('#builder-openingHeight').is_disabled())
        self.assertFalse(self.field_error('builder-openingWidth').is_visible())
        choices = self.choose_model_and_size()
        self.step(2)
        self.assertEqual(page.evaluate(f'{ROOT}.activeStep'), 2)
        self.assertEqual(page.locator('[data-layout-preset]').count(), 0)
        self.step(3)
        self.field_error('builder-design').wait_for(state='visible')
        self.assertFalse(self.field_error('builder-variant').is_visible())
        self.step(3)
        self.assertEqual(page.evaluate(f'{ROOT}.activeStep'), 2)
        self.select('builder-design', choices['design'])
        self.step(3)
        self.field_error('builder-variant').wait_for(state='visible')
        self.step(3)
        self.assertEqual(page.evaluate(f'{ROOT}.activeStep'), 2)
        self.select('builder-variant', str(choices['variant']))
        self.step(3)
        self.assertEqual(page.locator('[id^=builder-panel-][role=combobox]:visible').count(), choices['panels'])
        self.step(4)
        for panel in range(choices['panels']):
            page.locator(f'#builder-panel-{panel}-error').wait_for(state='visible')
        for panel in range(choices['panels']):
            self.select(f'builder-panel-{panel}', choices['infills'][panel%2])
            page.locator(f'#builder-panel-{panel}-error').wait_for(state='hidden')
        self.step(4)
        self.assertEqual(page.evaluate(f'{ROOT}.activeStep'), 4)
        self.assertEqual(page.locator('[data-builder-next]:visible').count(), 0)
        self.assertEqual(page.locator('[data-builder-previous]:visible').count(), 1)
        self.assertEqual(page.locator('.builder-summary').count(), 0)

    def test_size_messages_use_the_matching_translated_validation_rule(self):
        page = self.page
        for locale in ['en', 'ka']:
            self.locale(locale)
            choices = self.choose_model_and_size()
            for value, rule in [('', 'required'), ('abc', 'numeric'), ('0', 'gt'), ('-20', 'gt')]:
                with self.subTest(locale=locale, value=value, rule=rule):
                    page.locator('#builder-openingWidth').fill(value)
                    self.step(2)
                    error = self.field_error('builder-openingWidth')
                    error.wait_for(state='visible')
                    expected = page.evaluate(f"{ROOT}.issueMessage({{code:'{rule}',field:'builder-openingWidth'}})")
                    self.assertEqual(error.inner_text(), expected)
                    self.assertEqual(page.evaluate(f'{ROOT}.activeStep'), 1)
                    self.assertEqual(page.locator('#builder-openingWidth').get_attribute('aria-invalid'), 'true')
            page.locator('#builder-openingWidth').fill(str(choices['width']))
            page.locator('#builder-openingHeight').fill(str(choices['height']))
            self.step(2)
            self.field_error('builder-openingWidth').wait_for(state='hidden')
            self.assertEqual(page.evaluate(f'{ROOT}.activeStep'), 2)
            self.step(1)

    def test_native_filament_fields_resize_and_reset_without_reviving_old_values(self):
        choices = self.configure()
        page = self.page
        before = page.evaluate(f'JSON.stringify({ROOT}.previewConfiguration.infillIdsByDoor)')
        self.assertEqual(page.locator('#builder-soft-close').count(), 0)
        self.assertEqual(page.evaluate(f'{ROOT}.previewConfiguration.softClose'), page.evaluate(f'{ROOT}.selectedVariant.hasSoftCloseMechanism'))
        self.step(1)
        page.locator('#builder-openingWidth').fill(str(choices['width']+1))
        page.locator('#builder-openingHeight').focus()
        page.wait_for_function(f'{ROOT}.previewConfiguration.openingWidth==={choices["width"]+1}')
        self.assertEqual(page.evaluate(f'JSON.stringify({ROOT}.previewConfiguration.infillIdsByDoor)'), before)
        self.step(2)
        page.locator('[data-reset-builder]').click()
        page.locator('[data-confirm-reset]').click()
        page.wait_for_function(f'!{ROOT}.formBusy && {ROOT}.previewConfiguration.openingWidth===null')
        self.assertEqual(page.locator('#builder-openingWidth').input_value(), '')
        self.assertEqual(page.locator('#builder-openingHeight').input_value(), '')
        self.assertEqual(self.selected_value('builder-model'), '')
        self.assertIsNone(page.evaluate(f'{ROOT}.previewConfiguration.variantId'))
        self.assertEqual(page.locator('.builder-preview-panel').count(), 0)
        self.assertTrue(page.locator('.builder-preview-placeholder').is_visible())
        self.assertTrue(page.locator('#builder-openingWidth').is_disabled())
        self.choose_model_and_size()
        self.step(2)
        self.assertEqual(page.evaluate(f'{ROOT}.activeStep'), 2)
        self.assertEqual(page.evaluate(f'{ROOT}.previewConfiguration.modelId'), choices['model'])

    def test_upstream_invalidation_retains_finishes_and_blocks_review(self):
        choices = self.configure()
        page = self.page
        before = page.evaluate(f'JSON.stringify({ROOT}.previewConfiguration.infillIdsByDoor)')
        page.evaluate(f'{ROOT}.selectedVariant.hasPartition=false')
        page.wait_for_function(f'!{ROOT}.canReview')
        self.assertEqual(page.locator('[data-layout-preset]').count(), 0)
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

    def test_model_bounds_and_fixed_divisions_follow_model_changes(self):
        choices = self.configure()
        page = self.page
        self.step(1)
        bounds = page.evaluate(f'({{min:{ROOT}.selectedModel.minWidth*2,max:{ROOT}.selectedModel.maxWidth*2}})')
        for value, rule, limit in [(bounds['min']-1, 'min', bounds['min']), (bounds['max']+1, 'max', bounds['max'])]:
            page.locator('#builder-openingWidth').fill(str(value))
            self.step(2)
            error = self.field_error('builder-openingWidth')
            error.wait_for(state='visible')
            self.assertEqual(error.inner_text(), page.evaluate(f"{ROOT}.issueMessage({{code:'{rule}',field:'builder-openingWidth',limit:{limit}}})"))
            self.assertEqual(page.evaluate(f'{ROOT}.activeStep'), 1)
        page.locator('#builder-openingWidth').fill(str(choices['width']))
        page.locator('#builder-openingHeight').focus()
        page.wait_for_function(f'{ROOT}.previewConfiguration.openingWidth==={choices["width"]}')
        smaller = page.evaluate(f'{ROOT}.catalog.models.find(m=>m.partitionCount+1 < {choices["panels"]} && m.variants.some(v=>v.hasPartition))?.id')
        if smaller is not None:
            before = page.evaluate(f'JSON.stringify({ROOT}.previewConfiguration)')
            self.select('builder-model', str(smaller))
            page.locator('[data-cancel-replacement]').click()
            page.wait_for_function(f'!{ROOT}.formBusy')
            self.assertEqual(self.selected_value('builder-model'), str(choices['model']))
            self.assertEqual(page.evaluate(f'JSON.stringify({ROOT}.previewConfiguration)'), before)
            self.select('builder-model', str(smaller))
            page.locator('[data-confirm-replacement]').click()
            page.wait_for_function(f'{ROOT}.previewConfiguration.modelId==={smaller}')
            count = page.evaluate(f'{ROOT}.selectedModel.partitionCount+1')
            self.assertEqual(page.evaluate(f'{ROOT}.previewConfiguration.panelWeights'), [1]*count)
            self.assertEqual(page.locator('.builder-preview-panel').count(), count*2)
        self.assertEqual(page.locator('[data-layout-preset]').count(), 0)

    def test_infill_select_searches_clears_and_follows_the_selected_panel(self):
        choices = self.configure()
        page = self.page
        infill_id = choices['infills'][0]
        code = page.evaluate(f'{ROOT}.catalog.infills.find(i=>i.id==={infill_id}).code')
        page.locator('#builder-panel-0').click()
        page.locator('.fi-select-input-search-ctn input:visible').fill(code)
        option = page.locator(f'[role=option][data-value="{infill_id}"]:visible')
        option.wait_for(state='visible')
        self.assertIn(code, option.inner_text())
        swatch=option.locator('[aria-hidden=true]')
        self.assertTrue(swatch.is_visible())
        self.assertIn('background:', swatch.get_attribute('style'))
        option.click()
        page.wait_for_function(f'{ROOT}.previewConfiguration.infillIdsByDoor[0][0]==={infill_id}')
        page.locator('#builder-panel-0').locator('..').locator('.fi-select-input-value-remove-btn').click()
        page.wait_for_function(f'{ROOT}.previewConfiguration.infillIdsByDoor[0][0]===null')
        page.locator('[data-preview-panel-control]').first.click()
        page.locator('[data-preview-panel-control]').first.click()
        page.wait_for_function(f'{ROOT}.infillState[0]===null')
        self.assertIsNone(page.evaluate(f'{ROOT}.previewConfiguration.infillIdsByDoor[1][0]'))
        self.assertEqual(page.locator('[data-infill-id]').count(), 0)

    def test_database_materials_render_local_svg_paints(self):
        self.configure()
        page = self.page
        records = page.evaluate(f'{ROOT}.catalog.infills')
        samples = []
        textures = set()
        for infill in records:
            page.evaluate(f"""id => {{
                const b={ROOT};
                b.previewConfiguration={{...b.previewConfiguration,infillIdsByDoor:b.previewConfiguration.infillIdsByDoor.map(row=>row.map(()=>id))}};
                b.updatePreview();
            }}""", infill['id'])
            page.wait_for_function(f'{ROOT}.previewConfiguration.infillIdsByDoor[0][0]==={infill["id"]}')
            visual = page.evaluate(f'{ROOT}.infillVisual({infill["id"]})')
            textures.add(visual['texture'])
            fills = page.locator('.builder-preview-panel').evaluate_all('els=>els.map(el=>el.getAttribute("fill"))')
            for fill in fills:
                if visual['texture'] == 'solid':
                    self.assertEqual(fill, visual['fill'])
                else:
                    self.assertTrue(fill.startswith('url(#builder-material-'))
                    paint_id = fill[5:-1]
                    self.assertEqual(page.locator(f'.builder-preview-svg defs [id="{paint_id}"]').count(), 1)
            self.assertEqual(page.locator('.builder-preview-svg image, .builder-preview-svg script').count(), 0)
            if visual['texture'] not in [sample['texture'] for sample in samples] or len(samples) < 3:
                samples.append({'texture':visual['texture'], 'name':infill['description'], 'svg':page.locator('.builder-preview-svg').evaluate('el=>el.outerHTML')})
        self.assertTrue({'solid','wood_straight','wood_cathedral','glass','mirror'}.issubset(textures))
        if os.environ.get('BUILDER_TEST_ARTIFACTS'):
            directory = Path(os.environ['BUILDER_TEST_ARTIFACTS'])
            directory.mkdir(parents=True, exist_ok=True)
            gallery = self.context.new_page()
            cards = ''.join('<article><h3>'+html.escape(sample['name'])+'</h3>'+sample['svg']+'</article>' for sample in samples)
            gallery.set_viewport_size({'width':1440,'height':1100})
            gallery.set_content('<style>body{font:14px sans-serif;margin:20px;background:#eef0ed;color:#333}main{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}article{background:white;padding:16px;border-radius:10px}h3{min-height:50px;font-size:14px}svg{position:static!important;width:100%;height:260px;color:#444}.builder-preview-panel-highlight{visibility:hidden}.builder-preview-annotation,.builder-preview-door-label{fill:currentColor}</style><main>'+cards+'</main>')
            gallery.screenshot(path=str(directory / 'infill-materials.png'), full_page=True)
            gallery.close()

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
        choices = self.configure()
        page.wait_for_function(f"{ROOT}.draftStatus==='unavailable'")
        self.assertTrue(page.evaluate(f'{ROOT}.canReview'))


if __name__ == '__main__':
    unittest.main()
