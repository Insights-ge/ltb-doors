import { exportConfiguration } from './sliding-door-export.js';
import { saveDraft, readDraft, configurationSnapshot } from './sliding-door-draft.js';
import { summarizeConfiguration } from './sliding-door-review.js';
import { validateBuilder, incompatibleInfillIds, catalogSizingReference } from './sliding-door-rules.js';
import { createInitialConfiguration, parseMeasurement, measurementError, applyModelLayout } from './sliding-door-state.js';
import { createDoorPreview } from './sliding-door-preview.js';
import { frameVisual, infillVisual } from './sliding-door-visuals.js';
import { reconcilePreviewConfiguration, previewSelectionTargets, reconcilePreviewSelection } from './sliding-door-selection.js';

export default function slidingDoorBuilder({ initialStep, payload, previewLabels = {}, validationLabels = {}, fieldValidationMessages = {}, storageKey = null, formState = null }) {
    let preview;
    let stopWatching;
    let destroyed = false;
    let previousConfiguration = null;
    let stopSelectionWatching;
    let stopFormWatching;
    let rootElement;
    let storage;
    let lastSaved = null;

    return {
        activeStep: initialStep,
        formState,
        formBusy: false,
        catalog: payload.catalog,
        metadata: payload.metadata,
        previewConfiguration: createInitialConfiguration(),
        exportFailed: false,
        draftStatus: null,
        attemptedSteps: [],
        pendingChange: null,
        sizeInputs: { openingWidth: '', openingHeight: '' },
        sizeErrors: { openingWidth: false, openingHeight: false },
        hasPreview: false,
        previewDoors: [],
        previewSelection: { doorId: null, panelId: null },

        applyFormFields() {
            if (!this.formState || destroyed) return;
            const state = this.formState;
            for (const field of ['openingWidth', 'openingHeight']) {
                const text = String(state[field] ?? '');
                if (this.sizeInputs[field] !== text) this.setMeasurement(field, text);
            }
            const id = (value) => value === null || value === undefined || value === '' ? null : Number(value);
            const modelId = id(state.modelId);
            const variantId = id(state.variantId);
            const model = this.catalog.models.find((candidate) => candidate.id === modelId);
            const variant = model?.variants.find((candidate) => candidate.id === variantId);
            const fields = { modelId, variantId, frameDesignCode: state.frameDesignCode || null, frameColor: variant?.color ?? null, softClose: Boolean(variant?.hasSoftCloseMechanism) };
            const next = applyModelLayout({ ...this.previewConfiguration, ...fields }, model);
            const modelChange = fields.modelId !== this.previewConfiguration.modelId
                || next.panelWeights !== this.previewConfiguration.panelWeights;
            if (this.pendingChange?.kind === 'model') {
                this.pendingChange.configuration = next;
            } else if (modelChange) {
                this.requestConfigurationChange(next, 'model');
            } else if (Object.entries(fields).some(([field, value]) => this.previewConfiguration[field] !== value)) {
                this.previewConfiguration = next;
            }
        },

        async restoreFormFields() {
            if (!this.formState || !this.$wire) return;
            this.formBusy = true;
            try {
                const config = this.previewConfiguration;
                const fields = {
                    openingWidth: this.sizeInputs.openingWidth,
                    openingHeight: this.sizeInputs.openingHeight,
                    modelId: config.modelId ?? null,
                    frameDesignCode: config.frameDesignCode ?? null,
                    variantId: config.variantId ?? null,
                };
                this.formState = fields;
                await this.$wire.restoreBuilderFields(fields);
            } finally { this.formBusy = false; }
        },

        exportDesign() {
            if (!this.canReview) { this.goToStep(4); return; }
            this.exportFailed = false;
            try {
                const content = exportConfiguration(this.previewConfiguration, this.catalog, this.metadata);
                const url = URL.createObjectURL(new Blob([content], { type: 'application/json' }));
                const link = document.createElement('a');
                link.href = url;
                link.download = 'sliding-door-design.json';
                try { document.body.appendChild(link); link.click(); }
                finally { link.remove(); setTimeout(() => URL.revokeObjectURL(url), 0); }
            } catch { this.exportFailed = true; }
        },

        printDesign() {
            if (!this.canReview) { this.goToStep(4); return; }
            this.goToStep(4);
            this.$nextTick(() => window.print());
        },

        discardStoredDraft() {
            try { storage?.removeItem(storageKey); this.draftStatus = null; lastSaved = null; }
            catch { this.draftStatus = 'unavailable'; }
        },

        saveLocalDraft() {
            if (!storageKey || !storage || !this.canReview) return;
            const fingerprint = JSON.stringify(configurationSnapshot(this.previewConfiguration));
            if (fingerprint === lastSaved) return;
            this.draftStatus = saveDraft(storage, storageKey, this.previewConfiguration);
            if (this.draftStatus === 'saved') lastSaved = fingerprint;
        },

        get layoutSummary() {
            if (!this.selectedModel) return '';
            return (previewLabels.layout_label ?? '')
                .replace(':panels', String(this.selectedModel.partitionCount + 1))
                .replace(':partitions', String(this.selectedModel.partitionCount));
        },

        get reviewDesign() {
            return summarizeConfiguration(this.previewConfiguration, this.catalog, this.metadata);
        },

        issueMessage(issue) {
            return (fieldValidationMessages[issue.field]?.[issue.code] ?? validationLabels[issue.code] ?? '')
                .replace(':door', String((issue.doorIndex ?? 0) + 1))
                .replace(':panel', String((issue.panelIndex ?? 0) + 1))
                .replace(':min', String(issue.limit ?? ''))
                .replace(':max', String(issue.limit ?? ''));
        },

        infillIsIncompatible(infillId) {
            return incompatibleInfillIds(this.previewConfiguration, this.selectedVariant).has(infillId);
        },

        get validation() {
            const result = validateBuilder(this.previewConfiguration, this.catalog, this.metadata);
            for (const [field, invalid] of Object.entries(this.sizeErrors)) {
                if (invalid && this.selectedModel) {
                    const errorField = `builder-${field}`;
                    const code = measurementError(this.sizeInputs[field]);
                    result.errors = result.errors.filter((issue) => issue.field !== errorField);
                    if (code) result.errors.unshift({ code, step: 1, field: errorField });
                }
            }
            const sizeFields = ['builder-model', 'builder-openingWidth', 'builder-openingHeight'];
            result.errors.sort((first, second) => first.step - second.step
                || (first.step === 1 ? sizeFields.indexOf(first.field) - sizeFields.indexOf(second.field) : 0));
            return result;
        },

        get canReview() {
            return this.validation.errors.length === 0;
        },

        fieldError(field) {
            if (field === 'builder-design' && !this.selectedModel) return '';
            if (field === 'builder-variant' && (!this.selectedModel || !this.previewConfiguration.frameDesignCode)) return '';
            const issue = this.validation.errors.find((candidate) => {
                if (candidate.field !== field) return false;
                const sizeField = field.replace('builder-', '');
                if (!this.attemptedSteps.includes(candidate.step) && !this.sizeErrors[sizeField]) return false;
                if (field === 'builder-finishes') {
                    return candidate.doorIndex === this.selectedPreviewDoor?.index
                        && candidate.panelIndex === this.selectedPreviewPanel?.index;
                }
                return true;
            });
            return issue ? this.issueMessage(issue) : '';
        },

        focusIssue(issue) {
            issue = this.validation.errors.find((candidate) => candidate.step < issue.step) ?? issue;
            this.attemptedSteps = [...new Set([...this.attemptedSteps, issue.step])];
            if (issue.doorIndex !== undefined) {
                const door = this.previewDoors[issue.doorIndex];
                if (door) this.selectPreviewDoor(door.id);
                const panel = door?.panels[issue.panelIndex];
                if (panel) this.selectPreviewPanel(door.id, panel.id);
            }
            this.activeStep = issue.step;
            this.$nextTick?.(() => {
                const field = rootElement?.querySelector(`#${issue.field}`);
                (field?.querySelector('button:not(:disabled), input:not(:disabled), select:not(:disabled)') ?? field)?.focus();
            });
        },

        requestReset() {
            if (JSON.stringify(this.previewConfiguration) === JSON.stringify(createInitialConfiguration()) && !Object.values(this.sizeInputs).some(Boolean)) return;
            this.$dispatch('open-modal', { id: 'builder-confirm-reset' });
        },

        resetConfiguration() {
            previousConfiguration = null;
            lastSaved = null;
            try { storage?.removeItem(storageKey); this.draftStatus = null; } catch { this.draftStatus = 'unavailable'; }
            this.pendingChange = null;
            this.previewConfiguration = createInitialConfiguration();
            this.previewSelection = { doorId: null, panelId: null };
            this.sizeInputs = { openingWidth: '', openingHeight: '' };
            this.sizeErrors = { openingWidth: false, openingHeight: false };
            this.attemptedSteps = [];
            this.activeStep = 1;
            this.$nextTick?.(() => rootElement?.querySelector('#builder-step-1')?.focus());
            this.$dispatch('close-modal', { id: 'builder-confirm-reset' });
            this.restoreFormFields();
        },

        async goToStep(step) {
            if (this.formBusy || ![1, 2, 3, 4].includes(step)) return;
            this.applyFormFields();
            const target = step > this.activeStep ? Math.min(step, this.activeStep + 1) : step;
            if (target > this.activeStep && this.activeStep === 1) {
                for (const field of ['openingWidth', 'openingHeight']) this.setMeasurement(field, this.sizeInputs[field]);
            }
            if (target > this.activeStep && this.activeStep <= 2 && this.formState) {
                const currentStep = this.activeStep;
                this.attemptedSteps = [...new Set([...this.attemptedSteps, currentStep])];
                this.formBusy = true;
                let valid;
                try { valid = await this.$wire.validateBuilderSection(currentStep); }
                finally { this.formBusy = false; }
                this.applyFormFields();
                if (!valid) {
                    const issue = this.validation.errors.find((candidate) => candidate.step <= currentStep);
                    if (issue) this.focusIssue(issue);
                    return;
                }
            }
            if (target > 1) {
                const attempted = Array.from({ length: target - 1 }, (_, index) => index + 1);
                this.attemptedSteps = [...new Set([...this.attemptedSteps, ...attempted])];
                const issue = this.validation.errors.find((candidate) => candidate.step < target);
                if (issue) { this.focusIssue(issue); return; }
            }
            this.activeStep = target;
            this.$nextTick?.(() => rootElement?.querySelector(`#builder-step-${target}`)?.focus());
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
            return [...new Set(this.selectedModel?.variants.filter(variant => variant.hasPartition && this.previewConfiguration.openingHeight >= variant.heightRangeMin && this.previewConfiguration.openingHeight <= variant.heightRangeMax).map((variant) => variant.frameDesignCode) ?? [])];
        },

        get availableVariants() {
            return this.selectedModel?.variants.filter((variant) => variant.frameDesignCode === this.previewConfiguration.frameDesignCode && variant.hasPartition && this.previewConfiguration.openingHeight >= variant.heightRangeMin && this.previewConfiguration.openingHeight <= variant.heightRangeMax) ?? [];
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
            this.previewConfiguration = { ...this.previewConfiguration, variantId: variant.id, frameColor: variant.color, softClose: Boolean(variant.hasSoftCloseMechanism) };
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

        requestConfigurationChange(configuration, kind = null) {
            const current = this.previewConfiguration;
            const replacesFinish = current?.infillIdsByDoor?.some((finishes, doorIndex) => finishes.some((infillId, panelIndex) => (
                infillId !== null && infillId !== undefined
                && infillId !== configuration.infillIdsByDoor?.[doorIndex]?.[panelIndex]
            )));
            if (replacesFinish) {
                this.pendingChange = { before: JSON.stringify(current), configuration, kind };
                this.$dispatch('open-modal', { id: 'builder-confirm-replacement' });
            } else {
                this.previewConfiguration = configuration;
            }
        },

        confirmConfigurationChange() {
            const pending = this.pendingChange;
            const applied = pending && pending.before === JSON.stringify(this.previewConfiguration);
            if (applied) this.previewConfiguration = pending.configuration;
            this.pendingChange = null;
            this.$dispatch('close-modal', { id: 'builder-confirm-replacement' });
            if (pending?.kind === 'model') {
                if (applied) this.applyFormFields();
                else this.restoreFormFields();
            }
        },

        cancelConfigurationChange() {
            const restore = this.pendingChange?.kind === 'model';
            this.pendingChange = null;
            this.$dispatch('close-modal', { id: 'builder-confirm-replacement' });
            if (restore) this.restoreFormFields();
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
            this.$nextTick(async () => {
                rootElement = this.$el;
                if (storageKey) {
                    try { storage = window.localStorage; } catch { this.draftStatus = 'unavailable'; }
                    if (storage) {
                        const draft = readDraft(storage, storageKey, this.catalog, this.metadata);
                        if (draft.configuration) {
                            this.previewConfiguration = draft.configuration;
                            this.sizeInputs = { openingWidth: String(draft.configuration.openingWidth), openingHeight: String(draft.configuration.openingHeight) };
                            this.attemptedSteps = draft.status === 'outdated' ? [1, 2, 3] : [];
                            if (draft.status === 'restored') lastSaved = JSON.stringify(configurationSnapshot(draft.configuration));
                            await this.restoreFormFields();
                        }
                        this.draftStatus = draft.status;
                    }
                }
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
                if (this.formState) {
                    this.applyFormFields();
                    stopFormWatching = this.$watch('formState', () => this.applyFormFields());
                }
                stopWatching = this.$watch('previewConfiguration', () => this.updatePreview());
                stopSelectionWatching = this.$watch('previewSelection', (selection) => preview.setSelection(selection));
                this.updatePreview();
            });
        },

        destroy() {
            destroyed = true;
            stopWatching?.();
            stopSelectionWatching?.();
            stopFormWatching?.();
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
            this.saveLocalDraft();
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
                ?? rootElement?.querySelector(`#builder-step-${this.activeStep}`);
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
