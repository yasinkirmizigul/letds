export function initMediaPicker() {
    // idempotent: page-registry yeniden init etse bile çift bind olmasın
    if (window.__mediaPickerInitBound) return;
    window.__mediaPickerInitBound = true;

    let state = { page: 1, perpage: 24, q: '', type: '' };
    let currentTarget = null; // {inputSel, previewSel, mime}

    const modal = document.getElementById('mediaPickerModal');
    if (!modal) return;

    const grid = modal.querySelector('#mediaPickerGrid');
    const search = modal.querySelector('#mediaPickerSearch');
    const type = modal.querySelector('#mediaPickerType');

    if (!grid) return;

    let debounceTimer = null;
    let aborter = null;
    let lastRequestId = 0;

    function showModal() {
        // 1) Hidden toggle button inside modal (preferred)
        const opener = modal.querySelector('[data-kt-modal-toggle="#mediaPickerModal"]');
        if (opener) {
            opener.click();
            return true;
        }

        // 2) KTModal instance (if available in your bundle)
        try {
            if (window.KTModal && typeof window.KTModal.getOrCreateInstance === 'function') {
                const inst = window.KTModal.getOrCreateInstance(modal);
                if (inst && typeof inst.show === 'function') {
                    inst.show();
                    return true;
                }
            }
        } catch (_) {}

        // 3) Fallback: remove hidden (best-effort)
        modal.classList.remove('hidden');
        return true;
    }

    function hideModal() {
        // Prefer dismiss button
        const dismiss = modal.querySelector('[data-kt-modal-dismiss="true"]');
        if (dismiss) {
            dismiss.click();
            return;
        }

        // KTModal fallback
        try {
            if (window.KTModal && typeof window.KTModal.getOrCreateInstance === 'function') {
                const inst = window.KTModal.getOrCreateInstance(modal);
                if (inst && typeof inst.hide === 'function') {
                    inst.hide();
                    return;
                }
            }
        } catch (_) {}

        modal.classList.add('hidden');
    }

    function renderEmpty(message = 'Kayıt yok') {
        grid.innerHTML = `
            <div class="col-span-full py-10 text-center text-muted-foreground text-sm">
                ${escapeHtml(message)}
            </div>
        `;
    }

    async function fetchList() {
        const reqId = ++lastRequestId;

        // cancel previous
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
                    const thumb = m.is_image
                        ? `<img src="${escapeAttr(m.url)}" class="w-full h-28 object-cover rounded-lg" alt="">`
                        : `<div class="w-full h-28 rounded-lg flex items-center justify-center bg-muted">
                               <i class="ki-outline ki-file text-2xl"></i>
                           </div>`;

                    const title = m.original_name ?? '-';

                    return `
                        <button type="button"
                                class="kt-card overflow-hidden text-left"
                                data-pick="1"
                                data-id="${escapeAttr(String(m.id))}"
                                data-url="${escapeAttr(m.url)}"
                                data-mime="${escapeAttr(m.mime_type || '')}">
                          <div class="kt-card-content p-3 grid gap-2">
                            ${thumb}
                            <div class="text-xs font-medium truncate">${escapeHtml(String(title))}</div>
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
        state.type = (opts?.mime && opts.mime.startsWith('image/')) ? 'image' : '';

        if (search) search.value = '';
        if (type) type.value = state.type;

        showModal();
        fetchList();
    }

    // Trigger: herhangi bir yerde data-media-picker="true" olan buton
    document.addEventListener(
        'click',
        (e) => {
            const btn = e.target.closest?.('[data-media-picker="true"]');
            if (!btn) return;

            const inputSel = btn.getAttribute('data-media-picker-target');
            const previewSel = btn.getAttribute('data-media-picker-preview');
            const mime = btn.getAttribute('data-media-picker-mime') || '';

            if (!inputSel || !previewSel) return;

            openPicker({ inputSel, previewSel, mime });
        },
        true
    );

    // Seçim
    grid.addEventListener('click', (e) => {
        const pick = e.target.closest?.('[data-pick="1"]');
        if (!pick || !currentTarget) return;

        const id = pick.getAttribute('data-id');
        const url = pick.getAttribute('data-url');
        const mime = pick.getAttribute('data-mime') || '';

        // mime kısıtı (avatar: image/*)
        if (currentTarget.mime && currentTarget.mime.startsWith('image/') && !mime.startsWith('image/')) {
            alert('Sadece görsel seçebilirsin.');
            return;
        }

        const input = document.querySelector(currentTarget.inputSel);
        if (input) input.value = id ?? '';

        const prev = document.querySelector(currentTarget.previewSel);
        if (prev && url) {
            if (prev.tagName === 'IMG') {
                prev.src = url;
                prev.classList.remove('hidden');
            } else {
                prev.style.backgroundImage = `url('${url}')`;
            }

            // KTImageInput state update
            const root = prev.closest?.('[data-kt-image-input]');
            if (root && window.KTImageInput) {
                const inst =
                    typeof window.KTImageInput.getOrCreateInstance === 'function'
                        ? window.KTImageInput.getOrCreateInstance(root)
                        : window.KTImageInput.getInstance?.(root);

                if (inst?.setPreviewUrl) inst.setPreviewUrl(url);
                else if (inst?.update) inst.update();
            }
        }

        hideModal();
    });

    // filtreler
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

    // helpers
    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function escapeAttr(str) {
        return escapeHtml(str).replaceAll('`', '&#096;');
    }
}
