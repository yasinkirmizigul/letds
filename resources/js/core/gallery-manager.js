// resources/js/core/gallery-manager.js
import Sortable from 'sortablejs';

function escapeHtml(str) {
    return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function jreq(url, method, body, signal = null) {
    const res = await fetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            Accept: 'application/json',
            ...(body ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
        credentials: 'same-origin',
        signal,
    });

    const j = await res.json().catch(() => ({}));
    return { res, j };
}

function getIds(container) {
    return [...container.querySelectorAll('[data-gallery-id]')]
        .map((el) => Number(el.dataset.galleryId))
        .filter(Boolean);
}

function ensureKTSelects(scopeEl) {
    try {
        scopeEl.querySelectorAll('select[data-kt-select="true"]').forEach((el) => {
            if (el.__ktSelectInited) return;
            el.__ktSelectInited = true;
            window.KTSelect?.getOrCreateInstance?.(el);
        });
    } catch {}
}

function attachedRowHtml(r) {
    const slot = (r.slot || 'main') === 'sidebar' ? 'sidebar' : 'main';
    const g = r.gallery || {};
    const name = escapeHtml(g.name ?? `Galeri #${r.gallery_id}`);
    const slug = escapeHtml(g.slug ?? '');

    return `
    <div class="kt-card kt-card-border p-3 flex items-center gap-3" data-gallery-id="${Number(r.gallery_id)}" data-slot="${slot}">
      <div class="cursor-move js-gal-handle kt-text-muted">
        <i class="ki-outline ki-drag"></i>
      </div>

      <div class="flex-1 min-w-0">
        <div class="font-medium truncate">${name}</div>
        <div class="text-xs kt-text-muted truncate">#${Number(r.gallery_id)} • ${slug}</div>
      </div>

      <div class="w-36">
        <select class="kt-select js-gal-slot" data-kt-select="true">
          <option value="main">main</option>
          <option value="sidebar">sidebar</option>
        </select>
      </div>

      <button type="button" class="kt-btn kt-btn-sm kt-btn-light js-detach">
        Kaldır
      </button>
    </div>
  `;
}

function renderPager(meta) {
    const current = Number(meta.current_page || 1);
    const last = Number(meta.last_page || 1);

    if (last <= 1) return '';

    const mk = (p, label, disabled = false, active = false) => {
        const cls = [
            'kt-btn kt-btn-sm',
            active ? 'kt-btn-primary' : 'kt-btn-light',
            disabled ? 'opacity-60 pointer-events-none' : '',
        ].join(' ');
        return `<button type="button" class="${cls}" data-page="${p}">${escapeHtml(label)}</button>`;
    };

    const parts = [];
    parts.push(mk(current - 1, '‹', current <= 1));

    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);

    if (start > 1) parts.push(mk(1, '1', false, current === 1));
    if (start > 2) parts.push(`<span class="px-2 kt-text-muted">…</span>`);

    for (let p = start; p <= end; p++) parts.push(mk(p, String(p), false, p === current));

    if (end < last - 1) parts.push(`<span class="px-2 kt-text-muted">…</span>`);
    if (end < last) parts.push(mk(last, String(last), false, current === last));

    parts.push(mk(current + 1, '›', current >= last));

    return `<div class="flex flex-wrap gap-2 items-center justify-center">${parts.join('')}</div>`;
}

function mountOne(mgr) {
    // idempotent: varsa önce temizle
    if (typeof mgr.__gmCleanup === 'function') {
        try { mgr.__gmCleanup(); } catch {}
    }

    const id = mgr.dataset.gmId || 'gm';

    const URLS = {
        list: mgr.dataset.urlList || '',
        index: mgr.dataset.urlIndex || '',
        attach: mgr.dataset.urlAttach || '',
        detach: mgr.dataset.urlDetach || '',
        reorder: mgr.dataset.urlReorder || '',
    };

    // deterministik: URL yoksa hiçbir şey yapma
    if (!URLS.list || !URLS.index || !URLS.attach || !URLS.detach || !URLS.reorder) {
        console.error('[gallery-manager] missing urls', { id, URLS });
        return;
    }

    const $ = (sel) => mgr.querySelector(sel);

    const galleriesMain = $('[data-gm="slot-main"]');
    const galleriesSidebar = $('[data-gm="slot-sidebar"]');
    const galleriesEmpty = $('[data-gm="empty"]');

    const attachBtn = $('[data-gm="attach-btn"]');

    const pickerModalSel = `#${id}-pickerModal`;
    const pickerSearch = $('[data-gm="picker-search"]');
    const pickerSlot = $('[data-gm="picker-slot"]');
    const pickerRefresh = $('[data-gm="picker-refresh"]');
    const pickerList = $('[data-gm="picker-list"]');
    const pickerEmpty = $('[data-gm="picker-empty"]');
    const pickerInfo = $('[data-gm="picker-info"]');
    const pickerPagination = $('[data-gm="picker-pagination"]');

    if (!galleriesMain || !galleriesSidebar || !pickerList) return;

    // ---- state
    const gState = { page: 1, perpage: 10, q: '' };

    const cleanupFns = [];
    let debounceTimer = null;

    // picker request guard
    let pickerAbort = null;
    let pickerReqId = 0;

    function cleanupTransient() {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }
        if (pickerAbort) {
            pickerAbort.abort();
            pickerAbort = null;
        }
    }

    function syncSlotSelects() {
        galleriesMain.querySelectorAll('[data-gallery-id]').forEach((el) => {
            el.dataset.slot = 'main';
            const sel = el.querySelector('.js-gal-slot');
            if (sel) sel.value = 'main';
        });
        galleriesSidebar.querySelectorAll('[data-gallery-id]').forEach((el) => {
            el.dataset.slot = 'sidebar';
            const sel = el.querySelector('.js-gal-slot');
            if (sel) sel.value = 'sidebar';
        });
    }

    async function persistBothSlots() {
        syncSlotSelects();
        const main_ids = getIds(galleriesMain);
        const sidebar_ids = getIds(galleriesSidebar);
        const { res, j } = await jreq(URLS.reorder, 'POST', { main_ids, sidebar_ids });
        if (!res.ok || !j?.ok) console.error('[gallery-manager] reorder failed', res.status, j);
    }

    function ensureSortables() {
        if (typeof Sortable !== 'function') return;

        try { galleriesMain.__sortable?.destroy?.(); } catch {}
        try { galleriesSidebar.__sortable?.destroy?.(); } catch {}

        const groupName = `gm-${id}`;

        galleriesMain.__sortable = new Sortable(galleriesMain, {
            group: { name: groupName, pull: true, put: true },
            handle: '.js-gal-handle',
            animation: 150,
            onEnd: persistBothSlots,
        });

        galleriesSidebar.__sortable = new Sortable(galleriesSidebar, {
            group: { name: groupName, pull: true, put: true },
            handle: '.js-gal-handle',
            animation: 150,
            onEnd: persistBothSlots,
        });

        cleanupFns.push(() => {
            try { galleriesMain.__sortable?.destroy?.(); } catch {}
            try { galleriesSidebar.__sortable?.destroy?.(); } catch {}
            galleriesMain.__sortable = null;
            galleriesSidebar.__sortable = null;
        });
    }

    function bindSlotSelectHandlers(scopeEl) {
        scopeEl.querySelectorAll('select.js-gal-slot').forEach((sel) => {
            // DOM flag yerine handler ref sakla
            if (sel.__gmSlotHandler) return;

            const onChange = async () => {
                const row = sel.closest('[data-gallery-id]');
                if (!row) return;

                const to = (sel.value || 'main') === 'sidebar' ? 'sidebar' : 'main';
                const from = (row.dataset.slot || 'main') === 'sidebar' ? 'sidebar' : 'main';
                if (to === from) return;

                row.dataset.slot = to;
                if (to === 'main') galleriesMain.appendChild(row);
                else galleriesSidebar.appendChild(row);

                await persistBothSlots();
            };

            sel.__gmSlotHandler = onChange;
            sel.addEventListener('change', onChange);

            cleanupFns.push(() => {
                try { sel.removeEventListener('change', sel.__gmSlotHandler); } catch {}
                sel.__gmSlotHandler = null;
            });
        });
    }

    async function fetchAttached() {
        const { res, j } = await jreq(URLS.index, 'GET');
        if (!res.ok || !j?.ok) return;

        const rows = Array.isArray(j.data) ? j.data : [];
        const main = rows.filter((x) => (x.slot || 'main') === 'main');
        const side = rows.filter((x) => (x.slot || 'main') === 'sidebar');

        galleriesMain.innerHTML = main.map(attachedRowHtml).join('');
        galleriesSidebar.innerHTML = side.map(attachedRowHtml).join('');

        ensureKTSelects(mgr);

        const total = main.length + side.length;
        if (galleriesEmpty) galleriesEmpty.classList.toggle('hidden', total > 0);

        bindSlotSelectHandlers(mgr);
        ensureSortables();
        syncSlotSelects();
    }

    function renderPickerError(message) {
        pickerList.innerHTML = `
          <div class="kt-text-muted kt-text-sm py-6 text-center">
            ${escapeHtml(message)}
          </div>
        `;
        if (pickerEmpty) pickerEmpty.classList.remove('hidden');
        if (pickerInfo) pickerInfo.textContent = '';
        if (pickerPagination) pickerPagination.innerHTML = '';
    }

    async function fetchPicker() {
        if (!pickerList) return;

        const myReq = ++pickerReqId;

        if (pickerAbort) pickerAbort.abort();
        pickerAbort = new AbortController();

        const qs = new URLSearchParams({
            page: String(gState.page),
            perpage: String(gState.perpage),
            q: gState.q || '',
            mode: 'active',
        });

        let res, j;
        try {
            ({ res, j } = await jreq(`${URLS.list}?${qs.toString()}`, 'GET', null, pickerAbort.signal));
        } catch (e) {
            if (e?.name === 'AbortError') return;
            renderPickerError('İstek hatası');
            return;
        }

        if (myReq !== pickerReqId) return;

        if (!res.ok || !j?.ok) {
            renderPickerError(`Liste alınamadı (${res.status})`);
            return;
        }

        const items = Array.isArray(j.data) ? j.data : [];
        const meta = j.meta || {};

        pickerList.innerHTML = items
            .map((g) => {
                const gid = Number(g.id || g.gallery_id || 0);
                const name = escapeHtml(g.name || '-');
                const slug = escapeHtml(g.slug || '');
                return `
                  <div class="kt-card kt-card-border p-3 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                      <div class="font-medium truncate">${name}</div>
                      <div class="text-xs kt-text-muted truncate">${slug}</div>
                    </div>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary js-picker-attach" data-gallery-id="${gid}">
                      Bağla
                    </button>
                  </div>
                `;
            })
            .join('');

        if (pickerEmpty) pickerEmpty.classList.toggle('hidden', items.length > 0);

        const from = items.length
            ? (Number(meta.current_page || 1) - 1) * Number(meta.per_page || items.length) + 1
            : 0;
        const to = items.length ? from + items.length - 1 : 0;

        if (pickerInfo) pickerInfo.textContent = `${from}-${to} / ${meta.total ?? items.length}`;
        if (pickerPagination) pickerPagination.innerHTML = renderPager(meta);
    }

    // ---- Events (scoped) ----
    const onMgrClick = async (e) => {
        const detachBtn = e.target.closest('.js-detach');
        if (detachBtn) {
            const row = detachBtn.closest('[data-gallery-id]');
            const gid = Number(row?.dataset.galleryId);
            if (!gid) return;
            await jreq(URLS.detach, 'POST', { gallery_id: gid });
            await fetchAttached();
            return;
        }

        const attachPick = e.target.closest('.js-picker-attach');
        if (attachPick) {
            const gid = Number(attachPick.dataset.galleryId);
            const slot = pickerSlot?.value || 'main';
            if (!gid) return;
            await jreq(URLS.attach, 'POST', { gallery_id: gid, slot });
            await fetchAttached();
            return;
        }

        const pageBtn = e.target.closest('[data-page]');
        if (pageBtn && pickerPagination?.contains(pageBtn)) {
            const p = Number(pageBtn.dataset.page);
            if (!p) return;
            gState.page = p;
            await fetchPicker();
        }
    };

    mgr.addEventListener('click', onMgrClick);
    cleanupFns.push(() => {
        try { mgr.removeEventListener('click', onMgrClick); } catch {}
    });

    const onRefresh = async () => {
        gState.page = 1;
        await fetchPicker();
    };

    const onSearchDebounced = () => {
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            gState.q = pickerSearch?.value || '';
            gState.page = 1;
            await fetchPicker();
        }, 250);
    };

    pickerRefresh?.addEventListener('click', onRefresh);
    if (pickerRefresh) cleanupFns.push(() => pickerRefresh.removeEventListener('click', onRefresh));

    pickerSearch?.addEventListener('input', onSearchDebounced);
    if (pickerSearch) cleanupFns.push(() => pickerSearch.removeEventListener('input', onSearchDebounced));

    const onAttachBtn = async () => {
        gState.page = 1;
        gState.q = '';
        if (pickerSearch) pickerSearch.value = '';
        await fetchPicker();

        const modalEl = document.querySelector(pickerModalSel);
        if (modalEl) {
            try { window.KTModal?.getOrCreateInstance?.(modalEl)?.show?.(); } catch {}
        }
    };

    attachBtn?.addEventListener('click', onAttachBtn);
    if (attachBtn) cleanupFns.push(() => attachBtn.removeEventListener('click', onAttachBtn));

    // modal kapanınca transient temizle (best-effort)
    const modalEl = document.querySelector(pickerModalSel);
    if (modalEl) {
        const onHidden = () => cleanupTransient();
        modalEl.addEventListener('hidden', onHidden);
        cleanupFns.push(() => {
            try { modalEl.removeEventListener('hidden', onHidden); } catch {}
        });
    }

    // ---- boot ----
    fetchAttached();

    // attach cleanup to element for idempotency + external destroy
    mgr.__gmCleanup = () => {
        cleanupTransient();

        cleanupFns.reverse().forEach((fn) => {
            try { fn(); } catch {}
        });
        cleanupFns.length = 0;

        // sortable destroy (ek garanti)
        try { galleriesMain.__sortable?.destroy?.(); } catch {}
        try { galleriesSidebar.__sortable?.destroy?.(); } catch {}
        galleriesMain.__sortable = null;
        galleriesSidebar.__sortable = null;

        mgr.__gmCleanup = null;
    };
}

export default function initGalleryManager(root = document) {
    const managers = [...root.querySelectorAll('[data-gallery-manager]')];
    if (!managers.length) return;
    managers.forEach((mgr) => mountOne(mgr));
}

export function destroyGalleryManager(root = document) {
    const managers = [...root.querySelectorAll('[data-gallery-manager]')];
    if (!managers.length) return;
    managers.forEach((mgr) => {
        if (typeof mgr.__gmCleanup === 'function') {
            try { mgr.__gmCleanup(); } catch {}
        }
    });
}
