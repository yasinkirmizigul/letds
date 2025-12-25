// resources/js/pages/media/index.js
export default function init() {
    const root = document.querySelector('[data-page="media.index"]');
    if (!root) return;

    // -------------------------
    // Elements
    // -------------------------
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const tabButtons = [...root.querySelectorAll('[data-media-tab]')];
    const uploadPane = root.querySelector('#mediaUploadPane');
    const libraryPane = root.querySelector('#mediaLibraryPane');

    const dropzone = root.querySelector('#mediaDropzone');
    const filesInput = root.querySelector('#mediaFiles');
    const startBtn = root.querySelector('#mediaStartUpload');
    const clearBtn = root.querySelector('#mediaClearQueue');
    const globalErr = root.querySelector('#mediaGlobalError');

    const uploadList = root.querySelector('#mediaUploadList');
    const recentList = root.querySelector('#mediaRecentList');

    const grid = root.querySelector('#mediaGrid');
    const empty = root.querySelector('#mediaEmpty');
    const info = root.querySelector('#mediaInfo');
    const pagination = root.querySelector('#mediaPagination');

    const searchInput = root.querySelector('#mediaSearch');
    const typeSelect = root.querySelector('#mediaType');
    const perPageInput = root.querySelector('#mediaPerPage');

    // Bulk UI
    const bulkBar = root.querySelector('#mediaBulkBar');
    const selectedCountEl = root.querySelector('#mediaSelectedCount');
    const checkAll = root.querySelector('#mediaCheckAll');
    const bulkDeleteBtn = root.querySelector('#mediaBulkDeleteBtn');

    // Modal library (optional)
    const libSearch = root.querySelector('#mediaLibSearch');
    const libType = root.querySelector('#mediaLibType');
    const libResults = root.querySelector('#mediaLibResults');
    const refreshLibraryBtn = root.querySelector('#mediaLibRefresh');

    // -------------------------
    // State
    // -------------------------
    let busy = false;
    let debounceTimer = null;

    let queue = []; // {qid,file,previewUrl,status,error,kind}
    let recent = []; // {id,url,thumb_url,kind,original_name,mime_type,size,name}

    const selectedIds = new Set(); // bulk selection

    const state = {
        q: '',
        type: '',
        page: 1,
        perpage: Number(perPageInput?.value || 24) || 24,
        last_page: 1,
        total: 0,
    };

    // -------------------------
    // Helpers
    // -------------------------
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

    function setBulkUI() {
        if (!bulkBar) return;

        const n = selectedIds.size;
        bulkBar.classList.toggle('hidden', n === 0);

        if (selectedCountEl) selectedCountEl.textContent = String(n);
        if (bulkDeleteBtn) bulkDeleteBtn.disabled = (n === 0); // ✅ (senin bugın)

        const boxes = [...grid.querySelectorAll('input[data-media-check="1"]')];
        const checked = boxes.filter(b => b.checked).length;

        if (checkAll) {
            checkAll.indeterminate = checked > 0 && checked < boxes.length;
            checkAll.checked = boxes.length > 0 && checked === boxes.length;
        }
    }

    function applySelectionToGrid() {
        grid.querySelectorAll('input[data-media-check="1"]').forEach(cb => {
            const id = String(cb.getAttribute('data-id') || '');
            cb.checked = selectedIds.has(id);
        });
        setBulkUI();
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
        if (!globalErr) return;
        globalErr.textContent = msg || '';
        globalErr.classList.toggle('hidden', !msg);
    }

    function switchTab(tab) {
        const isUpload = tab === 'upload';

        // Pane toggle
        uploadPane?.classList.toggle('hidden', !isUpload);
        libraryPane?.classList.toggle('hidden', isUpload);

        // Button state
        tabButtons.forEach(btn => {
            const t = btn.getAttribute('data-media-tab');
            const active = t === tab;

            btn.setAttribute('aria-selected', active ? 'true' : 'false');

            // aktif görsel: istersen kt-btn-primary, değilse kt-btn-light
            btn.classList.toggle('kt-btn-primary', active);
            btn.classList.toggle('kt-btn-light', !active);
        });
    }

    // -------------------------
    // Lightbox (CSS-class based: styles.css -> .media-lb*)
    // -------------------------
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
        lbBody?.querySelectorAll('video,audio').forEach(m => {
            try { m.pause(); } catch (_) {}
            try { m.currentTime = 0; } catch (_) {}
        });
        lbBody.innerHTML = '';
    }

    function renderLightbox() {
        const it = lbItems[lbIndex];
        if (!it) return;

        stopAllMedia();

        lbTitle.textContent = it.title || it.name || 'Önizleme';
        lbSub.textContent = it.sub || '';
        const url = it.url || '#';

        lbOpenLink.href = url;

        const kind = it.kind || inferKindFromMimeOrExt(it.mime, it.name || url);

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

    lbBackdrop.addEventListener('click', closeLightbox);
    lbClose.addEventListener('click', closeLightbox);
    lbPrev.addEventListener('click', lbPrevFn);
    lbNext.addEventListener('click', lbNextFn);

    window.addEventListener('keydown', (e) => {
        if (!lbOpen) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') lbPrevFn();
        if (e.key === 'ArrowRight') lbNextFn();
    });

    // -------------------------
    // Rendering (RECENT + GRID)
    // -------------------------
    function renderRecent() {
        if (!recentList) return;

        if (!recent.length) {
            recentList.innerHTML = `<div class="text-xs text-muted-foreground">Henüz yükleme yok.</div>`;
            return;
        }

        recentList.innerHTML = recent.slice(0, 8).map(m => {
            const kind = m.kind || inferKindFromMimeOrExt(m.mime_type, m.original_name || m.url);
            const name = m.original_name || m.name || 'Medya';
            const url = m.url || '#';

            return `
              <button class="kt-card p-2 flex items-center gap-3 w-full text-left hover:bg-muted/30"
                      type="button"
                      data-action="recent-open"
                      data-url="${esc(url)}"
                      data-kind="${esc(kind)}"
                      data-name="${esc(name)}"
                      data-mime="${esc(m.mime_type || '')}"
                      data-size="${esc(m.size || 0)}">
                <div class="w-12 h-12 rounded overflow-hidden bg-muted shrink-0">
                  ${kind === 'image'
                ? `<img class="w-full h-full" style="object-fit:cover" src="${esc(m.thumb_url || m.url)}" alt="">`
                : `<div class="w-full h-full grid place-items-center text-muted-foreground"><i class="ki-outline ki-document"></i></div>`}
                </div>
                <div class="min-w-0">
                  <div class="text-sm font-medium truncate">${esc(name)}</div>
                  <div class="text-xs text-muted-foreground">${esc(m.mime_type || '')}</div>
                </div>
              </button>
            `;
        }).join('');
    }

    // ✅ Kart: checkbox inline (top-2/left-2 yok), thumbnail daha büyük, Gör/Sil geri.
    function mediaCard(m) {
        const url = m.thumb_url || m.url;
        const kind = m.is_image ? 'image' : inferKindFromMimeOrExt(m.mime_type, m.original_name || m.url);

        const thumb = (kind === 'image')
            ? `<img src="${esc(url)}" class="w-full rounded-xl ring-1 ring-border" style="height:220px;object-fit:cover" alt="">`
            : (kind === 'video')
                ? `<div class="w-full rounded-xl bg-muted ring-1 ring-border flex items-center justify-center" style="height:220px">
                       <i class="ki-outline ki-video text-3xl text-muted-foreground"></i>
                   </div>`
                : (kind === 'pdf')
                    ? `<div class="w-full rounded-xl bg-muted ring-1 ring-border flex items-center justify-center" style="height:220px">
                           <i class="ki-outline ki-file-sheet text-3xl text-muted-foreground"></i>
                       </div>`
                    : `<div class="w-full rounded-xl bg-muted ring-1 ring-border flex items-center justify-center" style="height:220px">
                           <i class="ki-outline ki-file text-3xl text-muted-foreground"></i>
                       </div>`;

        return `
          <div class="kt-card relative">
            <div class="absolute z-10" style="top:8px;left:8px;">
                <label class="inline-flex items-center gap-2 bg-background/80 backdrop-blur px-2 py-1 rounded-lg ring-1 ring-border">
                    <input type="checkbox" class="kt-checkbox kt-checkbox-sm" data-media-check="1" data-id="${esc(m.id)}">
                    <span class="kt-checkbox-label"></span>
                </label>
            </div>

            <div class="kt-card-content p-3">
              <button type="button"
                class="w-full text-left"
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
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" data-action="delete" data-id="${esc(m.id)}" title="Sil">
                      <i class="ki-outline ki-trash"></i>
                    </button>

                    <a href="${esc(m.url)}" target="_blank" rel="noreferrer" class="kt-btn kt-btn-sm kt-btn-outline" title="Link">
                      <i class="ki-outline ki-fasten"></i>
                    </a>
                </div>
              </div>
            </div>
          </div>
        `;
    }

    async function fetchList() {
        const qs = new URLSearchParams({
            page: String(state.page),
            perpage: String(state.perpage),
            q: state.q || '',
            type: state.type || '',
        });

        const res = await fetch(`/admin/media/list?${qs.toString()}`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!res.ok) {
            setGlobalError('Liste alınamadı.');
            return;
        }

        const j = await res.json().catch(() => ({}));
        if (!j?.ok) {
            setGlobalError(j?.error?.message || 'Liste alınamadı.');
            return;
        }

        const items = Array.isArray(j.data) ? j.data : [];
        const meta = j.meta || {};

        state.last_page = Number(meta.last_page || 1) || 1;
        state.total = Number(meta.total || 0) || 0;

        grid.innerHTML = items.map(mediaCard).join('');
        empty.classList.toggle('hidden', items.length > 0);

        const from = items.length ? ((meta.current_page - 1) * meta.per_page + 1) : 0;
        const to = items.length ? (from + items.length - 1) : 0;

        info.textContent = `${from}-${to} / ${meta.total ?? items.length}`;
        renderPagination(meta);

        applySelectionToGrid();
    }

    function renderPagination(meta) {
        const current = Number(meta.current_page || 1);
        const last = Number(meta.last_page || 1);

        if (last <= 1) {
            pagination.innerHTML = '';
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
        parts.push(btn(current - 1, '‹', current <= 1, false));

        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);

        if (start > 1) parts.push(btn(1, '1', false, current === 1));
        if (start > 2) parts.push(`<span class="px-2 text-muted-foreground">…</span>`);

        for (let p = start; p <= end; p++) parts.push(btn(p, String(p), false, p === current));

        if (end < last - 1) parts.push(`<span class="px-2 text-muted-foreground">…</span>`);
        if (end < last) parts.push(btn(last, String(last), false, current === last));

        parts.push(btn(current + 1, '›', current >= last, false));

        pagination.innerHTML = `<div class="flex items-center justify-center gap-2">${parts.join('')}</div>`;
    }

    // -------------------------
    // Upload queue (senin mevcut mantık: kısa bıraktım ama kaldırmadım)
    // -------------------------
    function addFiles(fileList) {
        const files = [...(fileList || [])];
        if (!files.length) return;

        for (const file of files) {
            const qid = crypto.randomUUID?.() || String(Date.now()) + Math.random();
            const previewUrl = URL.createObjectURL(file);

            queue.push({
                qid,
                file,
                previewUrl,
                status: 'pending',
                error: '',
                kind: inferKindFromMimeOrExt(file.type, file.name),
            });
        }
        renderQueue();
    }

    function removeFromQueue(qid) {
        const idx = queue.findIndex(x => x.qid === qid);
        if (idx < 0) return;

        const it = queue[idx];
        if (it.previewUrl) {
            try { URL.revokeObjectURL(it.previewUrl); } catch (_) {}
        }
        queue.splice(idx, 1);
        renderQueue();
    }

    function renderQueue() {
        if (!uploadList) return;

        if (!queue.length) {
            uploadList.innerHTML = `<div class="text-sm text-muted-foreground">Kuyruk boş.</div>`;
            return;
        }

        uploadList.innerHTML = queue.map(it => {
            const name = it.file?.name || 'Dosya';
            const mime = it.file?.type || 'unknown';
            const size = formatBytes(it.file?.size || 0);

            const badge = it.status === 'success'
                ? `<span class="kt-badge kt-badge-sm kt-badge-success">Yüklendi</span>`
                : it.status === 'uploading'
                    ? `<span class="kt-badge kt-badge-sm kt-badge-primary">Yükleniyor</span>`
                    : it.status === 'error'
                        ? `<span class="kt-badge kt-badge-sm kt-badge-danger">Hata</span>`
                        : `<span class="kt-badge kt-badge-sm kt-badge-light">Bekliyor</span>`;

            return `
              <div class="kt-card p-3 flex items-center gap-3" data-qid="${esc(it.qid)}">
                <div class="w-12 h-12 rounded overflow-hidden bg-muted shrink-0">
                  ${it.kind === 'image'
                ? `<img class="w-full h-full" style="object-fit:cover" src="${esc(it.previewUrl)}" alt="">`
                : `<div class="w-full h-full grid place-items-center text-muted-foreground"><i class="ki-outline ki-document"></i></div>`}
                </div>

                <div class="min-w-0 flex-1">
                  <div class="flex items-center justify-between gap-2">
                    <div class="text-sm font-medium truncate">${esc(name)}</div>
                    ${badge}
                  </div>
                  <div class="text-xs text-muted-foreground truncate">${esc(mime)} • ${esc(size)}</div>
                  ${it.error ? `<div class="text-xs text-danger mt-1">${esc(it.error)}</div>` : ''}
                </div>

                <div class="flex items-center gap-2">
                  <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-action="preview">
                    <i class="ki-outline ki-eye"></i>
                  </button>
                  <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-action="remove">
                    <i class="ki-outline ki-cross"></i>
                  </button>
                  <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-action="retry">
                    <i class="ki-outline ki-arrows-circle"></i>
                  </button>
                </div>
              </div>
            `;
        }).join('');
    }

    async function uploadOne(it) {
        it.status = 'uploading';
        it.error = '';
        renderQueue();

        const fd = new FormData();
        fd.append('file', it.file);

        const res = await fetch('/admin/media/upload', {
            method: 'POST',
            headers: { ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}), 'Accept': 'application/json' },
            body: fd
        });

        const j = await res.json().catch(() => ({}));

        if (!res.ok || !j?.ok) {
            it.status = 'error';
            it.error = j?.error?.message || j?.message || 'Upload başarısız';
            renderQueue();
            return null;
        }

        it.status = 'success';
        renderQueue();

        const payload = j.data;
        const inserted = Array.isArray(payload) ? payload : [payload];

        recent = [...inserted, ...recent].slice(0, 30);
        renderRecent();

        return inserted;
    }

    async function uploadAll() {
        if (busy) return;
        busy = true;
        setGlobalError('');

        try {
            for (const it of queue) {
                if (it.status === 'success') continue;
                await uploadOne(it);
            }

            state.page = 1;
            await fetchList();
        } finally {
            busy = false;
        }
    }

    // -------------------------
    // Events
    // -------------------------
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const t = btn.getAttribute('data-media-tab');
            if (!t) return;
            switchTab(t);
        });
    });

    dropzone?.addEventListener('click', () => filesInput.click());

    dropzone?.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('ring-2', 'ring-primary');
    });

    dropzone?.addEventListener('dragleave', () => {
        dropzone.classList.remove('ring-2', 'ring-primary');
    });

    dropzone?.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('ring-2', 'ring-primary');
        addFiles(e.dataTransfer?.files);
    });

    filesInput?.addEventListener('change', (e) => {
        addFiles(e.target.files);
        filesInput.value = '';
    });

    uploadList?.addEventListener('click', async (e) => {
        const card = e.target.closest('[data-qid]');
        if (!card) return;

        const qid = card.getAttribute('data-qid');
        const action = e.target.closest('[data-action]')?.getAttribute('data-action');
        if (!qid || !action) return;

        const it = queue.find(x => x.qid === qid);
        if (!it) return;

        if (action === 'preview') {
            const items = queue.map(q => ({
                url: q.previewUrl || '',
                kind: q.kind || inferKindFromMimeOrExt(q.file?.type, q.file?.name),
                title: q.file?.name || 'Dosya',
                sub: `${q.file?.type || 'unknown'} • ${formatBytes(q.file?.size || 0)}`,
                mime: q.file?.type || '',
                name: q.file?.name || '',
            }));
            const idx = Math.max(0, queue.findIndex(x => x.qid === qid));
            openLightbox(items, idx);
            return;
        }

        if (action === 'remove') {
            if (busy && it.status === 'uploading') return;
            removeFromQueue(qid);
            return;
        }

        if (action === 'retry') {
            if (busy) return;
            if (it.status === 'success') return;
            await uploadOne(it);
            state.page = 1;
            await fetchList();
            return;
        }
    });

    recentList?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="recent-open"]');
        if (!btn) return;

        const items = [...recentList.querySelectorAll('[data-action="recent-open"]')].map(b => ({
            url: b.getAttribute('data-url') || '',
            kind: b.getAttribute('data-kind') || 'other',
            title: b.getAttribute('data-name') || 'Medya',
            sub: `${b.getAttribute('data-mime') || ''} • ${formatBytes(Number(b.getAttribute('data-size') || 0))}`,
            mime: b.getAttribute('data-mime') || '',
            name: b.getAttribute('data-name') || '',
        }));

        const idx = items.findIndex(x => x.url === (btn.getAttribute('data-url') || ''));
        openLightbox(items, Math.max(0, idx));
    });

    // Grid checkbox selection
    grid?.addEventListener('change', (e) => {
        const cb = e.target.closest('input[data-media-check="1"]');
        if (!cb) return;

        const id = String(cb.getAttribute('data-id') || '');
        if (!id) return;

        if (cb.checked) selectedIds.add(id);
        else selectedIds.delete(id);

        setBulkUI();
    });

    // ✅ ekstra garanti: bazı temalarda change gecikiyor → click ile de yakala
    grid?.addEventListener('click', (e) => {
        const cb = e.target.closest('input[data-media-check="1"]');
        if (!cb) return;
        queueMicrotask(() => {
            const id = String(cb.getAttribute('data-id') || '');
            if (!id) return;
            if (cb.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            setBulkUI();
        });
    });

    // Grid open/delete (delegation)
    grid?.addEventListener('click', async (e) => {
        if (e.target.closest('input[data-media-check="1"]')) return;

        const delBtn = e.target.closest('[data-action="delete"]');
        if (delBtn) {
            const id = delBtn.getAttribute('data-id');
            if (!id) return;

            if (!confirm('Bu medyayı silmek istiyor musun?')) return;

            const res = await fetch(`/admin/media/${id}`, {
                method: 'DELETE',
                headers: {
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) {
                const j = await res.json().catch(() => ({}));
                alert(j?.error?.message || j?.message || 'Silme başarısız');
                return;
            }

            selectedIds.delete(String(id));
            setBulkUI();

            state.page = 1;
            await fetchList();
            recent = recent.filter(x => String(x.id) !== String(id));
            renderRecent();
            return;
        }

        const btn = e.target.closest('[data-action="open"]');
        if (!btn) return;

        const all = [...grid.querySelectorAll('[data-action="open"]')];

        const keyToIndex = new Map();
        const items = [];
        const btnToIndex = new Map();

        for (const b of all) {
            const url = b.getAttribute('data-url') || '';
            if (!url) continue;

            const key = b.getAttribute('data-id') || url;

            let uidx = keyToIndex.get(key);
            if (uidx === undefined) {
                uidx = items.length;
                keyToIndex.set(key, uidx);
                items.push({
                    url,
                    kind: b.getAttribute('data-kind') || inferKindFromMimeOrExt(b.getAttribute('data-mime'), b.getAttribute('data-name') || url),
                    title: b.getAttribute('data-name') || 'Medya',
                    sub: `${b.getAttribute('data-mime') || ''} • ${formatBytes(Number(b.getAttribute('data-size') || 0))}`,
                    mime: b.getAttribute('data-mime') || '',
                    name: b.getAttribute('data-name') || '',
                });
            }

            btnToIndex.set(b, uidx);
        }

        const idx = btnToIndex.get(btn) ?? 0;
        openLightbox(items, idx);
    });

    startBtn?.addEventListener('click', uploadAll);

    clearBtn?.addEventListener('click', () => {
        if (busy) return;
        queue.forEach(it => {
            if (it.previewUrl) {
                try { URL.revokeObjectURL(it.previewUrl); } catch (_) { }
            }
        });
        queue = [];
        renderQueue();
    });

    refreshLibraryBtn?.addEventListener('click', async () => {
        state.page = 1;
        await fetchList();
    });

    searchInput?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            state.q = searchInput.value.trim();
            state.page = 1;
            await fetchList();
        }, 250);
    });

    typeSelect?.addEventListener('change', async () => {
        state.type = typeSelect.value || '';
        state.page = 1;
        await fetchList();
    });

    pagination?.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-page]');
        if (!btn) return;
        const p = Number(btn.getAttribute('data-page') || 1);
        if (!p || p === state.page) return;
        state.page = p;
        await fetchList();
    });

    checkAll?.addEventListener('change', () => {
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
    });

    bulkDeleteBtn?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        if (!confirm(`${ids.length} medya silinsin mi?`)) return;

        const res = await fetch('/admin/media/bulk', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ ids })
        });

        if (!res.ok) {
            const j = await res.json().catch(() => ({}));
            alert(j?.error?.message || j?.message || 'Toplu silme başarısız');
            return;
        }

        selectedIds.clear();
        setBulkUI();
        state.page = 1;
        await fetchList();
        recent = recent.filter(x => !ids.includes(String(x.id)));
        renderRecent();
    });

    // First load
    switchTab('upload');
    renderRecent();
    renderQueue();
    setBulkUI();
    fetchList();
}
