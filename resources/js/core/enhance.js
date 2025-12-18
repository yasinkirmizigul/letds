const _registry = new Set();
const _ranFor = new WeakSet();

function _toArray(input) {
    if (!input) return [];
    if (input instanceof Element || input === document) return [input];
    if (input instanceof NodeList || Array.isArray(input)) return Array.from(input);
    return [];
}

function _runOncePerElement(fn, el) {
    if (!el || !(el instanceof Element)) return;
    if (!el.__enhanceRan) el.__enhanceRan = new WeakSet();
    if (el.__enhanceRan.has(fn)) return;
    el.__enhanceRan.add(fn);
    fn(el);
}

export const Enhance = {
    register(fn) {
        if (typeof fn === 'function') _registry.add(fn);
    },

    run(root = document) {
        const roots = _toArray(root);
        roots.forEach(r => {
            // aynı root’ta spam init yapma (document’i her seferinde çalıştırmak istersen bu guard’ı kaldırabilirsin)
            if (r !== document && _ranFor.has(r)) return;
            if (r !== document) _ranFor.add(r);

            _registry.forEach(fn => {
                try { fn(r); } catch (e) { console.warn('[Enhance] init failed:', e); }
            });
        });
    },

    runEach(selector, fn, root = document) {
        root.querySelectorAll(selector).forEach(el => _runOncePerElement(fn, el));
    }
};

export function enhance(root = document) {
    Enhance.run(root);
}
