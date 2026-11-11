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
