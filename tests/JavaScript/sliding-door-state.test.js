import assert from 'node:assert/strict';
import test from 'node:test';
import { createInitialConfiguration } from '../../resources/js/sliding-door-state.js';

test('new configurations contain no fabricated product or dimension choices and do not share mutable state', () => {
    const first = createInitialConfiguration();
    const second = createInitialConfiguration();
    assert.equal(first.openingWidth, null);
    assert.equal(first.variantId, null);
    first.panelWeights.push(1);
    assert.deepEqual(second.panelWeights, [1]);
});
