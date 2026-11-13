import { calculateDoorGeometry } from './sliding-door-geometry.js';


/** All doors share one infill layout; instance identities remain independent. */
export function reconcilePreviewConfiguration(configuration, previous, createId = () => globalThis.crypto.randomUUID()) {
    if (configuration === null) {
        return null;
    }

    if (!Array.isArray(configuration.infillWeights)) {
        throw new RangeError('The builder requires one shared infill layout.');
    }
    const doorCount = configuration.doorCount;
    if (![2,3,4,5].includes(doorCount)) throw new RangeError('Choose between two and five doors.');
    const geometryConfiguration = {
        ...configuration,
        doorCount,
        infillWeightsByDoor: Array.from({ length: doorCount }, () => [...configuration.infillWeights]),
    };
    calculateDoorGeometry(geometryConfiguration);
    if ((configuration.doorIds !== undefined && !Array.isArray(configuration.doorIds))
        || (configuration.infillInstanceIdsByDoor !== undefined && !Array.isArray(configuration.infillInstanceIdsByDoor))) {
        throw new RangeError('Instance IDs must be supplied as arrays.');
    }
    const doorIds = [];
    const infillInstanceIdsByDoor = [];
    const seenDoors = new Set();
    const seenInfills = new Set();

    for (let index = 0; index < doorCount; index++) {
        const id = configuration.doorIds?.[index] ?? previous?.doorIds?.[index] ?? createId();
        requireIdentity(id, seenDoors);
        doorIds.push(id);

        const previousIndex = previous?.doorIds?.indexOf(id) ?? -1;
        if (configuration.infillInstanceIdsByDoor?.[index] !== undefined && !Array.isArray(configuration.infillInstanceIdsByDoor[index])) {
            throw new RangeError('Infill instance IDs must be supplied as arrays.');
        }
        const infillInstanceIds = configuration.infillWeights.map((_, infillIndex) => {
            const infillInstanceId = configuration.infillInstanceIdsByDoor?.[index]?.[infillIndex]
                ?? previous?.infillInstanceIdsByDoor?.[previousIndex]?.[infillIndex]
                ?? createId();
            requireIdentity(infillInstanceId, seenInfills);
            return infillInstanceId;
        });
        infillInstanceIdsByDoor.push(infillInstanceIds);
    }

    const infillIdsByDoor = Array.from({length:doorCount}, () => configuration.infillWeights.map((_,index) => configuration.infillIdsByDoor?.[0]?.[index] ?? null));
    const sameFinishes = configuration.infillIdsByDoor?.length === doorCount
        && infillIdsByDoor.every((row,index) => JSON.stringify(row) === JSON.stringify(configuration.infillIdsByDoor[index]));
    if (sameFinishes && sameIdentities(configuration, doorIds, infillInstanceIdsByDoor) && sameGeometry(configuration, geometryConfiguration)) {
        return configuration;
    }

    return { ...geometryConfiguration, doorIds, infillInstanceIdsByDoor, infillIdsByDoor };
}

function sameGeometry(configuration, derived) {
    return configuration.doorCount === derived.doorCount
        && Array.isArray(configuration.infillWeightsByDoor)
        && configuration.infillWeightsByDoor.length === derived.doorCount
        && derived.infillWeightsByDoor.every((weights, index) => Array.isArray(configuration.infillWeightsByDoor[index])
            && weights.length === configuration.infillWeightsByDoor[index].length
            && weights.every((weight, infillIndex) => weight === configuration.infillWeightsByDoor[index][infillIndex]));
}

export function previewSelectionTargets(configuration) {
    return configuration?.doorIds.map((id, index) => ({
        id,
        index,
        infills: configuration.infillInstanceIdsByDoor[index].map((infillInstanceId, infillIndex) => ({ id: infillInstanceId, index: infillIndex })),
    })) ?? [];
}

export function reconcilePreviewSelection(selection, doors) {
    const door = doors.find((candidate) => candidate.id === selection.doorId);
    if (!door) {
        return { doorId: null, infillInstanceId: null };
    }
    if (selection.infillInstanceId !== null && !door.infills.some((infill) => infill.id === selection.infillInstanceId)) {
        return { doorId: door.id, infillInstanceId: null };
    }
    return selection;
}

function requireIdentity(id, seen) {
    if (typeof id !== 'string' || id.trim() === '' || seen.has(id)) {
        throw new RangeError('Door and infill instance IDs must be nonempty and unique.');
    }
    seen.add(id);
}

function sameIdentities(configuration, doorIds, infillInstanceIdsByDoor) {
    return configuration.doorIds?.length === doorIds.length
        && doorIds.every((id, index) => id === configuration.doorIds[index])
        && configuration.infillInstanceIdsByDoor?.length === infillInstanceIdsByDoor.length
        && infillInstanceIdsByDoor.every((ids, index) => Array.isArray(configuration.infillInstanceIdsByDoor[index])
            && ids.length === configuration.infillInstanceIdsByDoor[index].length
            && ids.every((id, infillIndex) => id === configuration.infillInstanceIdsByDoor[index][infillIndex]));
}
