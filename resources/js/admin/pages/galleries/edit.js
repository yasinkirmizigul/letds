import Sortable from 'sortablejs';

let ac = null;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function inferKind(mime) {
    if (!mime) return 'file';
    if (mime.startsWith('image/')) return 'image';
    if (mime.startsWith('video/')) return 'video';
    if (mime === 'application/pdf') return 'pdf';
    return 'file';
}

function escapeHtml(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function req(url, method, body) {
    const res = await fetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
            ...(body ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
        signal: ac?.signal,
        credentials: 'same-origin',
    });

    const j = await res.json().catch(() => ({}));
    return { res, j };
}

export default function init() {
    const root = document.querySelector('[data-page="galleries.edit"]');
    if (!root) return;

    const galleryId = root.dataset.galleryId;
    if (!galleryId) return;

    const listEl = root.querySelector('#galleryItemsList');
    const emptyEl = root.querySelector('#galleryItemsEmpty');
    const saveAllBtn = document.getElementById('gallerySaveAllBtn');

    if (!listEl) return;

    // “dirty” takibi: satır değişti mi?
    const dirtyIds = new Set();

    function setSaveAllEnabled() {
        if (!saveAllBtn) return;
        saveAllBtn.disabled = dirtyIds.size === 0;
    }

    function markRowDirty(row) {
        const itemId = Number(row.dataset.itemId);
        if (!itemId) return;
        dirtyIds.add(itemId);
        row.dataset.dirty = '1';
        row.classList.add('ring-1', 'ring-border'); // çok abartma, hafif görsel ipucu
        setSaveAllEnabled();
    }

    function clearRowDirty(row) {
        const itemId = Number(row.dataset.itemId);
        if (!itemId) return;
        dirtyIds.delete(itemId);
        delete row.dataset.dirty;
        row.classList.remove('ring-1', 'ring-border');
        setSaveAllEnabled();
    }

    async function saveRow(row, btn = null) {
        const itemId = Number(row.dataset.itemId);
        if (!itemId) return { ok: false };

        const caption = row.querySelector('.js-caption')?.value ?? '';
        const alt = row.querySelector('.js-alt')?.value ?? '';
        const link_url = row.querySelector('.js-link-url')?.value ?? '';
        const link_target = row.querySelector('.js-link-target')?.value ?? '';

        // UI feedback
        let oldHtml = null;
        if (btn) {
            oldHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<i class="ki-outline ki-loading"></i> Kaydediliyor`;
        }

        const { res, j } = await req(
            `/admin/galleries/${galleryId}/items/${itemId}`,
            'PATCH',
            { caption, alt, link_url, link_target }
        );

        const ok = res.ok && j?.ok;

        if (btn) {
            if (ok) {
                btn.innerHTML = `<i class="ki-outline ki-check-circle text-success"></i> Kaydedildi`;
                setTimeout(() => {
                    btn.innerHTML = oldHtml;
                    btn.disabled = false;
                }, 900);
            } else {
                btn.innerHTML = `<i class="ki-outline ki-cross-circle text-destructive"></i> Hata`;
                setTimeout(() => {
                    btn.innerHTML = oldHtml;
                    btn.disabled = false;
                }, 1200);
            }
        }

        if (!ok) {
            console.error('save failed', res.status, j);
            return { ok: false, status: res.status, json: j };
        }

        clearRowDirty(row);
        return { ok: true };
    }

    function itemRow(it) {
        const m = it.media;
        const kind = inferKind(m?.mime_type);
        const title = escapeHtml(m?.original_name || 'Medya');
        const thumb = m?.thumb_url || m?.url || '';
        const mediaId = m?.id ?? '-';

        return `
        <div class="rounded-xl border border-border bg-background p-4 grid gap-3" data-item-id="${it.id}">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="js-move-handle cursor-move select-none mt-1 text-muted-foreground" title="Sürükle-bırak">
                        <i class="ki-outline ki-menu"></i>
                    </div>

                    <div class="size-14 rounded-lg border border-border bg-muted/20 overflow-hidden flex items-center justify-center">
                        ${
            kind === 'image' && thumb
                ? `<img src="${thumb}" alt="" class="w-full h-full object-cover" />`
                : `<i class="ki-outline ki-document text-xl"></i>`
        }
                    </div>

                    <div class="grid">
                        <div class="font-medium">${title}</div>
                        <div class="text-xs text-muted-foreground">Media #${mediaId}</div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-light js-save">
                        <i class="ki-outline ki-check"></i> Kaydet
                    </button>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-danger js-item-remove">
                        <i class="ki-outline ki-trash"></i>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <div class="grid gap-2">
                    <label class="text-xs text-muted-foreground">Caption</label>
                    <input class="kt-input js-caption" type="text" value="${escapeHtml(it.caption || '')}">
                </div>

                <div class="grid gap-2">
                    <label class="text-xs text-muted-foreground">Alt</label>
                    <input class="kt-input js-alt" type="text" value="${escapeHtml(it.alt || '')}">
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <div class="grid gap-2">
                    <label class="text-xs text-muted-foreground">Link (opsiyonel)</label>
                    <input class="kt-input js-link-url" type="text" value="${escapeHtml(it.link_url || '')}" placeholder="https://...">
                </div>

                <div class="grid gap-2">
                    <label class="text-xs text-muted-foreground">Target</label>
                    <select class="kt-select js-link-target">
                        <option value="" ${!it.link_target ? 'selected' : ''}>(boş)</option>
                        <option value="_self" ${it.link_target === '_self' ? 'selected' : ''}>_self</option>
                        <option value="_blank" ${it.link_target === '_blank' ? 'selected' : ''}>_blank</option>
                    </select>
                </div>
            </div>
        </div>
        `;
    }

    function ensureSortable() {
        if (listEl.__sortable) {
            try { listEl.__sortable.destroy(); } catch {}
            listEl.__sortable = null;
        }

        listEl.__sortable = new Sortable(listEl, {
            handle: '.js-move-handle',
            animation: 150,
            onEnd: async () => {
                const ids = [...listEl.querySelectorAll('[data-item-id]')]
                    .map(el => Number(el.dataset.itemId))
                    .filter(Boolean);
                if (!ids.length) return;

                await req(`/admin/galleries/${galleryId}/items/reorder`, 'POST', { ids });
                // reorder başarılıysa satırları dirty yapma; sadece sıralama
            }
        });
    }

    async function fetchItems() {
        const { res, j } = await req(`/admin/galleries/${galleryId}/items`, 'GET');
        if (!res.ok || !j?.ok) {
            listEl.innerHTML = '';
            emptyEl?.classList.remove('hidden');
            return;
        }

        const items = Array.isArray(j.data) ? j.data : [];
        listEl.innerHTML = items.map(itemRow).join('');
        emptyEl?.classList.toggle('hidden', items.length > 0);

        // yeni liste gelince dirty reset
        dirtyIds.clear();
        setSaveAllEnabled();

        ensureSortable();
    }

    // Input değişince dirty
    listEl.addEventListener('input', (e) => {
        const row = e.target.closest('[data-item-id]');
        if (!row) return;
        if (
            e.target.closest('.js-caption') ||
            e.target.closest('.js-alt') ||
            e.target.closest('.js-link-url') ||
            e.target.closest('.js-link-target')
        ) {
            markRowDirty(row);
        }
    });

    // Save / delete
    listEl.addEventListener('click', async (e) => {
        const row = e.target.closest('[data-item-id]');
        if (!row) return;

        if (e.target.closest('.js-item-remove')) {
            e.preventDefault();
            const itemId = Number(row.dataset.itemId);
            await req(`/admin/galleries/${galleryId}/items/${itemId}`, 'DELETE');
            row.remove();
            emptyEl?.classList.toggle('hidden', listEl.querySelectorAll('[data-item-id]').length > 0);
            dirtyIds.delete(itemId);
            setSaveAllEnabled();
            return;
        }

        const saveBtn = e.target.closest('.js-save');
        if (saveBtn) {
            e.preventDefault();
            await saveRow(row, saveBtn);
        }
    });

    // Toplu Kaydet
    saveAllBtn?.addEventListener('click', async () => {
        if (dirtyIds.size === 0) return;

        const old = saveAllBtn.innerHTML;
        saveAllBtn.disabled = true;
        saveAllBtn.innerHTML = `<i class="ki-outline ki-loading"></i> Kaydediliyor (${dirtyIds.size})`;

        // Sadece dirty olanları sırayla kaydet (backend’e bulk endpoint yazmadan güvenli çözüm)
        const rows = [...listEl.querySelectorAll('[data-item-id][data-dirty="1"]')];

        let fail = 0;
        for (const row of rows) {
            const r = await saveRow(row, null);
            if (!r.ok) fail++;
        }

        if (fail === 0) {
            saveAllBtn.innerHTML = `<i class="ki-outline ki-check-circle text-success"></i> Kaydedildi`;
        } else {
            saveAllBtn.innerHTML = `<i class="ki-outline ki-cross-circle text-destructive"></i> ${fail} hata`;
        }

        setTimeout(() => {
            saveAllBtn.innerHTML = old;
            setSaveAllEnabled();
        }, 1200);
    });

    ac = new AbortController();
    fetchItems();
}

export function destroy() {
    try { ac?.abort(); } catch {}
    ac = null;
}
