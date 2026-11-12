import { createInfillGhost } from './sliding-door-infill-ghost.js';

/** Pointer gestures work with mouse, pen and touch; the form controls remain keyboard alternatives. */
export function attachInfillDrag(svg, { onStart, onDrop }) {
    let gesture = null;
    let suppressedClick = null;
    let ghosts = [];
    const settling = new Set();
    const root = svg.closest('.builder-preview') ?? svg.parentElement;
    const hit = event => svg.ownerDocument.elementFromPoint(event.clientX, event.clientY);
    const targetIndex = node => {
        const target = node?.closest('.builder-preview-infill, .builder-preview-add');
        return target ? Number(target.dataset.infillIndex) : null;
    };
    function clearTargets() {
        root.querySelectorAll('.is-drop-target').forEach(node => node.classList.remove('is-drop-target'));
    }
    function settleGhost(destination, remove = false) {
        svg.classList.remove('is-dragging');
        root.querySelectorAll('.is-drag-source').forEach(node => node.classList.remove('is-drag-source'));
        const current = ghosts;
        ghosts = [];
        current.forEach(({ghost, door}) => {
            const target = remove ? destination : door.querySelector(`.builder-preview-infill[data-infill-index="${targetIndex(destination)}"]`);
            settling.add(ghost);
            ghost.settle(target?.getBoundingClientRect(), remove).then(() => settling.delete(ghost));
        });
    }
    function cancel() {
        const current = gesture;
        gesture = null;
        clearTargets();
        settleGhost();
        if (current?.active) onDrop(null, false);
        if (current && svg.hasPointerCapture(current.id)) svg.releasePointerCapture(current.id);
    }
    function down(event) {
        if (event.button !== 0 || !event.isPrimary) return;
        const source = event.target.closest?.('.builder-preview-infill[data-filled="true"]');
        if (!source) return;
        gesture = {id:event.pointerId, index:Number(source.dataset.infillIndex), x:event.clientX, y:event.clientY, active:false, source};
    }
    function move(event) {
        if (!gesture || gesture.id !== event.pointerId) return;
        if (!gesture.active && Math.hypot(event.clientX-gesture.x,event.clientY-gesture.y) < 6) return;
        if (!gesture.active) {
            gesture.active=true;
            svg.setPointerCapture(event.pointerId);
            const origin = gesture.source.getBoundingClientRect();
            ghosts = [...svg.querySelectorAll(`.builder-preview-infill[data-infill-index="${gesture.index}"]`)].map(source => {
                const bounds = source.getBoundingClientRect();
                const dx = bounds.left + bounds.width / 2 - origin.left - origin.width / 2;
                const dy = bounds.top + bounds.height / 2 - origin.top - origin.height / 2;
                return {
                    ghost: createInfillGhost(svg, source, {x:gesture.x + dx, y:gesture.y + dy}),
                    door: source.closest('.builder-preview-door'),
                    dx,
                    dy,
                };
            });
            svg.classList.add('is-dragging');
            root.querySelectorAll(`.builder-preview-infill[data-infill-index="${gesture.index}"]`).forEach(node=>node.classList.add('is-drag-source'));
            onStart(gesture.index);
        }
        ghosts.forEach(({ghost, dx, dy}) => ghost.move(event.clientX + dx, event.clientY + dy));
        event.preventDefault();
        clearTargets();
        const node=hit(event);
        const remove=node?.closest('[data-remove-infill-drop]');
        if (remove) remove.classList.add('is-drop-target');
        else {
            const index=targetIndex(node);
            if (index !== null && index !== gesture.index) root.querySelectorAll(`.builder-preview-infill[data-infill-index="${index}"]`).forEach(infill=>infill.classList.add('is-drop-target'));
        }
    }
    function up(event) {
        if (!gesture || gesture.id !== event.pointerId) return;
        const current=gesture;
        gesture=null;
        clearTargets();
        if (svg.hasPointerCapture(event.pointerId)) svg.releasePointerCapture(event.pointerId);
        if (!current.active) return;
        suppressedClick={until:performance.now()+400,x:event.clientX,y:event.clientY};
        const node=hit(event);
        const remove=node?.closest('[data-remove-infill-drop]');
        const target=node?.closest('.builder-preview-infill, .builder-preview-add');
        settleGhost(remove ?? target,Boolean(remove));
        onDrop(targetIndex(node),Boolean(remove));
    }
    function lost(event) {
        if (event.target === svg && gesture?.id === event.pointerId) cancel();
    }
    svg.addEventListener('pointerdown',down);
    svg.ownerDocument.addEventListener('pointermove',move);
    svg.ownerDocument.addEventListener('pointerup',up);
    svg.addEventListener('pointercancel',cancel);
    svg.addEventListener('lostpointercapture',lost);
    return {
        cancel,
        consumeClick(event) { const previous=suppressedClick; suppressedClick=null; return previous !== null && performance.now()<previous.until && Math.hypot(event.clientX-previous.x,event.clientY-previous.y)<6; },
        destroy() {
            cancel();
            settling.forEach(item=>item.remove());
            settling.clear();
            svg.removeEventListener('pointerdown',down);
            svg.ownerDocument.removeEventListener('pointermove',move);
            svg.ownerDocument.removeEventListener('pointerup',up);
            svg.removeEventListener('pointercancel',cancel);
            svg.removeEventListener('lostpointercapture',lost);
        },
    };
}
