/** A new configuration starts without measurements, products or finishes. */
export function createInitialConfiguration() {
    return {
        openingWidth: null,
        openingHeight: null,
        modelId: null,
        variantId: null,
        frameDesignCode: null,
        frameColor: null,
        panelWeights: [1],
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
