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
