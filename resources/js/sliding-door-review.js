/** Resolve current catalog labels at review time; never trust saved display names. */
export function summarizeConfiguration(config, catalog, metadata) {
    if (!config) return null;
    const model = catalog.models?.find((item) => item.id === config.modelId);
    const variant = model?.variants.find((item) => item.id === config.variantId);
    const layout = metadata.layoutPresets?.find((preset) => preset.panelWeights.length === config.panelWeights.length
        && preset.panelWeights.every((weight,index) => weight === config.panelWeights[index]));
    return {
        openingWidth: config.openingWidth, openingHeight: config.openingHeight,
        modelName: model?.name ?? '', variantName: variant?.name ?? '',
        frameDesignCode: variant?.frameDesignCode ?? '',
        colorLabel: metadata.frameColors?.find((color) => color.color === variant?.color)?.label ?? '',
        layoutLabel: layout?.label ?? '', softClose: Boolean(config.softClose),
        doors: Array.from({length:2}, (_,index) => ({
            index, id: config.doorIds?.[index] ?? index,
            panels: config.panelWeights.map((_,panelIndex) => ({
                index: panelIndex, id: config.panelIdsByDoor?.[index]?.[panelIndex] ?? panelIndex,
                infill: catalog.infills?.find((infill) => infill.id === config.infillIdsByDoor?.[index]?.[panelIndex]) ?? null,
            })),
        })),
    };
}
