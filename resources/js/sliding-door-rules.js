import { calculateDoorGeometry } from './sliding-door-geometry.js';
import { measurementError, parseMeasurement, modelInfillWeights, availableDoorCounts, modelOpeningHeightRange, variantSupportsHeight, variantSupportsWidth, variantSupportsOpening, openingBounds, doorHeight, doorWidth, resolvePanelHeights } from './sliding-door-state.js';

export const sizingScopes = Object.freeze({ model: 'per_door', variantHeight: 'per_door', discouragedHeight: 'unconfirmed' });

/** Model ranges constrain entered sizes; variant height ranges determine available variants. */
export function catalogSizingReference(model, variant) {
    return model && variant ? {
        width: [model.minWidth, model.maxWidth],
        height: [model.minHeight, model.maxHeight],
        variantHeight: [variant.heightRangeMin, variant.heightRangeMax],
        discouragedHeight: model.notRecommendedHeightMin !== null && model.notRecommendedHeightMin !== undefined
            && model.notRecommendedHeightMax !== null && model.notRecommendedHeightMax !== undefined
            ? [model.notRecommendedHeightMin, model.notRecommendedHeightMax] : null,
        scopes: sizingScopes,
    } : null;
}

/** Use the fixed component set recorded on the selected variant. */
export function usedComponents(configuration, variant) {
    const types = ['side_profile', 'top_rail', 'bottom_rail'];
    if (configuration?.infillWeights?.length > 1) types.push('partition');
    if (variant?.hasSoftCloseMechanism) types.push('soft_close_mechanism');
    return variant?.components.filter((component) => types.includes(component.type)) ?? [];
}

export function incompatibleInfillIds(configuration, variant) {
    return new Set(usedComponents(configuration, variant).flatMap((component) => component.incompatibleInfillIds));
}

export function validateBuilder(configuration, catalog, metadata) {
    const errors = [];
    const advisories = [];
    const assumptions = [];
    const add = (code, step, field, extra = {}) => errors.push({ code, step, field, ...extra });
    const config = configuration ?? {};
    const model = catalog.models.find((item) => item.id === config.modelId);
    const missing = (value) => value === null || value === undefined || value === '';
    if (!model) add(missing(config.modelId) ? 'required' : 'exists', 1, 'builder-model');
    const modelWidthApplies = model && [2, 3, 4, 5].includes(config.doorCount);
    const legacyWidthBounds = modelWidthApplies && !model.variants.some(variant => variant.overlapWidth != null);
    const variant = model?.variants.find((item) => item.id === config.variantId);
    const [minimumHeight, maximumHeight] = model ? modelOpeningHeightRange(model) : [];
    const openingRanges = [
        ['openingWidth', legacyWidthBounds ? model.minWidth * config.doorCount : null, legacyWidthBounds ? model.maxWidth * config.doorCount : null],
        ['openingHeight', minimumHeight, maximumHeight],
    ];
    for (const [field, modelMin, modelMax] of openingRanges) {
        const error = measurementError(config[field]);
        if (error) {
            add(error, 1, `builder-${field}`);
            continue;
        }
        const value = parseMeasurement(config[field]);
        const bounds = openingBounds[field];
        const min = Math.max(bounds.min, modelMin ?? bounds.min);
        const max = Math.min(bounds.max, modelMax ?? bounds.max);
        if (value < min) add('min', 1, `builder-${field}`, { limit: min });
        else if (value > max) add('max', 1, `builder-${field}`, { limit: max });
    }
    const counts = availableDoorCounts(catalog.models, config.openingWidth, config.openingHeight);
    if (!counts.includes(config.doorCount)) add(missing(config.doorCount) ? 'required' : 'in', 1, 'builder-doorCount');
    if (model) {
        if (model.partitionCount > 0 && !model.variants.some(variant => variant.hasPartition)) add('model_unavailable', 1, 'builder-model');
        const height = parseMeasurement(config.openingHeight);
        const width = parseMeasurement(config.openingWidth);
        const widthMatches = model.variants.filter(variant => (model.partitionCount === 0 || variant.hasPartition)
            && variantSupportsWidth(model, variant, width, config.doorCount));
        if (modelWidthApplies && width !== null && !widthMatches.length && !legacyWidthBounds) add('no_variant_for_width', 1, 'builder-openingWidth');
        if (height !== null && !model.variants.some(variant => variantSupportsHeight(model, variant, height))) add('no_variant_for_height', 1, 'builder-openingHeight');
        else if (height !== null && widthMatches.length && !widthMatches.some(variant => variantSupportsOpening(model, variant, width, height, config.doorCount))) {
            add('no_variant_for_dimensions', 1, 'builder-openingHeight');
        }
        const expected = modelInfillWeights(model);
        if (!expected.length || config.infillWeights?.length !== expected.length || !config.infillWeights.every(weight => weight === 1)) {
            add('model_structure_changed', 1, 'builder-model');
        }
    }
    try {
        const height = doorHeight(config.openingHeight, variant);
        const panels = model ? resolvePanelHeights(model, height) : config.infillWeights;
        calculateDoorGeometry({ ...config, doorHeight: height, doorWidth: variant?.overlapWidth == null ? undefined : doorWidth(config.openingWidth, config.doorCount, variant), infillWeightsByDoor: Array.from({length:config.doorCount ?? 0}, () => panels) });
    } catch {
        if (!errors.length) add('geometry_invalid', 1, 'builder-openingWidth');
    }
    const discouragedHeight = model?.notRecommendedHeightMin != null && model?.notRecommendedHeightMax != null
        ? [model.notRecommendedHeightMin, model.notRecommendedHeightMax] : null;
    const chosenHeight = doorHeight(config.openingHeight, variant);
    if (discouragedHeight && chosenHeight !== null && chosenHeight >= discouragedHeight[0] && chosenHeight <= discouragedHeight[1]) {
        advisories.push({ code: 'discouraged_height_reference', step: 1, field: 'builder-openingHeight' });
    }
    if (model && !model.variants.some((item) => item.frameDesignCode === config.frameDesignCode)) add(missing(config.frameDesignCode) ? 'required' : 'in', 2, 'builder-design');
    if (!variant) add(missing(config.variantId) ? 'required' : 'exists', 2, 'builder-variant');
    else if (variant.color !== config.frameColor || variant.frameDesignCode !== config.frameDesignCode) add('in', 2, 'builder-variant');
    const partitionMissing = variant && model.partitionCount > 0 && !variant.hasPartition;
    if (partitionMissing) add('partition_missing', 2, 'builder-variant');
    if (variant && Boolean(config.softClose) !== Boolean(variant.hasSoftCloseMechanism)) add('variant_components_mismatch', 2, 'builder-variant');
    if (variant && !variantSupportsWidth(model, variant, config.openingWidth, config.doorCount)) add('variant_width_mismatch', 2, 'builder-variant');
    if (variant && !partitionMissing && !variantSupportsHeight(model, variant, config.openingHeight)) add('variant_height_mismatch', 2, 'builder-variant');
    if (variant?.hasSoftCloseMechanism) {
        assumptions.push({ code: 'soft_close_provisional', step: 2, field: 'builder-variant' });
    }
    const excluded = incompatibleInfillIds(config, variant);
    for (let infillIndex = 0; infillIndex < (config.infillWeights?.length ?? 0); infillIndex++) {
        const infillId = config.infillIdsByDoor?.[0]?.[infillIndex];
        const field = `builder-infill-${infillIndex}`;
        if (config.infillIdsByDoor?.length !== config.doorCount || config.infillIdsByDoor.some(row => row[infillIndex] !== infillId)) add('shared_infills_mismatch', 3, field, { infillIndex, doorIndex: 0 });
        if (!catalog.infills.some(infill => infill.id === infillId)) add(missing(infillId) ? 'required' : 'exists', 3, field, { infillIndex, doorIndex: 0 });
        else if (excluded.has(infillId)) add('finish_incompatible', 3, field, { infillIndex, doorIndex: 0 });
    }
    return { errors, advisories, assumptions };
}
