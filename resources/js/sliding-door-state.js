/** A new configuration starts without measurements, products or finishes. */
export function createInitialConfiguration() {
    return {
        openingWidth: null,
        openingHeight: null,
        modelId: null,
        variantId: null,
        frameDesignCode: null,
        frameColor: null,
        panelWeights: [],
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


export function modelPanelWeights(model) {
    return Number.isInteger(model?.partitionCount) && model.partitionCount >= 1 && model.partitionCount <= 4
        ? Array(model.partitionCount + 1).fill(1) : [];
}

/** Preserve corresponding top-to-bottom finishes; new model panels start empty. */
export function applyModelLayout(configuration, model) {
    if (!model) return configuration;
    const panelWeights = modelPanelWeights(model);
    if (configuration.panelWeights?.length === panelWeights.length && configuration.panelWeights.every(weight => weight === 1)) return configuration;
    return {
        ...configuration,
        panelWeights,
        panelIdsByDoor: configuration.panelIdsByDoor?.map(ids => ids.slice(0, panelWeights.length)),
        infillIdsByDoor: Array.from({length:2}, (_,doorIndex) => panelWeights.map((_,panelIndex) =>
            panelIndex < (configuration.panelWeights?.length ?? 0) ? configuration.infillIdsByDoor?.[doorIndex]?.[panelIndex] ?? null : null)),
    };
}
