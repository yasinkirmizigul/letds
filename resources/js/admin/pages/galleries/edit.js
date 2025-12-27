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

export default function init() {
    const root = document.querySelector('[data-page="galleries.edit"]');
    if (!root) return;

    const galleryId = root.dataset.galleryId;
    if (!galleryId) return;

    const listEl = root.querySelector('#galleryItemsList');
    const emptyEl = root.querySelector('#galleryItemsEmpty');

    // SortableJS: projende var (kullanıyorsun)
    const Sortable = window.Sortable;

    async function req(url, method, body) {
        const res = await fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
                ...(body ? {'Content-Type':'application/json'} : {}),
            },
            body: body ? JSON.stringify(body) : undefined,
            signal: ac?.signal,
        });
        const j = await res.json().catch(() => ({}));
        return { res, j };
    }

    function itemRow(it) {
        const m = it.media;
        const thumb = m?.thumb_url || m?.url || '';
        const kind = inferKind(m?.mime_type);
        const title = m?.original_name || 'Medya';
        const id = it.id;

        return `
      <div class="border border-border rounded-md p-3"
           data-item-id="${id}">
        <div class="flex items-start gap-3">
          <div class="w-16 h-16 shrink-0 rounded-md overflow-hidden border border-border bg-background flex items-center justify-center">
            ${kind === 'image'
            ? `<img src="${thumb}" class="w-full h-full object-cover" />`
            : `<i class="ki-outline ki-file"></i>`
        }
          </div>

          <div class="flex-1 flex flex-col gap-3">
            <div class="flex items-center justify-between gap-3">
              <div class="flex flex-col">
                <div class="font-semibold">${title}</div>
                <div class="text-xs text-muted-foreground">#${m?.id ?? '-'}</div>
              </div>

              <div class="flex items-center gap-2">
                <button type="button" class="kt-btn kt-btn-sm kt-btn-light js-move-handle" title="Sürükle">
                  <i class="ki-outline ki-menu"></i>
                </button>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-danger js-item-remove">Kaldır</button>
              </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
              <div class="flex flex-col gap-1">
                <label class="text-xs text-muted-foreground">Caption</label>
                <input class="kt-input kt-input-sm js-caption" value="${it.caption ?? ''}">
              </div>
              <div class="flex flex-col gap-1">
                <label class="text-xs text-muted-foreground">Alt</label>
                <input class="kt-input kt-input-sm js-alt" value="${it.alt ?? ''}">
              </div>
              <div class="flex flex-col gap-1 lg:col-span-2">
                <label class="text-xs text-muted-foreground">Link (opsiyonel)</label>
                <input class="kt-input kt-input-sm js-link-url" value="${it.link_url ?? ''}">
              </div>
              <div class="flex flex-col gap-1">
                <label class="text-xs text-muted-foreground">Target</label>
                <select class="kt-select kt-select-sm js-link-target">
                  <option value="" ${!it.link_target ? 'selected' : ''}>(boş)</option>
                  <option value="_self" ${it.link_target === '_self' ? 'selected' : ''}>_self</option>
                  <option value="_blank" ${it.link_target === '_blank' ? 'selected' : ''}>_blank</option>
                </select>
              </div>
              <div class="flex items-end">
                <button type="button" class="kt-btn kt-btn-sm kt-btn-primary js-save">Kaydet</button>
              </div>
            </div>
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
                    const ids = [...listEl.querySelectorAll('[data-item-id]')].map(el => Number(el.dataset.itemId));
                    await req(`/admin/galleries/${galleryId}/items/reorder`, 'POST', { ids });
                }
            });
        }
    }

    // Media picker: mevcut media modal UI’sini burada “seçim amaçlı” kullanıyoruz.
    // Basit mantık: library list’i /admin/media/list ile doldur, checkbox ile seç, “Ekle” basınca /galleries/{id}/items.
    const mediaModal = document.querySelector('#mediaUploadModal');
    const mediaTabLibrary = document.querySelector('#mediaTabLibrary');
    const mediaSearch = document.querySelector('#mediaLibrarySearch');
    const mediaType = document.querySelector('#mediaLibraryType');
    const mediaResults = document.querySelector('#mediaLibraryResults');
    const mediaPagination = document.querySelector('#mediaLibraryPagination');
    const mediaSelectedCount = document.querySelector('#mediaLibrarySelectedCount');

    // Modal içinde “Ekle” butonu yoktu → footer’a dokunmadan basit bir buton enjekte ediyoruz.
    let mediaUseBtn = document.querySelector('#mediaLibraryUseSelectedBtn');
    if (!mediaUseBtn) {
        const bar = document.querySelector('#mediaLibraryBulkBar');
        if (bar) {
            mediaUseBtn = document.createElement('button');
            mediaUseBtn.type = 'button';
            mediaUseBtn.id = 'mediaLibraryUseSelectedBtn';
            mediaUseBtn.className = 'kt-btn kt-btn-sm kt-btn-primary';
            mediaUseBtn.textContent = 'Seçili Medyaları Galeriye Ekle';
            bar.querySelector('.flex.items-center.gap-3')?.appendChild(mediaUseBtn);
        }
    }

    const pickerState = { page: 1, perpage: 24, q: '', type: '' };
    const selected = new Set();

    function mediaCard(m) {
        const kind = inferKind(m.mime_type);
        return `
      <label class="border border-border rounded-md p-2 flex items-center gap-3 cursor-pointer hover:bg-muted"
             data-mid="${m.id}">
        <input class="kt-checkbox kt-checkbox-sm js-media-check" type="checkbox" ${selected.has(m.id) ? 'checked' : ''}>
        <div class="w-12 h-12 rounded-md overflow-hidden border border-border bg-background flex items-center justify-center">
          ${kind === 'image' ? `<img src="${m.thumb_url || m.url}" class="w-full h-full object-cover">` : `<i class="ki-outline ki-file"></i>`}
        </div>
        <div class="flex-1">
          <div class="text-sm font-medium">${m.original_name || 'Medya'}</div>
          <div class="text-xs text-muted-foreground">#${m.id}</div>
        </div>
      </label>
    `;
    }

    function renderPager(meta) {
        if (!mediaPagination) return;
        const current = Number(meta.current_page || 1);
        const last = Number(meta.last_page || 1);
        if (last <= 1) { mediaPagination.innerHTML = ''; return; }

        const mk = (p, label, disabled = false, active = false) =>
            `<button class="kt-btn kt-btn-sm ${active ? 'kt-btn-primary' : 'kt-btn-light'}"
               data-page="${p}" ${disabled ? 'disabled' : ''}>${label}</button>`;

        const parts = [];
        parts.push(mk(current - 1, '‹', current <= 1));
        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);
        if (start > 1) parts.push(mk(1, '1', false, current === 1));
        if (start > 2) parts.push(`<span class="px-2">…</span>`);
        for (let p = start; p <= end; p++) parts.push(mk(p, String(p), false, p === current));
        if (end < last - 1) parts.push(`<span class="px-2">…</span>`);
        if (end < last) parts.push(mk(last, String(last), false, current === last));
        parts.push(mk(current + 1, '›', current >= last));

        mediaPagination.innerHTML = `<div class="flex items-center gap-2">${parts.join('')}</div>`;
    }

    async function fetchMediaList() {
        if (!mediaResults) return;
        const mode = 'active';
        const qs = new URLSearchParams({
            page: String(pickerState.page),
            perpage: String(pickerState.perpage),
            q: pickerState.q || '',
            type: pickerState.type || '',
            mode,
        });

        const res = await fetch(`/admin/media/list?${qs.toString()}`, { headers: { Accept: 'application/json' } });
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
    }

    mediaResults?.addEventListener('change', (e) => {
        const chk = e.target.closest('.js-media-check');
        if (!chk) return;
        const wrap = chk.closest('[data-mid]');
        const id = Number(wrap?.dataset.mid);
        if (!Number.isFinite(id)) return;
        if (chk.checked) selected.add(id); else selected.delete(id);
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

        // reset
        selected.clear();
        if (mediaSelectedCount) mediaSelectedCount.textContent = '0';
        await fetchItems();
    });

    // Library tab’a geçince listeyi çek
    mediaTabLibrary?.addEventListener('click', () => {
        pickerState.page = 1;
        fetchMediaList();
    });

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

            await req(`/admin/galleries/${galleryId}/items/${itemId}`, 'PATCH', { caption, alt, link_url, link_target });
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
