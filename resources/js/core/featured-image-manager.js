function cleanupObjectUrl(state) {
    if (!state?.lastObjectUrl) return;
    try { URL.revokeObjectURL(state.lastObjectUrl); } catch {}
    state.lastObjectUrl = null;
}

function togglePreviewUI({ img, ph, hasSrc }) {
    if (!img || !ph) return;

    img.toggleAttribute('hidden', !hasSrc);
    ph.toggleAttribute('hidden', !!hasSrc);

    img.classList.toggle('hidden', !hasSrc);
    ph.classList.toggle('hidden', !!hasSrc);
}

function safeClearFileInput(input) {
    if (!input) return;
    try { input.value = ''; } catch {}
}

function emitFeaturedChange(host) {
    if (!host) return;

    host.dispatchEvent(new CustomEvent('featured-image:change', {
        bubbles: true,
        detail: {
            src: host.querySelector('[data-featured-preview]')?.getAttribute('src') || '',
        },
    }));
}

function getHost(el) {
    return el?.closest?.('[data-featured-image-manager="1"]') || null;
}

function ensureHostInited(host, states) {
    if (!host || host.__fimHostInited) return;

    const img = host.querySelector('[data-featured-preview]');
    const ph = host.querySelector('[data-featured-placeholder]');
    if (!img || !ph) return;

    host.__fimHostInited = true;

    const state = { lastObjectUrl: null };
    states.set(host, state);

    const initialHas = !!String(img.getAttribute('src') || '').trim();
    togglePreviewUI({ img, ph, hasSrc: initialHas });
}

function handleFileChange(e, states) {
    const input = e.target;
    if (!input?.matches?.('[data-featured-input]')) return;

    const host = getHost(input);
    if (!host) return;

    ensureHostInited(host, states);

    const state = states.get(host) || { lastObjectUrl: null };
    states.set(host, state);

    const mediaId = host.querySelector('[data-featured-media-id]');
    const clearFlag = host.querySelector('[data-featured-clear-flag]');
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
        emitFeaturedChange(host);
        return;
    }

    if (mediaId) mediaId.value = '';
    if (clearFlag) clearFlag.value = '0';

    state.lastObjectUrl = URL.createObjectURL(file);
    img.src = state.lastObjectUrl;
    togglePreviewUI({ img, ph, hasSrc: true });
    emitFeaturedChange(host);
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
    const clearFlag = host.querySelector('[data-featured-clear-flag]');
    const img = host.querySelector('[data-featured-preview]');
    const ph = host.querySelector('[data-featured-placeholder]');

    cleanupObjectUrl(state);
    safeClearFileInput(input);

    if (mediaId) mediaId.value = '';
    if (clearFlag) clearFlag.value = '1';
    if (img) img.src = '';

    togglePreviewUI({ img, ph, hasSrc: false });
    emitFeaturedChange(host);
}

function handleMediaPick(e, states) {
    const d = e?.detail || {};
    const id = d.id ?? '';
    const url = d.url ?? '';
    const mime = d.mime ?? '';

    if (mime && !String(mime).startsWith('image/')) return;
    if (!id && !url) return;

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
    const clearFlag = host.querySelector('[data-featured-clear-flag]');
    const img = host.querySelector('[data-featured-preview]');
    const ph = host.querySelector('[data-featured-placeholder]');

    cleanupObjectUrl(state);
    safeClearFileInput(fileInput);

    if (mediaId) mediaId.value = id ?? '';
    if (clearFlag) clearFlag.value = '0';

    if (url && img) {
        img.src = url;
        togglePreviewUI({ img, ph, hasSrc: true });
    }

    emitFeaturedChange(host);
}

function ensureGlobalListeners() {
    if (window.__fim_listeners_bound) return;

    window.__fim_listeners_bound = true;

    if (!window.__fim_states) window.__fim_states = new WeakMap();
    const states = window.__fim_states;

    const onChange = (e) => handleFileChange(e, states);
    const onClick = (e) => handleClearClick(e, states);
    const onPick = (e) => handleMediaPick(e, states);

    window.__fim_handlers = { onChange, onClick, onPick };

    document.addEventListener('change', onChange, true);
    document.addEventListener('click', onClick, true);
    document.addEventListener('media:pick', onPick);
}

export default function initFeaturedImageManager(root = document) {
    ensureGlobalListeners();

    const states = window.__fim_states;
    root.querySelectorAll('[data-featured-image-manager="1"]').forEach((host) => ensureHostInited(host, states));
}

export function destroyFeaturedImageManager() {
    const handlers = window.__fim_handlers;
    if (handlers) {
        try { document.removeEventListener('change', handlers.onChange, true); } catch {}
        try { document.removeEventListener('click', handlers.onClick, true); } catch {}
        try { document.removeEventListener('media:pick', handlers.onPick); } catch {}
    }

    window.__fim_handlers = null;
    window.__fim_listeners_bound = false;

    try { window.__fim_states = new WeakMap(); } catch {}
}
