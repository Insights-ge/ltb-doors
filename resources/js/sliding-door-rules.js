import { calculateDoorGeometry } from './sliding-door-geometry.js';
import { measurementError, parseMeasurement, modelPanelWeights } from './sliding-door-state.js';

export const sizingScopes = Object.freeze({ model: 'configuration', variantHeight: 'unconfirmed', discouragedHeight: 'unconfirmed' });

/** Raw catalog ranges are reference facts, not bounds on the opening or cutting sizes. */
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

/** Catalog constraints with unconfirmed measurement scope remain informational. */
export function usedComponents(configuration, variant) {
    const types = ['side_profile', 'top_rail', 'bottom_rail'];
    if (configuration?.panelWeights?.length > 1) types.push('partition');
    if (configuration?.softClose) types.push('soft_close_mechanism');
    return variant?.components.filter((component) => types.includes(component.type)) ?? [];
}

export function incompatibleInfillIds(configuration, variant) {
    return new Set(usedComponents(configuration, variant).flatMap((component) => component.incompatibleInfillIds));
}

export function validateBuilder(configuration, catalog, metadata) {
    const errors = [];
    const advisories = [];
    const assumptions = [{ code: 'sizing_unconfirmed', step: 2, field: 'builder-variant' }];
    const add = (code, step, field, extra = {}) => errors.push({ code, step, field, ...extra });
    const config = configuration ?? {};
    const model = catalog.models.find((item) => item.id === config.modelId);
    const missing = (value) => value === null || value === undefined || value === '';
    if (!model) add(missing(config.modelId) ? 'required' : 'exists', 1, 'builder-model');
    if (model) {
        if (!model.variants.some(variant => variant.hasPartition)) add('model_unavailable', 1, 'builder-model');
        for (const [field, min, max] of [
            ['openingWidth', model.minWidth, model.maxWidth],
            ['openingHeight', model.minHeight, model.maxHeight],
        ]) {
            const error = measurementError(config[field]);
            if (error) add(error, 1, `builder-${field}`);
            else if (parseMeasurement(config[field]) < min) add('min', 1, `builder-${field}`, {limit:min});
            else if (parseMeasurement(config[field]) > max) add('max', 1, `builder-${field}`, {limit:max});
        }
        const expected = modelPanelWeights(model);
        if (!expected.length || config.panelWeights?.length !== expected.length || !config.panelWeights.every(weight => weight === 1)) {
            add('model_structure_changed', 1, 'builder-model');
        }
    }
    try {
        calculateDoorGeometry({ ...config, doorCount: 2, panelWeightsByDoor: [config.panelWeights, config.panelWeights] });
    } catch {
        if (!errors.length) add('geometry_invalid', 1, 'builder-openingWidth');
    }
    const variant = model?.variants.find((item) => item.id === config.variantId);
    if (catalogSizingReference(model, variant)?.discouragedHeight) {
        advisories.push({ code: 'discouraged_height_reference', step: 2, field: 'builder-variant' });
    }
    if (model && !model.variants.some((item) => item.frameDesignCode === config.frameDesignCode)) add(missing(config.frameDesignCode) ? 'required' : 'in', 2, 'builder-design');
    if (!variant) add(missing(config.variantId) ? 'required' : 'exists', 2, 'builder-variant');
    else if (variant.color !== config.frameColor || variant.frameDesignCode !== config.frameDesignCode) add('in', 2, 'builder-variant');
    if (variant && config.panelWeights?.length > 1 && !variant.hasPartition) add('partition_missing', 2, 'builder-variant');
    if (config.softClose) {
        assumptions.push({ code: 'soft_close_provisional', step: 2, field: 'builder-soft-close' });
        if (!variant?.hasSoftCloseMechanism) add('soft_close_missing', 2, 'builder-soft-close');
    }
    const excluded = incompatibleInfillIds(config, variant);
    for (let doorIndex = 0; doorIndex < 2; doorIndex++) {
        for (let panelIndex = 0; panelIndex < (config.panelWeights?.length ?? 0); panelIndex++) {
            const infillId = config.infillIdsByDoor?.[doorIndex]?.[panelIndex];
            if (!catalog.infills.some((infill) => infill.id === infillId)) add(missing(infillId) ? 'required' : 'exists', 3, 'builder-finishes', { doorIndex, panelIndex });
            else if (excluded.has(infillId)) add('finish_incompatible', 3, 'builder-finishes', { doorIndex, panelIndex });
        }
    }
    return { errors, advisories, assumptions };
}
