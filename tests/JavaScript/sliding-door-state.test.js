import assert from 'node:assert/strict';
import test from 'node:test';
import { createInitialConfiguration, parseMeasurement, measurementError, modelInfillWeights, applyModelLayout, availableDoorCounts } from '../../resources/js/sliding-door-state.js';

test('new configurations contain no fabricated product or dimension choices and do not share mutable state', () => {
    const first = createInitialConfiguration();
    const second = createInitialConfiguration();
    assert.equal(first.openingWidth, null);
    assert.equal(first.variantId, null);
    first.infillWeights.push(1);
    assert.deepEqual(second.infillWeights, []);
});

test('measurements accept decimal millimetres and reject missing, nonfinite and nonpositive input', () => {
    for (const input of ['', ' ', null, undefined, '1e999', '-1', '0', '0x10', 'Infinity', '12 mm']) assert.equal(parseMeasurement(input), null);
    for (const [input, expected] of [['2400',2400], [' 10.5 ',10.5], ['12,5',12.5], ['0.1',0.1]]) assert.equal(parseMeasurement(input), expected);
});


test('committed finite numeric measurements retain validity even when their string uses exponent notation', () => {
    assert.equal(parseMeasurement(1e22),1e22);
    assert.equal(parseMeasurement(1e-10),1e-10);
    assert.equal(parseMeasurement(Infinity),null);
});


test('measurement errors distinguish required, numeric and greater-than-zero rules', () => {
    for (const value of ['', ' ', null, undefined]) assert.equal(measurementError(value), 'required');
    for (const value of ['abc', '12e', '0x10', NaN, Infinity, '1e999']) assert.equal(measurementError(value), 'numeric');
    for (const value of ['0', '-12', '-0.5', 0, -12]) assert.equal(measurementError(value), 'gt');
    for (const value of ['.5', '12.', '+12', '1e3', '12,5', 2400]) assert.equal(measurementError(value), null);
});


for (const partitionCount of [1,2,3,4]) {
    test(`${partitionCount} model partitions produce ${partitionCount+1} equal infills on each door`, () => {
        assert.deepEqual(modelInfillWeights({partitionCount}), Array(partitionCount+1).fill(1));
        const result=applyModelLayout({doorCount:2,infillWeights:[],openingWidth:800}, {partitionCount});
        assert.equal(result.infillWeights.length, partitionCount+1);
        assert.deepEqual(result.infillIdsByDoor, [Array(partitionCount+1).fill(null), Array(partitionCount+1).fill(null)]);
        assert.equal(result.openingWidth,800);
    });
}

test('model layout remapping preserves matching infills and does not resurrect removed finishes', () => {
    const source={doorCount:2,infillWeights:[1,1,1,1,1],doorIds:['d1','d2'],infillInstanceIdsByDoor:[['a','b','c','d','e'],['f','g','h','i','j']],infillIdsByDoor:[[7,8,7,8,7],[8,7,8,7,8]]};
    const before=structuredClone(source);
    const smaller=applyModelLayout(source,{partitionCount:1});
    assert.deepEqual(smaller.infillIdsByDoor,[[7,8],[8,7]]);
    assert.deepEqual(smaller.infillInstanceIdsByDoor,[['a','b'],['f','g']]);
    assert.deepEqual(smaller.doorIds,source.doorIds);
    const larger=applyModelLayout(smaller,{partitionCount:4});
    assert.deepEqual(larger.infillIdsByDoor,[[7,8,null,null,null],[8,7,null,null,null]]);
    assert.deepEqual(source,before);
});

test('door suggestions use inclusive per-door widths, model heights and usable variants', () => {
    const model={minWidth:600,maxWidth:1000,minHeight:1600,maxHeight:2600,variants:[{hasPartition:true,heightRangeMin:1800,heightRangeMax:2400}]};
    assert.deepEqual(availableDoorCounts([model],3000,2400),[3,4,5]);
    assert.deepEqual(availableDoorCounts([model],1200,1800),[2]);
    assert.deepEqual(availableDoorCounts([model],5000,2400),[5]);
    for (const [width,height] of [[1199,2000],[5001,2000],[3000,1799],[3000,2401],['',2000],['abc',2000],[Infinity,2000]]) {
        assert.deepEqual(availableDoorCounts([model],width,height),[]);
    }
    assert.deepEqual(availableDoorCounts([{...model,variants:[{...model.variants[0],hasPartition:false}]}],3000,2000),[]);
    assert.deepEqual(availableDoorCounts([],3000,2000),[]);
});
