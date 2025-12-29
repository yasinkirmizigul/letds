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

        if (Sortable && listEl && !listEl.__sortable) {
            listEl.__sortable = new Sortable(listEl, {
                handle: '.js-move-handle',
                animation: 150,
                onEnd: async () => {
                    const ids = [...listEl.querySelectorAll('[data-item-id]')]
                        .map(el => Number(el.dataset.itemId))
                        .filter(Boolean);

                    if (!ids.length) return;
                    await req(`/admin/galleries/${galleryId}/items/reorder`, 'POST', { ids });
                    // reorder sonrası tekrar çek: sort_order yazısını güncellemek istersen burada yaparsın
                }
            });
        }
    }

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

        // bulk bar görünür kalsın; seçili sayacı güncelle
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

        await req(`/admin/galleries/${galleryId}/items`, 'POST', { media_ids: ids });

        selected.clear();
        if (mediaSelectedCount) mediaSelectedCount.textContent = '0';

        await fetchItems();
    });

    // Library tab’a geçilince listeyi çek
    mediaTabLibrary?.addEventListener('click', () => {
        pickerState.page = 1;
        fetchMediaList();
    });

    // Item actions
    listEl?.addEventListener('click', async (e) => {
        const row = e.target.closest('[data-item-id]');
        if (!row) return;

        const itemId = Number(row.dataset.itemId);

        if (e.target.closest('.js-item-remove')) {
            await req(`/admin/galleries/${galleryId}/items/${itemId}`, 'DELETE');
            row.remove();
            emptyEl?.classList.toggle('hidden', listEl.querySelectorAll('[data-item-id]').length > 0);
            return;
        }

        if (e.target.closest('.js-save')) {
            const caption = row.querySelector('.js-caption')?.value ?? '';
            const alt = row.querySelector('.js-alt')?.value ?? '';
            const link_url = row.querySelector('.js-link-url')?.value ?? '';
            const link_target = row.querySelector('.js-link-target')?.value ?? '';

            await req(`/admin/galleries/${galleryId}/items/${itemId}`, 'PATCH', {
                caption, alt, link_url, link_target
            });

            return;
        }
    });

    ac = new AbortController();
    fetchItems();
}

export function destroy() {
    try { ac?.abort(); } catch {}
    ac = null;
}
