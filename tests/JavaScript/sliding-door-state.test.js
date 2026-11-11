import assert from 'node:assert/strict';
import test from 'node:test';
import { createInitialConfiguration, parseMeasurement } from '../../resources/js/sliding-door-state.js';

test('new configurations contain no fabricated product or dimension choices and do not share mutable state', () => {
    const first = createInitialConfiguration();
    const second = createInitialConfiguration();
    assert.equal(first.openingWidth, null);
    assert.equal(first.variantId, null);
    first.panelWeights.push(1);
    assert.deepEqual(second.panelWeights, [1]);
});

test('measurements accept decimal millimetres and reject missing, nonfinite and nonpositive input', () => {
    for (const input of ['', ' ', null, undefined, '1e999', '-1', '0', '0x10', 'Infinity', '12 mm']) assert.equal(parseMeasurement(input), null);
    for (const [input, expected] of [['2400',2400], [' 10.5 ',10.5], ['12,5',12.5], ['0.1',0.1]]) assert.equal(parseMeasurement(input), expected);
});
