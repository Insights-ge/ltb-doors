import assert from 'node:assert/strict';
import test from 'node:test';
import { calculateDoorGeometry, fitDoorGeometry } from '../../resources/js/sliding-door-geometry.js';

function geometry(openingWidth = 3000, openingHeight = 2000) {
    return calculateDoorGeometry({
        openingWidth,
        openingHeight,
        doorCount: 3,
        infillWeightsByDoor: [[1], [1, 1], [1, 2, 1]],
    });
}

function approximately(actual, expected) {
    const tolerance = 64 * Number.EPSILON * Math.max(Math.abs(actual), Math.abs(expected), Number.MIN_VALUE);
    assert.ok(Math.abs(actual - expected) <= tolerance, `${actual} differs from ${expected}`);
}

function within(rectangle, viewport) {
    const tolerance = 64 * Number.EPSILON * Math.max(viewport.width, viewport.height);
    assert.ok(rectangle.x >= viewport.padding - tolerance);
    assert.ok(rectangle.y >= viewport.padding - tolerance);
    assert.ok(rectangle.x + rectangle.width <= viewport.width - viewport.padding + tolerance);
    assert.ok(rectangle.y + rectangle.height <= viewport.height - viewport.padding + tolerance);
    assert.ok(rectangle.width > 0 && rectangle.height > 0);
    for (const key of ['x', 'y', 'width', 'height']) {
        assert.ok(Number.isFinite(rectangle[key]));
    }
}

test('wide and tall designs are centered with equal scale on both axes', () => {
    const viewport = { width: 800, height: 600, padding: 20 };
    const wide = fitDoorGeometry(geometry(4000, 2000), viewport);
    const tall = fitDoorGeometry(geometry(1000, 2000), viewport);

    assert.deepEqual(wide.opening, { x: 20, y: 110, width: 760, height: 380 });
    assert.equal(wide.scale, 380);
    assert.deepEqual(tall.opening, { x: 260, y: 20, width: 280, height: 560 });
    assert.equal(tall.scale, 560);
});

for (const [openingWidth, openingHeight] of [[2400, 2400], [1000000, 1], [1, 1000000], [1e12, 1], [1, 1e12]]) {
    for (const viewport of [
        { width: 320, height: 200, padding: 16 },
        { width: 1280, height: 720, padding: 24 },
        { width: 400, height: 800, padding: 12 },
    ]) {
        test(`${openingWidth}:${openingHeight} fits ${viewport.width} × ${viewport.height} without stretching or clipping`, () => {
            const source = geometry(openingWidth, openingHeight);
            const fitted = fitDoorGeometry(source, viewport);
            within(fitted.opening, viewport);
            approximately(fitted.opening.width / source.opening.width, fitted.scale);
            approximately(fitted.opening.height / source.opening.height, fitted.scale);
            approximately(fitted.opening.x, (viewport.width - fitted.opening.width) / 2);
            approximately(fitted.opening.y, (viewport.height - fitted.opening.height) / 2);

            fitted.doors.forEach((door, doorIndex) => {
                within(door, viewport);
                const original = source.doors[doorIndex];
                approximately(door.width / original.width, fitted.scale);
                approximately(door.height / original.height, fitted.scale);
                door.infills.forEach((infill, infillIndex) => {
                    within(infill, viewport);
                    const originalInfill = original.infills[infillIndex];
                    approximately(infill.width / originalInfill.width, fitted.scale);
                    approximately(infill.height / originalInfill.height, fitted.scale);
                });
            });
        });
    }
}

test('fitted doors and infills retain shared boundaries', () => {
    const fitted = fitDoorGeometry(geometry(), { width: 375, height: 240, padding: 17 });
    let right = fitted.opening.x;

    for (const door of fitted.doors) {
        approximately(door.x, right);
        right = door.x + door.width;
        let bottom = fitted.opening.y;
        for (const infill of door.infills) {
            approximately(infill.y, bottom);
            bottom = infill.y + infill.height;
        }
        approximately(bottom, fitted.opening.y + fitted.opening.height);
    }
    approximately(right, fitted.opening.x + fitted.opening.width);
});

test('fitting is repeatable across viewport changes and does not mutate geometry', () => {
    const source = geometry();
    const before = structuredClone(source);
    const mobile = { width: 320, height: 240, padding: 16 };
    const first = fitDoorGeometry(source, mobile);
    fitDoorGeometry(source, { width: 1200, height: 800, padding: 32 });

    assert.deepEqual(fitDoorGeometry(source, mobile), first);
    first.doors[0].infills[0].width = 0;
    assert.deepEqual(source, before);
});

test('extreme finite ratios retain positive dimensions without minimum-size distortion', () => {
    const viewport = { width: 800, height: 600, padding: 20 };
    const huge = fitDoorGeometry(geometry(Number.MAX_VALUE, 1), viewport);
    within(huge.opening, viewport);
    assert.ok(huge.opening.height < 1e-300);
    approximately(huge.opening.width, 760);

    const narrow = calculateDoorGeometry({
        openingWidth: Number.MIN_VALUE,
        openingHeight: 1,
        doorCount: 1,
        infillWeightsByDoor: [[1]],
    });
    const tiny = fitDoorGeometry(narrow, viewport);
    within(tiny.opening, viewport);
    assert.ok(tiny.opening.width < 1e-300);
    approximately(tiny.opening.height, 560);
});

test('zero padding is supported while invalid or exhausted viewports are rejected', () => {
    const source = geometry();
    assert.equal(fitDoorGeometry(source, { width: 300, height: 200 }).viewport.padding, 0);

    for (const viewport of [
        { width: 0, height: 200 }, { width: 300, height: -1 },
        { width: NaN, height: 200 }, { width: 300, height: Infinity },
        { width: '300', height: 200 }, { width: 300, height: 200, padding: -1 },
        { width: 300, height: 200, padding: NaN },
        { width: 300, height: 200, padding: '10' },
        { width: 300, height: 200, padding: 100 },
        { width: 300, height: 200, padding: 150 },
    ]) {
        assert.throws(() => fitDoorGeometry(source, viewport), RangeError);
    }
});
