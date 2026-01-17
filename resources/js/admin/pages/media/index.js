import { createMediaList } from './list';
import { attachBulkActions } from './bulk';
import { attachGridActions } from './actions';

export default function init(ctx) {
    // Standard: init(ctx)
    // Bu sayfa değilse çık
    if (ctx?.page && ctx.page !== 'media.index') return;

    const root = ctx.root;
    const signal = ctx.signal;

    // Fallback: eğer page name geçmiyorsa selector ile kontrol et
    if (!root || !(root instanceof HTMLElement) || root.getAttribute('data-page') !== 'media.index') return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const grid = root.querySelector('#mediaGrid');
    const empty = root.querySelector('#mediaEmpty');
    const info = root.querySelector('#mediaInfo');
    const pagination = root.querySelector('#mediaPagination');

    const searchInput = root.querySelector('#mediaSearch');
    const typeSelect = root.querySelector('#mediaType');
    const perPageInput = root.querySelector('#mediaPerPage'); // opsiyonel

    const bulkBar = root.querySelector('#mediaBulkBar');
    const selectedCountEl = root.querySelector('#mediaSelectedCount');
    const checkAll = root.querySelector('#mediaCheckAll');

    const selectedIds = new Set();
    let debounceTimer = null;

    const state = {
        q: '',
        type: '',
        page: 1,
        perpage: Number(perPageInput?.value || 24) || 24,
        last_page: 1,
        total: 0,
    };

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }

    function formatBytes(bytes) {
        const b = Number(bytes || 0);
        if (!b) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB'];
        let i = 0, n = b;
        while (n >= 1024 && i < units.length - 1) { n /= 1024; i++; }
        return `${n.toFixed(i ? 1 : 0)} ${units[i]}`;
    }

    function fileExt(name) {
        const s = String(name || '');
        const i = s.lastIndexOf('.');
        return i >= 0 ? s.slice(i + 1).toLowerCase() : '';
    }

    function inferKindFromMimeOrExt(mime, nameOrUrl) {
        const m = String(mime || '').toLowerCase();
        const ext = fileExt(nameOrUrl);
        if (m.startsWith('image/')) return 'image';
        if (m.startsWith('video/')) return 'video';
        if (m === 'application/pdf') return 'pdf';
        if (['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'].includes(ext)) return 'image';
        if (['mp4', 'webm', 'ogg', 'mov', 'm4v'].includes(ext)) return 'video';
        if (ext === 'pdf') return 'pdf';
        return 'other';
    }

    function setGlobalError(msg) {
        const globalErr = root.querySelector('#mediaGlobalError');
        if (!globalErr) return;
        globalErr.textContent = msg || '';
        globalErr.classList.toggle('hidden', !msg);
    }

    function setBulkUI() {
        if (!bulkBar || !grid) return;

        const n = selectedIds.size;
        bulkBar.classList.toggle('hidden', n === 0);
        if (selectedCountEl) selectedCountEl.textContent = String(n);

        const boxes = [...grid.querySelectorAll('input[data-media-check="1"]')];
        const checked = boxes.filter(b => b.checked).length;

        if (checkAll) {
            checkAll.indeterminate = checked > 0 && checked < boxes.length;
            checkAll.checked = boxes.length > 0 && checked === boxes.length;
        }

        const btnDel = root.querySelector('#mediaBulkDeleteBtn');
        const btnRes = root.querySelector('#mediaBulkRestoreBtn');
        const btnForce = root.querySelector('#mediaBulkForceDeleteBtn');

        if (btnDel) btnDel.disabled = (n === 0);
        if (btnRes) btnRes.disabled = (n === 0);
        if (btnForce) btnForce.disabled = (n === 0);
    }

    function applySelectionToGrid() {
        if (!grid) return;
        grid.querySelectorAll('input[data-media-check="1"]').forEach(cb => {
            const id = String(cb.getAttribute('data-id') || '');
            cb.checked = selectedIds.has(id);
        });
        setBulkUI();
    }

    // -----------------------------
    // Lightbox (page-scoped + cleanup)
    // -----------------------------
    let lbOpen = false;
    let lbItems = [];
    let lbIndex = 0;

    const lb = document.createElement('div');
    lb.id = 'mediaLightbox';
    lb.className = 'media-lb hidden';
    lb.innerHTML = `
        <div class="media-lb__bg" data-lb-backdrop></div>
        <div class="media-lb__panel" role="dialog" aria-modal="true">
          <div class="media-lb__top">
            <div class="media-lb__meta">
              <div class="media-lb__title" data-lb-title></div>
              <div class="media-lb__sub" data-lb-sub></div>
            </div>
            <div class="media-lb__actions">
              <a class="kt-btn kt-btn-sm kt-btn-light" href="#" target="_blank" rel="noreferrer" data-lb-open>
                <i class="ki-outline ki-fasten"></i>
              </a>
              <button class="kt-btn kt-btn-sm kt-btn-light" type="button" data-lb-close>
                <i class="ki-outline ki-cross"></i>
              </button>
            </div>
          </div>
          <div class="media-lb__body" data-lb-body></div>
          <div class="media-lb__nav">
            <button class="kt-btn kt-btn-sm kt-btn-light" type="button" data-lb-prev>
              <i class="ki-outline ki-arrow-left"></i>
            </button>
            <button class="kt-btn kt-btn-sm kt-btn-light" type="button" data-lb-next>
              <i class="ki-outline ki-arrow-right"></i>
            </button>
          </div>
        </div>
    `;
    document.body.appendChild(lb);

    const lbTitle = lb.querySelector('[data-lb-title]');
    const lbSub = lb.querySelector('[data-lb-sub]');
    const lbBody = lb.querySelector('[data-lb-body]');
    const lbBackdrop = lb.querySelector('[data-lb-backdrop]');
    const lbClose = lb.querySelector('[data-lb-close]');
    const lbPrev = lb.querySelector('[data-lb-prev]');
    const lbNext = lb.querySelector('[data-lb-next]');
    const lbOpenLink = lb.querySelector('[data-lb-open]');

    function stopAllMedia() {
        try {
            lbBody?.querySelectorAll('video,audio').forEach(m => {
                try { m.pause(); } catch (_) {}
                try { m.currentTime = 0; } catch (_) {}
            });
        } catch (_) {}
        if (lbBody) lbBody.innerHTML = '';
    }

    function renderLightbox() {
        const it = lbItems[lbIndex];
        if (!it) return;

        stopAllMedia();

        if (lbTitle) lbTitle.textContent = it.title || it.name || 'Önizleme';
        if (lbSub) lbSub.textContent = it.sub || '';
        const url = it.url || '#';
        if (lbOpenLink) lbOpenLink.href = url;

        const kind = it.kind || inferKindFromMimeOrExt(it.mime, it.name || url);

        if (!lbBody) return;

        if (kind === 'image') {
            lbBody.innerHTML = `<img class="media-lb__img" src="${esc(url)}" alt="${esc(it.title || it.name || '')}">`;
            return;
        }
        if (kind === 'video') {
            lbBody.innerHTML = `
                <video class="media-lb__video" controls playsinline>
                  <source src="${esc(url)}" type="${esc(it.mime || 'video/mp4')}">
                </video>
            `;
            return;
        }
        if (kind === 'pdf') {
            lbBody.innerHTML = `<iframe class="media-lb__iframe" src="${esc(url)}"></iframe>`;
            return;
        }

        lbBody.innerHTML = `
            <div class="media-lb__other">
              <i class="ki-outline ki-document"></i>
              <div class="media-lb__other-title">${esc(it.title || it.name || 'Dosya')}</div>
              <a class="kt-btn kt-btn-light" href="${esc(url)}" target="_blank" rel="noreferrer">Aç</a>
            </div>
        `;
    }

    function openLightbox(items, index = 0) {
        lbItems = Array.isArray(items) ? items : [];
        lbIndex = Math.max(0, Math.min(Number(index || 0), lbItems.length - 1));
        lbOpen = true;
        lb.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        renderLightbox();
    }

    function closeLightbox() {
        lbOpen = false;
        stopAllMedia();
        lb.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function lbPrevFn() {
        if (!lbItems.length) return;
        lbIndex = (lbIndex - 1 + lbItems.length) % lbItems.length;
        renderLightbox();
    }

    function lbNextFn() {
        if (!lbItems.length) return;
        lbIndex = (lbIndex + 1) % lbItems.length;
        renderLightbox();
    }

    // Lightbox listeners (signal ile)
    lbBackdrop?.addEventListener('click', closeLightbox, { signal });
    lbClose?.addEventListener('click', closeLightbox, { signal });
    lbPrev?.addEventListener('click', lbPrevFn, { signal });
    lbNext?.addEventListener('click', lbNextFn, { signal });

    window.addEventListener('keydown', (e) => {
        if (!lbOpen) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') lbPrevFn();
        if (e.key === 'ArrowRight') lbNextFn();
    }, { signal });

    // sayfa kapanınca lightbox DOM temizliği
    ctx.cleanup(() => {
        try { closeLightbox(); } catch {}
        try { lb.remove(); } catch {}
    });

    function mediaCard(m) {
        const url = m.thumb_url || m.url;
        const kind = m.is_image ? 'image' : inferKindFromMimeOrExt(m.mime_type, m.original_name || m.url);
        const isTrash = (root.dataset.mode === 'trash');

        const thumb = (kind === 'image')
            ? `<img src="${esc(url)}" class="w-full rounded-xl ring-1 ring-border" style="height:220px;object-fit:cover" alt="">`
            : `<div class="w-full rounded-xl bg-muted ring-1 ring-border flex items-center justify-center" style="height:220px">
                   <i class="ki-outline ki-file text-3xl text-muted-foreground"></i>
               </div>`;

        const actionButtons = isTrash
            ? `
                <button type="button" class="kt-btn kt-btn-sm kt-btn-success"
                  data-action="restore" data-id="${esc(m.id)}" title="Geri Yükle">
                  <i class="ki-outline ki-arrow-circle-left"></i>
                </button>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive"
                  data-action="force-delete" data-id="${esc(m.id)}" title="Kalıcı Sil">
                  <i class="ki-outline ki-trash"></i>
                </button>
              `
            : `
                <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive"
                  data-action="delete" data-id="${esc(m.id)}" title="Sil">
                  <i class="ki-outline ki-trash"></i>
                </button>
              `;

        return `
            <div class="kt-card relative">
              <div class="absolute z-9" style="top:8px;left:8px;">
                <label class="inline-flex items-center gap-2 bg-background/80 backdrop-blur px-2 py-1 rounded-lg ring-1 ring-border">
                  <input type="checkbox" class="kt-checkbox kt-checkbox-sm" data-media-check="1" data-id="${esc(m.id)}">
                </label>
              </div>

              <div class="kt-card-content p-3">
                <button type="button" class="w-full text-left"
                  data-action="open"
                  data-url="${esc(m.url)}"
                  data-thumb="${esc(url)}"
                  data-name="${esc(m.original_name || '')}"
                  data-mime="${esc(m.mime_type || '')}"
                  data-size="${esc(m.size || 0)}"
                  data-kind="${esc(kind)}">
                  ${thumb}
                </button>

                <div class="mt-3">
                  <div class="text-sm font-medium truncate" title="${esc(m.original_name)}">${esc(m.original_name || '-')}</div>
                  <div class="text-xs text-muted-foreground truncate">${esc(m.mime_type || '')} • ${formatBytes(m.size || 0)}</div>
                </div>

                <div class="mt-3 flex items-center justify-between gap-2">
                  <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-action="open"
                    data-url="${esc(m.url)}"
                    data-thumb="${esc(url)}"
                    data-name="${esc(m.original_name)}"
                    data-mime="${esc(m.mime_type || '')}"
                    data-size="${esc(m.size || 0)}"
                    data-kind="${esc(kind)}">
                    <i class="ki-outline ki-eye"></i> Gör
                  </button>

                  <div class="flex items-center gap-2">
                    ${actionButtons}
                    <a href="${esc(m.url)}" target="_blank" rel="noreferrer" class="kt-btn kt-btn-sm kt-btn-outline" title="Link">
                      <i class="ki-outline ki-fasten"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
        `;
    }

    const { fetchList } = createMediaList({
        root,
        state,
        grid,
        empty,
        info,
        pagination,
        setGlobalError,
        applySelectionToGrid,
        mediaCard,
    });

    // Bu helper’lar senin mevcut çalışan sistemin:
    // Bozmamak için dokunmuyorum. (İstersen bir sonraki adımda signal/cleanup uyumlu hale getiririz.)
    attachBulkActions({
        root,
        csrf,
        selectedIds,
        setBulkUI,
        fetchList,
        state,
        signal, // opsiyonel: helper kullanmıyorsa bile sorun değil
        cleanup: (fn) => ctx.cleanup(fn), // opsiyonel
    });

    attachGridActions({
        root,
        grid,
        csrf,
        selectedIds,
        setBulkUI,
        state,
        fetchList,
        openLightbox,
        formatBytes,
        inferKindFromMimeOrExt,
        signal, // opsiyonel
        cleanup: (fn) => ctx.cleanup(fn), // opsiyonel
    });

    // Seçim checkbox (signal ile)
    grid?.addEventListener('change', (e) => {
        const cb = e.target.closest('input[data-media-check="1"]');
        if (!cb) return;
        const id = String(cb.getAttribute('data-id') || '');
        if (!id) return;
        if (cb.checked) selectedIds.add(id);
        else selectedIds.delete(id);
        setBulkUI();
    }, { signal });

    // Check all (signal ile)
    checkAll?.addEventListener('change', () => {
        if (!grid) return;
        const boxes = [...grid.querySelectorAll('input[data-media-check="1"]')];
        const on = !!checkAll.checked;
        boxes.forEach(cb => {
            const id = String(cb.getAttribute('data-id') || '');
            cb.checked = on;
            if (!id) return;
            if (on) selectedIds.add(id);
            else selectedIds.delete(id);
        });
        setBulkUI();
    }, { signal });

    // Search debounce (signal + cleanup)
    searchInput?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            state.q = searchInput.value.trim();
            state.page = 1;
            await fetchList();
        }, 250);
    }, { signal });

    ctx.cleanup(() => {
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = null;
    });

    // Type change (signal ile)
    typeSelect?.addEventListener('change', async () => {
        state.type = typeSelect.value || '';
        state.page = 1;
        await fetchList();
    }, { signal });

    // Pagination (signal ile)
    pagination?.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-page]');
        if (!btn) return;
        const p = Number(btn.getAttribute('data-page') || 1);
        if (!p || p === state.page) return;
        state.page = p;
        await fetchList();
    }, { signal });

    setBulkUI();
    fetchList();
}
