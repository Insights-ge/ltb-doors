import { calculateDoorGeometry } from './sliding-door-geometry.js';
import { parseMeasurement } from './sliding-door-state.js';

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
    for (const field of ['openingWidth', 'openingHeight']) {
        if (!parseMeasurement(config[field])) add('size_invalid', 1, `builder-${field}`);
    }
    try {
        calculateDoorGeometry({ ...config, doorCount: 2, panelWeightsByDoor: [config.panelWeights, config.panelWeights] });
    } catch {
        if (!errors.length) add('geometry_invalid', 1, 'builder-openingWidth');
    }
    const model = catalog.models.find((item) => item.id === config.modelId);
    const variant = model?.variants.find((item) => item.id === config.variantId);
    if (!model) add('model_required', 2, 'builder-model');
    if (!variant || variant.color !== config.frameColor || variant.frameDesignCode !== config.frameDesignCode) add('variant_required', 2, 'builder-variant');
    if (!metadata.layoutPresets?.some((preset) => preset.panelWeights.length === config.panelWeights?.length
        && preset.panelWeights.every((weight, index) => weight === config.panelWeights[index]))) add('layout_required', 3, 'builder-layouts');
    const excluded = incompatibleInfillIds(config, variant);
    for (let doorIndex = 0; doorIndex < 2; doorIndex++) {
        for (let panelIndex = 0; panelIndex < (config.panelWeights?.length ?? 0); panelIndex++) {
            const infillId = config.infillIdsByDoor?.[doorIndex]?.[panelIndex];
            if (!catalog.infills.some((infill) => infill.id === infillId)) add('finish_required', 3, 'builder-finishes', { doorIndex, panelIndex });
            else if (excluded.has(infillId)) add('finish_incompatible', 3, 'builder-finishes', { doorIndex, panelIndex });
        }
    }
    return { errors, advisories, assumptions };
}
