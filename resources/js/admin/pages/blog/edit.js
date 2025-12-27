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
                try { json = JSON.parse(xhr.responseText); }
                catch { return reject('Invalid JSON'); }

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
            // edit sayfasında mevcut görsel olabilir → sadece “placeholder”a dönme
            // eğer img.src boşsa placeholder göster
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

    const syncPreview = () => {
        if (preview) preview.textContent = (slugInput.value || '').trim();
    };

    const setManualMode = (isManual) => {
        if (badge) badge.classList.toggle('hidden', !isManual);
        slugInput.style.boxShadow = isManual ? '0 0 0 2px rgba(245, 158, 11, .35)' : '';
    };

    const applyAutoSlug = () => {
        if (!titleInput) return;
        slugInput.value = slugifyTR(titleInput.value);
        syncPreview();
    };

    // başlangıç: toggle checked ama slug dolu → otomatik “override” etmeyeceğiz
    syncPreview();
    setManualMode(false);

    // slug input: manuel yazarsa auto kapat
    slugInput.addEventListener('input', () => {
        const v = slugInput.value.trim();
        if (toggle && toggle.checked && v.length > 0) {
            toggle.checked = false;
            setManualMode(true);
        }
        syncPreview();
    }, { signal });

    // title input: sadece auto açıkken ve slug boşken üret
    if (titleInput && toggle) {
        titleInput.addEventListener('input', () => {
            if (!toggle.checked) return;
            if (slugInput.value.trim().length > 0) return; // doluyu ezme
            applyAutoSlug();
        }, { signal });

        toggle.addEventListener('change', () => {
            const isAuto = toggle.checked;
            setManualMode(!isAuto);
            if (isAuto) {
                // “auto”ya dönünce slug’u başlıktan yeniden üretelim
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

export default async function init({ root, dataset }) {
    ac = new AbortController();
    const { signal } = ac;

    setupFeaturedPreview(root, signal);
    setupSlugUI(root, signal);

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
    if (updateForm) {
        updateForm.addEventListener('submit', () => lockSubmitButtons(root, 'blog-update-form'), { signal, once: true });
    }

    const deleteForm = root.querySelector('#blog-delete-form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', () => lockSubmitButtons(root, 'blog-delete-form'), { signal, once: true });
    }
    // ---------------------------
    // Galleries (Blog ↔ Gallery)
    // ---------------------------
    const blogId = dataset.blogId || root.dataset.blogId;
    const galleriesMain = root.querySelector('#blogGalleriesMain');
    const galleriesSidebar = root.querySelector('#blogGalleriesSidebar');
    const galleriesEmpty = root.querySelector('#blogGalleriesEmpty');

    const attachBtn = root.querySelector('#blogGalleryAttachBtn');
    const pickerModalId = '#blogGalleryPickerModal';

    const pickerSearch = root.querySelector('#blogGalleryPickerSearch');
    const pickerSlot = root.querySelector('#blogGalleryPickerSlot');
    const pickerRefresh = root.querySelector('#blogGalleryPickerRefresh');
    const pickerList = root.querySelector('#blogGalleryPickerList');
    const pickerEmpty = root.querySelector('#blogGalleryPickerEmpty');
    const pickerInfo = root.querySelector('#blogGalleryPickerInfo');
    const pickerPagination = root.querySelector('#blogGalleryPickerPagination');

    const gState = { page: 1, perpage: 10, q: '' };

    async function jreq(url, method, body) {
        const res = await fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
                ...(body ? {'Content-Type':'application/json'} : {}),
            },
            body: body ? JSON.stringify(body) : undefined,
            signal,
        });
        const j = await res.json().catch(() => ({}));
        return { res, j };
    }

    function galleryRow(r) {
        const slot = r.slot || 'main';
        return `
          <div class="border border-border rounded-md p-2 flex items-center justify-between gap-3"
               data-gallery-id="${r.gallery_id}"
               data-slot="${slot}">
            <div class="flex items-center gap-2">
              <button type="button" class="kt-btn kt-btn-sm kt-btn-light js-gal-handle" title="Sürükle">
                <i class="ki-outline ki-menu"></i>
              </button>
              <span class="kt-badge kt-badge-light">#${r.gallery_id}</span>
              <div class="flex flex-col">
                <div class="font-semibold">${r.name}</div>
                <div class="text-xs text-muted-foreground">${r.slug || ''}</div>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <select class="kt-select kt-select-sm js-gal-slot">
                <option value="main" ${slot === 'main' ? 'selected' : ''}>main</option>
                <option value="sidebar" ${slot === 'sidebar' ? 'selected' : ''}>sidebar</option>
              </select>
              <button type="button" class="kt-btn kt-btn-sm kt-btn-danger js-gal-detach">Kaldır</button>
            </div>
          </div>
        `;
    }

    async function fetchAttached() {
        if (!blogId) return;

        const { res, j } = await jreq(`/admin/blog/${blogId}/galleries`, 'GET');
        if (!res.ok || !j?.ok) return;

        const rows = Array.isArray(j.data) ? j.data : [];
        const main = rows.filter(x => (x.slot || 'main') === 'main');
        const side = rows.filter(x => (x.slot || 'main') === 'sidebar');

        galleriesMain.innerHTML = main.map(galleryRow).join('');
        galleriesSidebar.innerHTML = side.map(galleryRow).join('');

        const total = main.length + side.length;
        galleriesEmpty?.classList.toggle('hidden', total > 0);

        // Sortable (slot bazlı)
        if (window.Sortable && galleriesMain && !galleriesMain.__sortable) {
            galleriesMain.__sortable = new window.Sortable(galleriesMain, {
                handle: '.js-gal-handle',
                animation: 150,
                onEnd: async () => {
                    const ids = [...galleriesMain.querySelectorAll('[data-gallery-id]')].map(el => Number(el.dataset.galleryId));
                    await jreq(`/admin/blog/${blogId}/galleries/reorder`, 'POST', { slot: 'main', gallery_ids: ids });
                }
            });
        }
        if (window.Sortable && galleriesSidebar && !galleriesSidebar.__sortable) {
            galleriesSidebar.__sortable = new window.Sortable(galleriesSidebar, {
                handle: '.js-gal-handle',
                animation: 150,
                onEnd: async () => {
                    const ids = [...galleriesSidebar.querySelectorAll('[data-gallery-id]')].map(el => Number(el.dataset.galleryId));
                    await jreq(`/admin/blog/${blogId}/galleries/reorder`, 'POST', { slot: 'sidebar', gallery_ids: ids });
                }
            });
        }
    }

    function renderPickerPager(meta) {
        if (!pickerPagination) return;

        const current = Number(meta.current_page || 1);
        const last = Number(meta.last_page || 1);
        if (last <= 1) { pickerPagination.innerHTML = ''; return; }

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

        pickerPagination.innerHTML = `<div class="flex items-center gap-2">${parts.join('')}</div>`;
    }

    async function fetchPicker() {
        const qs = new URLSearchParams({
            page: String(gState.page),
            perpage: String(gState.perpage),
            q: gState.q || '',
            mode: 'active',
        });

        const res = await fetch(`/admin/galleries/list?${qs.toString()}`, { headers: { Accept: 'application/json' }, signal });
        const j = await res.json().catch(() => ({}));
        if (!res.ok || !j?.ok) return;

        const items = Array.isArray(j.data) ? j.data : [];
        const meta = j.meta || {};

        pickerList.innerHTML = items.map(g => `
          <div class="border border-border rounded-md p-3 flex items-center justify-between">
            <div class="flex flex-col">
              <div class="font-semibold">${g.name}</div>
              <div class="text-xs text-muted-foreground">${g.slug}</div>
            </div>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-primary"
                    data-attach-id="${g.id}">Bağla</button>
          </div>
        `).join('');

        pickerEmpty?.classList.toggle('hidden', items.length > 0);

        const from = items.length ? ((meta.current_page - 1) * meta.per_page + 1) : 0;
        const to = items.length ? (from + items.length - 1) : 0;
        if (pickerInfo) pickerInfo.textContent = `${from}-${to} / ${meta.total ?? items.length}`;

        renderPickerPager(meta);
    }

    attachBtn?.addEventListener('click', async () => {
        // modal açılınca listeyi çek
        gState.page = 1;
        gState.q = '';
        if (pickerSearch) pickerSearch.value = '';
        await fetchPicker();

        // kt-modal toggle için: buton modalı açmıyor, JS ile açıyoruz
        // Senin KT modal sistemin data-kt-modal-toggle ile çalışıyor.
        // Burada en temiz çözüm: attachBtn yerine modal toggle attribute koymak da olur.
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.setAttribute('data-kt-modal-toggle', pickerModalId);
        root.appendChild(toggle);
        toggle.click();
        toggle.remove();
    }, { signal });

    pickerRefresh?.addEventListener('click', () => fetchPicker(), { signal });
    pickerSearch?.addEventListener('input', () => {
        gState.q = (pickerSearch.value || '').trim();
        gState.page = 1;
        fetchPicker();
    }, { signal });

    pickerPagination?.addEventListener('click', (e) => {
        const b = e.target.closest('button[data-page]');
        if (!b) return;
        const p = Number(b.dataset.page || 1);
        if (!Number.isFinite(p) || p < 1) return;
        gState.page = p;
        fetchPicker();
    }, { signal });

    pickerList?.addEventListener('click', async (e) => {
        const b = e.target.closest('button[data-attach-id]');
        if (!b) return;

        const gid = Number(b.dataset.attachId);
        const slot = pickerSlot?.value || 'main';

        await jreq(`/admin/blog/${blogId}/galleries/attach`, 'POST', { gallery_id: gid, slot });

        // modal kapat
        const dismiss = root.querySelector(`${pickerModalId} [data-kt-modal-dismiss="true"]`);
        dismiss?.click();

        await fetchAttached();
    }, { signal });

    // detach + slot change
    function onGalleryAction(e) {
        const row = e.target.closest('[data-gallery-id]');
        if (!row) return;
        const gid = Number(row.dataset.galleryId);
        const curSlot = row.dataset.slot || 'main';

        if (e.target.closest('.js-gal-detach')) {
            jreq(`/admin/blog/${blogId}/galleries/detach`, 'POST', { gallery_id: gid, slot: curSlot })
                .then(fetchAttached);
            return;
        }

        if (e.target.closest('.js-gal-slot')) {
            // select change handler ayrı
            return;
        }
    }

    galleriesMain?.addEventListener('click', onGalleryAction, { signal });
    galleriesSidebar?.addEventListener('click', onGalleryAction, { signal });

    function onSlotChange(e) {
        const sel = e.target.closest('.js-gal-slot');
        if (!sel) return;
        const row = e.target.closest('[data-gallery-id]');
        if (!row) return;

        const gid = Number(row.dataset.galleryId);
        const from = row.dataset.slot || 'main';
        const to = sel.value;

        jreq(`/admin/blog/${blogId}/galleries/slot`, 'POST', { gallery_id: gid, from_slot: from, to_slot: to })
            .then(fetchAttached);
    }

    galleriesMain?.addEventListener('change', onSlotChange, { signal });
    galleriesSidebar?.addEventListener('change', onSlotChange, { signal });

    // init
    fetchAttached();


    // TinyMCE
    const selector = '#content_editor';
    const uploadUrl = dataset.uploadUrl;
    const tinymceSrc = dataset.tinymceSrc;
    const baseUrl = dataset.tinymceBase;
    const langUrl = dataset.tinymceLangUrl;

    await loadScriptOnce(tinymceSrc);
    initTiny({ selector, uploadUrl, baseUrl, langUrl });

    observer = observeThemeChanges(() => {
        initTiny({ selector, uploadUrl, baseUrl, langUrl });
    });
}

export function destroy() {
    try { observer?.disconnect(); } catch {}
    observer = null;

    try { ac?.abort(); } catch {}
    ac = null;

    if (lastObjectUrl) {
        try { URL.revokeObjectURL(lastObjectUrl); } catch {}
        lastObjectUrl = null;
    }

    safeTinyRemove('#content_editor');
}
