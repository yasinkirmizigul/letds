// resources/js/core/featured-image-manager.js

function cleanupObjectUrl(state) {
    if (!state?.lastObjectUrl) return;
    try { URL.revokeObjectURL(state.lastObjectUrl); } catch {}
    state.lastObjectUrl = null;
}

function togglePreviewUI({ img, ph, hasSrc }) {
    if (!img || !ph) return;
    img.classList.toggle('hidden', !hasSrc);
    ph.classList.toggle('hidden', !!hasSrc);
}

function safeClearFileInput(input) {
    if (!input) return;
    try { input.value = ''; } catch {}
}

export default function initFeaturedImageManager(root = document) {
    const hosts = [...root.querySelectorAll('[data-featured-image-manager="1"]')];
    if (!hosts.length) return;

    hosts.forEach((host) => {
        if (host.__featuredInited) return;
        host.__featuredInited = true;

        const input = host.querySelector('[data-featured-input]');          // file input
        const mediaId = host.querySelector('[data-featured-media-id]');     // hidden selected media id
        const img = host.querySelector('[data-featured-preview]');
        const ph = host.querySelector('[data-featured-placeholder]');
        const clearBtn = host.querySelector('[data-featured-clear]');
        const uid = host.getAttribute('data-featured-uid') || '';

        if (!img || !ph) return;

        const state = { lastObjectUrl: null };

        // initial state
        const initialHas = !!(img.getAttribute('src') || '').trim();
        togglePreviewUI({ img, ph, hasSrc: initialHas });

        // Upload -> preview
        const onFileChange = () => {
            const file = input?.files && input.files[0] ? input.files[0] : null;

            cleanupObjectUrl(state);

            if (!file) {
                const existing = (img.getAttribute('src') || '').trim();
                if (!existing) {
                    img.src = '';
                    togglePreviewUI({ img, ph, hasSrc: false });
                }
                return;
            }

            // upload seçildiğinde library seçimi geçersiz
            if (mediaId) mediaId.value = '';

            state.lastObjectUrl = URL.createObjectURL(file);
            img.src = state.lastObjectUrl;
            togglePreviewUI({ img, ph, hasSrc: true });
        };

        input?.addEventListener('change', onFileChange);

        // Clear
        clearBtn?.addEventListener('click', () => {
            cleanupObjectUrl(state);
            safeClearFileInput(input);
            if (mediaId) mediaId.value = '';
            img.src = '';
            togglePreviewUI({ img, ph, hasSrc: false });
        });

        // Library picked event (media-picker.js patch ile gelecek)
        const onPicked = (e) => {
            const d = e?.detail || {};
            // hedef selector’ı eşleştiriyoruz: picker hangi inputSel’e yazdı?
            // Bizim buton data-media-picker-target olarak: [data-featured-uid="X"] [data-featured-media-id]
            // Bu inputSel içindeki uid bizim host ile aynı mı?
            if (!uid) return;
            const targetSel = d?.target?.inputSel || '';
            if (!targetSel.includes(`data-featured-uid="${uid}"`)) return;

            const url = d.url || '';
            const id = d.id || '';
            const mime = d.mime || '';

            // sadece image
            if (mime && !String(mime).startsWith('image/')) return;
            if (!id && !url) return;
            // library seçildi -> upload temizle + objectURL revoke
            cleanupObjectUrl(state);
            safeClearFileInput(input);

            if (mediaId) mediaId.value = id ?? '';

            if (url) {
                img.src = url;
                togglePreviewUI({ img, ph, hasSrc: true });
            }
        };

        document.addEventListener('media:pick', onPicked);

        host.__featuredDestroy = () => {
            cleanupObjectUrl(state);
            document.removeEventListener('media:pick', onPicked);
        };
    });
}

export function destroyFeaturedImageManager(root = document) {
    root.querySelectorAll('[data-featured-image-manager="1"]').forEach((host) => {
        try { host.__featuredDestroy?.(); } catch {}
        host.__featuredDestroy = null;
        host.__featuredInited = false;
    });
}
