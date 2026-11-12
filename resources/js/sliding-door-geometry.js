/**
 * @typedef {{ x: number, y: number, width: number, height: number }} Rectangle
 * @typedef {Rectangle & { index: number }} InfillGeometry
 * @typedef {Rectangle & { index: number, infills: InfillGeometry[] }} DoorGeometry
 */

/**
 * Calculate a front elevation in normalized coordinates (opening height = 1).
 * Infill weights describe relative heights from top to bottom for each door.
 * All rectangles use the opening's coordinate system. No track overlaps,
 * profile deductions, viewport scaling or manufacturing sizes are inferred.
 *
 * @param {{ openingWidth: number, openingHeight: number, doorCount: number, infillWeightsByDoor: number[][] }} configuration
 * @returns {{ opening: Rectangle, doors: DoorGeometry[] }}
 * @throws {RangeError} When dimensions, counts or weights cannot form nonzero finite rectangles.
 */
export function calculateDoorGeometry({ openingWidth, openingHeight, doorCount, infillWeightsByDoor }) {
    requirePositiveFinite(openingWidth);
    requirePositiveFinite(openingHeight);

    if (!Number.isSafeInteger(doorCount) || doorCount < 1) {
        throw new RangeError('Door count must be a positive safe integer.');
    }

    if (!Array.isArray(infillWeightsByDoor) || infillWeightsByDoor.length !== doorCount) {
        throw new RangeError('Infill weights must be supplied for every door.');
    }

    const width = openingWidth / openingHeight;
    requirePositiveFinite(width);

    return {
        opening: { x: 0, y: 0, width, height: 1 },
        doors: Array.from(infillWeightsByDoor, (weights, index) => calculateDoor(index, doorCount, width, weights)),
    };
}

/**
 * Fit calculated geometry into a viewport using one uniform scale.
 * Padding is reserved on every side; unused space centers the design.
 * Thin dimensions are not clamped to a minimum pixel size, as that would
 * distort extreme aspect ratios. Input geometry remains unchanged.
 *
 * @param {{ opening: Rectangle, doors: DoorGeometry[] }} geometry Output of calculateDoorGeometry.
 * @param {{ width: number, height: number, padding?: number }} viewport
 * @returns {{ viewport: { width: number, height: number, padding: number }, scale: number, opening: Rectangle, doors: DoorGeometry[] }}
 * @throws {RangeError} When the viewport has no usable area or projected dimensions are unrepresentable.
 */
export function fitDoorGeometry(geometry, { width, height, padding = 0 }) {
    requirePositiveFinite(width);
    requirePositiveFinite(height);
    requirePositiveFinite(geometry.opening.width);
    requirePositiveFinite(geometry.opening.height);

    if (!Number.isFinite(padding) || padding < 0) {
        throw new RangeError('Viewport padding must be finite and nonnegative.');
    }

    const availableWidth = width - 2 * padding;
    const availableHeight = height - 2 * padding;
    requirePositiveFinite(availableWidth);
    requirePositiveFinite(availableHeight);

    const scale = Math.min(availableWidth / geometry.opening.width, availableHeight / geometry.opening.height);
    requirePositiveFinite(scale);
    const offsetX = padding + (availableWidth - geometry.opening.width * scale) / 2;
    const offsetY = padding + (availableHeight - geometry.opening.height * scale) / 2;
    const project = (rectangle) => projectRectangle(rectangle, geometry.opening, scale, offsetX, offsetY);

    return {
        viewport: { width, height, padding },
        scale,
        opening: project(geometry.opening),
        doors: geometry.doors.map((door) => ({
            ...door,
            ...project(door),
            infills: door.infills.map((infill) => ({ ...infill, ...project(infill) })),
        })),
    };
}

function projectRectangle(rectangle, opening, scale, offsetX, offsetY) {
    const width = rectangle.width * scale;
    const height = rectangle.height * scale;
    requirePositiveFinite(width);
    requirePositiveFinite(height);

    return {
        x: offsetX + (rectangle.x - opening.x) * scale,
        y: offsetY + (rectangle.y - opening.y) * scale,
        width,
        height,
    };
}

function calculateDoor(index, count, openingWidth, weights) {
    const x = openingWidth * (index / count);
    const right = openingWidth * ((index + 1) / count);
    const width = right - x;
    requirePositiveFinite(width);

    return {
        index,
        x,
        y: 0,
        width,
        height: 1,
        infills: calculateInfills(x, width, weights),
    };
}

function calculateInfills(x, width, weights) {
    if (!Array.isArray(weights) || weights.length === 0) {
        throw new RangeError('Each door must contain at least one infill weight.');
    }

    const values = Array.from(weights);
    values.forEach(requirePositiveFinite);

    // Scaling first avoids overflowing the sum of otherwise valid weights.
    const largest = values.reduce((maximum, value) => Math.max(maximum, value), 0);
    const scaled = values.map((value) => value / largest);
    const total = scaled.reduce((sum, value) => sum + value, 0);
    let cumulative = 0;
    let y = 0;

    return scaled.map((weight, index) => {
        cumulative += weight;
        const bottom = index === scaled.length - 1 ? 1 : cumulative / total;
        const height = bottom - y;
        requirePositiveFinite(height);

        const infill = { index, x, y, width, height };
        y = bottom;

        return infill;
    });
}

function requirePositiveFinite(value) {
    if (!Number.isFinite(value) || value <= 0) {
        throw new RangeError('Geometry requires positive finite dimensions and proportions.');
    }
}
