import { bootPage } from './page-registry';

function getPageRoot() {
    const root = document.querySelector('[data-page]');
    if (!root) return null;
    return root;
}

function getPageName(root) {
    const page = root?.getAttribute('data-page') || '';
    return page.trim() || null;
}

export async function AppInit() {
    const root = getPageRoot();
    if (!root) {
        console.warn('[AppInit] No [data-page] found. Skipping page boot.');
        return { root: document.body, page: null, dataset: {} };
    }

    const page = getPageName(root);
    if (!page) {
        console.warn('[AppInit] Empty data-page. Skipping page boot.');
        return { root, page: null, dataset: root.dataset };
    }

    const ctx = { root, page, dataset: root.dataset };

    // ✅ KTUI init ONLY HERE (app.js içinden kaldır)
    try { window.KTComponents?.init?.(); } catch (e) { console.warn('[KTUI] KTComponents init failed:', e); }
    try { window.KTMenu?.init?.(); } catch (e) { console.warn('[KTUI] KTMenu init failed:', e); }
    try { window.KTDrawer?.init?.(); } catch (e) { console.warn('[KTUI] KTDrawer init failed:', e); }

    await bootPage(page, ctx);
    return ctx;
}
