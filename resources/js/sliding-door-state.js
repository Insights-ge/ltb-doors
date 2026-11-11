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
