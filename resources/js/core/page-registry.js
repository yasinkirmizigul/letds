const registry = new Map();

/**
 * Register a page module.
 * Module contract:
 * - export default async function init(ctx) {}
 * - optional: export function destroy(ctx) {}
 */
export function register(name, module) {
    if (!name) throw new Error('[pages] register() requires name');
    if (!module) throw new Error(`[pages] register(${name}) requires module`);

    if (registry.has(name)) {
        console.warn(`[pages] Duplicate register for "${name}". Overwriting.`);
    }
    registry.set(name, module);
}

let current = {
    name: null,
    destroy: null,
    ctx: null,
    booted: false,
};

export async function bootPage(pageName, ctx) {
    if (!pageName) return;

    // ✅ guard: same page booted already
    if (current.booted && current.name === pageName) {
        return;
    }

    // ✅ if another page was booted before, destroy it (MPA’da pek gerekmez ama HMR/debug’da hayat kurtarır)
    if (current.destroy) {
        try { await current.destroy(current.ctx); } catch (e) { console.warn('[pages] destroy failed:', e); }
    }

    const mod = registry.get(pageName);
    if (!mod) {
        console.warn(`[pages] No module registered for: ${pageName}`);
        current = { name: pageName, destroy: null, ctx, booted: false };
        return;
    }

    const init = mod.default ?? mod.init;
    const destroy = mod.destroy;

    if (typeof init !== 'function') {
        console.warn(`[pages] Module for "${pageName}" has no init() / default export`);
        current = { name: pageName, destroy: null, ctx, booted: false };
        return;
    }

    await init(ctx);

    current = {
        name: pageName,
        destroy: (typeof destroy === 'function') ? destroy : null,
        ctx,
        booted: true,
    };
}
