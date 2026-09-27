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
    const box = () => { layoutReads++; return { left: 0, top: 0, width: 520, height: 520 }; };
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
        install('Image', class { addEventListener(_type, callback) { this.onload = callback; } set src(_value) { queueMicrotask(() => this.onload()); } });
        initServicesParticleLogo();
        await new Promise((resolve) => setImmediate(resolve));
        step(120);
        const population = draws;
        const measuredLayouts = layoutReads;
        assert.ok(population > 0);
        for (let i = 0; i < 120; i++) {
            events.get('pointermove')({ pointerType: 'mouse', clientX: 260 + Math.sin(i * .1) * 120, clientY: 260, timeStamp: now });
            step();
            assert.equal(draws, population, 'Moving the mouse must never spawn particles');
        }
        assert.equal(layoutReads, measuredLayouts, 'Animation and pointer movement must not measure page layout');
        events.get('pointermove')({ pointerType: 'mouse', clientX: 400, clientY: 260, timeStamp: now });
        step(180);
        const largeSymbols = rendered.filter((symbol) => symbol.size > 40);
        assert.ok(largeSymbols.length >= 2 && largeSymbols.length <= 5, 'Only a small local group should be enlarged');
        largeSymbols.forEach((a, index) => {
            largeSymbols.slice(index + 1).forEach((b) => {
                assert.ok(Math.hypot(a.x - b.x, a.y - b.y) >= (a.size + b.size) * .28,
                    'Enlarged symbols must leave space between their visible glyphs');
            });
        });
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
