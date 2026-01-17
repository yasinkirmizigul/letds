function escapeHtml(str) {
    return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function escapeAttr(str) {
    return escapeHtml(str).replaceAll('`', '&#96;');
}

function getPickerEls(scope = document) {
    const modal = scope.getElementById?.('mediaPickerModal') || document.getElementById('mediaPickerModal');
    if (!modal) return null;

    const grid = modal.querySelector('#mediaPickerGrid');
    const search = modal.querySelector('#mediaPickerSearch');
    const type = modal.querySelector('#mediaPickerType');

    if (!grid) return null;

    return { modal, grid, search, type };
}

/**
 * Deterministik modal açma:
 * 1) KTModal (varsa) -> show()
 * 2) Global opener -> click()
 * 3) Fallback: hidden kaldır
 */
function showModal(modal) {
    try {
        if (window.KTModal && typeof window.KTModal.getOrCreateInstance === 'function') {
            const inst = window.KTModal.getOrCreateInstance(modal);
            if (inst && typeof inst.show === 'function') {
                inst.show();
                return true;
            }
        }
        if (window.KTModal && typeof window.KTModal.getInstance === 'function') {
            const inst = window.KTModal.getInstance(modal) || new window.KTModal(modal);
            if (inst && typeof inst.show === 'function') {
                inst.show();
                return true;
            }
        }
    } catch (e) {
        console.error('[media-picker] KTModal show error:', e);
    }

    const opener =
        document.querySelector('[data-kt-modal-toggle="#mediaPickerModal"]') ||
        document.getElementById('mediaPickerModalOpener');

    if (opener) {
        opener.click();
        return true;
    }

    modal.classList.remove('hidden');
    return true;
}

function hideModal(modal) {
    const dismiss = modal.querySelector('[data-kt-modal-dismiss="true"]');
    if (dismiss) {
        dismiss.click();
        return;
    }

    try {
        if (window.KTModal && typeof window.KTModal.getOrCreateInstance === 'function') {
            const inst = window.KTModal.getOrCreateInstance(modal);
            if (inst && typeof inst.hide === 'function') {
                inst.hide();
                return;
            }
        }
        if (window.KTModal && typeof window.KTModal.getInstance === 'function') {
            const inst = window.KTModal.getInstance(modal) || new window.KTModal(modal);
            if (inst && typeof inst.hide === 'function') {
                inst.hide();
                return;
            }
        }
    } catch (e) {
        console.error('[media-picker] KTModal hide error:', e);
    }

    modal.classList.add('hidden');
}

// ===== Singleton holder (module-level)
let __inited = false;

export function initMediaPicker(scope = document) {
    // 🔥 KRİTİK: modal yokken init etme
    const els = getPickerEls(scope);
    if (!els) return;

    if (__inited) return;
    __inited = true;

    const { modal, grid, search, type } = els;

    let state = { page: 1, perpage: 24, q: '', type: '' };
    let currentTarget = null; // { inputSel, previewSel, mime }

    let debounceTimer = null;
    let aborter = null;
    let lastRequestId = 0;

    function cleanupTransient() {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }
        if (aborter) {
            aborter.abort();
            aborter = null;
        }
    }

    function renderEmpty(message = 'Kayıt yok') {
        grid.innerHTML = `
          <div class="kt-text-muted kt-text-sm py-6 text-center">
            ${escapeHtml(message)}
          </div>
        `;
    }

    async function fetchList() {
        const reqId = ++lastRequestId;

        if (aborter) aborter.abort();
        aborter = new AbortController();

        const qs = new URLSearchParams({
            page: String(state.page),
            perpage: String(state.perpage),
            q: state.q || '',
            type: state.type || '',
        });

        try {
            const res = await fetch(`/admin/media/list?${qs.toString()}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: aborter.signal,
            });

            if (reqId !== lastRequestId) return;

            if (!res.ok) {
                renderEmpty(`Liste alınamadı (HTTP ${res.status})`);
                return;
            }

            const json = await res.json().catch(() => null);
            if (!json) {
                renderEmpty('Geçersiz yanıt (JSON okunamadı)');
                return;
            }

            const items = Array.isArray(json.data) ? json.data : [];
            if (!items.length) {
                renderEmpty(state.q ? 'Sonuç bulunamadı' : 'Medya yok');
                return;
            }

            grid.innerHTML = items
                .map((m) => {
                    const id = String(m.id ?? '');
                    const url = String(m.url ?? '');
                    // backend bazen mime, bazen mime_type döndürebilir
                    const mime = String(m.mime ?? m.mime_type ?? '');
                    const title = m.original_name ?? m.title ?? '-';

                    const thumb = m.is_image
                        ? `<img class="w-full h-28 object-cover rounded-lg" src="${escapeAttr(url)}" alt="${escapeAttr(title)}">`
                        : `<div class="w-full h-28 rounded-lg kt-bg-muted flex items-center justify-center text-sm kt-text-muted">
                             ${escapeHtml((mime || 'file').toUpperCase())}
                           </div>`;

                    return `
                      <button type="button"
                        class="kt-card kt-card-border w-full text-left hover:shadow-sm transition"
                        data-pick="1"
                        data-id="${escapeAttr(id)}"
                        data-url="${escapeAttr(url)}"
                        data-mime="${escapeAttr(mime)}"
                        title="${escapeAttr(title)}"
                      >
                        <div class="p-2 space-y-2">
                          ${thumb}
                          <div class="text-xs truncate">${escapeHtml(String(title))}</div>
                        </div>
                      </button>
                    `;
                })
                .join('');
        } catch (err) {
            if (err?.name === 'AbortError') return;
            renderEmpty('İstek hatası: liste alınamadı');
        }
    }

    function openPicker(opts) {
        currentTarget = opts || null;

        state.page = 1;
        state.q = '';
        state.type = opts?.mime && String(opts.mime).startsWith('image/') ? 'image' : '';

        if (search) search.value = '';
        if (type) type.value = state.type;

        showModal(modal);
        fetchList();
    }

    // ---- Global trigger (delegation) ----
    // buton: data-media-picker="true"
    // target: data-media-picker-target="CSS_SELECTOR"
    // preview: data-media-picker-preview="CSS_SELECTOR"
    // mime: data-media-picker-mime="image/*" gibi
    document.addEventListener(
        'click',
        (e) => {
            const btn = e.target?.closest?.('[data-media-picker="true"]');
            if (!btn) return;

            const inputSel = btn.getAttribute('data-media-picker-target');
            const previewSel = btn.getAttribute('data-media-picker-preview');
            const mime = btn.getAttribute('data-media-picker-mime') || '';

            if (!inputSel || !previewSel) return;

            openPicker({ inputSel, previewSel, mime });
        },
        true
    );

    // ---- Pick ----
    grid.addEventListener('click', (e) => {
        const pick = e.target?.closest?.('[data-pick="1"]');
        if (!pick || !currentTarget) return;

        const id = pick.getAttribute('data-id') || '';
        const url = pick.getAttribute('data-url') || '';
        const mime = pick.getAttribute('data-mime') || '';

        if (currentTarget.mime && String(currentTarget.mime).startsWith('image/') && !String(mime).startsWith('image/')) {
            alert('Sadece görsel seçebilirsin.');
            return;
        }

        let input = null;
        let prev = null;

        try { input = document.querySelector(currentTarget.inputSel); } catch {}
        try { prev = document.querySelector(currentTarget.previewSel); } catch {}

        if (input) input.value = id;

        if (prev && url) {
            if (prev.tagName === 'IMG') {
                prev.src = url;
                prev.classList.remove('hidden');
                prev.toggleAttribute('hidden', false);
            } else {
                prev.style.backgroundImage = `url('${url}')`;
            }

            // KTUI ImageInput state update (best effort)
            const wrap = prev.closest?.('[data-kt-image-input]');
            if (wrap && window.KTImageInput) {
                const inst =
                    typeof window.KTImageInput.getOrCreateInstance === 'function'
                        ? window.KTImageInput.getOrCreateInstance(wrap)
                        : window.KTImageInput.getInstance?.(wrap);

                if (inst?.setPreviewUrl) inst.setPreviewUrl(url);
                else if (inst?.update) inst.update();
            }
        }

        document.dispatchEvent(
            new CustomEvent('media:pick', {
                bubbles: true,
                detail: { id, url, mime, target: currentTarget },
            })
        );

        hideModal(modal);
        cleanupTransient();
        currentTarget = null;
    });

    // ---- Filters ----
    if (search) {
        search.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                state.q = (search.value || '').trim();
                state.page = 1;
                fetchList();
            }, 250);
        });
    }

    if (type) {
        type.addEventListener('change', () => {
            state.type = type.value || '';
            state.page = 1;
            fetchList();
        });
    }

    // ---- Modal kapanınca transient reset ----
    modal.addEventListener('hidden', () => {
        cleanupTransient();
        currentTarget = null;
    });

    // optional: ESC / dismiss fallback
    modal.addEventListener('click', (e) => {
        const d = e.target.closest('[data-kt-modal-dismiss="true"]');
        if (!d) return;
        cleanupTransient();
        currentTarget = null;
    });
}
