let instance = 0;
const namespace = 'http://www.w3.org/2000/svg';

function append(parent, name, attributes) {
    const node = parent.ownerDocument.createElementNS(namespace, name);
    for (const [key, value] of Object.entries(attributes)) node.setAttribute(key, String(value));
    parent.appendChild(node);
    return node;
}

function shade(hex, amount) {
    const target = amount > 0 ? 255 : 0;
    const rgb = [1, 3, 5].map(index => parseInt(hex.slice(index, index + 2), 16));
    return `rgb(${rgb.map(value => Math.round(value + (target - value) * Math.abs(amount))).join(',')})`;
}

/** Native SVG paints: no remote images, arbitrary markup or material-dependent geometry. */
export function createPanelPaints(svg) {
    const prefix = `builder-material-${++instance}`;
    const paints = new Map();
    let defs;
    return ({ fill, texture }) => {
        if (!['wood_straight', 'wood_cathedral', 'glass', 'mirror'].includes(texture)) return fill;
        const key = `${fill}:${texture}`;
        if (paints.has(key)) return paints.get(key);
        defs ??= append(svg, 'defs', {});
        const id = `${prefix}-${paints.size}`;
        if (texture.startsWith('wood_')) {
            const pattern = append(defs, 'pattern', { id, width: 48, height: 180, patternUnits: 'userSpaceOnUse' });
            append(pattern, 'rect', { width: 48, height: 180, fill });
            for (let index = 0; index < 8; index++) {
                const x = index * 6;
                const path = texture === 'wood_straight'
                    ? `M${x} -5 C${x+5} 40 ${x-4} 100 ${x+1} 185`
                    : `M${x} 185 C${x-22} 110 ${24-index*2} ${10+index*8} 24 ${8+index*8} C${24+index*2} ${10+index*8} ${x+22} 110 ${48-x} 185`;
                append(pattern, 'path', { d: path, fill: 'none', stroke: shade(fill, index % 2 ? 0.28 : -0.38), 'stroke-width': index % 3 ? 0.65 : 1.1, opacity: 0.55 });
            }
        } else {
            const gradient = append(defs, 'linearGradient', { id, x1: '0%', y1: '0%', x2: '100%', y2: '100%' });
            const stops = texture === 'mirror'
                ? [[0,-0.25],[0.30,0.25],[0.46,0.70],[0.53,0.20],[0.61,0.55],[1,-0.20]]
                : [[0,0.20],[0.42,0.06],[0.49,0.55],[0.55,0.12],[1,-0.14]];
            for (const [offset, amount] of stops) append(gradient, 'stop', { offset, 'stop-color': shade(fill, amount) });
        }
        const paint = `url(#${id})`;
        paints.set(key, paint);
        return paint;
    };
}
