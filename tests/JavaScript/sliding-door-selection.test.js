import assert from 'node:assert/strict';
import test from 'node:test';
import { reconcilePreviewConfiguration, previewSelectionTargets, reconcilePreviewSelection } from '../../resources/js/sliding-door-selection.js';
import slidingDoorBuilder from '../../resources/js/sliding-door-builder.js';

function configuration() {
    return {
        openingWidth: 3000, openingHeight: 2400, doorCount: 2,
        infillWeights: [1, 1],
    };
}

function identifiers() {
    let counter = 0;
    return () => `instance-${++counter}`;
}

test('instance IDs are unique and input configuration remains unchanged', () => {
    const input = configuration();
    const before = structuredClone(input);
    const normalized = reconcilePreviewConfiguration(input, null, identifiers());
    const ids = [...normalized.doorIds, ...normalized.infillInstanceIdsByDoor.flat()];

    assert.equal(new Set(ids).size, 6);
    assert.deepEqual(input, before);
    assert.deepEqual(normalized.infillWeightsByDoor, [[1,1],[1,1]]);
    assert.equal(reconcilePreviewConfiguration(normalized, normalized), normalized);
});

test('resizing preserves both doors and their existing infill identities', () => {
    const nextId = identifiers();
    const original = reconcilePreviewConfiguration(configuration(), null, nextId);
    const updated = reconcilePreviewConfiguration({
        ...configuration(), openingWidth: 4000,
    }, original, nextId);

    assert.deepEqual(updated.doorIds.slice(0, 2), original.doorIds);
    assert.deepEqual(updated.infillInstanceIdsByDoor.slice(0, 2), original.infillInstanceIdsByDoor);
    assert.equal(updated.doorIds.length, 2);
});

test('explicit door and infill reordering preserves selection by identity', () => {
    const original = reconcilePreviewConfiguration(configuration(), null, identifiers());
    const selected = { doorId: original.doorIds[1], infillInstanceId: original.infillInstanceIdsByDoor[1][1] };
    const reordered = reconcilePreviewConfiguration({
        ...original,
        doorIds: [...original.doorIds].reverse(),
        infillWeightsByDoor: [...original.infillWeightsByDoor].reverse(),
        infillInstanceIdsByDoor: [[...original.infillInstanceIdsByDoor[1]].reverse(), original.infillInstanceIdsByDoor[0]],
    }, original);

    assert.deepEqual(reconcilePreviewSelection(selected, previewSelectionTargets(reordered)), selected);
    assert.equal(previewSelectionTargets(reordered)[0].infills[0].id, selected.infillInstanceId);
});

test('removed doors clear selection and removed infills retain only their parent door', () => {
    const nextId = identifiers();
    const original = reconcilePreviewConfiguration(configuration(), null, nextId);
    const targets = previewSelectionTargets(original);
    const selected = { doorId: targets[1].id, infillInstanceId: targets[1].infills[1].id };

    assert.deepEqual(reconcilePreviewSelection(selected, targets.slice(0, 1)), { doorId: null, infillInstanceId: null });
    const fewerInfills = reconcilePreviewConfiguration({
        ...original, infillWeights: [1],
    }, original, nextId);
    assert.deepEqual(reconcilePreviewSelection(selected, previewSelectionTargets(fewerInfills)), {
        doorId: selected.doorId, infillInstanceId: null,
    });
    assert.equal(reconcilePreviewConfiguration(null, original), null);
    assert.deepEqual(previewSelectionTargets(null), []);
});

test('door count and per-door partition overrides cannot diverge the shared layout', () => {
    const nextId = identifiers();
    const normalized = reconcilePreviewConfiguration({
        ...configuration(),
        doorCount: 4,
        infillWeightsByDoor: [[1], [1, 1, 1], [1], [1]],
    }, null, nextId);

    assert.equal(normalized.doorCount, 4);
    assert.deepEqual(normalized.infillWeightsByDoor, Array.from({length:4}, () => [1,1]));
    assert.equal(normalized.doorIds.length, 4);
    const updated = reconcilePreviewConfiguration({ ...normalized, infillWeights: [1, 1, 1] }, normalized, nextId);
    assert.deepEqual(updated.infillWeightsByDoor, Array.from({length:4}, () => [1,1,1]));
    assert.deepEqual(updated.doorIds, normalized.doorIds);
});

test('duplicate invalid and malformed instance IDs are rejected', () => {
    for (const overrides of [
        { doorIds: ['same', 'same'] },
        { doorIds: ['', 'second'] },
        { doorIds: [1, 'second'] },
        { doorIds: 'invalid' },
        { infillInstanceIdsByDoor: 'invalid' },
        { infillInstanceIdsByDoor: ['invalid'] },
        { infillInstanceIdsByDoor: [['same', 'same']] },
    ]) {
        assert.throws(() => reconcilePreviewConfiguration({ ...configuration(), ...overrides }, null, identifiers()), RangeError);
    }
});

test('clicking a infill on either door selects the shared infill index without changing finishes', () => {
    const config=reconcilePreviewConfiguration(configuration(),null,identifiers());
    const builder=slidingDoorBuilder({initialStep:1,payload:{catalog:{},metadata:{}}});
    builder.previewConfiguration=config;
    builder.previewDoors=previewSelectionTargets(config);
    builder.selectPreviewInfill(builder.previewDoors[1].id,builder.previewDoors[1].infills[1].id);
    assert.deepEqual(builder.previewSelection,{doorId:builder.previewDoors[0].id,infillInstanceId:builder.previewDoors[0].infills[1].id});
    builder.clearPreviewSelection();
    assert.deepEqual(builder.previewSelection,{doorId:null,infillInstanceId:null});
    assert.equal(builder.previewConfiguration,config);
});
