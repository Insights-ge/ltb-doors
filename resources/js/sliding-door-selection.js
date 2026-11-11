import { calculateDoorGeometry } from './sliding-door-geometry.js';

const DOOR_COUNT = 2;

/** Two doors share one panel layout; instance identities remain independent. */
export function reconcilePreviewConfiguration(configuration, previous, createId = () => globalThis.crypto.randomUUID()) {
    if (configuration === null) {
        return null;
    }

    if (!Array.isArray(configuration.panelWeights)) {
        throw new RangeError('The builder requires one shared panel layout.');
    }
    const geometryConfiguration = {
        ...configuration,
        doorCount: DOOR_COUNT,
        panelWeightsByDoor: Array.from({ length: DOOR_COUNT }, () => [...configuration.panelWeights]),
    };
    calculateDoorGeometry(geometryConfiguration);
    if ((configuration.doorIds !== undefined && !Array.isArray(configuration.doorIds))
        || (configuration.panelIdsByDoor !== undefined && !Array.isArray(configuration.panelIdsByDoor))) {
        throw new RangeError('Instance IDs must be supplied as arrays.');
    }
    const doorIds = [];
    const panelIdsByDoor = [];
    const seenDoors = new Set();
    const seenPanels = new Set();

    for (let index = 0; index < DOOR_COUNT; index++) {
        const id = configuration.doorIds?.[index] ?? previous?.doorIds?.[index] ?? createId();
        requireIdentity(id, seenDoors);
        doorIds.push(id);

        const previousIndex = previous?.doorIds?.indexOf(id) ?? -1;
        if (configuration.panelIdsByDoor?.[index] !== undefined && !Array.isArray(configuration.panelIdsByDoor[index])) {
            throw new RangeError('Panel instance IDs must be supplied as arrays.');
        }
        const panelIds = configuration.panelWeights.map((_, panelIndex) => {
            const panelId = configuration.panelIdsByDoor?.[index]?.[panelIndex]
                ?? previous?.panelIdsByDoor?.[previousIndex]?.[panelIndex]
                ?? createId();
            requireIdentity(panelId, seenPanels);
            return panelId;
        });
        panelIdsByDoor.push(panelIds);
    }

    if (sameIdentities(configuration, doorIds, panelIdsByDoor) && sameGeometry(configuration, geometryConfiguration)) {
        return configuration;
    }

    return { ...geometryConfiguration, doorIds, panelIdsByDoor };
}

function sameGeometry(configuration, derived) {
    return configuration.doorCount === DOOR_COUNT
        && Array.isArray(configuration.panelWeightsByDoor)
        && configuration.panelWeightsByDoor.length === DOOR_COUNT
        && derived.panelWeightsByDoor.every((weights, index) => Array.isArray(configuration.panelWeightsByDoor[index])
            && weights.length === configuration.panelWeightsByDoor[index].length
            && weights.every((weight, panelIndex) => weight === configuration.panelWeightsByDoor[index][panelIndex]));
}

export function previewSelectionTargets(configuration) {
    return configuration?.doorIds.map((id, index) => ({
        id,
        index,
        panels: configuration.panelIdsByDoor[index].map((panelId, panelIndex) => ({ id: panelId, index: panelIndex })),
    })) ?? [];
}

export function reconcilePreviewSelection(selection, doors) {
    const door = doors.find((candidate) => candidate.id === selection.doorId);
    if (!door) {
        return { doorId: null, panelId: null };
    }
    if (selection.panelId !== null && !door.panels.some((panel) => panel.id === selection.panelId)) {
        return { doorId: door.id, panelId: null };
    }
    return selection;
}

function requireIdentity(id, seen) {
    if (typeof id !== 'string' || id.trim() === '' || seen.has(id)) {
        throw new RangeError('Door and panel instance IDs must be nonempty and unique.');
    }
    seen.add(id);
}

function sameIdentities(configuration, doorIds, panelIdsByDoor) {
    return configuration.doorIds?.length === doorIds.length
        && doorIds.every((id, index) => id === configuration.doorIds[index])
        && configuration.panelIdsByDoor?.length === panelIdsByDoor.length
        && panelIdsByDoor.every((ids, index) => Array.isArray(configuration.panelIdsByDoor[index])
            && ids.length === configuration.panelIdsByDoor[index].length
            && ids.every((id, panelIndex) => id === configuration.panelIdsByDoor[index][panelIndex]));
}
