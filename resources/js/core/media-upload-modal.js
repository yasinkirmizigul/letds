// resources/js/core/media-upload-modal.js

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function esc(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function preventAll(e) {
    e.preventDefault();
    e.stopPropagation();
}

function setRing(el, on) {
    el.classList.toggle('ring-2', !!on);
    el.classList.toggle('ring-border', !!on);
}

function inferKind(mime, nameOrUrl) {
    const m = String(mime || '').toLowerCase();
    const s = String(nameOrUrl || '').toLowerCase();
    const ext = s.includes('.') ? s.split('.').pop() : '';
    if (m.startsWith('image/') || ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'].includes(ext)) return 'image';
    if (m.startsWith('video/') || ['mp4', 'webm', 'ogg', 'mov', 'm4v'].includes(ext)) return 'video';
    if (m === 'application/pdf' || ext === 'pdf') return 'pdf';
    return 'other';
}

async function jsonReq(url, method, bodyObj) {
    const res = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken() ? { 'X-CSRF-TOKEN': csrfToken() } : {}),
            ...(bodyObj ? { 'Content-Type': 'application/json' } : {}),
        },
        credentials: 'same-origin',
        body: bodyObj ? JSON.stringify(bodyObj) : undefined,
    });

    const j = await res.json().catch(() => ({}));
    return { res, j };
}

/**
 * Global modal init. Safe to call multiple times.
 * Expects modal ids:
 *  - #mediaUploadModal
 *  - Upload: #mediaDropzone, #mediaFiles (or #mediaFile), #mediaUploadList, #mediaStartUpload, #mediaClearQueue
 *  - Apply All: #mediaApplyTitleAll, #mediaApplyAltAll, plus inputs #mediaTitle, #mediaAlt
 *  - Library: #mediaLibraryPane, #mediaLibraryResults, #mediaLibraryPagination
 *            #mediaLibrarySearch, #mediaLibraryType, #mediaRefreshLibrary
 *            #mediaLibraryBulkBar, #mediaLibrarySelectedCount, #mediaLibraryCheckAll
 */
export function initMediaUploadModal(scope = document) {
    const modal = scope.querySelector('#mediaUploadModal');
    if (!modal || modal.__mediaUploadInited) return;
    modal.__mediaUploadInited = true;

    // -----------------------------
    // UPLOAD PANE (Dropzone + queue)
    // -----------------------------
    const dz = modal.querySelector('#mediaDropzone');
    const input = modal.querySelector('#mediaFiles') || modal.querySelector('#mediaFile');
    const uploadList = modal.querySelector('#mediaUploadList');
    const startBtn = modal.querySelector('#mediaStartUpload');
    const clearBtn = modal.querySelector('#mediaClearQueue');

    const titleEl = modal.querySelector('#mediaTitle');
    const altEl = modal.querySelector('#mediaAlt');
    const applyTitleAllBtn = modal.querySelector('#mediaApplyTitleAll');
    const applyAltAllBtn = modal.querySelector('#mediaApplyAltAll');

    const queueInfo = modal.querySelector('#mediaQueueInfo');
    const errBox = modal.querySelector('#mediaUploadError');

    let queue = []; // {file, title, alt, status, progress, error}
    let uploading = false;

    function setError(msg) {
        if (!errBox) return;
        errBox.textContent = msg || '';
        errBox.classList.toggle('hidden', !msg);
    }

    function renderQueue() {
        if (!uploadList) return;

        if (queueInfo) queueInfo.textContent = String(queue.length);

        uploadList.innerHTML = queue.map((q, idx) => {
            const p = Math.max(0, Math.min(100, Number(q.progress || 0)));

            const badge =
                q.status === 'done' ? `<span class="kt-badge kt-badge-outline">OK</span>` :
                    q.status === 'error' ? `<span class="kt-badge kt-badge-outline">Hata</span>` :
                        q.status === 'uploading' ? `<span class="kt-badge kt-badge-outline">Yükleniyor</span>` :
                            `<span class="kt-badge kt-badge-outline">Bekliyor</span>`;

            return `
                <div class="rounded-xl border border-border bg-background p-4 grid gap-3" data-i="${idx}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="grid">
                            <div class="font-medium">${esc(q.file?.name || 'Dosya')}</div>
                            ${q.error ? `<div class="text-xs text-destructive whitespace-pre-wrap">${esc(q.error)}</div>` : ''}
                        </div>
                        <div class="flex items-center gap-2">
                            ${badge}
                            <button type="button"
                                    class="kt-btn kt-btn-sm kt-btn-light"
                                    data-act="remove"
                                    ${uploading ? 'disabled' : ''}>
                                <i class="ki-outline ki-cross"></i>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input class="kt-input" data-act="title" type="text" placeholder="Başlık (opsiyonel)"
                               value="${esc(q.title || '')}" ${uploading ? 'disabled' : ''}>
                        <input class="kt-input" data-act="alt" type="text" placeholder="Alt (opsiyonel)"
                               value="${esc(q.alt || '')}" ${uploading ? 'disabled' : ''}>
                    </div>

                    <div class="rounded-lg border border-border bg-muted/10 overflow-hidden">
                        <div class="h-2 bg-muted" style="width:${p}%"></div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function addFiles(fileList) {
        const t = (titleEl?.value || '').trim();
        const a = (altEl?.value || '').trim();

        [...fileList].forEach(f => {
            queue.push({
                file: f,
                title: t,
                alt: a,
                status: 'queued',
                progress: 0,
                error: '',
            });
        });

        setError('');
        renderQueue();
    }

    function applyTitleAll() {
        const v = (titleEl?.value || '').trim();
        if (!queue.length) return;
        queue.forEach(q => { q.title = v; }); // “hepsine uygula” = hepsine overwrite
        renderQueue();
    }

    function applyAltAll() {
        const v = (altEl?.value || '').trim();
        if (!queue.length) return;
        queue.forEach(q => { q.alt = v; }); // hepsine overwrite
        renderQueue();
    }

    async function uploadOne(q) {
        q.status = 'uploading';
        q.progress = 30;
        q.error = '';
        renderQueue();

        const fd = new FormData();
        fd.append('file', q.file);

        // satır boşsa, global’i kullan
        const t = (q.title || '').trim() || (titleEl?.value || '').trim();
        const a = (q.alt || '').trim() || (altEl?.value || '').trim();
        if (t) fd.append('title', t);
        if (a) fd.append('alt', a);

        const res = await fetch('/admin/media/upload', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: fd,
        });

        const j = await res.json().catch(() => ({}));

        if (!res.ok || !j?.ok) {
            q.status = 'error';
            q.progress = 0;
            q.error = j?.error?.message || j?.message || `HTTP ${res.status}`;
            renderQueue();
            return null;
        }

        q.status = 'done';
        q.progress = 100;
        renderQueue();
        return j.data;
    }

    if (dz && input) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, preventAll));
        dz.addEventListener('dragover', () => setRing(dz, true));
        dz.addEventListener('dragleave', () => setRing(dz, false));
        dz.addEventListener('drop', (e) => {
            setRing(dz, false);
            const files = e.dataTransfer?.files;
            if (files?.length) addFiles(files);
        });
        dz.addEventListener('click', () => input.click());

        input.addEventListener('change', () => {
            if (input.files?.length) addFiles(input.files);
            input.value = ''; // aynı dosyayı tekrar seçebil
        });
    }

    uploadList?.addEventListener('input', (e) => {
        const row = e.target.closest('[data-i]');
        if (!row) return;
        const i = Number(row.dataset.i);
        if (!queue[i]) return;

        const act = e.target.getAttribute('data-act');
        if (act === 'title') queue[i].title = e.target.value;
        if (act === 'alt') queue[i].alt = e.target.value;
    });

    uploadList?.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-act="remove"]');
        if (!btn) return;
        const row = btn.closest('[data-i]');
        const i = Number(row?.dataset.i);
        if (!Number.isFinite(i)) return;
        queue.splice(i, 1);
        renderQueue();
    });

    applyTitleAllBtn?.addEventListener('click', applyTitleAll);
    applyAltAllBtn?.addEventListener('click', applyAltAll);

    clearBtn?.addEventListener('click', () => {
        if (uploading) return;
        queue = [];
        setError('');
        renderQueue();
    });

    startBtn?.addEventListener('click', async () => {
        if (uploading) return;
        if (!queue.length) return;

        uploading = true;
        startBtn.disabled = true;
        if (clearBtn) clearBtn.disabled = true;
        setError('');

        for (const q of queue) {
            if (q.status === 'done') continue;
            await uploadOne(q);
        }

        uploading = false;
        startBtn.disabled = false;
        if (clearBtn) clearBtn.disabled = false;

        // upload bitti -> library açıldıysa yenilemek isteyen dinleyebilir
        modal.dispatchEvent(new CustomEvent('media:upload:done', { bubbles: true }));
    });

    renderQueue();

    // -----------------------------
    // LIBRARY PANE (checkAll + select + pagination)
    // -----------------------------
    const libPane = modal.querySelector('#mediaLibraryPane');
    const libResults = modal.querySelector('#mediaLibraryResults');
    const libPagination = modal.querySelector('#mediaLibraryPagination');

    const libSearch = modal.querySelector('#mediaLibrarySearch');
    const libType = modal.querySelector('#mediaLibraryType');
    const libRefresh = modal.querySelector('#mediaRefreshLibrary');

    const libBulkBar = modal.querySelector('#mediaLibraryBulkBar');
    const libSelectedCount = modal.querySelector('#mediaLibrarySelectedCount');
    const libCheckAll = modal.querySelector('#mediaLibraryCheckAll');

    const picker = {
        q: '',
        type: '',
        page: 1,
        perpage: 24,
        last_page: 1,
        total: 0,
    };

    const selected = new Set(); // number ids

    function updateLibraryBulkUI() {
        const n = selected.size;

        // Bulk bar hep açık kalsın
        if (libBulkBar) libBulkBar.classList.remove('hidden');

        if (libSelectedCount) libSelectedCount.textContent = String(n);

        // Bulk bar içindeki aksiyonları selection yokken disable et
        const useBtn = modal.querySelector('#mediaLibraryUseSelectedBtn');
        const bulkDeleteBtn = modal.querySelector('#mediaLibraryBulkDeleteBtn');
        if (useBtn) useBtn.disabled = (n === 0);
        if (bulkDeleteBtn) bulkDeleteBtn.disabled = (n === 0);

        if (!libResults || !libCheckAll) return;

        const boxes = [...libResults.querySelectorAll('input[data-media-check="1"]')];
        const total = boxes.length;
        const checked = boxes.filter(b => b.checked).length;

        // Liste boşsa: checkAll kapalı + disabled
        if (total === 0) {
            libCheckAll.indeterminate = false;
            libCheckAll.checked = false;
            libCheckAll.disabled = true;
            return;
        }

        // Liste doluysa: aktif
        libCheckAll.disabled = false;
        libCheckAll.indeterminate = checked > 0 && checked < total;
        libCheckAll.checked = checked === total;
    }


    function applySelectionToLibrary() {
        if (!libResults) return;
        libResults.querySelectorAll('input[data-media-check="1"]').forEach(cb => {
            const id = Number(cb.getAttribute('data-id') || 0);
            cb.checked = selected.has(id);
        });
        updateLibraryBulkUI();
    }

    function mediaCard(m) {
        const kind = inferKind(m.mime_type, m.original_name || m.url);
        const thumb = m.thumb_url || m.url || '';
        const title = esc(m.original_name || m.title || 'Medya');

        const checked = selected.has(Number(m.id)) ? 'checked' : '';

        return `
            <label class="rounded-xl border border-border bg-background px-4 py-3 flex items-center gap-3 cursor-pointer">
                <input type="checkbox"
                       class="kt-checkbox kt-checkbox-sm"
                       data-media-check="1"
                       data-id="${Number(m.id)}"
                       ${checked}>

                <div class="size-10 rounded-lg border border-border bg-muted/20 overflow-hidden flex items-center justify-center">
                    ${
            kind === 'image' && thumb
                ? `<img src="${thumb}" alt="" class="w-full h-full object-cover">`
                : `<i class="ki-outline ${kind === 'video' ? 'ki-video' : kind === 'pdf' ? 'ki-file' : 'ki-document'}"></i>`
        }
                </div>

                <div class="grid">
                    <div class="font-medium">${title}</div>
                    <div class="text-xs text-muted-foreground">#${Number(m.id)}</div>
                </div>
            </label>
        `;
    }

    function renderLibraryPagination(meta) {
        if (!libPagination) return;

        const current = Number(meta.current_page || 1);
        const last = Number(meta.last_page || 1);

        if (last <= 1) {
            libPagination.innerHTML = '';
            return;
        }

        const btn = (p, label, disabled = false, active = false) => `
            <button type="button"
                    class="kt-btn kt-btn-sm ${active ? 'kt-btn-primary' : 'kt-btn-light'}"
                    data-page="${p}"
                    ${disabled ? 'disabled' : ''}>
                ${label}
            </button>
        `;

        const parts = [];
        parts.push(btn(current - 1, '‹', current <= 1));

        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);

        if (start > 1) parts.push(btn(1, '1', false, current === 1));
        if (start > 2) parts.push(`<span class="px-2 text-muted-foreground">…</span>`);

        for (let p = start; p <= end; p++) parts.push(btn(p, String(p), false, p === current));

        if (end < last - 1) parts.push(`<span class="px-2 text-muted-foreground">…</span>`);
        if (end < last) parts.push(btn(last, String(last), false, current === last));

        parts.push(btn(current + 1, '›', current >= last));

        libPagination.innerHTML = `<div class="flex items-center gap-1 justify-center">${parts.join('')}</div>`;
    }

    async function fetchLibrary() {
        if (!libResults) return;

        const qs = new URLSearchParams({
            page: String(picker.page),
            perpage: String(picker.perpage),
            q: picker.q || '',
            type: picker.type || '',
            mode: 'active',
        });

        const { res, j } = await jsonReq(`/admin/media/list?${qs.toString()}`, 'GET');

        if (!res.ok || !j?.ok) {
            libResults.innerHTML = `<div class="text-sm text-muted-foreground">Liste alınamadı.</div>`;
            renderLibraryPagination({ current_page: 1, last_page: 1 });
            updateLibraryBulkUI();
            return;
        }

        const items = Array.isArray(j.data) ? j.data : [];
        const meta = j.meta || {};

        picker.last_page = Number(meta.last_page || 1) || 1;
        picker.total = Number(meta.total || 0) || 0;

        libResults.innerHTML = items.length
            ? items.map(mediaCard).join('')
            : `<div class="text-sm text-muted-foreground">Kayıt yok.</div>`;

        renderLibraryPagination(meta);
        applySelectionToLibrary();
    }

    // checkbox toggle
    libResults?.addEventListener('change', (e) => {
        const cb = e.target.closest('input[data-media-check="1"]');
        if (!cb) return;

        const id = Number(cb.getAttribute('data-id') || 0);
        if (!id) return;

        if (cb.checked) selected.add(id);
        else selected.delete(id);

        updateLibraryBulkUI();
    });

    // check all
    libCheckAll?.addEventListener('change', () => {
        if (!libResults) return;

        const want = !!libCheckAll.checked;
        libResults.querySelectorAll('input[data-media-check="1"]').forEach(cb => {
            const id = Number(cb.getAttribute('data-id') || 0);
            if (!id) return;

            cb.checked = want;
            if (want) selected.add(id);
            else selected.delete(id);
        });

        updateLibraryBulkUI();
    });

    // pagination click
    libPagination?.addEventListener('click', (e) => {
        const b = e.target.closest('button[data-page]');
        if (!b) return;
        const p = Number(b.getAttribute('data-page') || 1);
        if (!Number.isFinite(p) || p < 1) return;
        picker.page = p;
        fetchLibrary();
    });

    // search/type/refresh
    let debounceTimer = null;

    libSearch?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            picker.q = (libSearch.value || '').trim();
            picker.page = 1;
            fetchLibrary();
        }, 250);
    });

    libType?.addEventListener('change', () => {
        picker.type = libType.value || '';
        picker.page = 1;
        fetchLibrary();
    });

    libRefresh?.addEventListener('click', () => {
        fetchLibrary();
    });

    // library tab opened (app.js emits this) :contentReference[oaicite:4]{index=4}
    modal.addEventListener('media:library:open', () => {
        picker.page = 1;
        fetchLibrary();
    });

    // upload done -> if library is visible, refresh
    modal.addEventListener('media:upload:done', () => {
        const isLibVisible = libPane && !libPane.classList.contains('hidden');
        if (isLibVisible) fetchLibrary();
    });

    // “Use selected” button (if exists, we emit event; consumer handles attach)
    const useBtn = modal.querySelector('#mediaLibraryUseSelectedBtn');
    useBtn?.addEventListener('click', () => {
        const ids = [...selected.values()];
        if (!ids.length) return;

        modal.dispatchEvent(new CustomEvent('media:library:useSelected', {
            bubbles: true,
            detail: { ids },
        }));
    });

    // init bulk ui state
    updateLibraryBulkUI();
}
