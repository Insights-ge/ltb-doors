import { reconcilePreviewConfiguration } from './sliding-door-selection.js';
import { modelPanelWeights } from './sliding-door-state.js';
import { validateBuilder } from './sliding-door-rules.js';

/** Persist only configuration facts, never catalog records or translated labels. */
export function configurationSnapshot(configuration) {
    const fields = ['openingWidth','openingHeight','modelId','variantId','frameDesignCode','frameColor','panelWeights','softClose','doorIds','panelIdsByDoor','infillIdsByDoor'];
    return Object.fromEntries(fields.filter((field) => configuration[field] !== undefined).map((field) => [field, JSON.parse(JSON.stringify(configuration[field]))]));
}

export function saveDraft(storage, key, configuration) {
    try {
        storage.setItem(key, JSON.stringify(configurationSnapshot(configuration)));
        return 'saved';
    } catch {
        return 'unavailable';
    }
}

function validStructure(config) {
    if (!config || typeof config !== 'object' || Array.isArray(config)) return false;
    if (!['openingWidth','openingHeight'].every(key => typeof config[key] === 'number' && Number.isFinite(config[key]) && config[key] > 0)) return false;
    if (!['modelId','variantId'].every(key => Number.isSafeInteger(config[key]) && config[key] > 0)) return false;
    if (typeof config.softClose !== 'boolean' || typeof config.frameColor !== 'string' || typeof config.frameDesignCode !== 'string' || config.frameDesignCode.length > 255) return false;
    if (!Array.isArray(config.panelWeights) || config.panelWeights.length < 1 || config.panelWeights.length > 5 || !config.panelWeights.every(weight => Number.isFinite(weight) && weight > 0)) return false;
    const identity = id => typeof id === 'string' && id.length > 0 && id.length <= 128;
    if (!Array.isArray(config.doorIds) || config.doorIds.length !== 2 || !config.doorIds.every(identity) || new Set(config.doorIds).size !== 2) return false;
    for (const field of ['panelIdsByDoor','infillIdsByDoor']) {
        if (!Array.isArray(config[field]) || config[field].length !== 2 || !config[field].every(row => Array.isArray(row) && row.length === config.panelWeights.length)) return false;
    }
    const panelIds=config.panelIdsByDoor.flat();
    return panelIds.every(identity) && new Set(panelIds).size === panelIds.length
        && config.infillIdsByDoor.flat().every(id => id === null || (Number.isSafeInteger(id) && id > 0));
}

export function readDraft(storage, key, catalog, metadata) {
    let raw;
    try { raw = storage.getItem(key); } catch { return {status:'unavailable',configuration:null}; }
    if (raw === null) return {status:null,configuration:null};
    try {
        if (raw.length > 100000) throw new Error('Oversized draft');
        const parsed=JSON.parse(raw);
        if (!validStructure(parsed)) throw new Error('Invalid draft structure');
        const configuration=configurationSnapshot(parsed);
        const model=catalog.models.find(item=>item.id===configuration.modelId);
        if (model && (configuration.panelWeights.length!==modelPanelWeights(model).length || configuration.panelWeights.some(weight=>weight!==1))) {
            return {status:'outdated_layout',configuration:null};
        }
        // This also checks representability and the supplied instance identities.
        const normalized=reconcilePreviewConfiguration(configuration,null);
        const valid=validateBuilder(normalized,catalog,metadata).errors.length === 0;
        return {status:valid ? 'restored':'outdated',configuration:normalized};
    } catch { return {status:'invalid',configuration:null}; }
}
