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
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function nowTime() {
    const d = new Date();
    const hh = String(d.getHours()).padStart(2, '0');
    const mm = String(d.getMinutes()).padStart(2, '0');
    return `${hh}:${mm}`;
}

function firstValidationMessage(j) {
    const errors = j?.errors || null;
    if (!errors || typeof errors !== 'object') return '';
    const firstKey = Object.keys(errors)[0];
    const firstArr = firstKey ? errors[firstKey] : null;
    if (Array.isArray(firstArr) && firstArr.length) return String(firstArr[0]);
    return '';
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

    // Toplu kaydet UI
    const saveAllBtn = root.querySelector('#gallerySaveAllBtn');
    const dirtyCountEl = root.querySelector('#galleryDirtyCount');
    const saveAllStatusEl = root.querySelector('#gallerySaveAllStatus');

    // Media modal elemanları (mevcut modal’ını “picker” gibi kullanıyoruz)
    const mediaTabLibrary = document.querySelector('#mediaTabLibrary');
    const mediaSearch = document.querySelector('#mediaLibrarySearch');
    const mediaType = document.querySelector('#mediaLibraryType');
    const mediaResults = document.querySelector('#mediaLibraryResults');
    const mediaPagination = document.querySelector('#mediaLibraryPagination');
    const mediaSelectedCount = document.querySelector('#mediaLibrarySelectedCount');
    const mediaBulkBar = document.querySelector('#mediaLibraryBulkBar');

    // SortableJS
    const Sortable = window.Sortable;

    const pickerState = { page: 1, perpage: 24, q: '', type: '' };
    const selected = new Set();

    // Bulk bar içine “Seçilileri Galeriye Ekle” butonu enjekte
    let mediaUseBtn = document.querySelector('#mediaLibraryUseSelectedBtn');
    if (!mediaUseBtn && mediaBulkBar) {
        const actionsWrap = mediaBulkBar.querySelector('.flex.items-center.gap-3');
        if (actionsWrap) {
            mediaUseBtn = document.createElement('button');
            mediaUseBtn.type = 'button';
            mediaUseBtn.id = 'mediaLibraryUseSelectedBtn';
            mediaUseBtn.className = 'kt-btn kt-btn-sm kt-btn-primary';
            mediaUseBtn.innerHTML = `<i class="ki-outline ki-check"></i> Seçilileri Galeriye Ekle`;
            actionsWrap.appendChild(mediaUseBtn);
        }
    }

    // ---------- Item UI / State helpers ----------
    function setRowMsg(row, kind, text) {
        const box = row.querySelector('.js-row-msg');
        if (!box) return;

        if (!text) {
            box.classList.add('hidden');
            box.innerHTML = '';
            return;
        }

        const cls =
            kind === 'error' ? 'text-destructive' :
                kind === 'warn' ? 'text-warning' :
                    'text-muted-foreground';

        box.classList.remove('hidden');
        box.innerHTML = `<div class="text-xs ${cls}">${escapeHtml(text)}</div>`;
    }

    function setRowBadge(row, state, extraText = '') {
        const badge = row.querySelector('.js-row-badge');
        if (!badge) return;

        if (state === 'dirty') {
            badge.className = 'js-row-badge inline-flex items-center gap-1 text-xs font-medium rounded-full px-2 py-1 bg-warning/10 text-warning';
            badge.innerHTML = `<i class="ki-outline ki-pencil"></i><span>Değiştirildi</span>`;
            return;
        }

        if (state === 'saving') {
            badge.className = 'js-row-badge inline-flex items-center gap-1 text-xs font-medium rounded-full px-2 py-1 bg-primary/10 text-primary';
            badge.innerHTML = `<span class="kt-spinner kt-spinner-xs kt-spinner-primary"></span><span>Kaydediliyor</span>`;
            return;
        }

        if (state === 'saved') {
            badge.className = 'js-row-badge inline-flex items-center gap-1 text-xs font-medium rounded-full px-2 py-1 bg-success/10 text-success';
            badge.innerHTML = `<i class="ki-outline ki-check-circle"></i><span>Kaydedildi${extraText ? ' • ' + escapeHtml(extraText) : ''}</span>`;
            return;
        }

        if (state === 'error') {
            badge.className = 'js-row-badge inline-flex items-center gap-1 text-xs font-medium rounded-full px-2 py-1 bg-destructive/10 text-destructive';
            badge.innerHTML = `<i class="ki-outline ki-cross-circle"></i><span>Hata</span>`;
            return;
        }

        // none
        badge.className = 'js-row-badge hidden';
        badge.innerHTML = '';
    }

    function setSaveBtnState(row, state) {
        const btn = row.querySelector('.js-save');
        const icon = row.querySelector('.js-save-icon');
        const text = row.querySelector('.js-save-text');
        if (!btn || !icon || !text) return;

        btn.dataset.state = state;

        if (state === 'saving') {
            btn.disabled = true;
            icon.innerHTML = `<span class="kt-spinner kt-spinner-sm kt-spinner-light"></span>`;
            text.textContent = 'Kaydediliyor';
            return;
        }

        btn.disabled = false;

        if (state === 'saved') {
            icon.innerHTML = `<i class="ki-outline ki-check-circle text-success"></i>`;
            text.textContent = 'Kaydedildi';
            return;
        }

        if (state === 'error') {
            icon.innerHTML = `<i class="ki-outline ki-cross-circle text-destructive"></i>`;
            text.textContent = 'Tekrar Dene';
            return;
        }

        // dirty/default
        icon.innerHTML = `<i class="ki-outline ki-save-2"></i>`;
        text.textContent = 'Kaydet';
    }

    function markDirty(row) {
        if (!row) return;
        row.dataset.dirty = '1';
        row.dataset.savedAt = '';
        setRowMsg(row, null, '');
        setRowBadge(row, 'dirty');
        setSaveBtnState(row, 'dirty');
        updateSaveAllUI();
    }

    function markSaving(row) {
        if (!row) return;
        setRowMsg(row, null, '');
        setRowBadge(row, 'saving');
        setSaveBtnState(row, 'saving');
    }

    function markSaved(row) {
        if (!row) return;
        row.dataset.dirty = '';
        row.dataset.savedAt = nowTime();
        setRowMsg(row, null, '');
        setRowBadge(row, 'saved', row.dataset.savedAt || '');
        setSaveBtnState(row, 'saved');
        updateSaveAllUI();
    }

    function markError(row, message) {
        if (!row) return;
        row.dataset.dirty = '1'; // hata varsa dirty say
        setRowMsg(row, 'error', message || 'Kaydetme başarısız.');
        setRowBadge(row, 'error');
        setSaveBtnState(row, 'error');
        updateSaveAllUI();
    }

    function getDirtyRows() {
        return listEl ? [...listEl.querySelectorAll('[data-item-id][data-dirty="1"]')] : [];
    }

    function updateSaveAllUI() {
        const n = getDirtyRows().length;

        if (dirtyCountEl) {
            dirtyCountEl.textContent = n ? `${n} değişiklik` : '';
        }

        if (saveAllBtn) {
            const busy = saveAllBtn.dataset.busy === '1';
            saveAllBtn.disabled = busy || n === 0;
        }
    }

    function setSaveAllStatus(text, kind = 'info') {
        if (!saveAllStatusEl) return;
        if (!text) {
            saveAllStatusEl.classList.add('hidden');
            saveAllStatusEl.textContent = '';
            return;
        }

        saveAllStatusEl.classList.remove('hidden');
        saveAllStatusEl.className = 'text-xs font-medium';

        if (kind === 'error') saveAllStatusEl.classList.add('text-destructive');
        else if (kind === 'success') saveAllStatusEl.classList.add('text-success');
        else saveAllStatusEl.classList.add('text-muted-foreground');

        saveAllStatusEl.textContent = text;
    }

    function anyDirty() {
        return getDirtyRows().length > 0;
    }

    // Sayfadan çıkarken uyarı (kullanıcıyı korur)
    function beforeUnloadHandler(e) {
        if (!anyDirty()) return;
        e.preventDefault();
        e.returnValue = '';
        return '';
    }

    // ---------- Rendering ----------
    function itemRow(it) {
        const m = it.media;
        const kind = inferKind(m?.mime_type);
        const title = escapeHtml(m?.original_name || 'Medya');
        const thumb = m?.thumb_url || m?.url || '';
        const mediaId = m?.id ?? '-';

        return `
            <div class="rounded-xl border border-border bg-background p-4 grid gap-3" data-item-id="${it.id}" data-dirty="" data-saved-at="">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div class="js-move-handle cursor-move select-none mt-1 text-muted-foreground">
                            <i class="ki-outline ki-menu"></i>
                        </div>

                        <div class="size-14 rounded-lg border border-border bg-muted/20 overflow-hidden flex items-center justify-center">
                            ${
            kind === 'image' && thumb
                ? `<img src="${thumb}" alt="" class="w-full h-full object-cover" />`
                : `<i class="ki-outline ${kind === 'video' ? 'ki-video' : kind === 'pdf' ? 'ki-file' : 'ki-document'} text-xl"></i>`
        }
                        </div>

                        <div class="grid gap-1">
                            <div class="font-medium">${title}</div>
                            <div class="text-xs text-muted-foreground flex items-center gap-2">
                                <span>Media #${mediaId}</span>
                                <span class="js-row-badge inline-flex items-center gap-1 text-xs font-medium rounded-full px-2 py-1 bg-success/10 text-success">
                                    <i class="ki-outline ki-check-circle"></i><span>Kaydedildi</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-light js-save">
                            <span class="js-save-icon me-2"><i class="ki-outline ki-save-2"></i></span>
                            <span class="js-save-text">Kaydet</span>
                        </button>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-danger js-item-remove" title="Kaldır">
                            <i class="ki-outline ki-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="js-row-msg hidden"></div>

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

    async function fetchItems() {
        const { res, j } = await req(`/admin/galleries/${galleryId}/items`, 'GET');
        if (!res.ok || !j?.ok) {
            listEl.innerHTML = '';
            emptyEl?.classList.remove('hidden');
            setSaveAllStatus('', 'info');
            updateSaveAllUI();
            return;
        }

        const items = Array.isArray(j.data) ? j.data : [];
        listEl.innerHTML = items.map(itemRow).join('');
        emptyEl?.classList.toggle('hidden', items.length > 0);

        // İlk render sonrası: hepsi "kaydedildi" olsun
        listEl.querySelectorAll('[data-item-id]').forEach(row => {
            row.dataset.dirty = '';
            row.dataset.savedAt = nowTime();
            setRowBadge(row, 'saved', row.dataset.savedAt);
            setSaveBtnState(row, 'saved');
            setRowMsg(row, null, '');
        });

        updateSaveAllUI();
        window.removeEventListener('beforeunload', beforeUnloadHandler);
        window.addEventListener('beforeunload', beforeUnloadHandler);

        if (Sortable && listEl && !listEl.__sortable) {
            listEl.__sortable = new Sortable(listEl, {
                handle: '.js-move-handle',
                animation: 150,
                onEnd: async () => {
                    const ids = [...listEl.querySelectorAll('[data-item-id]')]
                        .map(el => Number(el.dataset.itemId))
                        .filter(Boolean);

                    if (!ids.length) return;

                    const { res } = await req(`/admin/galleries/${galleryId}/items/reorder`, 'POST', { ids });
                    if (!res.ok) {
                        setSaveAllStatus('Sıralama kaydedilemedi.', 'error');
                    } else {
                        setSaveAllStatus('Sıralama kaydedildi.', 'success');
                        setTimeout(() => setSaveAllStatus('', 'info'), 1500);
                    }
                }
            });
        }
    }

    // ---------- Media picker ----------
    function mediaCard(m) {
        const kind = inferKind(m.mime_type);
        const thumb = m.thumb_url || m.url || '';
        const checked = selected.has(m.id) ? 'checked' : '';

        return `
            <label class="rounded-xl border border-border bg-background px-4 py-3 flex items-center gap-3 cursor-pointer" data-mid="${m.id}">
                <input type="checkbox" class="kt-checkbox kt-checkbox-sm js-media-check" ${checked} />
                <div class="size-10 rounded-lg border border-border bg-muted/20 overflow-hidden flex items-center justify-center">
                    ${
            kind === 'image' && thumb
                ? `<img src="${thumb}" alt="" class="w-full h-full object-cover" />`
                : `<i class="ki-outline ${kind === 'video' ? 'ki-video' : kind === 'pdf' ? 'ki-file' : 'ki-document'}"></i>`
        }
                </div>
                <div class="grid">
                    <div class="font-medium">${escapeHtml(m.original_name || 'Medya')}</div>
                    <div class="text-xs text-muted-foreground">#${m.id}</div>
                </div>
            </label>
        `;
    }

    function renderPager(meta) {
        if (!mediaPagination) return;

        const current = Number(meta.current_page || 1);
        const last = Number(meta.last_page || 1);

        if (last <= 1) {
            mediaPagination.innerHTML = '';
            return;
        }

        const mk = (p, label, disabled = false, active = false) => `
            <button type="button"
                    class="kt-btn kt-btn-sm ${active ? 'kt-btn-primary' : 'kt-btn-light'}"
                    data-page="${p}"
                    ${disabled ? 'disabled' : ''}>
                ${label}
            </button>
        `;

        const parts = [];
        parts.push(mk(current - 1, '‹', current <= 1));

        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);

        if (start > 1) parts.push(mk(1, '1', false, current === 1));
        if (start > 2) parts.push(`<span class="px-2 text-muted-foreground">…</span>`);

        for (let p = start; p <= end; p++) parts.push(mk(p, String(p), false, p === current));

        if (end < last - 1) parts.push(`<span class="px-2 text-muted-foreground">…</span>`);
        if (end < last) parts.push(mk(last, String(last), false, current === last));

        parts.push(mk(current + 1, '›', current >= last));

        mediaPagination.innerHTML = `<div class="flex items-center gap-1 justify-center">${parts.join('')}</div>`;
    }

    async function fetchMediaList() {
        if (!mediaResults) return;

        const qs = new URLSearchParams({
            page: String(pickerState.page),
            perpage: String(pickerState.perpage),
            q: pickerState.q || '',
            type: pickerState.type || '',
            mode: 'active',
        });

        const res = await fetch(`/admin/media/list?${qs.toString()}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        const j = await res.json().catch(() => ({}));
        if (!res.ok || !j?.ok) {
            mediaResults.innerHTML = '';
            renderPager({ current_page: 1, last_page: 1 });
            return;
        }

        const items = Array.isArray(j.data) ? j.data : [];
        const meta = j.meta || {};

        mediaResults.innerHTML = items.map(mediaCard).join('');
        renderPager(meta);

        if (mediaSelectedCount) mediaSelectedCount.textContent = String(selected.size);
        document.querySelector('#mediaLibraryBulkBar')?.classList.toggle('hidden', false);
    }

    // Media seçim
    mediaResults?.addEventListener('change', (e) => {
        const chk = e.target.closest('.js-media-check');
        if (!chk) return;

        const wrap = chk.closest('[data-mid]');
        const id = Number(wrap?.dataset.mid);
        if (!Number.isFinite(id)) return;

        if (chk.checked) selected.add(id);
        else selected.delete(id);

        if (mediaSelectedCount) mediaSelectedCount.textContent = String(selected.size);
    });

    mediaPagination?.addEventListener('click', (e) => {
        const b = e.target.closest('button[data-page]');
        if (!b) return;

        const p = Number(b.dataset.page || 1);
        if (!Number.isFinite(p) || p < 1) return;

        pickerState.page = p;
        fetchMediaList();
    });

    mediaSearch?.addEventListener('input', () => {
        pickerState.q = (mediaSearch.value || '').trim();
        pickerState.page = 1;
        fetchMediaList();
    });

    mediaType?.addEventListener('change', () => {
        pickerState.type = mediaType.value || '';
        pickerState.page = 1;
        fetchMediaList();
    });

    mediaUseBtn?.addEventListener('click', async () => {
        const ids = [...selected.values()];
        if (!ids.length) return;

        const { res, j } = await req(`/admin/galleries/${galleryId}/items`, 'POST', { media_ids: ids });

        if (!res.ok || !j?.ok) {
            setSaveAllStatus('Medya eklenemedi.', 'error');
            return;
        }

        selected.clear();
        if (mediaSelectedCount) mediaSelectedCount.textContent = '0';

        await fetchItems();
        setSaveAllStatus('Medya eklendi.', 'success');
        setTimeout(() => setSaveAllStatus('', 'info'), 1500);
    });

    // Library tab’a geçilince listeyi çek
    mediaTabLibrary?.addEventListener('click', () => {
        pickerState.page = 1;
        fetchMediaList();
    });

    // ---------- Dirty tracking: input/change ----------
    listEl?.addEventListener('input', (e) => {
        if (!e.target.closest('.js-caption, .js-alt, .js-link-url')) return;
        const row = e.target.closest('[data-item-id]');
        markDirty(row);
    });

    listEl?.addEventListener('change', (e) => {
        if (!e.target.closest('.js-link-target')) return;
        const row = e.target.closest('[data-item-id]');
        markDirty(row);
    });

    // ---------- Save logic ----------
    function buildPayloadFromRow(row) {
        return {
            caption: row.querySelector('.js-caption')?.value ?? '',
            alt: row.querySelector('.js-alt')?.value ?? '',
            link_url: row.querySelector('.js-link-url')?.value ?? '',
            link_target: row.querySelector('.js-link-target')?.value ?? '',
        };
    }

    async function saveOneRow(row) {
        const itemId = Number(row.dataset.itemId);
        if (!Number.isFinite(itemId)) return { ok: false, id: itemId, message: 'Invalid id' };

        markSaving(row);

        const payload = buildPayloadFromRow(row);
        const { res, j } = await req(`/admin/galleries/${galleryId}/items/${itemId}`, 'PATCH', payload);

        if (res.ok && j?.ok) {
            markSaved(row);
            return { ok: true, id: itemId };
        }

        const msg = (j?.message || firstValidationMessage(j) || 'Kaydetme başarısız.');
        markError(row, msg);
        return { ok: false, id: itemId, message: msg };
    }

    // Item actions
    listEl?.addEventListener('click', async (e) => {
        const row = e.target.closest('[data-item-id]');
        if (!row) return;

        const itemId = Number(row.dataset.itemId);

        if (e.target.closest('.js-item-remove')) {
            const { res } = await req(`/admin/galleries/${galleryId}/items/${itemId}`, 'DELETE');
            if (!res.ok) {
                markError(row, 'Silme başarısız.');
                return;
            }
            row.remove();
            emptyEl?.classList.toggle('hidden', listEl.querySelectorAll('[data-item-id]').length > 0);
            updateSaveAllUI();
            setSaveAllStatus('Öğe kaldırıldı.', 'success');
            setTimeout(() => setSaveAllStatus('', 'info'), 1200);
            return;
        }

        if (e.target.closest('.js-save')) {
            await saveOneRow(row);
            return;
        }
    });

    // ---------- Bulk save ----------
    saveAllBtn?.addEventListener('click', async () => {
        const rows = getDirtyRows();
        if (!rows.length) return;

        saveAllBtn.dataset.busy = '1';
        updateSaveAllUI();

        setSaveAllStatus(`Kaydediliyor… (0/${rows.length})`, 'info');

        // UI: hepsini saving göster
        rows.forEach(r => markSaving(r));

        const items = rows.map(r => {
            const id = Number(r.dataset.itemId);
            return { id, ...buildPayloadFromRow(r) };
        }).filter(x => Number.isFinite(x.id));

        const { res, j } = await req(`/admin/galleries/${galleryId}/items/bulk`, 'PATCH', { items });

        if (!res.ok || !j?.ok) {
            // bulk komple fail
            const msg = (j?.message || firstValidationMessage(j) || 'Toplu kayıt başarısız.');
            rows.forEach(r => markError(r, msg));
            setSaveAllStatus(msg, 'error');
            saveAllBtn.dataset.busy = '0';
            updateSaveAllUI();
            return;
        }

        const results = Array.isArray(j.results) ? j.results : [];
        const byId = new Map(results.map(x => [Number(x.id), x]));

        let okCount = 0;
        let failCount = 0;

        rows.forEach(r => {
            const id = Number(r.dataset.itemId);
            const rr = byId.get(id);

            if (rr?.ok) {
                okCount++;
                markSaved(r);
            } else {
                failCount++;
                const msg = rr?.message || 'Kaydetme başarısız.';
                markError(r, msg);
            }
        });

        setSaveAllStatus(`Bitti: ${okCount} başarılı, ${failCount} hatalı.`, failCount ? 'error' : 'success');
        setTimeout(() => setSaveAllStatus('', 'info'), 2500);

        saveAllBtn.dataset.busy = '0';
        updateSaveAllUI();
    });

    ac = new AbortController();
    fetchItems();
}

export function destroy() {
    try { ac?.abort(); } catch {}
    ac = null;
}
