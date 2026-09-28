import assert from 'node:assert/strict';
import test from 'node:test';
import { initServicesParticleLogo } from '../../resources/js/site/visual-stories.js';

test('particle animation keeps a fixed population, avoids frame layout reads and respects reduced motion', async () => {
    const saved = new Map();
    const install = (key, value) => { saved.set(key, Object.getOwnPropertyDescriptor(globalThis, key)); Object.defineProperty(globalThis, key, { configurable: true, writable: true, value }); };
    const events = new Map();
    const frames = new Map();
    let frameId = 0, now = 0, layoutReads = 0, draws = 0;
    let transform = [1, 0, 0, 1, 0, 0];
    let rendered = [];
    let onMotionChange, onVisibility, onThemeChange;
    const paintedColors = [];
    const motion = { matches: false, addEventListener: (_type, callback) => { onMotionChange = callback; } };
    const context = {
        setTransform(...values) { transform = values; },
        clearRect() { draws = 0; rendered = []; },
        getImageData: () => ({ data: alpha }),
        fillText() {},
        strokeText() { paintedColors.push(this.strokeStyle); },
        drawImage(_image, _x, _y, size) {
            draws++;
            const ratio = Math.hypot(transform[0], transform[1]);
            rendered.push({ x: transform[4] / ratio, y: transform[5] / ratio, size });
        },
    };
    const canvas = { dataset: { logoSrc: '/logo.svg' }, getContext: () => context };
    const box = () => { layoutReads++; return { left: 0, right: 520, top: 0, width: 520, height: 520 }; };
    const hero = { getBoundingClientRect: box };
    const root = {
        querySelector: () => canvas, closest: () => hero, getBoundingClientRect: box,
        style: { setProperty() {} }, classList: { add() {} },
        addEventListener: (type, callback) => events.set(type, callback),
    };
    // A full mask exercises a heavier population than the actual P silhouette.
    const alpha = new Uint8ClampedArray(420 * 420 * 4).fill(255);
    const documentStub = {
        hidden: false, documentElement: { dataset: { siteTheme: 'dark' } },
        querySelector: () => root,
        createElement: () => ({ getContext: () => context }),
        addEventListener: (_type, callback) => { onVisibility = callback; },
    };
    const step = (count = 1) => {
        for (let i = 0; i < count; i++) {
            const callbacks = [...frames.values()]; frames.clear(); now += 1000 / 60;
            callbacks.forEach((callback) => callback(now));
            assert.ok(frames.size <= 1, 'Only one animation loop may be active');
        }
    };
    try {
        install('window', { matchMedia: () => motion, scrollY: 0, devicePixelRatio: 2, addEventListener() {} });
        install('document', documentStub);
        install('getComputedStyle', () => ({ getPropertyValue: () => '#559acc' }));
        install('requestAnimationFrame', (callback) => { frames.set(++frameId, callback); return frameId; });
        install('cancelAnimationFrame', (id) => frames.delete(id));
        install('ResizeObserver', class { observe() {} });
        install('MutationObserver', class { constructor(callback) { onThemeChange = callback; } observe() {} });
        install('IntersectionObserver', class { constructor(callback) { this.callback = callback; } observe() { this.callback([{ isIntersecting: true }]); } });
        install('Image', class {
            handlers = new Map();
            addEventListener(type, callback) { this.handlers.set(type, callback); }
            set src(_value) { queueMicrotask(() => this.handlers.get('load')?.()); }
        });
        initServicesParticleLogo();
        await new Promise((resolve) => setImmediate(resolve));
        assert.ok(canvas.width > 520 * 1.75, 'The drawing surface needs bleed beyond the visible P for enlarged edge glyphs');
        const formingPositions = rendered.slice(0, 20).map(({ x, y }) => [x, y]);
        assert.ok(formingPositions.length > 0, 'Particles must already be visible before the first animation frame');
        step(120);
        assert.ok(formingPositions.some(([x, y], index) => Math.hypot(x - rendered[index].x, y - rendered[index].y) > 20),
            'The initial cloud must gather into the logo instead of appearing in its finished form');
        const population = draws;
        const measuredLayouts = layoutReads;
        const restingAccents = rendered.filter((symbol) => symbol.size > 24).length;
        assert.ok(population > 0);
        for (let i = 0; i < 120; i++) {
            events.get('pointermove')({ pointerType: 'mouse', clientX: 260 + Math.sin(i * .1) * 120, clientY: 260, timeStamp: now });
            step();
            assert.equal(draws, population, 'Moving the mouse must never spawn particles');
        }
        assert.equal(layoutReads, measuredLayouts, 'Animation and pointer movement must not measure page layout');
        events.get('pointermove')({ pointerType: 'mouse', clientX: 400, clientY: 260, timeStamp: now });
        step(180);
        const largeSymbols = rendered.filter((symbol) => symbol.size > 24);
        assert.ok(largeSymbols.length > restingAccents && largeSymbols.length <= 160,
            `Hover should accent a local patch of the surface (${restingAccents} at rest, ${largeSymbols.length} on hover)`);
        assert.ok(Math.max(...rendered.map(({ size }) => size)) <= 45,
            'No hovered glyph should overpower the particle surface');
        const logoSymbols = rendered.slice(0, -22);
        const leftEdge = Math.min(...logoSymbols.map(({ x, size }) => x - size * .4));
        const rightEdge = Math.max(...logoSymbols.map(({ x, size }) => x + size * .4));
        assert.ok(leftEdge >= 81 && rightEdge <= 602,
            `The projected mark must remain inside the hero when hovered near an edge (${leftEdge}, ${rightEdge})`);
        const darkColors = paintedColors.slice(-4);
        documentStub.documentElement.dataset.siteTheme = 'light';
        onThemeChange(); step();
        assert.notDeepEqual(paintedColors.slice(-4), darkColors, 'Glyphs must be repainted with light-theme contrast');
        motion.matches = true;
        onMotionChange(); step();
        assert.equal(frames.size, 0, 'Reduced motion renders once and stops');
        motion.matches = false;
        onMotionChange(); step();
        assert.equal(frames.size, 1, 'Motion can resume without duplicate loops');
        documentStub.hidden = true;
        onVisibility();
        assert.equal(frames.size, 0, 'Background tabs stop drawing');
    } finally {
        saved.forEach((descriptor, key) => { if (descriptor) Object.defineProperty(globalThis, key, descriptor); else delete globalThis[key]; });
    }
});
