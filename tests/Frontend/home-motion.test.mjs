import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/assets/site/home/js/home.js', import.meta.url), 'utf8');
const motionSource = source.slice(source.indexOf('  function initHeroMotion()'), source.indexOf('  function initViewportAnimations()'));

function setup({ reduced = false, distance = '24px', supported = true } = {}) {
    const created = [];
    const attributes = new Set();
    const listeners = new Map();
    const makeElement = () => ({
        animate: supported ? (frames, timing) => {
            const animation = { frames, timing, cancelled: false, cancel() { this.cancelled = true; } };
            created.push(animation);
            return animation;
        } : undefined,
    });
    const floats = [makeElement(), makeElement()];
    const shadows = [makeElement(), makeElement()];
    const hero = {
        querySelectorAll: (selector) => selector === '.home-hero-float' ? floats : shadows,
        setAttribute: (name) => attributes.add(name),
        removeAttribute: (name) => attributes.delete(name),
    };
    const preference = { matches: reduced, addEventListener: (name, callback) => listeners.set(name, callback) };
    vm.runInNewContext(`${motionSource}\ninitHeroMotion();`, {
        document: { getElementById: () => hero, timeline: { currentTime: 1234 } },
        window: { matchMedia: () => preference, addEventListener: (name, callback) => listeners.set(name, callback) },
        getComputedStyle: () => ({ getPropertyValue: () => distance }),
    });
    return { created, attributes, listeners, preference };
}

test('both P halves and shadows use identical timing on desktop and mobile', () => {
    for (const distance of ['30px', '24px']) {
        const { created, attributes } = setup({ distance });
        assert.equal(created.length, 4);
        assert.ok(attributes.has('data-home-motion-synced'));
        for (const animation of created) {
            assert.equal(animation.startTime, 1234);
            assert.equal(animation.timing.duration, 3500);
            assert.equal(animation.timing.iterations, Infinity);
        }
        assert.deepEqual(created[0].frames, created[1].frames);
        assert.equal(created[0].frames[1].transform, `translate3d(0, ${distance}, 0)`);
    }
});

test('resizing preserves the shared phase and reduced-motion changes cancel every animation', () => {
    const state = setup();
    state.listeners.get('resize')();
    assert.ok(state.created.slice(0, 4).every((animation) => animation.cancelled));
    assert.ok(state.created.slice(4).every((animation) => animation.startTime === 1234));
    state.preference.matches = true;
    state.listeners.get('change')();
    assert.ok(state.created.every((animation) => animation.cancelled));
    assert.ok(!state.attributes.has('data-home-motion-synced'));
    state.preference.matches = false;
    state.listeners.get('change')();
    assert.equal(state.created.length, 12);
});

test('reduced motion and older browsers retain the accessible CSS fallback', () => {
    for (const options of [{ reduced: true }, { supported: false }]) {
        const state = setup(options);
        assert.equal(state.created.length, 0);
        assert.ok(!state.attributes.has('data-home-motion-synced'));
    }
});

test('mobile header separates the left-aligned logo from non-wrapping controls', () => {
    const css = readFileSync(new URL('../../resources/css/app.css', import.meta.url), 'utf8');
    const mobile = css.slice(css.indexOf('@media (max-width: 720px)'));
    assert.match(mobile, /\.site-header__inner\s*\{[^}]*grid-template-columns:\s*minmax\(0, 1fr\) auto;/s);
    assert.match(mobile, /\.site-header \.probablue-brand--shell\s*\{[^}]*grid-column:\s*1;[^}]*justify-self:\s*start;/s);
    assert.match(mobile, /\.site-header__actions\s*\{[^}]*grid-column:\s*2;[^}]*flex-wrap:\s*nowrap;/s);
});
