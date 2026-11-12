/** Resolve current catalog labels at review time; never trust saved display names. */
export function summarizeConfiguration(config, catalog, metadata) {
    if (!config) return null;
    const model = catalog.models?.find((item) => item.id === config.modelId);
    const variant = model?.variants.find((item) => item.id === config.variantId);
    return {
        openingWidth: config.openingWidth, openingHeight: config.openingHeight,
        modelName: model?.name ?? '', variantName: variant?.name ?? '',
        frameDesignCode: variant?.frameDesignCode ?? '',
        colorLabel: metadata.frameColors?.find((color) => color.color === variant?.color)?.label ?? '',
        partitionCount: model?.partitionCount ?? null, infillsPerDoor: config.infillWeights.length, softClose: Boolean(config.softClose),
        components: (variant?.components ?? []).map(component => ({
            id:component.id, type:component.type, code:component.code, description:component.description,
            colorLabel:metadata.frameColors?.find(color => color.color===component.color)?.label ?? '',
        })),
        doors: Array.from({length:2}, (_,index) => ({
            index, id: config.doorIds?.[index] ?? index,
            infills: config.infillWeights.map((_,infillIndex) => ({
                index: infillIndex, id: config.infillInstanceIdsByDoor?.[index]?.[infillIndex] ?? infillIndex,
                infill: catalog.infills?.find((infill) => infill.id === config.infillIdsByDoor?.[index]?.[infillIndex]) ?? null,
            })),
        })),
    };
}
