import assert from 'node:assert/strict';
import test from 'node:test';
import { validateBuilder } from '../../resources/js/sliding-door-rules.js';

function fixture() {
    return {
        config: { openingWidth: 3000, openingHeight: 2400, modelId: 1, variantId: 2, frameDesignCode: 'A', frameColor:'black', panelWeights:[1], infillIdsByDoor:[[7],[7]], softClose:false },
        catalog: { models:[{id:1, variants:[{id:2, frameDesignCode:'A', color:'black', components:[], hasPartition:false, hasSoftCloseMechanism:false}]}], infills:[{id:7}] },
        metadata: {layoutPresets:[{id:'full_panel',panelWeights:[1]},{id:'two_horizontal_panels',panelWeights:[1,1]}]},
    };
}

test('validation distinguishes actionable blockers from unresolved sizing assumptions', () => {
    const { config, catalog, metadata } = fixture();
    const result = validateBuilder(config,catalog,metadata);
    assert.deepEqual(result.errors, []);
    assert.equal(result.assumptions[0].code, 'sizing_unconfirmed');
    config.openingWidth = 0;
    config.infillIdsByDoor[1][0] = 999;
    const invalid = validateBuilder(config,catalog,metadata);
    assert.ok(invalid.errors.some(issue => issue.field === 'builder-openingWidth' && issue.step === 1));
    assert.ok(invalid.errors.some(issue => issue.doorIndex === 1 && issue.panelIndex === 0));
});


test('exclusions apply to every used component and optional components only when used', () => {
    const { config, catalog, metadata } = fixture();
    const variant = catalog.models[0].variants[0];
    for (const type of ['side_profile','top_rail','bottom_rail']) {
        variant.components = [{type, incompatibleInfillIds:[7]}];
        assert.equal(validateBuilder(config,catalog,metadata).errors.filter(issue => issue.code === 'finish_incompatible').length, 2);
    }
    variant.components = [{type:'partition',incompatibleInfillIds:[7]}, {type:'soft_close_mechanism',incompatibleInfillIds:[7]}];
    assert.deepEqual(validateBuilder(config,catalog,metadata).errors, []);
    config.softClose = true;
    assert.equal(validateBuilder(config,catalog,metadata).errors.filter(issue => issue.code === 'finish_incompatible').length, 2);
    config.softClose = false;
    variant.hasPartition = true;
    config.panelWeights = [1,1];
    config.infillIdsByDoor = [[7,7],[7,7]];
    assert.equal(validateBuilder(config,catalog,metadata).errors.filter(issue => issue.code === 'finish_incompatible').length, 4);
    variant.components.forEach(component => component.incompatibleInfillIds = []);
    assert.deepEqual(validateBuilder(config,catalog,metadata).errors, []);
});


test('an upstream variant without partitions invalidates a retained divided layout', () => {
    const {config,catalog,metadata} = fixture();
    config.panelWeights = [1,1];
    config.infillIdsByDoor = [[7,7],[7,7]];
    const before = structuredClone(config);
    assert.ok(validateBuilder(config,catalog,metadata).errors.some(issue => issue.code === 'partition_missing'));
    assert.deepEqual(config,before);
    catalog.models[0].variants[0].hasPartition = true;
    assert.deepEqual(validateBuilder(config,catalog,metadata).errors, []);
});


test('soft close is a provisional preference and unavailable hardware blocks validation', () => {
    const {config,catalog,metadata} = fixture();
    config.softClose = true;
    let result = validateBuilder(config,catalog,metadata);
    assert.ok(result.errors.some(issue => issue.code === 'soft_close_missing'));
    catalog.models[0].variants[0].hasSoftCloseMechanism = true;
    result = validateBuilder(config,catalog,metadata);
    assert.deepEqual(result.errors, []);
    assert.ok(result.assumptions.some(issue => issue.code === 'soft_close_provisional'));
});


test('unconfirmed catalog measurement scope does not invent opening or half-door limits', () => {
    const {config,catalog,metadata} = fixture();
    Object.assign(catalog.models[0], {minWidth:600,maxWidth:1000,minHeight:2000,maxHeight:2500});
    Object.assign(catalog.models[0].variants[0], {heightRangeMin:2100,heightRangeMax:2300});
    for (const [width,height] of [[599,1999],[600,2000],[1000,2500],[1001,2501]]) {
        config.openingWidth=width; config.openingHeight=height;
        assert.deepEqual(validateBuilder(config,catalog,metadata).errors, []);
    }
});


test('discouraged height references remain advisory while their scope is unconfirmed', () => {
    const {config,catalog,metadata} = fixture();
    Object.assign(catalog.models[0], {notRecommendedHeightMin:2200,notRecommendedHeightMax:2500});
    for (const height of [2199,2200,2400,2500,2501]) {
        config.openingHeight=height;
        const result=validateBuilder(config,catalog,metadata);
        assert.deepEqual(result.errors, []);
        assert.equal(result.advisories[0].code, 'discouraged_height_reference');
    }
    catalog.models[0].notRecommendedHeightMin=null;
    assert.deepEqual(validateBuilder(config,catalog,metadata).advisories, []);
});
