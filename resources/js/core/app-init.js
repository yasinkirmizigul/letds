import { bootPage } from './page-registry';
import { enhance } from './enhance';

function getPageRoot() {
    return document.querySelector('[data-page]') || null;
}

function getPageName(root) {
    const page = root?.getAttribute('data-page') || '';
    return page.trim() || null;
}

function createCtx(root, page) {
    const ac = new AbortController();
    const cleanups = [];

    return {
        root,
        page,
        dataset: root?.dataset ?? {},

        abortController: ac,
        signal: ac.signal,

        cleanup(fn) {
            if (typeof fn === 'function') cleanups.push(fn);
        },

        async destroyAll() {
            // Abort first: listeners added with { signal } are released automatically
            try {
                ac.abort();
            } catch (_) {}

            // Then run manual cleanups (LIFO)
            for (let i = cleanups.length - 1; i >= 0; i--) {
                try {
                    await cleanups[i]();
                } catch (e) {
                    console.warn('[ctx] cleanup failed:', e);
                }
            }
            cleanups.length = 0;
        },
    };
}

export async function AppInit() {
    const root = getPageRoot();

    if (!root) {
        console.warn('[AppInit] No [data-page] found. Skipping page boot.');
        return createCtx(document.body, null);
    }

    const page = getPageName(root);
    if (!page) {
        console.warn('[AppInit] Empty data-page. Skipping page boot.');
        return createCtx(root, null);
    }

    const ctx = createCtx(root, page);

    try {
        window.KTComponents?.init?.();
    } catch (e) {
        console.warn('[KTUI] KTComponents init failed:', e);
    }

    try {
        window.KTMenu?.init?.();
    } catch (e) {
        console.warn('[KTUI] KTMenu init failed:', e);
    }

    try {
        window.KTDrawer?.init?.();
    } catch (e) {
        console.warn('[KTUI] KTDrawer init failed:', e);
    }

    // ✅ burada
    try {
        enhance(root);
    } catch (e) {
        console.warn('[Enhance] failed:', e);
    }

    await bootPage(page, ctx);

    return ctx;
}
