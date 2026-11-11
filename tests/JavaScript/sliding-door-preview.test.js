import assert from 'node:assert/strict';
import test from 'node:test';
import { reconcilePreviewConfiguration } from '../../resources/js/sliding-door-selection.js';
import { createPreviewScene } from '../../resources/js/sliding-door-preview.js';

function metadata() {
    return {
        frameColors: [{ color: 'black', label: 'Black', visual: { fill: '#202020' } }],
        infillVisuals: [{ infillId: 7, visual: { fill: '#AABBCC' } }],
        neutralVisual: { fill: '#B8B8B8' },
    };
}

function configuration() {
    return {
        openingWidth: 3000,
        openingHeight: 2400,
        doorCount: 3,
        panelWeightsByDoor: [[1], [1, 1], [1, 1, 1]],
        frameColor: 'black',
        infillIdsByDoor: [[7], [7, null], [null, 7, 999]],
    };
}

test('preview scenes preserve geometry and resolve fills per panel', () => {
    const scene = createPreviewScene(configuration(), metadata(), { width: 800, height: 500 });

    assert.equal(scene.doors.length, 3);
    assert.deepEqual(scene.doors.map((door) => door.panels.length), [1, 2, 3]);
    assert.equal(scene.frameFill, '#202020');
    assert.deepEqual(scene.doors.map((door) => door.panels.map((panel) => panel.fill)), [
        ['#AABBCC'], ['#AABBCC', '#B8B8B8'], ['#B8B8B8', '#AABBCC', '#B8B8B8'],
    ]);
    assert.equal(scene.opening.width / scene.opening.height, 3000 / 2400);
    assert.ok(scene.opening.x >= 48 && scene.opening.y >= 48);
    assert.ok(scene.opening.x + scene.opening.width <= 752);
    assert.ok(scene.opening.y + scene.opening.height <= 452);
});

test('unknown frame colours and unselected infills use neutral visuals', () => {
    const input = configuration();
    delete input.frameColor;
    delete input.infillIdsByDoor;
    const scene = createPreviewScene(input, metadata(), { width: 320, height: 300 });

    assert.equal(scene.frameFill, '#B8B8B8');
    for (const door of scene.doors) {
        door.panels.forEach((panel) => assert.equal(panel.fill, '#B8B8B8'));
    }
});

test('visual frame widths shrink for thin doors without altering opening proportions', () => {
    const input = { ...configuration(), openingWidth: 1, openingHeight: 1000000 };
    const scene = createPreviewScene(input, metadata(), { width: 320, height: 300 });

    for (const door of scene.doors) {
        assert.ok(door.frameWidth > 0 && door.frameWidth <= door.width / 12);
    }
    assert.ok(Math.abs(scene.opening.width / scene.opening.height - 1e-6) < 1e-18);
});

test('scene generation never modifies configuration or catalog metadata', () => {
    const input = configuration();
    const visuals = metadata();
    const before = structuredClone({ input, visuals });
    createPreviewScene(input, visuals, { width: 800, height: 500 });

    assert.deepEqual({ input, visuals }, before);
});

test('incomplete geometry does not produce a fabricated preview', () => {
    const input = configuration();
    input.openingWidth = 0;
    assert.throws(() => createPreviewScene(input, metadata(), { width: 800, height: 500 }), RangeError);
});

// Exercise the builder configuration path, not just the generic geometry API.
for (const [name, width, height, weights] of [
    ['full', 3000, 2400, [1]],
    ['two equal', 2400, 2400, [1, 1]],
    ['three equal', 1200, 2600, [1, 1, 1]],
    ['unequal', 4200, 1800, [1, 2, 1]],
    ['extremely wide', 1000000, 1, [1, 1, 1]],
    ['extremely tall', 1, 1000000, [1, 1]],
]) {
    for (const viewport of [{ width: 300, height: 360 }, { width: 1000, height: 500 }]) {
        test(`${name} builder layout retains two doors, proportions and bounds at ${viewport.width}px`, () => {
            let id = 0;
            const input = reconcilePreviewConfiguration({
                openingWidth: width, openingHeight: height, panelWeights: weights,
                // Conflicting generic inputs must not override the builder's two-door rule.
                doorCount: 5, panelWeightsByDoor: [[1]],
            }, null, () => `geometry-${++id}`);
            const scene = createPreviewScene(input, metadata(), viewport);
            const close = (actual, expected) => assert.ok(
                Math.abs(actual - expected) <= 1e-12 * Math.max(Math.abs(expected), Number.MIN_VALUE),
                `${actual} differs from ${expected}`,
            );
            const contains = (outer, inner) => {
                const tolerance = 1e-10;
                for (const key of ['x', 'y', 'width', 'height']) assert.ok(Number.isFinite(inner[key]));
                assert.ok(inner.width > 0 && inner.height > 0);
                assert.ok(inner.x >= outer.x - tolerance && inner.y >= outer.y - tolerance);
                assert.ok(inner.x + inner.width <= outer.x + outer.width + tolerance);
                assert.ok(inner.y + inner.height <= outer.y + outer.height + tolerance);
            };
            assert.equal(scene.doors.length, 2);
            assert.deepEqual(scene.doors.map(door => door.panels.length), [weights.length, weights.length]);
            close(scene.opening.width / scene.opening.height, width / height);
            contains({ x: 48, y: 48, width: viewport.width - 96, height: viewport.height - 96 }, scene.opening);
            close(scene.doors[0].x + scene.doors[0].width, scene.doors[1].x);
            close(scene.doors[1].x + scene.doors[1].width, scene.opening.x + scene.opening.width);
            for (const door of scene.doors) {
                contains(scene.opening, door);
                close(door.width / scene.opening.width, 0.5);
                close(door.panels.reduce((sum, panel) => sum + panel.height, 0), door.height);
                for (const [index, panel] of door.panels.entries()) {
                    contains(door, panel);
                    assert.equal(panel.id, input.panelIdsByDoor[door.index][index]);
                    close(panel.height / door.height, weights[index] / weights.reduce((a, b) => a + b, 0));
                    if (index > 0) close(door.panels[index - 1].y + door.panels[index - 1].height, panel.y);
                }
            }
        });
    }
}

test('database material textures reach the scene without changing panel geometry', () => {
    const input = configuration();
    const visuals = metadata();
    const before = createPreviewScene(input, visuals, {width:600,height:400});
    visuals.infillVisuals[0].visual.texture='wood_straight';
    const after = createPreviewScene(input, visuals, {width:600,height:400});
    for (let door=0;door<before.doors.length;door++) {
        for (let panel=0;panel<before.doors[door].panels.length;panel++) {
            const original=before.doors[door].panels[panel];
            const textured=after.doors[door].panels[panel];
            assert.deepEqual({...textured,texture:null},original);
            assert.equal(textured.texture, textured.fill==='#AABBCC' ? 'wood_straight' : null);
        }
    }
});
