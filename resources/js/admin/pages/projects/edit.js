import Sortable from 'sortablejs';

let ac = null;

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}
let tinyObserver = null;

function getTheme() {
    const root = document.documentElement;
    const body = document.body;
    const isDark = root.classList.contains('dark') || body.classList.contains('dark');
    return isDark ? 'dark' : 'light';
}

function loadScriptOnce(src) {
    if (!src) return Promise.reject(new Error('tinymce src missing'));
    if (document.querySelector(`script[data-once="${src}"]`)) return Promise.resolve();

    return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = src;
        s.async = true;
        s.dataset.once = src;
        s.onload = () => resolve();
        s.onerror = () => reject(new Error('Failed to load: ' + src));
        document.head.appendChild(s);
    });
}

function safeTinyRemove(selector) {
    try { window.tinymce?.remove?.(selector); } catch {}
}

function initTiny({ selector, uploadUrl, baseUrl, langUrl }) {
    if (!window.tinymce) return;

    const theme = getTheme();
    safeTinyRemove(selector);

    window.tinymce.init({
        selector,
        height: 420,
        license_key: 'gpl',
        base_url: baseUrl,
        suffix: '.min',
        language: 'tr',
        language_url: langUrl,
        skin: theme === 'dark' ? 'oxide-dark' : 'oxide',
        content_css: theme === 'dark' ? 'dark' : 'default',
        plugins: 'lists link image code table',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link image table | code',
        menubar: false,
        branding: false,
        promotion: false,
        automatic_uploads: true,
        paste_data_images: true,
        images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', uploadUrl);
            xhr.withCredentials = true;
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) progress((e.loaded / e.total) * 100);
            };

            xhr.onload = () => {
                if (xhr.status < 200 || xhr.status >= 300) return reject('Upload failed: ' + xhr.status);
                let json;
                try { json = JSON.parse(xhr.responseText); } catch { return reject('Invalid JSON'); }
                if (!json || typeof json.location !== 'string') return reject('No location returned');
                resolve(json.location);
            };

            xhr.onerror = () => reject('Network error');

            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            xhr.send(formData);
        }),
    });
}

function observeThemeChanges(onChange) {
    let current = getTheme();
    const obs = new MutationObserver(() => {
        const next = getTheme();
        if (next === current) return;
        current = next;
        onChange(next);
    });

    obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    obs.observe(document.body, { attributes: true, attributeFilter: ['class'] });

    return obs;
}

function openModal(root, selector) {
    const modal = root.querySelector(selector);
    if (!modal) return;

    try {
        if (window.KTModal?.getOrCreateInstance) {
            const inst = window.KTModal.getOrCreateInstance(modal);
            inst?.show?.();
            return;
        }
        if (window.KTModal?.getInstance) {
            const inst = window.KTModal.getInstance(modal) || new window.KTModal(modal);
            inst?.show?.();
            return;
        }
    } catch {}

    modal.classList.remove('hidden');
}

function closeModal(modal) {
    if (!modal) return;

    try {
        if (window.KTModal?.getOrCreateInstance) {
            const inst = window.KTModal.getOrCreateInstance(modal);
            inst?.hide?.();
            return;
        }
        if (window.KTModal?.getInstance) {
            const inst = window.KTModal.getInstance(modal) || new window.KTModal(modal);
            inst?.hide?.();
            return;
        }
    } catch {}

    modal.classList.add('hidden');
}

async function jreq(signal, url, method, body) {
    const res = await fetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
            ...(body ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
        credentials: 'same-origin',
        signal,
    });

    const j = await res.json().catch(() => ({}));
    return { res, j };
}

function escapeHtml(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getIds(container) {
    return [...container.querySelectorAll('[data-gallery-id]')]
        .map(el => Number(el.dataset.galleryId))
        .filter(Boolean);
}

export default async function init() {
    const root = document.querySelector('[data-page="projects.edit"]');
    if (!root) return;

    ac = new AbortController();
    const { signal } = ac;

    const projectId = root.getAttribute('data-id');
    if (!projectId) return;

    // TinyMCE
    const ds = root.dataset;
    const tinymceSrc = ds.tinymceSrc;
    const tinymceBase = ds.tinymceBase;
    const tinymceLangUrl = ds.tinymceLangUrl;
    const uploadUrl = ds.uploadUrl;

    if (tinymceSrc && tinymceBase && tinymceLangUrl && uploadUrl) {
        await loadScriptOnce(tinymceSrc).catch(() => {});
        initTiny({
            selector: '#content_editor',
            uploadUrl,
            baseUrl: tinymceBase,
            langUrl: tinymceLangUrl,
        });

        tinyObserver = observeThemeChanges(() => {
            initTiny({
                selector: '#content_editor',
                uploadUrl,
                baseUrl: tinymceBase,
                langUrl: tinymceLangUrl,
            });
        });
    }

    // slug preview
    const title = root.querySelector('#projectTitle');
    const slug = root.querySelector('#projectSlug');
    const genBtn = root.querySelector('#projectSlugGenBtn');
    const prev = root.querySelector('#projectSlugPreview');

    const slugify = (s) => (s || '')
        .toString()
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');

    const setPrev = (v) => { if (prev) prev.textContent = v || ''; };

    const applyFromTitle = () => {
        if (!title || !slug) return;
        const v = slugify(title.value);
        slug.value = v;
        setPrev(v);
    };

    if (slug) {
        setPrev(slug.value);
        slug.addEventListener('input', () => setPrev(slug.value.trim()), { signal });
    }
    if (genBtn) genBtn.addEventListener('click', applyFromTitle, { signal });
    if (title && slug) {
        title.addEventListener('blur', () => {
            if (slug.value.trim() !== '') return;
            applyFromTitle();
        }, { signal });
    }

    // modal delegation (legacy support: data-kt-modal-target/close)
    root.addEventListener('click', (e) => {
        const openBtn = e.target.closest('[data-kt-modal-target]');
        if (openBtn && root.contains(openBtn)) {
            const sel = openBtn.getAttribute('data-kt-modal-target');
            if (sel) openModal(root, sel);
            return;
        }

        const closeBtn = e.target.closest('[data-kt-modal-close]');
        if (closeBtn && root.contains(closeBtn)) {
            closeModal(closeBtn.closest('.kt-modal'));
            return;
        }

        const modal = e.target.classList?.contains('kt-modal') ? e.target : null;
        if (modal) closeModal(modal);
    }, { signal });

    // Delete
    const delBtn = root.querySelector('#projectDeleteBtn');
    if (delBtn) {
        delBtn.addEventListener('click', async () => {
            const ok = confirm('Bu projeyi silmek istiyor musun?');
            if (!ok) return;

            const { j } = await jreq(signal, `/admin/projects/${projectId}`, 'DELETE');
            if (j?.ok) window.location.href = '/admin/projects';
        }, { signal });
    }

    // ---------------------------
    // Galleries (Project ↔ Gallery)
    // ---------------------------
    const galleriesMain = root.querySelector('#projectGalleriesMain');
    const galleriesSidebar = root.querySelector('#projectGalleriesSidebar');
    const galleriesEmpty = root.querySelector('#projectGalleriesEmpty');

    const pickerModalId = '#projectGalleryPickerModal';
    const pickerSearch = root.querySelector('#projectGalleryPickerSearch');
    const pickerSlot = root.querySelector('#projectGalleryPickerSlot');
    const pickerRefresh = root.querySelector('#projectGalleryPickerRefresh');
    const pickerList = root.querySelector('#projectGalleryPickerList');
    const pickerEmpty = root.querySelector('#projectGalleryPickerEmpty');
    const pickerInfo = root.querySelector('#projectGalleryPickerInfo');
    const pickerPagination = root.querySelector('#projectGalleryPickerPagination');

    const galleryCard = root.querySelector('#projectGalleryCard');
    if (!galleryCard) return;

    const URLS = {
        list: galleryCard.dataset.galleriesListUrl,
        index: galleryCard.dataset.projectGalleriesIndexUrl,
        attach: galleryCard.dataset.projectGalleriesAttachUrl,
        detach: galleryCard.dataset.projectGalleriesDetachUrl,
        reorder: galleryCard.dataset.projectGalleriesReorderUrl,
    };

    if (!galleriesMain || !galleriesSidebar) return;

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

        const { res, j } = await jreq(signal, URLS.reorder, 'POST', {
            main_ids,
            sidebar_ids,
        });

        if (!res.ok || !j?.ok) console.error('bulk reorder failed', res.status, j);
    }

    // ✅ PATCH: slot’u render ederken select’e selected bas + data-slot yaz
    function attachedRow(r) {
        const slot = (r.slot || 'main') === 'sidebar' ? 'sidebar' : 'main';
        const g = r.gallery || {};
        const name = escapeHtml(g.name ?? `Galeri #${r.gallery_id}`);
        const slug = escapeHtml(g.slug ?? '');

        return `
<div class="flex items-center justify-between gap-3 border rounded-lg p-3"
     data-gallery-id="${r.gallery_id}"
     data-slot="${slot}">
    <div class="min-w-0">
        <div class="font-medium truncate">${name}</div>
        <div class="text-xs text-muted-foreground">#${r.gallery_id} • ${slug}</div>
    </div>

    <div class="shrink-0 flex items-center gap-2">
        <span class="cursor-grab js-gal-handle text-muted-foreground" title="Sürükle">
            <i class="ki-outline ki-dots-vertical fs-2"></i>
        </span>

        <select class="kt-select kt-select-sm w-[120px] js-gal-slot">
            <option value="main" ${slot === 'main' ? 'selected' : ''}>main</option>
            <option value="sidebar" ${slot === 'sidebar' ? 'selected' : ''}>sidebar</option>
        </select>

        <button type="button" class="kt-btn kt-btn-sm kt-btn-light js-detach">Kaldır</button>
    </div>
</div>
`;
    }

    function ensureSortables() {
        try { galleriesMain.__sortable?.destroy?.(); } catch {}
        try { galleriesSidebar.__sortable?.destroy?.(); } catch {}

        galleriesMain.__sortable = new Sortable(galleriesMain, {
            group: { name: 'project-galleries', pull: true, put: true },
            handle: '.js-gal-handle',
            animation: 150,
            onEnd: persistBothSlots,
        });

        galleriesSidebar.__sortable = new Sortable(galleriesSidebar, {
            group: { name: 'project-galleries', pull: true, put: true },
            handle: '.js-gal-handle',
            animation: 150,
            onEnd: persistBothSlots,
        });
    }

    async function fetchAttached() {
        const { res, j } = await jreq(signal, URLS.index, 'GET');
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

    // Detach
    root.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-detach');
        if (!btn) return;

        const row = btn.closest('[data-gallery-id]');
        const gid = Number(row?.dataset.galleryId);
        if (!gid) return;

        await jreq(signal, URLS.detach, 'POST', { gallery_id: gid });
        await fetchAttached();
    }, { signal });

    // Slot select change: DOM taşı + tek endpoint
    root.addEventListener('change', async (e) => {
        const sel = e.target.closest('.js-gal-slot');
        if (!sel) return;

        const row = sel.closest('[data-gallery-id]');
        if (!row) return;

        const to = sel.value || 'main';
        row.dataset.slot = to;

        if (to === 'main') galleriesMain.appendChild(row);
        else galleriesSidebar.appendChild(row);

        await persistBothSlots();
    }, { signal });

    // Picker
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

    // ✅ PATCH: fetch fail olunca visible mesaj bas + empty/info yönetimi
    async function fetchPicker() {
        if (!pickerList) return;

        const qs = new URLSearchParams({
            page: String(gState.page),
            perpage: String(gState.perpage),
            q: gState.q || '',
            mode: 'active',
        });

        const { res, j } = await jreq(signal, `${URLS.list}?${qs.toString()}`, 'GET');
        if (!res.ok || !j?.ok) {
            pickerList.innerHTML = `<div class="text-sm text-muted-foreground p-3">
                Liste alınamadı (${res.status})
            </div>`;
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

        const from = items.length
            ? ((Number(meta.current_page || 1) - 1) * Number(meta.per_page || items.length) + 1)
            : 0;
        const to = items.length ? (from + items.length - 1) : 0;

        if (pickerInfo) pickerInfo.textContent = `${from}-${to} / ${meta.total ?? items.length}`;
        renderPager(meta);
    }

    pickerRefresh?.addEventListener('click', async () => {
        gState.page = 1;
        await fetchPicker();
    }, { signal });

    pickerSearch?.addEventListener('input', async () => {
        gState.q = pickerSearch.value || '';
        gState.page = 1;
        await fetchPicker();
    }, { signal });

    pickerPagination?.addEventListener('click', async (e) => {
        const b = e.target.closest('[data-page]');
        if (!b) return;

        const p = Number(b.getAttribute('data-page') || 1);
        if (!p || p < 1) return;

        gState.page = p;
        await fetchPicker();
    }, { signal });

    pickerList?.addEventListener('click', async (e) => {
        const b = e.target.closest('.js-picker-attach');
        if (!b) return;

        const galleryId = Number(b.dataset.galleryId);
        const slot = pickerSlot?.value || 'main';
        if (!galleryId) return;

        const { res } = await jreq(signal, URLS.attach, 'POST', {
            gallery_id: galleryId,
            slot,
        });

        if (!res.ok) return;
        await fetchAttached();
    }, { signal });

    // ✅ PATCH 1: Modal açılınca picker’ı doldur (data-kt-modal-target)
    root.addEventListener('click', async (e) => {
        const openBtn = e.target.closest('[data-kt-modal-target]');
        if (!openBtn) return;

        const sel = openBtn.getAttribute('data-kt-modal-target');
        if (sel !== pickerModalId) return;

        gState.page = 1;
        gState.q = '';
        if (pickerSearch) pickerSearch.value = '';
        await fetchPicker();
    }, { signal });

    // ✅ PATCH 2: Modal açılınca picker’ı doldur (data-kt-modal-toggle)  ← senin blade bunu kullanıyor
    root.addEventListener('click', async (e) => {
        const tgl = e.target.closest(`[data-kt-modal-toggle="${pickerModalId}"]`);
        if (!tgl) return;

        gState.page = 1;
        gState.q = '';
        if (pickerSearch) pickerSearch.value = '';
        await fetchPicker();
    }, { signal });

    // initial
    await fetchAttached();
}

export function destroy() {
    try { ac?.abort(); } catch {}
    ac = null;
    try { tinyObserver?.disconnect?.(); } catch {}
    tinyObserver = null;
    safeTinyRemove('#content_editor');
}
