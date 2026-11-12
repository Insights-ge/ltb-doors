let serial = 0;
const namespace = 'http://www.w3.org/2000/svg';

/** A self-contained SVG thumbnail keeps wood/glass paints alive when the preview redraws. */
export function createInfillGhost(svg, source, pointer) {
    const document = svg.ownerDocument;
    const bounds = source.getBoundingClientRect();
    const box = source.getBBox();
    const scale = Math.min(1.08, 220 / Math.max(bounds.width, bounds.height));
    const width = bounds.width * scale;
    const height = bounds.height * scale;
    const anchorX = Math.max(0, Math.min(1, (pointer.x - bounds.left) / bounds.width));
    const anchorY = Math.max(0, Math.min(1, (pointer.y - bounds.top) / bounds.height));
    const reducedMotion = document.defaultView.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const node = document.createElement('div');
    node.className = 'builder-infill-drag-ghost';
    node.setAttribute('aria-hidden', 'true');
    Object.assign(node.style, {width:`${width}px`,height:`${height}px`});
    const thumbnail = document.createElementNS(namespace, 'svg');
    thumbnail.setAttribute('viewBox', `${box.x} ${box.y} ${box.width} ${box.height}`);
    thumbnail.setAttribute('preserveAspectRatio', 'none');
    const prefix = `infill-ghost-${++serial}-`;
    const ids = new Map();
    const definitions = svg.querySelector('defs')?.cloneNode(true);
    if (definitions) {
        definitions.querySelectorAll('[id]').forEach(item => {
            ids.set(item.id, prefix + item.id);
            item.id = prefix + item.id;
        });
        thumbnail.appendChild(definitions);
    }
    const material = source.cloneNode(false);
    for (const attribute of [...material.attributes]) {
        if (attribute.name === 'class' || attribute.name.startsWith('data-')) material.removeAttribute(attribute.name);
    }
    const fill = material.getAttribute('fill');
    if (fill?.startsWith('url(#')) material.setAttribute('fill', `url(#${ids.get(fill.slice(5,-1)) ?? fill.slice(5,-1)})`);
    thumbnail.appendChild(material);
    node.appendChild(thumbnail);
    document.body.appendChild(node);
    let animation;
    let left = 0;
    let top = 0;
    function move(x, y) {
        left = x - width * anchorX;
        top = y - height * anchorY - 10;
        node.style.translate = `${left}px ${top}px`;
    }
    move(pointer.x, pointer.y);
    if (!reducedMotion) animation = node.animate([
        {transform:'rotate(0deg) scale(0.97)',opacity:0.65},
        {transform:'rotate(-3deg) scale(1.04)',opacity:0.96},
    ], {duration:140,easing:'ease-out',fill:'forwards'});
    return {
        node,
        move,
        remove() { animation?.cancel(); node.remove(); },
        settle(destination, remove = false) {
            if (reducedMotion) { node.remove(); return Promise.resolve(); }
            const target = destination ?? (source.isConnected ? source.getBoundingClientRect() : bounds);
            const current = document.defaultView.getComputedStyle(node);
            const from = {transform:current.transform,opacity:current.opacity};
            animation?.cancel();
            const dx = target.left + target.width/2 - (left + width/2);
            const dy = target.top + target.height/2 - (top + height/2);
            const transform = `translate(${dx}px, ${dy}px) rotate(0deg) scale(${remove ? 0.12 : target.width/width}, ${remove ? 0.12 : target.height/height})`;
            animation = node.animate([from,{transform,opacity:0}], {duration:remove ? 190 : 180,easing:'cubic-bezier(0.2, 0.8, 0.2, 1)',fill:'forwards'});
            return animation.finished.then(()=>node.remove(),()=>node.remove());
        },
    };
}
