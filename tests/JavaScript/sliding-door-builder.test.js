import assert from 'node:assert/strict';
import test from 'node:test';
import { reconcilePreviewConfiguration, reconcilePreviewSelection, previewSelectionTargets } from '../../resources/js/sliding-door-selection.js';
import { createPreviewScene } from '../../resources/js/sliding-door-preview.js';
import slidingDoorBuilder from '../../resources/js/sliding-door-builder.js';

function createBuilder() {
    const builder = slidingDoorBuilder({
        initialStep: 1,
        payload: {
            catalog: { models: [], infills: [] },
            metadata: {
                frameColors: [{ color: 'black', label: 'Black', visual: { fill: '#202020' } }],
                infillVisuals: [{ infillId: 7, visual: { fill: '#AABBCC' } }],
                neutralVisual: { fill: '#B8B8B8' },
            },
        },
    });
    builder.previewConfiguration = { openingWidth: 3000, openingHeight: 2400, frameColor: 'black', panelWeights: [1,1,1] };
    return builder;
}

test('frame visuals use the mapping or neutral fallback', () => {
    const builder = createBuilder();

    assert.deepEqual(builder.frameVisual('black'), { fill: '#202020' });
    assert.deepEqual(builder.frameVisual('unknown'), { fill: '#B8B8B8' });
    assert.deepEqual(builder.frameVisual(null), { fill: '#B8B8B8' });
});

test('infill visuals use numeric catalog IDs and a neutral fallback', () => {
    const builder = createBuilder();

    assert.deepEqual(builder.infillVisual(7), { fill: '#AABBCC' });
    assert.deepEqual(builder.infillVisual(8), { fill: '#B8B8B8' });
    assert.deepEqual(builder.infillVisual(null), { fill: '#B8B8B8' });
});

test('visual resolution does not add products or change selections', () => {
    const builder = createBuilder();
    const before = JSON.stringify(builder);

    builder.frameVisual('unknown');
    builder.infillVisual(8);

    assert.equal(JSON.stringify(builder), before);
});


test('shared preset choices update both door divisions and preserve unrelated configuration', () => {
    const builder = createBuilder();
    builder.metadata.layoutPresets = [
        { id: 'full_panel', panelWeights: [1] },
        { id: 'two_horizontal_panels', panelWeights: [1, 1] },
        { id: 'three_horizontal_panels', panelWeights: [1, 1, 1] },
    ];
    let id = 0;
    const normalize = () => {
        builder.previewConfiguration = reconcilePreviewConfiguration(builder.previewConfiguration, null, () => `preset-${++id}`);
    };
    normalize();
    const doorIds = [...builder.previewConfiguration.doorIds];
    for (const preset of builder.metadata.layoutPresets) {
        builder.selectLayout(preset.id);
        normalize();
        const config = builder.previewConfiguration;
        const scene = createPreviewScene(config, builder.metadata, { width: 600, height: 400 });
        assert.equal(builder.selectedLayoutId, preset.id);
        assert.deepEqual(config.doorIds, doorIds);
        assert.equal(config.openingWidth, 3000);
        assert.equal(config.openingHeight, 2400);
        assert.equal(config.frameColor, 'black');
        assert.deepEqual(scene.doors.map(door => door.panels.length), [preset.panelWeights.length, preset.panelWeights.length]);
        assert.deepEqual(scene.doors[0].panels.map(panel => panel.y), scene.doors[1].panels.map(panel => panel.y));
        const before = builder.previewConfiguration;
        builder.selectLayout(preset.id);
        assert.equal(builder.previewConfiguration, before);
    }
    assert.deepEqual(builder.metadata.layoutPresets.map(preset => preset.panelWeights), [[1], [1, 1], [1, 1, 1]]);
    const before = builder.previewConfiguration;
    builder.selectLayout('missing');
    assert.equal(builder.previewConfiguration, before);
    builder.previewConfiguration = null;
    builder.selectLayout('full_panel');
    assert.equal(builder.previewConfiguration, null);
    assert.equal(builder.selectedLayoutId, null);
});

function editingBuilder() {
    const builder = createBuilder();
    builder.$dispatch = () => {};
    builder.catalog.infills = [{ id: 7, code: 'A', description: 'Mapped' }, { id: 8, code: 'B', description: 'Neutral' }];
    builder.metadata.layoutPresets = [
        { id: 'full_panel', panelWeights: [1] },
        { id: 'two_horizontal_panels', panelWeights: [1, 1] },
        { id: 'three_horizontal_panels', panelWeights: [1, 1, 1] },
    ];
    let id = 0;
    builder.previewConfiguration = reconcilePreviewConfiguration(builder.previewConfiguration, null, () => `edit-${++id}`);
    builder.previewDoors = builder.previewConfiguration.doorIds.map((doorId, index) => ({
        id: doorId, index,
        panels: builder.previewConfiguration.panelIdsByDoor[index].map((panelId, panelIndex) => ({ id: panelId, index: panelIndex })),
    }));
    builder.selectPreviewPanel(builder.previewDoors[0].id, builder.previewDoors[0].panels[0].id);
    return builder;
}

test('palette accepts catalog finishes for a selected panel and rejects unknown finishes', () => {
    const builder = editingBuilder();
    builder.chooseInfill(8);
    assert.equal(builder.paletteInfillId, 8);
    assert.deepEqual(builder.infillVisual(8), builder.metadata.neutralVisual);
    builder.chooseInfill(999);
    assert.equal(builder.paletteInfillId, 8);
    builder.chooseInfill(null);
    assert.equal(builder.paletteInfillId, null);
    builder.clearPreviewSelection();
    builder.chooseInfill(7);
    assert.equal(builder.paletteInfillId, null);
});


test('finish edits update only the selected panel and render mapped or neutral fills immediately', () => {
    const builder = editingBuilder();
    const original = structuredClone(builder.previewConfiguration);
    builder.chooseInfill(7);
    assert.deepEqual(builder.previewConfiguration.infillIdsByDoor, [[7, null, null], [null, null, null]]);
    let scene = createPreviewScene(builder.previewConfiguration, builder.metadata, { width: 600, height: 400 });
    assert.equal(scene.doors[0].panels[0].fill, '#AABBCC');
    assert.equal(scene.doors[1].panels[0].fill, '#B8B8B8');
    builder.selectPreviewPanel(builder.previewDoors[1].id, builder.previewDoors[1].panels[2].id);
    assert.equal(builder.paletteInfillId, null);
    builder.chooseInfill(8);
    assert.deepEqual(builder.previewConfiguration.infillIdsByDoor, [[7, null, null], [null, null, 8]]);
    builder.selectPreviewPanel(builder.previewDoors[0].id, builder.previewDoors[0].panels[0].id);
    assert.equal(builder.paletteInfillId, 7);
    builder.chooseInfill(null);
    scene = createPreviewScene(builder.previewConfiguration, builder.metadata, { width: 600, height: 400 });
    assert.equal(scene.doors[0].panels[0].fill, '#B8B8B8');
    assert.equal(builder.previewConfiguration.openingWidth, original.openingWidth);
    assert.deepEqual(builder.previewConfiguration.doorIds, original.doorIds);
    assert.deepEqual(builder.previewConfiguration.panelIdsByDoor, original.panelIdsByDoor);
});


test('bulk finish and door copy actions keep independent arrays and stable identities', () => {
    const builder = editingBuilder();
    const ids = structuredClone(builder.previewConfiguration.panelIdsByDoor);
    builder.chooseInfill(7);
    builder.applyFinishToAllDoors();
    assert.deepEqual(builder.previewConfiguration.infillIdsByDoor, [[7,7,7], [7,7,7]]);
    builder.selectPreviewPanel(builder.previewDoors[0].id, builder.previewDoors[0].panels[1].id);
    builder.chooseInfill(8);
    builder.copySelectedDoor();
    builder.confirmConfigurationChange();
    assert.deepEqual(builder.previewConfiguration.infillIdsByDoor, [[7,8,7], [7,8,7]]);
    assert.notEqual(builder.previewConfiguration.infillIdsByDoor[0], builder.previewConfiguration.infillIdsByDoor[1]);
    builder.chooseInfill(null);
    assert.deepEqual(builder.previewConfiguration.infillIdsByDoor, [[7,null,7], [7,8,7]]);
    assert.deepEqual(builder.previewConfiguration.panelIdsByDoor, ids);
    builder.clearPreviewSelection();
    const before = builder.previewConfiguration;
    builder.copySelectedDoor();
    builder.applyFinishToAllDoors();
    assert.equal(builder.previewConfiguration, before);
});


test('layout remapping preserves corresponding finishes and never resurrects removed panels', () => {
    const builder = editingBuilder();
    builder.previewConfiguration.infillIdsByDoor = [[7,8,7], [8,7,8]];
    const original = structuredClone(builder.previewConfiguration);
    builder.selectPreviewPanel(builder.previewDoors[1].id, builder.previewDoors[1].panels[2].id);
    builder.selectLayout('two_horizontal_panels');
    builder.confirmConfigurationChange();
    let config = reconcilePreviewConfiguration(builder.previewConfiguration, original, () => 'unused');
    assert.deepEqual(config.infillIdsByDoor, [[7,8], [8,7]]);
    assert.deepEqual(config.panelIdsByDoor, original.panelIdsByDoor.map(ids => ids.slice(0,2)));
    const selection = reconcilePreviewSelection(builder.previewSelection, previewSelectionTargets(config));
    assert.deepEqual(selection, { doorId: original.doorIds[1], panelId: null });
    builder.previewConfiguration = config;
    builder.selectLayout('full_panel');
    builder.confirmConfigurationChange();
    config = reconcilePreviewConfiguration(builder.previewConfiguration, config, () => 'unused');
    builder.previewConfiguration = config;
    builder.selectLayout('three_horizontal_panels');
    let id = 0;
    const expanded = reconcilePreviewConfiguration(builder.previewConfiguration, config, () => `new-${++id}`);
    assert.deepEqual(expanded.infillIdsByDoor, [[7,null,null], [8,null,null]]);
    assert.deepEqual(expanded.doorIds, original.doorIds);
    for (let index = 0; index < 2; index++) {
        assert.equal(expanded.panelIdsByDoor[index][0], original.panelIdsByDoor[index][0]);
        assert.ok(!original.panelIdsByDoor.flat().includes(expanded.panelIdsByDoor[index][1]));
        assert.ok(!original.panelIdsByDoor.flat().includes(expanded.panelIdsByDoor[index][2]));
    }
});


test('bulk overwrites require confirmation and cancel leaves the design intact', () => {
    const builder = editingBuilder();
    const events = [];
    builder.$dispatch = (name) => events.push(name);
    builder.previewConfiguration.infillIdsByDoor = [[7,8,null], [8,null,7]];
    const before = builder.previewConfiguration;
    builder.applyFinishToAllDoors();
    assert.equal(builder.previewConfiguration, before);
    assert.ok(builder.pendingChange);
    assert.equal(events.at(-1), 'open-modal');
    builder.cancelConfigurationChange();
    assert.equal(builder.pendingChange, null);
    assert.equal(builder.previewConfiguration, before);
    builder.copySelectedDoor();
    assert.equal(builder.previewConfiguration, before);
    builder.confirmConfigurationChange();
    assert.deepEqual(builder.previewConfiguration.infillIdsByDoor, [[7,8,null], [7,8,null]]);
    assert.equal(builder.pendingChange, null);
    builder.selectLayout('full_panel');
    assert.equal(builder.previewConfiguration.panelWeights.length, 3);
    builder.confirmConfigurationChange();
    assert.deepEqual(builder.previewConfiguration.infillIdsByDoor, [[7], [7]]);
});

test('stale confirmations cannot overwrite a newer design', () => {
    const builder = editingBuilder();
    builder.previewConfiguration.infillIdsByDoor = [[7,8,null], [null,null,null]];
    builder.applyFinishToAllDoors();
    assert.ok(builder.pendingChange);
    builder.chooseInfill(8);
    const newer = builder.previewConfiguration;
    builder.confirmConfigurationChange();
    assert.equal(builder.previewConfiguration, newer);
    assert.equal(builder.pendingChange, null);
});

for (const sourceIndex of [0, 1]) {
    test(`copying door ${sourceIndex + 1} preserves source and target identities and only replaces target finishes`, () => {
        const builder = editingBuilder();
        builder.previewConfiguration.infillIdsByDoor = [[7,null,8], [8,7,null]];
        const before = structuredClone(builder.previewConfiguration);
        builder.selectPreviewDoor(builder.previewDoors[sourceIndex].id);
        builder.copySelectedDoor();
        assert.deepEqual(builder.previewConfiguration, before);
        builder.confirmConfigurationChange();
        const expected = before.infillIdsByDoor[sourceIndex];
        assert.deepEqual(builder.previewConfiguration.infillIdsByDoor, [expected, expected]);
        assert.deepEqual(builder.previewConfiguration.doorIds, before.doorIds);
        assert.deepEqual(builder.previewConfiguration.panelIdsByDoor, before.panelIdsByDoor);
        assert.deepEqual(builder.previewConfiguration.panelWeights, before.panelWeights);
        assert.deepEqual(builder.previewSelection, { doorId: before.doorIds[sourceIndex], panelId: null });
    });
}

test('matching finishes and empty targets do not prompt for a replacement', () => {
    const builder = editingBuilder();
    const events = [];
    builder.$dispatch = name => events.push(name);
    builder.chooseInfill(7);
    builder.copySelectedDoor();
    assert.deepEqual(builder.previewConfiguration.infillIdsByDoor, [[7,null,null], [7,null,null]]);
    builder.copySelectedDoor();
    builder.applyFinishToAllDoors();
    assert.deepEqual(builder.previewConfiguration.infillIdsByDoor, [[7,7,7], [7,7,7]]);
    assert.equal(builder.pendingChange, null);
    assert.deepEqual(events, []);
});

test('clearing all finishes is protected and cancelling a smaller layout retains IDs and choices', () => {
    const builder = editingBuilder();
    builder.previewConfiguration.infillIdsByDoor = [[null,7,8], [8,null,7]];
    const before = structuredClone(builder.previewConfiguration);
    builder.selectLayout('full_panel');
    assert.ok(builder.pendingChange);
    builder.cancelConfigurationChange();
    assert.deepEqual(builder.previewConfiguration, before);
    builder.applyFinishToAllDoors();
    assert.ok(builder.pendingChange);
    builder.confirmConfigurationChange();
    assert.deepEqual(builder.previewConfiguration.infillIdsByDoor, [[null,null,null], [null,null,null]]);
    assert.deepEqual(builder.previewConfiguration.panelIdsByDoor, before.panelIdsByDoor);
});

test('size and colour changes preserve independent panel choices and surviving selections', () => {
    const builder = editingBuilder();
    builder.previewConfiguration.infillIdsByDoor = [[7,8,null], [8,null,7]];
    const before = structuredClone(builder.previewConfiguration);
    const selection = { ...builder.previewSelection };
    const resized = reconcilePreviewConfiguration({
        ...before, openingWidth: 4200, openingHeight: 1800, frameColor: 'silver',
    }, before);
    assert.deepEqual(resized.infillIdsByDoor, before.infillIdsByDoor);
    assert.deepEqual(resized.panelIdsByDoor, before.panelIdsByDoor);
    assert.deepEqual(reconcilePreviewSelection(selection, previewSelectionTargets(resized)), selection);
    const scene = createPreviewScene(resized, builder.metadata, { width: 390, height: 300 });
    assert.deepEqual(scene.doors.map(door => door.panels.map(panel => panel.fill)), [
        ['#AABBCC','#B8B8B8','#B8B8B8'], ['#B8B8B8','#B8B8B8','#AABBCC'],
    ]);
});


test('system controls resolve only real design and colour variants and preserve panel edits', () => {
    const builder = editingBuilder();
    builder.catalog.models = [{ id: 1, variants: [{id: 11, frameDesignCode: 'A', color:'black'}, {id:12, frameDesignCode:'B', color:'silver'}] }];
    builder.previewConfiguration.infillIdsByDoor = [[7,null,null],[8,null,null]];
    builder.selectModel('1');
    assert.deepEqual(builder.frameDesigns, ['A','B']);
    builder.selectFrameDesign('A');
    builder.selectVariant('12');
    assert.equal(builder.selectedVariant, null);
    builder.selectVariant('11');
    assert.equal(builder.previewConfiguration.frameColor, 'black');
    builder.selectFrameDesign('B');
    assert.equal(builder.selectedVariant, null);
    builder.selectVariant('12');
    assert.equal(builder.previewConfiguration.frameColor, 'silver');
    assert.deepEqual(builder.previewConfiguration.infillIdsByDoor, [[7,null,null],[8,null,null]]);
});


test('navigation gates incomplete size and product choices without losing panel edits', () => {
    const builder = editingBuilder();
    const before = structuredClone(builder.previewConfiguration);
    builder.goToStep(3);
    assert.equal(builder.activeStep, 2);
    assert.deepEqual(builder.previewConfiguration, before);
    builder.previewConfiguration.openingWidth = null;
    builder.goToStep(2);
    assert.equal(builder.activeStep, 1);
    assert.equal(builder.sizeErrors.openingWidth, true);
    builder.goToStep(99);
    assert.equal(builder.activeStep, 1);
});


test('incomplete measurement entry retains committed geometry but blocks forward navigation', () => {
    const builder = editingBuilder();
    builder.setMeasurement('openingWidth', '12.');
    assert.equal(builder.previewConfiguration.openingWidth, 3000);
    assert.equal(builder.sizeInputs.openingWidth, '12.');
    assert.equal(builder.sizeErrors.openingWidth, true);
    builder.goToStep(2);
    assert.equal(builder.activeStep, 1);
    builder.setMeasurement('openingWidth', '1200.5');
    assert.equal(builder.previewConfiguration.openingWidth, 1200.5);
    assert.equal(builder.sizeErrors.openingWidth, false);
});


test('reset requests confirmation before discarding edits and restores a blank configuration', () => {
    const builder = editingBuilder();
    const events = [];
    builder.$dispatch = (name, detail) => events.push([name, detail.id]);
    const before = structuredClone(builder.previewConfiguration);
    builder.requestReset();
    assert.deepEqual(builder.previewConfiguration, before);
    assert.deepEqual(events[0], ['open-modal','builder-confirm-reset']);
    builder.resetConfiguration();
    assert.equal(builder.previewConfiguration.openingWidth, null);
    assert.equal(builder.previewConfiguration.variantId, null);
    assert.deepEqual(builder.previewConfiguration.panelWeights, [1]);
    assert.deepEqual(builder.previewSelection, {doorId:null, panelId:null});
    assert.equal(builder.activeStep, 1);
});
