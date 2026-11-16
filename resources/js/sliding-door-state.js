/** A new configuration starts without measurements, products or finishes. */
export function createInitialConfiguration() {
    return {
        openingWidth: null,
        openingHeight: null,
        doorCount: null,
        modelId: null,
        variantId: null,
        frameDesignCode: null,
        frameColor: null,
        infillWeights: [],
        softClose: false,
    };
}

function numericMeasurement(value) {
    if (typeof value === 'number') return Number.isFinite(value) ? value : null;
    if (typeof value !== 'string') return null;
    const text = value.trim().replace(',', '.');
    if (!/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?$/.test(text)) return null;
    const number = Number(text);
    return Number.isFinite(number) ? number : null;
}

export function measurementError(value) {
    if (value === null || value === undefined || (typeof value === 'string' && value.trim() === '')) return 'required';
    const number = numericMeasurement(value);
    if (number === null) return 'numeric';
    return number > 0 ? null : 'gt';
}

export function parseMeasurement(value) {
    return measurementError(value) === null ? numericMeasurement(value) : null;
}


export function modelInfillWeights(model) {
    return Number.isInteger(model?.partitionCount) && model.partitionCount >= 1 && model.partitionCount <= 4
        ? Array(model.partitionCount + 1).fill(1) : [];
}

/** Preserve corresponding top-to-bottom finishes; new model infills start empty. */
export function applyModelLayout(configuration, model) {
    if (!model) return configuration;
    const infillWeights = modelInfillWeights(model);
    if (configuration.infillWeights?.length === infillWeights.length && configuration.infillWeights.every(weight => weight === 1)) return configuration;
    return {
        ...configuration,
        infillWeights,
        infillInstanceIdsByDoor: configuration.infillInstanceIdsByDoor?.map(ids => ids.slice(0, infillWeights.length)),
        infillIdsByDoor: Array.from({length:configuration.doorCount ?? 0}, (_,doorIndex) => infillWeights.map((_,infillIndex) =>
            infillIndex < (configuration.infillWeights?.length ?? 0) ? configuration.infillIdsByDoor?.[doorIndex]?.[infillIndex] ?? null : null)),
    };
}

/** Opening dimensions imply equal illustrative door widths; catalog width limits are per door. */
export function modelSupportsOpening(model, width, height, doorCount) {
    width = parseMeasurement(width);
    height = parseMeasurement(height);
    return width !== null && height !== null && [2,3,4,5].includes(doorCount)
        && width >= model.minWidth * doorCount && width <= model.maxWidth * doorCount
        && height >= model.minHeight && height <= model.maxHeight
        && model.variants.some(variant => variant.hasPartition && height >= variant.heightRangeMin && height <= variant.heightRangeMax);
}

export function availableDoorCounts(models, width, height) {
    return [2,3,4,5].filter(count => models.some(model => modelSupportsOpening(model, width, height, count)));
}
