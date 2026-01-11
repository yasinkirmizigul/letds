import Sortable from 'sortablejs';
export default function initGalleryManager(root = document) {
    const managers = [...root.querySelectorAll('[data-gallery-manager]')];
    if (!managers.length) return;

    managers.forEach((mgr) => mountOne(mgr));
}

function mountOne(mgr) {
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

    // ---- helpers
    const escapeHtml = (s) => String(s ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    async function jreq(url, method, body) {
        const res = await fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
                ...(body ? { 'Content-Type': 'application/json' } : {}),
            },
            body: body ? JSON.stringify(body) : undefined,
            credentials: 'same-origin',
        });

        const j = await res.json().catch(() => ({}));
        return { res, j };
    }

    function getIds(container) {
        return [...container.querySelectorAll('[data-gallery-id]')]
            .map(el => Number(el.dataset.galleryId))
            .filter(Boolean);
    }

    function syncSlotSelects() {
        galleriesMain.querySelectorAll('[data-gallery-id]').forEach(el => {
            el.dataset.slot = 'main';
            const sel = el.querySelector('.js-gal-slot');
            if (sel) sel.value = 'main';
        });

        galleriesSidebar.querySelectorAll('[data-gallery-id]').forEach(el => {
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

    function attachedRow(r) {
        const slot = (r.slot || 'main') === 'sidebar' ? 'sidebar' : 'main';
        const g = r.gallery || {};
        const name = escapeHtml(g.name ?? `Galeri #${r.gallery_id}`);
        const slug = escapeHtml(g.slug ?? '');

        return `
<div class="rounded-xl border border-border bg-background p-3 flex items-start justify-between gap-3"
     data-gallery-id="${r.gallery_id}"
     data-slot="${slot}">
    <div class="flex items-start gap-3">
        <div class="js-gal-handle cursor-move select-none text-muted-foreground mt-1" title="Sırala / Taşı">
            <i class="ki-outline ki-menu"></i>
        </div>
        <div class="grid">
            <div class="font-semibold">${name}</div>
            <div class="text-xs text-muted-foreground">#${r.gallery_id} • ${slug}</div>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <select class="kt-select kt-select-sm js-gal-slot" data-kt-select="true">
            <option value="main" ${slot === 'main' ? 'selected' : ''}>main</option>
            <option value="sidebar" ${slot === 'sidebar' ? 'selected' : ''}>sidebar</option>
        </select>

        <button type="button" class="kt-btn kt-btn-sm kt-btn-danger js-detach">
            <i class="ki-outline ki-trash"></i>
        </button>
    </div>
</div>`;
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
    }

    async function fetchAttached() {
        const { res, j } = await jreq(URLS.index, 'GET');
        if (!res.ok || !j?.ok) return;

        const rows = Array.isArray(j.data) ? j.data : [];
        const main = rows.filter(x => (x.slot || 'main') === 'main');
        const side = rows.filter(x => (x.slot || 'main') === 'sidebar');

        galleriesMain.innerHTML = main.map(attachedRow).join('');
        galleriesSidebar.innerHTML = side.map(attachedRow).join('');

        const total = main.length + side.length;
        if (galleriesEmpty) galleriesEmpty.classList.toggle('hidden', total > 0);

        ensureSortables();
        syncSlotSelects();
    }

    // ---- Picker
    const gState = { page: 1, perpage: 10, q: '' };

    function renderPager(meta) {
        if (!pickerPagination) return;

        const current = Number(meta.current_page || 1);
        const last = Number(meta.last_page || 1);

        if (last <= 1) {
            pickerPagination.innerHTML = '';
            return;
        }

        const mk = (p, label, disabled = false, active = false) => `
<button type="button"
        class="kt-btn kt-btn-sm ${active ? 'kt-btn-primary' : 'kt-btn-light'}"
        ${disabled ? 'disabled' : ''}
        data-page="${p}">
    ${label}
</button>`;

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

        pickerPagination.innerHTML = `<div class="flex items-center gap-1">${parts.join('')}</div>`;
    }

    async function fetchPicker() {
        if (!pickerList) return;

        const qs = new URLSearchParams({
            page: String(gState.page),
            perpage: String(gState.perpage),
            q: gState.q || '',
            mode: 'active',
        });

        const { res, j } = await jreq(`${URLS.list}?${qs.toString()}`, 'GET');
        if (!res.ok || !j?.ok) {
            pickerList.innerHTML = `<div class="text-sm text-muted-foreground p-3">Liste alınamadı (${res.status})</div>`;
            if (pickerEmpty) pickerEmpty.classList.remove('hidden');
            if (pickerInfo) pickerInfo.textContent = '';
            if (pickerPagination) pickerPagination.innerHTML = '';
            return;
        }

        const items = Array.isArray(j.data) ? j.data : [];
        const meta = j.meta || {};

        pickerList.innerHTML = items.map(g => `
<div class="p-3 flex items-center justify-between gap-3">
    <div class="min-w-0">
        <div class="font-medium truncate">${escapeHtml(g.name)}</div>
        <div class="text-xs text-muted-foreground">${escapeHtml(g.slug || '')}</div>
    </div>

    <button type="button"
            class="kt-btn kt-btn-sm kt-btn-primary js-picker-attach"
            data-gallery-id="${g.id}">
        Bağla
    </button>
</div>
`).join('');

        if (pickerEmpty) pickerEmpty.classList.toggle('hidden', items.length > 0);

        const from = items.length ? ((Number(meta.current_page || 1) - 1) * Number(meta.per_page || items.length) + 1) : 0;
        const to = items.length ? (from + items.length - 1) : 0;
        if (pickerInfo) pickerInfo.textContent = `${from}-${to} / ${meta.total ?? items.length}`;

        renderPager(meta);
    }

    // ---- Events (scoped to mgr)
    mgr.addEventListener('click', async (e) => {
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
    });

    mgr.addEventListener('change', async (e) => {
        const sel = e.target.closest('.js-gal-slot');
        if (!sel) return;

        const row = sel.closest('[data-gallery-id]');
        if (!row) return;

        const to = sel.value || 'main';
        row.dataset.slot = to;
        if (to === 'main') galleriesMain.appendChild(row);
        else galleriesSidebar.appendChild(row);

        await persistBothSlots();
    });

    pickerRefresh?.addEventListener('click', async () => {
        gState.page = 1;
        await fetchPicker();
    });

    pickerSearch?.addEventListener('input', async () => {
        gState.q = pickerSearch.value || '';
        gState.page = 1;
        await fetchPicker();
    });

    // attach button: modal açıkken listeyi deterministik çek
    attachBtn?.addEventListener('click', async () => {
        gState.page = 1;
        gState.q = '';
        if (pickerSearch) pickerSearch.value = '';
        await fetchPicker();

        const modalEl = document.querySelector(pickerModalSel);
        if (modalEl) {
            try { window.KTModal?.getOrCreateInstance?.(modalEl)?.show?.(); } catch {}
        }
    });

    // boot
    fetchAttached();
}
