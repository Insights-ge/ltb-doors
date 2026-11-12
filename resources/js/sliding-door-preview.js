import { calculateDoorGeometry, fitDoorGeometry } from './sliding-door-geometry.js';
import { frameVisual, infillVisual } from './sliding-door-visuals.js';

import { createInfillPaints } from './sliding-door-textures.js';

import { attachInfillDrag } from './sliding-door-infill-drag.js';

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
            infills: door.infills.map((infill) => ({
                ...infill,
                materialId: configuration.infillIdsByDoor?.[door.index]?.[infill.index] ?? null,
                id: configuration.infillInstanceIdsByDoor?.[door.index]?.[infill.index] ?? null,
                fill: infillVisual(metadata, configuration.infillIdsByDoor?.[door.index]?.[infill.index]).fill,
                texture: infillVisual(metadata, configuration.infillIdsByDoor?.[door.index]?.[infill.index]).texture ?? null,
            })),
        })),
    };
}

export function createDoorPreview(svg, { metadata, labels, locale, onRender, onSelect, onAdd = () => {}, onDragStart = () => {}, onDrop = () => {} }) {
    let configuration = null;
    let selection = { doorId: null, infillInstanceId: null };
    let sceneKey = null;
    let destroyed = false;
    const drag = attachInfillDrag(svg, {onStart:onDragStart,onDrop});

    function render(nextConfiguration) {
        if (destroyed) return;
        configuration = nextConfiguration;

        if (configuration === null) {
            sceneKey = null;
            drag.cancel();
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
            drag.cancel();
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
        drag.cancel();
        svg.replaceChildren();
        svg.style.setProperty('--builder-frame-fill', scene.frameFill);
        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
        drawScene(svg, scene, configuration, labels, locale);
        applySelection(svg, selection);
        onRender(true);
    }

    function select(event) {
        if (drag.consumeClick(event)) return;
        const add = event.target.closest?.('.builder-preview-add');
        if (add) { onAdd(Number(add.dataset.infillIndex)); return; }
        const group = event.target.closest?.('.builder-preview-door');
        const infill = event.target.closest?.('.builder-preview-infill');
        onSelect({ doorId: group?.dataset.doorId ?? null, infillInstanceId: infill?.dataset.infillInstanceId ?? null });
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
            drag.destroy();
            svg.removeEventListener('click', select);
            svg.replaceChildren();
        },
    };
}

function drawScene(svg, scene, configuration, labels, locale) {
    const title = element(svg, 'title');
    title.textContent = labels.drawing_label;

    const paintInfill = createInfillPaints(svg);
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
        for (const infill of door.infills) {
            element(group, 'rect', {
                ...rectangleAttributes(infill),
                fill: paintInfill(infill),
                class: 'builder-preview-infill',
                'data-infill-index': infill.index,
                'data-filled': infill.materialId !== null,
                'data-infill-instance-id': infill.id,
            });
        }

        for (const infill of door.infills.filter(item => item.materialId === null)) {
            const radius = Math.min(17, infill.width / 4, infill.height / 4);
            const x = infill.x + infill.width / 2;
            const y = infill.y + infill.height / 2;
            const add = element(group, 'g', {'class':'builder-preview-add', 'data-infill-index':infill.index, 'aria-hidden':'true'});
            element(add, 'circle', {cx:x,cy:y,r:radius,fill:'#FFFFFF',stroke:'#64748B','stroke-width':1});
            line(add,x-radius/2,y,x+radius/2,y,{stroke:'#334155','stroke-width':2,'pointer-events':'none'});
            line(add,x,y-radius/2,x,y+radius/2,{stroke:'#334155','stroke-width':2,'pointer-events':'none'});
        }

        element(group, 'rect', {
            ...rectangleAttributes(door),
            fill: 'none',
            stroke: scene.frameFill,
            'stroke-width': door.frameWidth,
            class: 'builder-preview-frame',
        });
        for (const infill of door.infills.slice(1)) {
            line(group, door.x, infill.y, door.x + door.width, infill.y, {
                stroke: scene.frameFill,
                'stroke-width': Math.min(door.frameWidth / 2, infill.height / 4),
                class: 'builder-preview-divider',
            });
        }
        for (const infill of door.infills) {
            const inset = Math.min(door.frameWidth, infill.width / 8, infill.height / 8);
            element(group, 'rect', {
                x: infill.x + inset, y: infill.y + inset,
                width: infill.width - inset * 2, height: infill.height - inset * 2,
                class: 'builder-preview-infill-highlight',
                'data-highlight-infill-instance-id': infill.id,
                'aria-hidden': 'true',
            });
        }
    }

    drawDimensions(svg, scene.opening, configuration, labels, locale);
}

function applySelection(svg, selection) {
    const selected = Array.from(svg.querySelectorAll('.builder-preview-infill')).find(infill => infill.dataset.infillInstanceId === selection.infillInstanceId);
    const index = selected?.dataset.infillIndex;
    for (const group of svg.querySelectorAll('.builder-preview-door')) {
        for (const infill of group.querySelectorAll('.builder-preview-infill')) {
            const active = index !== undefined && infill.dataset.infillIndex === index;
            infill.classList.toggle('is-selected', active);
            const highlight = Array.from(group.querySelectorAll('.builder-preview-infill-highlight')).find(item => item.dataset.highlightInfillInstanceId === infill.dataset.infillInstanceId);
            highlight?.classList.toggle('is-selected', active);
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
