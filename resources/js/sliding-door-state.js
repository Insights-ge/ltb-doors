export const openingHeightDeductionMm = 38;

export const openingBounds = {
    openingWidth: { min: 985, max: 5460 },
    openingHeight: { min: 238, max: 2738 },
};

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
    return Number.isInteger(model?.partitionCount) && model.partitionCount >= 0 && model.partitionCount <= 4
        ? Array(model.partitionCount + 1).fill(1) : [];
}

export function doorHeight(openingHeight) {
    const height = parseMeasurement(openingHeight);
    return height === null ? null : height - openingHeightDeductionMm;
}

export function panelLayoutSignature(model) {
    return model?.panelLayout == null ? null : JSON.stringify(model.panelLayout.map(panel =>
        panel.type === 'fixed' ? [panel.type, panel.fixed_mm] : [panel.type, panel.min_mm, panel.weight]));
}

function panelMinimum(panel) {
    return panel.type === 'fixed' ? panel.fixed_mm : panel.min_mm;
}

function panelStackHeight(layout) {
    return layout.reduce((sum, panel) => sum + panelMinimum(panel), 0);
}

export function modelMinimumHeight(model) {
    return Math.max(model.minHeight, panelStackHeight(model.panelLayout ?? []));
}

export function resolvePanelHeights(model, height) {
    if (!Number.isFinite(height) || height <= 0) return null;
    const layout = model.panelLayout;
    if (layout == null) return modelInfillWeights(model).map(() => height / (model.partitionCount + 1));

    const extra = height - panelStackHeight(layout);
    if (extra < 0) return null;

    const totalWeight = layout.reduce((sum, panel) => sum + (panel.weight ?? 0), 0);
    return layout.map(panel => panel.type === 'fixed' ? panel.fixed_mm : panel.min_mm + extra * (panel.weight / totalWeight));
}

/** Preserve corresponding top-to-bottom finishes; new model infills start empty. */
export function applyModelLayout(configuration, model) {
    if (!model) return configuration;
    const infillWeights = modelInfillWeights(model);
    const signature = panelLayoutSignature(model);
    if ((configuration.panelLayoutSignature ?? null) === signature
        && configuration.infillWeights?.length === infillWeights.length && configuration.infillWeights.every(weight => weight === 1)) return configuration;
    return {
        ...configuration,
        infillWeights,
        panelLayoutSignature: signature,
        infillInstanceIdsByDoor: configuration.infillInstanceIdsByDoor?.map(ids => ids.slice(0, infillWeights.length)),
        infillIdsByDoor: Array.from({length:configuration.doorCount ?? 0}, (_,doorIndex) => infillWeights.map((_,infillIndex) =>
            infillIndex < (configuration.infillWeights?.length ?? 0) ? configuration.infillIdsByDoor?.[doorIndex]?.[infillIndex] ?? null : null)),
    };
}

export function modelMaximumHeight(model) {
    return Math.max(model.maxHeight, model.notRecommendedHeightMax ?? model.maxHeight);
}

export function variantSupportsHeight(model, variant, openingHeight) {
    const height = doorHeight(openingHeight);
    if (height === null || (model.partitionCount !== 0 && !variant.hasPartition)) return false;

    const maximum = variant.heightRangeMax >= model.maxHeight
        ? Math.max(variant.heightRangeMax, modelMaximumHeight(model)) : variant.heightRangeMax;
    return height >= variant.heightRangeMin && height <= maximum;
}

/** Opening dimensions imply equal illustrative door widths; catalog width limits are per door. */
export function modelSupportsOpening(model, width, height, doorCount) {
    width = parseMeasurement(width);
    const openingHeight = parseMeasurement(height);
    height = doorHeight(openingHeight);
    return width !== null && openingHeight !== null && height !== null && [2,3,4,5].includes(doorCount)
        && width >= openingBounds.openingWidth.min && width <= openingBounds.openingWidth.max
        && openingHeight >= openingBounds.openingHeight.min && openingHeight <= openingBounds.openingHeight.max
        && width >= model.minWidth * doorCount && width <= model.maxWidth * doorCount
        && height >= modelMinimumHeight(model) && height <= modelMaximumHeight(model)
        && model.variants.some(variant => variantSupportsHeight(model, variant, openingHeight));
}

export function availableDoorCounts(models, width, height) {
    return [2,3,4,5].filter(count => models.some(model => modelSupportsOpening(model, width, height, count)));
}
