import { validateBuilder, incompatibleInfillIds, catalogSizingReference } from './sliding-door-rules.js';
import { createInitialConfiguration, parseMeasurement } from './sliding-door-state.js';
import { createDoorPreview } from './sliding-door-preview.js';
import { frameVisual, infillVisual } from './sliding-door-visuals.js';
import { reconcilePreviewConfiguration, previewSelectionTargets, reconcilePreviewSelection } from './sliding-door-selection.js';

export default function slidingDoorBuilder({ initialStep, payload, previewLabels = {}, validationLabels = {} }) {
    let preview;
    let stopWatching;
    let destroyed = false;
    let previousConfiguration = null;
    let stopSelectionWatching;
    let rootElement;

    return {
        activeStep: initialStep,
        catalog: payload.catalog,
        metadata: payload.metadata,
        previewConfiguration: createInitialConfiguration(),
        validationAttempted: false,
        pendingChange: null,
        sizeInputs: { openingWidth: '', openingHeight: '' },
        sizeErrors: { openingWidth: false, openingHeight: false },
        hasPreview: false,
        previewDoors: [],
        previewSelection: { doorId: null, panelId: null },

        issueMessage(issue) {
            return (validationLabels[issue.code] ?? '')
                .replace(':door', String((issue.doorIndex ?? 0) + 1))
                .replace(':panel', String((issue.panelIndex ?? 0) + 1));
        },

        infillIsIncompatible(infillId) {
            return incompatibleInfillIds(this.previewConfiguration, this.selectedVariant).has(infillId);
        },

        get validation() {
            const result = validateBuilder(this.previewConfiguration, this.catalog, this.metadata);
            for (const [field, invalid] of Object.entries(this.sizeErrors)) {
                if (invalid && !result.errors.some((issue) => issue.field === `builder-${field}`)) {
                    result.errors.push({ code: 'size_invalid', step: 1, field: `builder-${field}` });
                }
            }
            return result;
        },

        get canReview() {
            return this.validation.errors.length === 0;
        },

        requestReset() {
            if (JSON.stringify(this.previewConfiguration) === JSON.stringify(createInitialConfiguration()) && !Object.values(this.sizeInputs).some(Boolean)) return;
            this.$dispatch('open-modal', { id: 'builder-confirm-reset' });
        },

        resetConfiguration() {
            previousConfiguration = null;
            this.pendingChange = null;
            this.previewConfiguration = createInitialConfiguration();
            this.previewSelection = { doorId: null, panelId: null };
            this.sizeInputs = { openingWidth: '', openingHeight: '' };
            this.sizeErrors = { openingWidth: false, openingHeight: false };
            this.goToStep(1);
            this.$dispatch('close-modal', { id: 'builder-confirm-reset' });
        },

        goToStep(step) {
            if (![1,2,3,4].includes(step)) return;
            this.validationAttempted = true;
            if (step > 1 && (Object.values(this.sizeErrors).some(Boolean) || !parseMeasurement(this.previewConfiguration?.openingWidth) || !parseMeasurement(this.previewConfiguration?.openingHeight))) {
                this.sizeErrors = { openingWidth: this.sizeErrors.openingWidth || !parseMeasurement(this.previewConfiguration?.openingWidth), openingHeight: this.sizeErrors.openingHeight || !parseMeasurement(this.previewConfiguration?.openingHeight) };
                step = 1;
            } else if (step > 2 && !this.selectedVariant) {
                step = 2;
            }
            this.activeStep = step;
            this.$nextTick?.(() => rootElement?.querySelector(`#builder-step-${step}`)?.focus());
        },

        setMeasurement(field, value) {
            if (!['openingWidth', 'openingHeight'].includes(field)) return;
            const number = parseMeasurement(value);
            this.sizeErrors[field] = number === null;
            this.sizeInputs[field] = String(value ?? '');
            if (number !== null) this.previewConfiguration = { ...this.previewConfiguration, [field]: number };
        },

        get sizingReference() {
            return catalogSizingReference(this.selectedModel, this.selectedVariant);
        },

        get selectedModel() {
            return this.catalog.models.find((model) => model.id === this.previewConfiguration?.modelId) ?? null;
        },

        get selectedVariant() {
            return this.selectedModel?.variants.find((variant) => variant.id === this.previewConfiguration?.variantId) ?? null;
        },

        get frameDesigns() {
            return [...new Set(this.selectedModel?.variants.map((variant) => variant.frameDesignCode) ?? [])];
        },

        get availableVariants() {
            return this.selectedModel?.variants.filter((variant) => variant.frameDesignCode === this.previewConfiguration.frameDesignCode) ?? [];
        },

        selectModel(value) {
            const id = Number(value);
            if (!this.catalog.models.some((model) => model.id === id)) return;
            this.previewConfiguration = { ...this.previewConfiguration, modelId: id, variantId: null, frameDesignCode: null, frameColor: null };
        },

        selectFrameDesign(code) {
            if (!this.frameDesigns.includes(code)) return;
            this.previewConfiguration = { ...this.previewConfiguration, frameDesignCode: code, variantId: null, frameColor: null };
        },

        selectVariant(value) {
            const variant = this.availableVariants.find((candidate) => candidate.id === Number(value));
            if (!variant) return;
            this.previewConfiguration = { ...this.previewConfiguration, variantId: variant.id, frameColor: variant.color };
        },

        setSoftClose(enabled) {
            if (enabled && !this.selectedVariant?.hasSoftCloseMechanism) return;
            this.previewConfiguration = { ...this.previewConfiguration, softClose: Boolean(enabled) };
        },

        variantLabel(variant) {
            const color = this.metadata.frameColors.find((frame) => frame.color === variant.color)?.label ?? variant.color;
            return `${color} — ${variant.name}`;
        },

        get selectedPreviewPanel() {
            return this.selectedPreviewDoor?.panels.find((panel) => panel.id === this.previewSelection.panelId) ?? null;
        },

        get paletteInfillId() {
            const door = this.selectedPreviewDoor;
            const panel = this.selectedPreviewPanel;
            return door && panel
                ? this.previewConfiguration?.infillIdsByDoor?.[door.index]?.[panel.index] ?? null
                : null;
        },

        chooseInfill(infillId) {
            if (this.selectedPreviewPanel && (infillId === null || this.catalog.infills.some((infill) => infill.id === infillId))) {
                const door = this.selectedPreviewDoor;
                const panel = this.selectedPreviewPanel;
                const infillIdsByDoor = this.previewDoors.map((candidate) => candidate.panels.map((item) => (
                    candidate.id === door.id && item.id === panel.id
                        ? infillId
                        : this.previewConfiguration.infillIdsByDoor?.[candidate.index]?.[item.index] ?? null
                )));
                this.previewConfiguration = { ...this.previewConfiguration, infillIdsByDoor };
            }
        },

        requestConfigurationChange(configuration) {
            const current = this.previewConfiguration;
            const replacesFinish = current?.infillIdsByDoor?.some((finishes, doorIndex) => finishes.some((infillId, panelIndex) => (
                infillId !== null && infillId !== undefined
                && infillId !== configuration.infillIdsByDoor?.[doorIndex]?.[panelIndex]
            )));
            if (replacesFinish) {
                this.pendingChange = { before: JSON.stringify(current), configuration };
                this.$dispatch('open-modal', { id: 'builder-confirm-replacement' });
            } else {
                this.previewConfiguration = configuration;
            }
        },

        confirmConfigurationChange() {
            const pending = this.pendingChange;
            // A confirmation only applies to the exact design the user reviewed.
            if (pending && pending.before === JSON.stringify(this.previewConfiguration)) {
                this.previewConfiguration = pending.configuration;
            }
            this.cancelConfigurationChange();
        },

        cancelConfigurationChange() {
            this.pendingChange = null;
            this.$dispatch('close-modal', { id: 'builder-confirm-replacement' });
        },

        applyFinishToAllDoors() {
            if (!this.selectedPreviewPanel) {
                return;
            }
            const infillIdsByDoor = this.previewDoors.map((door) => door.panels.map(() => this.paletteInfillId));
            this.requestConfigurationChange({ ...this.previewConfiguration, infillIdsByDoor });
        },

        copySelectedDoor() {
            const door = this.selectedPreviewDoor;
            if (!door) {
                return;
            }
            const finishes = door.panels.map((panel) => this.previewConfiguration.infillIdsByDoor?.[door.index]?.[panel.index] ?? null);
            this.requestConfigurationChange({
                ...this.previewConfiguration,
                infillIdsByDoor: this.previewDoors.map(() => [...finishes]),
            });
        },

        get selectedLayoutId() {
            const weights = this.previewConfiguration?.panelWeights;
            return this.metadata.layoutPresets?.find((preset) => weights?.length === preset.panelWeights.length
                && weights.every((weight, index) => weight === preset.panelWeights[index]))?.id ?? null;
        },

        layoutIsAvailable(preset) {
            return preset.panelWeights.length === 1 || !this.selectedVariant || this.selectedVariant.hasPartition;
        },

        selectLayout(layoutId) {
            const preset = this.metadata.layoutPresets?.find((candidate) => candidate.id === layoutId);
            if (!preset || !this.layoutIsAvailable(preset) || !this.previewConfiguration || this.selectedLayoutId === layoutId) {
                return;
            }
            const configuration = this.previewConfiguration;
            // Corresponding panels are matched by their top-to-bottom position.
            // Trim discarded values so increasing the count cannot revive removed finishes.
            this.requestConfigurationChange({
                ...configuration,
                panelWeights: [...preset.panelWeights],
                panelIdsByDoor: configuration.panelIdsByDoor?.map((ids) => ids.slice(0, preset.panelWeights.length)),
                infillIdsByDoor: Array.from({ length: 2 }, (_, doorIndex) => preset.panelWeights.map((_, panelIndex) => (
                    panelIndex < configuration.panelWeights.length
                        ? configuration.infillIdsByDoor?.[doorIndex]?.[panelIndex] ?? null
                        : null
                ))),
            });
        },

        get selectedPreviewDoor() {
            return this.previewDoors.find((door) => door.id === this.previewSelection.doorId) ?? null;
        },

        get previewPanelGroupLabel() {
            const door = this.selectedPreviewDoor;
            return door
                ? (previewLabels.panels_for_door ?? '').replace(':number', String(door.index + 1))
                : (previewLabels.panels_label ?? '');
        },

        get previewSelectionAnnouncement() {
            const door = this.selectedPreviewDoor;
            if (!door) {
                return previewLabels.no_selection ?? '';
            }
            const panel = door.panels.find((candidate) => candidate.id === this.previewSelection.panelId);
            return panel
                ? (previewLabels.panel_selected ?? '').replace(':door', String(door.index + 1)).replace(':panel', String(panel.index + 1))
                : (previewLabels.door_selected ?? '').replace(':number', String(door.index + 1));
        },

        init() {
            this.$nextTick(() => {
                rootElement = this.$el;
                const svg = this.$el.querySelector('.builder-preview-svg');
                if (destroyed || !svg) {
                    return;
                }

                preview = createDoorPreview(svg, {
                    metadata: this.metadata,
                    labels: previewLabels,
                    locale: document.documentElement.lang,
                    onRender: (available) => { this.hasPreview = available; },
                    onSelect: ({ doorId, panelId }) => {
                        if (panelId !== null) {
                            this.selectPreviewPanel(doorId, panelId);
                        } else if (doorId !== null) {
                            this.selectPreviewDoor(doorId);
                        } else {
                            this.clearPreviewSelection();
                        }
                    },
                });
                stopWatching = this.$watch('previewConfiguration', () => this.updatePreview());
                stopSelectionWatching = this.$watch('previewSelection', (selection) => preview.setSelection(selection));
                this.updatePreview();
            });
        },

        destroy() {
            destroyed = true;
            stopWatching?.();
            stopSelectionWatching?.();
            preview?.destroy();
        },

        updatePreview() {
            const focused = rootElement?.ownerDocument.activeElement;
            const focusedPanelId = focused?.dataset.previewPanelControl;
            const focusedDoorId = focused?.dataset.previewDoorControl;
            let configuration;
            try {
                configuration = reconcilePreviewConfiguration(this.previewConfiguration, previousConfiguration);
            } catch (error) {
                if (!(error instanceof RangeError)) {
                    throw error;
                }
                this.previewDoors = [];
                this.clearPreviewSelection();
                preview?.render(null);
                if (focusedPanelId || focusedDoorId) {
                    this.$nextTick(() => this.focusPreviewDoor(null));
                }
                return;
            }
            if (configuration !== this.previewConfiguration) {
                this.previewConfiguration = configuration;
                return;
            }
            previousConfiguration = configuration;
            this.previewDoors = previewSelectionTargets(configuration);
            this.previewSelection = reconcilePreviewSelection(this.previewSelection, this.previewDoors);
            if ((focusedPanelId && !this.previewDoors.some((door) => door.panels.some((panel) => panel.id === focusedPanelId)))
                || (focusedDoorId && !this.previewDoors.some((door) => door.id === focusedDoorId))) {
                this.$nextTick(() => this.focusPreviewDoor(this.previewSelection.doorId));
            }
            preview?.setSelection(this.previewSelection);
            preview?.render(configuration);
        },

        selectPreviewDoor(doorId) {
            if (this.previewDoors.some((door) => door.id === doorId)) {
                this.previewSelection = { doorId, panelId: null };
            }
        },

        selectPreviewPanel(doorId, panelId) {
            const door = this.previewDoors.find((candidate) => candidate.id === doorId);
            if (door?.panels.some((panel) => panel.id === panelId)) {
                this.previewSelection = { doorId, panelId };
            }
        },

        clearPreviewSelection() {
            if (rootElement?.ownerDocument.activeElement?.hasAttribute('data-preview-panel-control')) {
                this.focusPreviewDoor(this.previewSelection.doorId);
            }
            this.previewSelection = { doorId: null, panelId: null };
        },

        focusPreviewDoor(doorId) {
            if (destroyed) {
                return;
            }
            const controls = Array.from(rootElement?.querySelectorAll('[data-preview-door-control]') ?? [])
                .filter((button) => button.getClientRects().length > 0);
            const control = controls.find((button) => button.dataset.previewDoorControl === doorId)
                ?? controls[0]
                ?? rootElement?.querySelector('.builder-step-navigation button');
            control?.focus({ preventScroll: true });
        },

        previewDoorLabel(index) {
            return previewLabels.door_label.replace(':number', String(index + 1));
        },

        previewPanelLabel(index) {
            return previewLabels.panel_short_label.replace(':number', String(index + 1));
        },

        previewPanelAccessibleLabel(index) {
            if (!this.selectedPreviewDoor) {
                return '';
            }
            return (previewLabels.panel_label ?? '')
                .replace(':door', String(this.selectedPreviewDoor.index + 1))
                .replace(':panel', String(index + 1));
        },

        frameVisual(color) {
            return frameVisual(this.metadata, color);
        },

        infillVisual(infillId) {
            return infillVisual(this.metadata, infillId);
        },
    };
}
