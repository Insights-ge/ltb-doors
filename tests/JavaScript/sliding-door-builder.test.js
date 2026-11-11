import assert from 'node:assert/strict';
import test from 'node:test';
import { reconcilePreviewConfiguration, reconcilePreviewSelection, previewSelectionTargets } from '../../resources/js/sliding-door-selection.js';
import { createPreviewScene } from '../../resources/js/sliding-door-preview.js';
import { applyModelLayout } from '../../resources/js/sliding-door-state.js';
import slidingDoorBuilder from '../../resources/js/sliding-door-builder.js';

function createBuilder(validationLabels = {}, fieldValidationMessages = {}) {
    const builder = slidingDoorBuilder({
        initialStep: 1,
        validationLabels,
        fieldValidationMessages,
        payload: {
            catalog: { models: [{id:1, partitionCount:2, minWidth:500, maxWidth:1100, minHeight:1600, maxHeight:2600, variants:[{id:11,frameDesignCode:'A',color:'black',hasPartition:true,components:[]}]}], infills: [] },
            metadata: {
                frameColors: [{ color: 'black', label: 'Black', visual: { fill: '#202020' } }],
                infillVisuals: [{ infillId: 7, visual: { fill: '#AABBCC' } }],
                neutralVisual: { fill: '#B8B8B8' },
            },
        },
    });
    builder.previewConfiguration = { modelId:1, openingWidth: 800, openingHeight: 2400, frameColor: 'black', panelWeights: [1,1,1] };
    builder.sizeInputs = { openingWidth: '800', openingHeight: '2400' };
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


function editingBuilder(validationLabels = {}, fieldValidationMessages = {}) {
    const builder = createBuilder(validationLabels, fieldValidationMessages);
    builder.$dispatch = () => {};
    builder.catalog.infills = [{ id: 7, code: 'A', description: 'Mapped' }, { id: 8, code: 'B', description: 'Neutral' }];
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
    builder.requestConfigurationChange(applyModelLayout(builder.previewConfiguration, {partitionCount:1}));
    builder.confirmConfigurationChange();
    let config = reconcilePreviewConfiguration(builder.previewConfiguration, original, () => 'unused');
    assert.deepEqual(config.infillIdsByDoor, [[7,8], [8,7]]);
    assert.deepEqual(config.panelIdsByDoor, original.panelIdsByDoor.map(ids => ids.slice(0,2)));
    const selection = reconcilePreviewSelection(builder.previewSelection, previewSelectionTargets(config));
    assert.deepEqual(selection, { doorId: original.doorIds[1], panelId: null });
    builder.previewConfiguration = config;
    builder.requestConfigurationChange(applyModelLayout(builder.previewConfiguration, {partitionCount:2}));
    let id = 0;
    const expanded = reconcilePreviewConfiguration(builder.previewConfiguration, config, () => `new-${++id}`);
    assert.deepEqual(expanded.infillIdsByDoor, [[7,8,null], [8,7,null]]);
    assert.deepEqual(expanded.doorIds, original.doorIds);
    for (let index = 0; index < 2; index++) {
        assert.equal(expanded.panelIdsByDoor[index][0], original.panelIdsByDoor[index][0]);
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
    builder.requestConfigurationChange(applyModelLayout(builder.previewConfiguration, {partitionCount:1}));
    assert.equal(builder.previewConfiguration.panelWeights.length, 2);
    builder.confirmConfigurationChange();
    assert.deepEqual(builder.previewConfiguration.infillIdsByDoor, [[7,8], [7,8]]);
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
    builder.requestConfigurationChange(applyModelLayout(builder.previewConfiguration, {partitionCount:1}));
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
    assert.ok(builder.validation.errors.some(issue => issue.field === 'builder-openingWidth'));
    builder.goToStep(99);
    assert.equal(builder.activeStep, 1);
});


test('incomplete measurement entry retains committed geometry but blocks forward navigation', () => {
    const builder = editingBuilder();
    builder.setMeasurement('openingWidth', '12e');
    assert.equal(builder.previewConfiguration.openingWidth, 800);
    assert.equal(builder.sizeInputs.openingWidth, '12e');
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
    assert.deepEqual(builder.previewConfiguration.panelWeights, []);
    assert.deepEqual(builder.previewSelection, {doorId:null, panelId:null});
    assert.equal(builder.activeStep, 1);
});


test('invalid review requests route back to a correctable issue and preserve the design', () => {
    const builder = editingBuilder();
    const before = structuredClone(builder.previewConfiguration);
    builder.goToStep(4);
    assert.notEqual(builder.activeStep, 4);
    assert.ok(builder.attemptedSteps.includes(1));
    assert.deepEqual(builder.previewConfiguration, before);
});


function sequentialBuilder() {
    const builder = editingBuilder({}, {
        'builder-openingWidth': { required: 'Width is required.', numeric: 'Width must be a number.', gt: 'Width must be greater than zero.' },
        'builder-openingHeight': { required: 'Height is required.' },
        'builder-model': { required: 'Choose a system.' },
        'builder-design': { required: 'Choose a design.' },
        'builder-variant': { required: 'Choose a variant.' },
        'builder-finishes': { required: 'Choose a finish.' },
    });
    builder.catalog.models = [{ id: 1, name: 'System', partitionCount:2,minWidth:500,maxWidth:1100,minHeight:1600,maxHeight:2600, variants: [
        { id: 11, frameDesignCode: 'A', color: 'black', hasPartition: true, hasSoftCloseMechanism: false, components: [] },
    ] }];
    return builder;
}

test('sections advance sequentially with errors only on the attempted fields', () => {
    const builder = sequentialBuilder();
    builder.goToStep(4);
    assert.equal(builder.activeStep, 2);
    assert.equal(builder.fieldError('builder-model'), '');
    assert.equal(builder.fieldError('builder-finishes'), '');
    builder.goToStep(3);
    assert.equal(builder.activeStep, 2);
    assert.equal(builder.fieldError('builder-design'), 'Choose a design.');
    assert.equal(builder.fieldError('builder-variant'), '');
    builder.selectModel(1);
    assert.equal(builder.fieldError('builder-model'), '');
    assert.equal(builder.fieldError('builder-design'), 'Choose a design.');
    builder.goToStep(3);
    assert.equal(builder.activeStep, 2);
    builder.selectFrameDesign('A');
    assert.equal(builder.fieldError('builder-design'), '');
    assert.equal(builder.fieldError('builder-variant'), 'Choose a variant.');
    builder.selectVariant(11);
    builder.goToStep(3);
    assert.equal(builder.activeStep, 3);
    assert.equal(builder.fieldError('builder-finishes'), '');
    builder.goToStep(4);
    assert.equal(builder.activeStep, 3);
    assert.equal(builder.fieldError('builder-finishes'), 'Choose a finish.');
    builder.chooseInfill(7);
    assert.equal(builder.fieldError('builder-finishes'), '');
    builder.goToStep(4);
    assert.equal(builder.activeStep, 3);
    assert.equal(builder.selectedPreviewPanel.index, 1);
    assert.equal(builder.fieldError('builder-finishes'), 'Choose a finish.');
    builder.chooseInfill(7);
    builder.applyFinishToAllDoors();
    builder.goToStep(4);
    assert.equal(builder.activeStep, 4);
    const before = structuredClone(builder.previewConfiguration);
    builder.goToStep(3);
    builder.goToStep(2);
    builder.goToStep(1);
    assert.deepEqual(builder.previewConfiguration, before);
});

test('Next validates unblurred input and cannot reuse a previously committed size', () => {
    const builder = sequentialBuilder();
    builder.sizeInputs.openingWidth = '';
    builder.goToStep(2);
    assert.equal(builder.activeStep, 1);
    assert.equal(builder.previewConfiguration.openingWidth, 800);
    assert.equal(builder.fieldError('builder-openingWidth'), 'Width is required.');
    assert.equal(builder.fieldError('builder-model'), '');
    builder.sizeInputs.openingWidth = '900';
    builder.goToStep(2);
    assert.equal(builder.activeStep, 2);
    assert.equal(builder.previewConfiguration.openingWidth, 900);
    assert.equal(builder.fieldError('builder-openingWidth'), '');
});


test('invalid draft text replaces the committed-size error with the matching field rule', () => {
    const builder = sequentialBuilder();
    for (const [value, message] of [['', 'Width is required.'], ['abc', 'Width must be a number.'], ['0', 'Width must be greater than zero.'], ['-12', 'Width must be greater than zero.']]) {
        builder.setMeasurement('openingWidth', value);
        assert.equal(builder.previewConfiguration.openingWidth, 800);
        assert.equal(builder.fieldError('builder-openingWidth'), message);
        assert.equal(builder.validation.errors.filter(issue => issue.field === 'builder-openingWidth').length, 1);
    }
    builder.setMeasurement('openingWidth', '900');
    assert.equal(builder.fieldError('builder-openingWidth'), '');
});

test('native model changes derive both doors and cancelling restores the previous model', async () => {
    const builder = editingBuilder();
    builder.catalog.models.push({...builder.catalog.models[0],id:2,partitionCount:1});
    builder.previewConfiguration.infillIdsByDoor = [[7,8,7],[8,7,8]];
    const before = structuredClone(builder.previewConfiguration);
    builder.formState = {openingWidth:'800',openingHeight:'2400',modelId:2,variantId:null,frameDesignCode:null,softClose:false};
    builder.$wire = {restoreBuilderFields:async () => {}};
    builder.applyFormFields();
    assert.equal(builder.pendingChange.kind,'model');
    assert.deepEqual(builder.previewConfiguration,before);
    builder.cancelConfigurationChange();
    await Promise.resolve();
    assert.equal(builder.formState.modelId,1);
    assert.deepEqual(builder.previewConfiguration,before);
    builder.formState.modelId=2;
    builder.applyFormFields();
    builder.confirmConfigurationChange();
    assert.equal(builder.previewConfiguration.modelId,2);
    assert.deepEqual(builder.previewConfiguration.panelWeights,[1,1]);
    assert.deepEqual(builder.previewConfiguration.infillIdsByDoor,[[7,8],[8,7]]);
    builder.formState.modelId=1;
    builder.applyFormFields();
    assert.equal(builder.pendingChange,null);
    assert.deepEqual(builder.previewConfiguration.panelWeights,[1,1,1]);
    assert.deepEqual(builder.previewConfiguration.infillIdsByDoor,[[7,8,null],[8,7,null]]);
});
