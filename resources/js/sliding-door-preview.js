import { calculateDoorGeometry, fitDoorGeometry } from './sliding-door-geometry.js';
import { frameVisual, infillVisual } from './sliding-door-visuals.js';

import { createPanelPaints } from './sliding-door-textures.js';

const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';

/**
 * Rendering proportions and stroke widths are illustrative, not fabrication sizes.
 * The configuration supplies geometry inputs, frameColor and optional infillIdsByDoor.
 */
export function createPreviewScene(configuration, metadata, viewport) {
    const fitted = fitDoorGeometry(calculateDoorGeometry(configuration), { ...viewport, padding: 48 });

    return {
        ...fitted,
        frameFill: frameVisual(metadata, configuration.frameColor).fill,
        doors: fitted.doors.map((door) => ({
            ...door,
            id: configuration.doorIds?.[door.index] ?? null,
            frameWidth: Math.min(6, door.width / 12, door.height / 12),
            panels: door.panels.map((panel) => ({
                ...panel,
                id: configuration.panelIdsByDoor?.[door.index]?.[panel.index] ?? null,
                fill: infillVisual(metadata, configuration.infillIdsByDoor?.[door.index]?.[panel.index]).fill,
                texture: infillVisual(metadata, configuration.infillIdsByDoor?.[door.index]?.[panel.index]).texture ?? null,
            })),
        })),
    };
}

export function createDoorPreview(svg, { metadata, labels, locale, onRender, onSelect }) {
    let configuration = null;
    let selection = { doorId: null, panelId: null };
    let sceneKey = null;
    let destroyed = false;

    function render(nextConfiguration) {
        if (destroyed) return;
        configuration = nextConfiguration;

        if (configuration === null) {
            sceneKey = null;
            svg.replaceChildren();
            onRender(false);
            return;
        }

        const { width, height } = svg.parentElement.getBoundingClientRect();
        let scene;
        try {
            scene = createPreviewScene(configuration, metadata, { width, height });
        } catch (error) {
            if (!(error instanceof RangeError)) {
                throw error;
            }
            sceneKey = null;
            svg.replaceChildren();
            onRender(false);
            return;
        }

        const nextSceneKey = JSON.stringify([scene, configuration.openingWidth, configuration.openingHeight]);
        if (nextSceneKey === sceneKey) {
            applySelection(svg, selection);
            onRender(true);
            return;
        }
        sceneKey = nextSceneKey;
        svg.replaceChildren();
        svg.style.setProperty('--builder-frame-fill', scene.frameFill);
        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
        drawScene(svg, scene, configuration, labels, locale);
        applySelection(svg, selection);
        onRender(true);
    }

    function select(event) {
        const group = event.target.closest?.('.builder-preview-door');
        const panel = event.target.closest?.('.builder-preview-panel');
        onSelect({ doorId: group?.dataset.doorId ?? null, panelId: panel?.dataset.panelId ?? null });
    }

    const observer = new ResizeObserver(() => render(configuration));
    observer.observe(svg.parentElement);
    svg.addEventListener('click', select);

    return {
        render,
        setSelection(nextSelection) {
            if (destroyed) return;
            selection = nextSelection;
            applySelection(svg, selection);
        },
        destroy() {
            destroyed = true;
            observer.disconnect();
            svg.removeEventListener('click', select);
            svg.replaceChildren();
        },
    };
}

function drawScene(svg, scene, configuration, labels, locale) {
    const title = element(svg, 'title');
    title.textContent = labels.drawing_label;

    const paintPanel = createPanelPaints(svg);
    for (const door of scene.doors) {
        const group = element(svg, 'g', {
            'data-door-index': door.index, 'data-door-id': door.id,
            class: 'builder-preview-door',
        });
        // Keep labels readable without changing the geometry of very narrow doors.
        if (door.width >= 64) {
            text(group, door.x + door.width / 2, door.y - 12,
                labels.door_label.replace(':number', String(door.index + 1)), {
                    class: 'builder-preview-door-label',
                    'aria-hidden': 'true',
                    'pointer-events': 'none',
                });
        }
        for (const panel of door.panels) {
            element(group, 'rect', {
                ...rectangleAttributes(panel),
                fill: paintPanel(panel),
                class: 'builder-preview-panel',
                'data-panel-index': panel.index,
                'data-panel-id': panel.id,
            });
        }

        element(group, 'rect', {
            ...rectangleAttributes(door),
            fill: 'none',
            stroke: scene.frameFill,
            'stroke-width': door.frameWidth,
            class: 'builder-preview-frame',
        });
        for (const panel of door.panels.slice(1)) {
            line(group, door.x, panel.y, door.x + door.width, panel.y, {
                stroke: scene.frameFill,
                'stroke-width': Math.min(door.frameWidth / 2, panel.height / 4),
                class: 'builder-preview-divider',
            });
        }
        for (const panel of door.panels) {
            const inset = Math.min(door.frameWidth, panel.width / 8, panel.height / 8);
            element(group, 'rect', {
                x: panel.x + inset, y: panel.y + inset,
                width: panel.width - inset * 2, height: panel.height - inset * 2,
                class: 'builder-preview-panel-highlight',
                'data-highlight-panel-id': panel.id,
                'aria-hidden': 'true',
            });
        }
    }

    drawDimensions(svg, scene.opening, configuration, labels, locale);
}

function applySelection(svg, selection) {
    for (const group of svg.querySelectorAll('.builder-preview-door')) {
        const selected = group.dataset.doorId === selection.doorId;
        group.classList.toggle('is-selected', selected);
        for (const panel of group.querySelectorAll('.builder-preview-panel')) {
            const active = selected && panel.dataset.panelId === selection.panelId;
            panel.classList.toggle('is-selected', active);
        }
        for (const highlight of group.querySelectorAll('.builder-preview-panel-highlight')) {
            highlight.classList.toggle('is-selected', selected && highlight.dataset.highlightPanelId === selection.panelId);
        }
    }
}

function drawDimensions(svg, opening, configuration, labels, locale) {
    const { x, y, width, height } = opening;
    const bottom = y + height;
    const right = x + width;
    const group = element(svg, 'g', { class: 'builder-preview-dimensions', 'aria-hidden': 'true' });
    line(group, x, bottom + 24, right, bottom + 24);
    line(group, x, bottom + 10, x, bottom + 29);
    line(group, right, bottom + 10, right, bottom + 29);
    line(group, x - 24, y, x - 24, bottom);
    line(group, x - 29, y, x - 10, y);
    line(group, x - 29, bottom, x - 10, bottom);

    const widthLabel = labels.width_annotation.replace(':value', formatDimension(configuration.openingWidth, locale));
    const heightLabel = labels.height_annotation.replace(':value', formatDimension(configuration.openingHeight, locale));
    text(group, x + width / 2, bottom + 40, widthLabel);
    text(group, x - 36, y + height / 2, heightLabel, {
        transform: `rotate(-90 ${x - 36} ${y + height / 2})`,
    });

    element(svg, 'desc').textContent = `${widthLabel}; ${heightLabel}`;
}

function formatDimension(value, locale) {
    return new Intl.NumberFormat(locale, {
        maximumSignificantDigits: 12,
        notation: value >= 1e8 || value < 0.001 ? 'scientific' : 'standard',
    }).format(value);
}

function rectangleAttributes({ x, y, width, height }) {
    return { x, y, width, height };
}

function line(parent, x1, y1, x2, y2, attributes = {}) {
    return element(parent, 'line', { x1, y1, x2, y2, stroke: 'currentColor', 'stroke-width': 1, ...attributes });
}

function text(parent, x, y, content, attributes = {}) {
    const label = element(parent, 'text', {
        x, y, 'text-anchor': 'middle', class: 'builder-preview-annotation', ...attributes,
    });
    label.textContent = content;
}

function element(parent, name, attributes = {}) {
    const node = parent.ownerDocument.createElementNS(SVG_NAMESPACE, name);
    for (const [key, value] of Object.entries(attributes)) {
        node.setAttribute(key, String(value));
    }
    parent.appendChild(node);
    return node;
}
