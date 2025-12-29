import Sortable from 'sortablejs';

let ac = null;
let observer = null;
let lastObjectUrl = null;

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function getTheme() {
    const root = document.documentElement;
    const body = document.body;
    const isDark = root.classList.contains('dark') || body.classList.contains('dark');
    return isDark ? 'dark' : 'light';
}

function slugifyTR(str) {
    return String(str || '')
        .trim()
        .toLowerCase()
        .replaceAll('ğ', 'g').replaceAll('ü', 'u').replaceAll('ş', 's')
        .replaceAll('ı', 'i').replaceAll('ö', 'o').replaceAll('ç', 'c')
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
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
    if (modal) modal.classList.remove('hidden');
}

function closeModal(modal) {
    if (modal) modal.classList.add('hidden');
}

function setupFeaturedPreview(root, signal) {
    const input = root.querySelector('#featured_image');
    const img = root.querySelector('#featured_preview');
    const ph = root.querySelector('#featured_placeholder');
    if (!input || !img || !ph) return;

    input.addEventListener('change', () => {
        const file = input.files && input.files[0] ? input.files[0] : null;

        if (lastObjectUrl) {
            try { URL.revokeObjectURL(lastObjectUrl); } catch {}
            lastObjectUrl = null;
        }

        if (!file) {
            if (!img.getAttribute('src')) {
                img.src = '';
                img.classList.add('hidden');
                ph.classList.remove('hidden');
            }
            return;
        }

        lastObjectUrl = URL.createObjectURL(file);
        img.src = lastObjectUrl;
        img.classList.remove('hidden');
        ph.classList.add('hidden');
    }, { signal });
}

function setupSlugUI(root, signal) {
    const titleInput = root.querySelector('#title') || root.querySelector('input[name="title"]');
    const slugInput = root.querySelector('#slug');
    const toggle = root.querySelector('#slug_auto_toggle');
    const regenBtn = root.querySelector('#slug_regen_btn');
    const badge = root.querySelector('#slug_mode_badge');
    const preview = root.querySelector('#url_slug_preview');

    if (!slugInput) return;

    const syncPreview = () => { if (preview) preview.textContent = (slugInput.value || '').trim(); };
    const setManualMode = (isManual) => {
        if (badge) badge.classList.toggle('hidden', !isManual);
        slugInput.style.boxShadow = isManual ? '0 0 0 2px rgba(245, 158, 11, .35)' : '';
    };
    const applyAutoSlug = () => {
        if (!titleInput) return;
        slugInput.value = slugifyTR(titleInput.value);
        syncPreview();
    };

    syncPreview();
    setManualMode(false);

    slugInput.addEventListener('input', () => {
        const v = slugInput.value.trim();
        if (toggle && toggle.checked && v.length > 0) {
            toggle.checked = false;
            setManualMode(true);
        }
        syncPreview();
    }, { signal });

    if (titleInput && toggle) {
        titleInput.addEventListener('input', () => {
            if (!toggle.checked) return;
            if (slugInput.value.trim().length > 0) return;
            applyAutoSlug();
        }, { signal });

        toggle.addEventListener('change', () => {
            const isAuto = toggle.checked;
            setManualMode(!isAuto);
            if (isAuto) {
                slugInput.value = '';
                applyAutoSlug();
            }
        }, { signal });
    }

    if (regenBtn && toggle) {
        regenBtn.addEventListener('click', () => {
            toggle.checked = true;
            setManualMode(false);
            slugInput.value = '';
            applyAutoSlug();
        }, { signal });
    }
}

function lockSubmitButtons(root, formId) {
    root.querySelectorAll(`button[form="${formId}"][type="submit"]`).forEach((b) => {
        b.disabled = true;
        b.classList.add('opacity-60', 'pointer-events-none');
    });
}

function escapeHtml(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

export default async function init({ root, dataset }) {
    ac = new AbortController();
    const { signal } = ac;

    setupFeaturedPreview(root, signal);
    setupSlugUI(root, signal);

    // TinyMCE (blade dataset ile geliyorsa)
    const tiny = dataset?.tinymce ? JSON.parse(dataset.tinymce) : null;
    if (tiny?.src) {
        await loadScriptOnce(tiny.src).catch(() => {});
        if (tiny?.selector && tiny?.uploadUrl && tiny?.baseUrl && tiny?.langUrl) {
            initTiny({
                selector: tiny.selector,
                uploadUrl: tiny.uploadUrl,
                baseUrl: tiny.baseUrl,
                langUrl: tiny.langUrl,
            });

            observer = observeThemeChanges(() => {
                initTiny({
                    selector: tiny.selector,
                    uploadUrl: tiny.uploadUrl,
                    baseUrl: tiny.baseUrl,
                    langUrl: tiny.langUrl,
                });
            });
        }
    }

    // Modal delegation
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

    // Prevent double submit
    const updateForm = root.querySelector('#blog-update-form');
    if (updateForm) updateForm.addEventListener('submit', () => lockSubmitButtons(root, 'blog-update-form'), { signal, once: true });

    const deleteForm = root.querySelector('#blog-delete-form');
    if (deleteForm) deleteForm.addEventListener('submit', () => lockSubmitButtons(root, 'blog-delete-form'), { signal, once: true });

    // ---------------------------
    // Galleries (Blog ↔ Gallery)
    // ---------------------------
    const blogId = dataset.blogId || root.dataset.blogId;
    const galleriesMain = root.querySelector('#blogGalleriesMain');
    const galleriesSidebar = root.querySelector('#blogGalleriesSidebar');
    const galleriesEmpty = root.querySelector('#blogGalleriesEmpty');

    const pickerModalId = '#blogGalleryPickerModal';
    const pickerSearch = root.querySelector('#blogGalleryPickerSearch');
    const pickerSlot = root.querySelector('#blogGalleryPickerSlot');
    const pickerRefresh = root.querySelector('#blogGalleryPickerRefresh');
    const pickerList = root.querySelector('#blogGalleryPickerList');
    const pickerEmpty = root.querySelector('#blogGalleryPickerEmpty');
    const pickerInfo = root.querySelector('#blogGalleryPickerInfo');
    const pickerPagination = root.querySelector('#blogGalleryPickerPagination');

    if (!blogId || !galleriesMain || !galleriesSidebar) return;

    async function jreq(url, method, body) {
        const res = await fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
                ...(body ? { 'Content-Type': 'application/json' } : {}),
            },
            body: body ? JSON.stringify(body) : undefined,
            signal,
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

        const { res, j } = await jreq(`/admin/blog/${blogId}/galleries/reorder`, 'POST', {
            main_ids,
            sidebar_ids,
        });

        if (!res.ok || !j?.ok) {
            console.error('bulk reorder failed', res.status, j);
        }
    }

    function attachedRow(r) {
        const slot = r.slot || 'main';
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
        <select class="kt-select kt-select-sm js-gal-slot">
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
        try { galleriesMain.__sortable?.destroy?.(); } catch {}
        try { galleriesSidebar.__sortable?.destroy?.(); } catch {}

        galleriesMain.__sortable = new Sortable(galleriesMain, {
            group: { name: 'blog-galleries', pull: true, put: true },
            handle: '.js-gal-handle',
            animation: 150,
            onEnd: persistBothSlots,
        });

        galleriesSidebar.__sortable = new Sortable(galleriesSidebar, {
            group: { name: 'blog-galleries', pull: true, put: true },
            handle: '.js-gal-handle',
            animation: 150,
            onEnd: persistBothSlots,
        });
    }

    async function fetchAttached() {
        const { res, j } = await jreq(`/admin/blog/${blogId}/galleries`, 'GET');
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

        await jreq(`/admin/blog/${blogId}/galleries/detach`, 'POST', { gallery_id: gid });
        row.remove();

        await fetchAttached();
    }, { signal });

    // Slot select: DOM’u taşı + tek endpoint
    root.addEventListener('change', async (e) => {
        const sel = e.target.closest('.js-gal-slot');
        if (!sel) return;

        const row = sel.closest('[data-gallery-id]');
        if (!row) return;

        const to = sel.value || 'main';
        if (to === 'main') galleriesMain.appendChild(row);
        else galleriesSidebar.appendChild(row);

        await persistBothSlots();
    }, { signal });

    // Picker (list)
    const gState = { page: 1, perpage: 10, q: '' };

    function renderPager(meta) {
        if (!pickerPagination) return;

        const current = Number(meta.current_page || 1);
        const last = Number(meta.last_page || 1);

        if (last <= 1) {
            pickerPagination.innerHTML = '';
            return;
        }

        const btn = (p, label, disabled = false, active = false) => `
<button type="button"
        class="kt-btn kt-btn-sm ${active ? 'kt-btn-primary' : 'kt-btn-light'}"
        data-page="${p}"
        ${disabled ? 'disabled' : ''}>${label}</button>`;

        const parts = [];
        parts.push(btn(current - 1, '‹', current <= 1));

        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);

        if (start > 1) parts.push(btn(1, '1', false, current === 1));
        if (start > 2) parts.push(`<span class="px-1 text-muted-foreground">…</span>`);

        for (let p = start; p <= end; p++) parts.push(btn(p, String(p), false, p === current));

        if (end < last - 1) parts.push(`<span class="px-1 text-muted-foreground">…</span>`);
        if (end < last) parts.push(btn(last, String(last), false, current === last));

        parts.push(btn(current + 1, '›', current >= last));

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

        const { res, j } = await jreq(`/admin/galleries/list?${qs.toString()}`, 'GET');
        if (!res.ok || !j?.ok) return;

        const items = Array.isArray(j.data) ? j.data : [];
        const meta = j.meta || {};

        pickerList.innerHTML = items.map(g => `
<div class="rounded-xl border border-border bg-background p-3 flex items-start justify-between gap-3">
    <div class="grid">
        <div class="font-medium">${escapeHtml(g.name)}</div>
        <div class="text-xs text-muted-foreground">${escapeHtml(g.slug || '')}</div>
    </div>
    <button type="button"
            class="kt-btn kt-btn-sm kt-btn-primary js-picker-attach"
            data-gallery-id="${g.id}">
        Bağla
    </button>
</div>`).join('');

        if (pickerEmpty) pickerEmpty.classList.toggle('hidden', items.length > 0);

        const from = items.length ? ((Number(meta.current_page || 1) - 1) * Number(meta.per_page || items.length) + 1) : 0;
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

        const { res } = await jreq(`/admin/blog/${blogId}/galleries/attach`, 'POST', { gallery_id: galleryId, slot });
        if (!res.ok) return;

        await fetchAttached();
    }, { signal });

    // Modal açılınca picker’ı doldur
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

    // initial
    await fetchAttached();
}

export function destroy() {
    try { ac?.abort(); } catch {}
    ac = null;

    try { observer?.disconnect?.(); } catch {}
    observer = null;

    if (lastObjectUrl) {
        try { URL.revokeObjectURL(lastObjectUrl); } catch {}
        lastObjectUrl = null;
    }
}
