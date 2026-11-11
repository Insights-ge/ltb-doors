/** A new configuration starts without measurements, products or finishes. */
export function createInitialConfiguration() {
    return {
        openingWidth: null,
        openingHeight: null,
        modelId: null,
        variantId: null,
        frameColor: null,
        panelWeights: [1],
        softClose: false,
    };
}

export function parseMeasurement(value) {
    const text = String(value ?? '').trim();
    if (!/^\d+(?:[.,]\d+)?$/.test(text)) return null;
    const number = Number(text.replace(',', '.'));
    return Number.isFinite(number) && number > 0 ? number : null;
}
