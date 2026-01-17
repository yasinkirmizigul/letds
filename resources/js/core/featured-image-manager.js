// resources/js/core/featured-image-manager.js

function cleanupObjectUrl(state) {
    if (!state?.lastObjectUrl) return;
    try { URL.revokeObjectURL(state.lastObjectUrl); } catch {}
    state.lastObjectUrl = null;
}

function togglePreviewUI({ img, ph, hasSrc }) {
    if (!img || !ph) return;

    // ✅ kesin: attribute her zaman kazanır
    img.toggleAttribute('hidden', !hasSrc);
    ph.toggleAttribute('hidden', !!hasSrc);

    // sınıf da kalsın (bazı yerlerde styling için)
    img.classList.toggle('hidden', !hasSrc);
    ph.classList.toggle('hidden', !!hasSrc);
}

function safeClearFileInput(input) {
    if (!input) return;
    try { input.value = ''; } catch {}
}

function getHost(el) {
    return el?.closest?.('[data-featured-image-manager="1"]') || null;
}

function ensureHostInited(host, states) {
    if (!host || host.__featuredInited) return;

    const input = host.querySelector('[data-featured-input]');
    const mediaId = host.querySelector('[data-featured-media-id]');
    const img = host.querySelector('[data-featured-preview]');
    const ph = host.querySelector('[data-featured-placeholder]');

    if (!img || !ph) return;

    host.__featuredInited = true;

    const state = { lastObjectUrl: null };
    states.set(host, state);

    // initial state
    const initialHas = !!String(img.getAttribute('src') || '').trim();
    togglePreviewUI({ img, ph, hasSrc: initialHas });

    // file input varsa, initial mediaId varsa bile preview url yoksa placeholder görünsün
    if (!initialHas) togglePreviewUI({ img, ph, hasSrc: false });
}

// ---- Delegated handlers (works even if init order is messy) ----

function handleFileChange(e, states) {
    const input = e.target;
    if (!input?.matches?.('[data-featured-input]')) return;

    const host = getHost(input);
    if (!host) return;

    ensureHostInited(host, states);

    const state = states.get(host) || { lastObjectUrl: null };
    states.set(host, state);

    const mediaId = host.querySelector('[data-featured-media-id]');
    const img = host.querySelector('[data-featured-preview]');
    const ph = host.querySelector('[data-featured-placeholder]');

    if (!img || !ph) return;

    const file = input.files && input.files[0] ? input.files[0] : null;

    cleanupObjectUrl(state);

    if (!file) {
        const existing = String(img.getAttribute('src') || '').trim();
        if (!existing) {
            img.src = '';
            togglePreviewUI({ img, ph, hasSrc: false });
        }
        return;
    }

    // upload seçildiyse -> library seçimini sıfırla
    if (mediaId) mediaId.value = '';

    state.lastObjectUrl = URL.createObjectURL(file);
    img.src = state.lastObjectUrl;
    togglePreviewUI({ img, ph, hasSrc: true });
}

function handleClearClick(e, states) {
    const btn = e.target?.closest?.('[data-featured-clear]');
    if (!btn) return;

    const host = getHost(btn);
    if (!host) return;

    ensureHostInited(host, states);

    const state = states.get(host) || { lastObjectUrl: null };
    states.set(host, state);

    const input = host.querySelector('[data-featured-input]');
    const mediaId = host.querySelector('[data-featured-media-id]');
    const img = host.querySelector('[data-featured-preview]');
    const ph = host.querySelector('[data-featured-placeholder]');

    cleanupObjectUrl(state);
    safeClearFileInput(input);
    if (mediaId) mediaId.value = '';

    if (img) img.src = '';
    togglePreviewUI({ img, ph, hasSrc: false });
}

function handleMediaPick(e, states) {
    const d = e?.detail || {};
    const id = d.id ?? '';
    const url = d.url ?? '';
    const mime = d.mime ?? '';

    // sadece image
    if (mime && !String(mime).startsWith('image/')) return;
    if (!id && !url) return;

    // media-picker tarafı selector gönderiyorsa: direkt hedef input’u bul
    const inputSel = d?.target?.inputSel || '';
    if (!inputSel) return;

    let targetInput = null;
    try { targetInput = document.querySelector(inputSel); } catch { targetInput = null; }
    if (!targetInput) return;

    const host = getHost(targetInput);
    if (!host) return;

    ensureHostInited(host, states);

    const state = states.get(host) || { lastObjectUrl: null };
    states.set(host, state);

    const fileInput = host.querySelector('[data-featured-input]');
    const mediaId = host.querySelector('[data-featured-media-id]');
    const img = host.querySelector('[data-featured-preview]');
    const ph = host.querySelector('[data-featured-placeholder]');

    cleanupObjectUrl(state);
    safeClearFileInput(fileInput);

    if (mediaId) mediaId.value = id ?? '';

    if (url && img) {
        img.src = url;
        togglePreviewUI({ img, ph, hasSrc: true });
    }
}

// ---- Public API ----

export default function initFeaturedImageManager(root = document) {
    // singleton guards (duplicate listeners istemiyorsun)
    if (window.__fim_inited) {
        // ama yeni DOM eklendiyse host init ederiz
        root.querySelectorAll('[data-featured-image-manager="1"]').forEach((h) => {
            if (!h.__featuredInited) window.__fim_states && ensureHostInited(h, window.__fim_states);
        });
        return;
    }

    window.__fim_inited = true;
    const states = new WeakMap();
    window.__fim_states = states;

    // init existing hosts
    root.querySelectorAll('[data-featured-image-manager="1"]').forEach((h) => ensureHostInited(h, states));

    // delegated listeners
    document.addEventListener('change', (e) => handleFileChange(e, states), true);
    document.addEventListener('click', (e) => handleClearClick(e, states), true);
    document.addEventListener('media:pick', (e) => handleMediaPick(e, states));

    // observe new hosts (page-registry / modal render vb.)
    const obs = new MutationObserver((mutations) => {
        for (const m of mutations) {
            for (const n of m.addedNodes || []) {
                if (!(n instanceof HTMLElement)) continue;
                if (n.matches?.('[data-featured-image-manager="1"]')) ensureHostInited(n, states);
                n.querySelectorAll?.('[data-featured-image-manager="1"]').forEach((h) => ensureHostInited(h, states));
            }
        }
    });

    obs.observe(document.documentElement, { childList: true, subtree: true });
    window.__fim_obs = obs;
}

export function destroyFeaturedImageManager() {
    // hard reset (debug için)
    try { window.__fim_obs?.disconnect?.(); } catch {}
    window.__fim_obs = null;

    window.__fim_inited = false;
    window.__fim_states = null;
}
