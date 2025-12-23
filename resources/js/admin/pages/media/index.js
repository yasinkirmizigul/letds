export default function init() {
    const root = document.querySelector('[data-page="media.index"]');
    if (!root) return;

    // -------------------------
    // Page list DOM
    // -------------------------
    const grid = root.querySelector('#mediaGrid');
    const empty = root.querySelector('#mediaEmpty');
    const info = root.querySelector('#mediaInfo');
    const pagination = root.querySelector('#mediaPagination');
    const searchInput = root.querySelector('#mediaSearch');
    const typeSelect = root.querySelector('#mediaType');

    // Bulk select (library)
    const bulkBar = root.querySelector('#mediaBulkBar');
    const checkAll = root.querySelector('#mediaCheckAll');
    const selectedCountEl = root.querySelector('#mediaSelectedCount');
    const bulkDeleteBtn = root.querySelector('#mediaBulkDeleteBtn');

    // -------------------------
    // Modal DOM
    // -------------------------
    const modal = root.querySelector('#mediaUploadModal');
    const tabUploadBtn = modal?.querySelector('[data-media-tab="upload"]');
    const tabLibraryBtn = modal?.querySelector('[data-media-tab="library"]');
    const uploadPane = modal?.querySelector('#mediaUploadPane');
    const libraryPane = modal?.querySelector('#mediaLibraryPane');

    const dropzone = modal?.querySelector('#mediaDropzone');
    const filesInput = modal?.querySelector('#mediaFiles');
    const titleInput = modal?.querySelector('#mediaTitle');
    const altInput = modal?.querySelector('#mediaAlt');

    // optional error box
    const globalErr = modal?.querySelector('#mediaUploadError');

    const startBtn = modal?.querySelector('#mediaStartUpload');
    const clearBtn = modal?.querySelector('#mediaClearQueue');

    const queueInfo = modal?.querySelector('#mediaQueueInfo');
    const uploadList = modal?.querySelector('#mediaUploadList');

    const recentList = modal?.querySelector('#mediaRecentList');
    const refreshLibraryBtn = modal?.querySelector('#mediaRefreshLibrary');

    // Optional: modal library search/filter/results (v2)
    const libSearch = modal?.querySelector('#mediaLibrarySearch');
    const libType = modal?.querySelector('#mediaLibraryType');
    const libResults = modal?.querySelector('#mediaLibraryResults');

    const required = {
        grid, empty, info, pagination, searchInput, typeSelect,
        modal, tabUploadBtn, tabLibraryBtn, uploadPane, libraryPane,
        dropzone, filesInput, titleInput, altInput,
        startBtn, clearBtn, queueInfo, uploadList,
        recentList, refreshLibraryBtn
    };

    const missing = Object.entries(required).filter(([, v]) => !v).map(([k]) => k);
    if (missing.length) {
        console.error('[media.index] Missing DOM elements:', missing);
        return;
    }

    // -------------------------
    // Config (v2)
    // -------------------------
    const csrf =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
        window?.Laravel?.csrfToken ||
        '';

    const endpoints = {
        list: '/admin/media/list',
        upload: '/admin/media/upload',
        chunkInit: '/admin/media/upload/init',
        chunk: '/admin/media/upload/chunk',
        chunkFinalize: '/admin/media/upload/finalize',
    };

    const CONCURRENCY = 3; // 2-3 paralel
    const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB
    const CHUNK_THRESHOLD = 20 * 1024 * 1024; // 20MB üstü chunk
    const MAX_RETRY = 3;

    // -------------------------
    // State
    // -------------------------
    const state = { page: 1, perpage: 24, q: '', type: '' };
    const selectedIds = new Set(); // ids selected in grid (string)
    let debounceTimer = null;

    let queue = [];    // {qid,file,previewUrl,status,progress,errMsg,serverMedia}
    let busy = false;

    let recent = [];   // server media payloads
    try {
        const x = JSON.parse(localStorage.getItem('media_recent') || '[]');
        if (Array.isArray(x)) recent = x;
    } catch (_) { }

    // -------------------------
    // Helpers
    // -------------------------
    function esc(s) {
        return String(s ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatBytes(bytes) {
        const b = Number(bytes || 0);
        if (!b) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(b) / Math.log(k));
        const v = (b / Math.pow(k, i)).toFixed(i === 0 ? 0 : 1);
        return `${v} ${sizes[i]}`;
    }

    function setBulkUI() {
        if (!bulkBar) return;
        const n = selectedIds.size;
        bulkBar.classList.toggle('hidden', n === 0);
        if (selectedCountEl) selectedCountEl.textContent = String(n);
        if (bulkDeleteBtn) bulkDeleteBtn.disabled = n === 0;

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
        tabUploadBtn.classList.toggle('kt-tab-active', isUpload);
        tabLibraryBtn.classList.toggle('kt-tab-active', !isUpload);

        uploadPane.classList.toggle('hidden', !isUpload);
        libraryPane.classList.toggle('hidden', isUpload);
    }

    // -------------------------
    // Lightbox (image + video + pdf iframe + swipe)
    // -------------------------
    let lbOpen = false;
    let lbItems = [];
    let lbIndex = 0;

    const lb = document.createElement('div');
    lb.id = 'mediaLightbox';
    lb.className = 'fixed inset-0 z-[9999] hidden';

    lb.innerHTML = `
      <div class="absolute inset-0 bg-black/70" data-lb-backdrop></div>

      <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-5xl">
          <button type="button" data-lb-close class="absolute -top-10 right-0 kt-btn kt-btn-sm kt-btn-light">
            <i class="ki-outline ki-cross"></i> Kapat
          </button>

          <div class="bg-background rounded-2xl ring-1 ring-border overflow-hidden shadow-xl">
            <div class="flex items-center justify-between p-3 border-b border-border gap-3">
              <div class="min-w-0">
                <div class="text-sm font-semibold truncate" data-lb-title></div>
                <div class="text-xs text-muted-foreground truncate" data-lb-sub></div>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-lb-prev title="Önceki">
                  <i class="ki-outline ki-left"></i>
                </button>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-lb-next title="Sonraki">
                  <i class="ki-outline ki-right"></i>
                </button>
                <a class="kt-btn kt-btn-sm kt-btn-light" data-lb-open target="_blank" rel="noreferrer" title="Yeni sekmede aç">
                  <i class="ki-outline ki-fasten"></i>
                </a>
              </div>
            </div>

            <div class="bg-black/5" data-lb-stage>
              <img data-lb-img class="hidden w-full max-h-[78vh] object-contain mx-auto block select-none" alt="">
              <video data-lb-video class="hidden w-full max-h-[78vh] mx-auto block" controls playsinline></video>
              <iframe data-lb-pdf class="hidden w-full h-[78vh] bg-white" frameborder="0"></iframe>

              <div data-lb-nonprev class="hidden p-10 text-center">
                <i class="ki-outline ki-file text-6xl text-muted-foreground"></i>
                <div class="mt-3 text-sm text-muted-foreground">Bu dosya önizlenemiyor.</div>
              </div>
            </div>
          </div>

          <div class="mt-3 text-xs text-white/80 text-center">
            <span class="hidden sm:inline">← / →</span>
            <span class="sm:hidden">Sağa/sola kaydır</span>
            <span> • ESC</span>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(lb);

    const lbTitle = lb.querySelector('[data-lb-title]');
    const lbSub = lb.querySelector('[data-lb-sub]');
    const lbImg = lb.querySelector('[data-lb-img]');
    const lbVideo = lb.querySelector('[data-lb-video]');
    const lbPdf = lb.querySelector('[data-lb-pdf]');
    const lbNonPrev = lb.querySelector('[data-lb-nonprev]');
    const lbOpenLink = lb.querySelector('[data-lb-open]');
    const lbStage = lb.querySelector('[data-lb-stage]');

    function stopAllMedia() {
        try { lbVideo.pause(); } catch (_) { }
        lbVideo.removeAttribute('src');
        lbVideo.load?.();
        lbImg.removeAttribute('src');
        lbPdf.removeAttribute('src');
    }

    function renderLightbox() {
        const it = lbItems[lbIndex];
        if (!it) return;

        stopAllMedia();

        lbTitle.textContent = it.title || it.name || 'Önizleme';
        lbSub.textContent = it.sub || '';
        lbOpenLink.href = it.url || '#';

        const kind = it.kind || inferKindFromMimeOrExt(it.mime, it.name || it.url || '');
        const url = it.url;

        lbImg.classList.add('hidden');
        lbVideo.classList.add('hidden');
        lbPdf.classList.add('hidden');
        lbNonPrev.classList.add('hidden');

        if (!url) {
            lbNonPrev.classList.remove('hidden');
            return;
        }

        if (kind === 'image') {
            lbImg.classList.remove('hidden');
            lbImg.src = url;
            return;
        }

        if (kind === 'video') {
            lbVideo.classList.remove('hidden');
            lbVideo.src = url;
            return;
        }

        if (kind === 'pdf') {
            lbPdf.classList.remove('hidden');
            lbPdf.src = url;
            return;
        }

        lbNonPrev.classList.remove('hidden');
    }

    function openLightbox(items, index = 0) {
        lbItems = items || [];
        lbIndex = Math.max(0, Math.min(index, lbItems.length - 1));
        lb.classList.remove('hidden');
        lbOpen = true;
        renderLightbox();
    }

    function closeLightbox() {
        lb.classList.add('hidden');
        lbOpen = false;
        stopAllMedia();
    }

    function lbPrev() {
        if (!lbItems.length) return;
        lbIndex = (lbIndex - 1 + lbItems.length) % lbItems.length;
        renderLightbox();
    }

    function lbNext() {
        if (!lbItems.length) return;
        lbIndex = (lbIndex + 1) % lbItems.length;
        renderLightbox();
    }

    lb.addEventListener('click', (e) => {
        if (e.target.matches('[data-lb-close]')) closeLightbox();
        if (e.target.matches('[data-lb-backdrop]')) closeLightbox();
        if (e.target.closest('[data-lb-prev]')) lbPrev();
        if (e.target.closest('[data-lb-next]')) lbNext();
    });

    document.addEventListener('keydown', (e) => {
        if (!lbOpen) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') lbPrev();
        if (e.key === 'ArrowRight') lbNext();
    });

    // touch swipe
    let pDown = false;
    let pStartX = 0;
    let pStartY = 0;
    let pLastX = 0;
    let pLastY = 0;
    let pStartT = 0;

    function resetPointer() {
        pDown = false;
        pStartX = pStartY = pLastX = pLastY = 0;
        pStartT = 0;
    }

    lbStage.addEventListener('pointerdown', (e) => {
        if (!lbOpen) return;
        pDown = true;
        pStartX = pLastX = e.clientX;
        pStartY = pLastY = e.clientY;
        pStartT = Date.now();
        try { lbStage.setPointerCapture(e.pointerId); } catch (_) { }
    });

    lbStage.addEventListener('pointermove', (e) => {
        if (!lbOpen || !pDown) return;
        pLastX = e.clientX;
        pLastY = e.clientY;
    });

    lbStage.addEventListener('pointerup', () => {
        if (!lbOpen || !pDown) return;

        const dx = pLastX - pStartX;
        const dy = pLastY - pStartY;
        const dt = Date.now() - pStartT;

        resetPointer();

        if (Math.abs(dx) < 50) return;
        if (Math.abs(dx) < Math.abs(dy) * 1.2) return;
        if (dt > 700) return;

        if (dx < 0) lbNext();
        else lbPrev();
    });

    lbStage.addEventListener('pointercancel', resetPointer);

    // -------------------------
    // Queue UI
    // -------------------------
    function updateQueueInfo() {
        const total = queue.length;
        const success = queue.filter(x => x.status === 'success').length;
        const errors = queue.filter(x => x.status === 'error').length;
        const uploading = queue.filter(x => x.status === 'uploading').length;

        queueInfo.innerHTML = `
          <div class="text-xs text-muted-foreground">
            Toplam: <b>${total}</b> • Yüklendi: <b>${success}</b> • Hata: <b>${errors}</b>${uploading ? ` • Yükleniyor: <b>${uploading}</b>` : ''}
          </div>
        `;
    }

    function isLikelyImage(file) {
        const t = String(file?.type || '');
        if (t.startsWith('image/')) return true;
        return ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'].includes(fileExt(file?.name));
    }

    function isLikelyVideo(file) {
        const t = String(file?.type || '');
        if (t.startsWith('video/')) return true;
        return ['mp4', 'webm', 'ogg', 'mov', 'm4v'].includes(fileExt(file?.name));
    }

    function isLikelyPdf(file) {
        const t = String(file?.type || '');
        if (t === 'application/pdf') return true;
        return fileExt(file?.name) === 'pdf';
    }

    function makeRowHtml(item) {
        const kind = item.kind || inferKindFromMimeOrExt(item.file?.type, item.file?.name);
        const canPreview = !!item.previewUrl && (kind === 'image' || kind === 'video' || kind === 'pdf');

        const previewBlock = item.previewUrl
            ? `
              <button type="button" class="shrink-0" data-action="preview" title="Önizle" ${canPreview ? '' : 'disabled'}>
                <img src="${esc(item.previewUrl)}" class="size-10 rounded-md object-cover ring-1 ring-border" alt="">
              </button>
            `
            : `
              <button type="button" class="shrink-0" data-action="preview" title="Önizle" disabled>
                <div class="size-10 rounded-md bg-muted flex items-center justify-center ring-1 ring-border">
                  <i class="ki-outline ki-file text-lg"></i>
                </div>
              </button>
            `;

        const statusBadge = (() => {
            if (item.status === 'success') return `<span class="kt-badge kt-badge-success">Yüklendi</span>`;
            if (item.status === 'uploading') return `<span class="kt-badge kt-badge-primary">Yükleniyor</span>`;
            if (item.status === 'error') return `<span class="kt-badge kt-badge-danger">Hata</span>`;
            return `<span class="kt-badge kt-badge-outline">Bekliyor</span>`;
        })();

        const errBlock = item.status === 'error' && item.errMsg
            ? `<div class="mt-1 text-[11px] text-destructive whitespace-pre-wrap">${esc(item.errMsg)}</div>`
            : ``;

        const retryDisabled = (item.status === 'uploading') ? 'opacity-50 pointer-events-none' : '';
        const removeDisabled = (item.status === 'uploading') ? 'opacity-50 pointer-events-none' : '';

        return `
          <div class="kt-card" data-qid="${esc(item.qid)}">
            <div class="kt-card-content p-3">
              <div class="flex items-start gap-3">
                ${previewBlock}

                <div class="grow">
                  <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                      <div class="text-sm font-medium text-mono truncate" title="${esc(item.file?.name)}">${esc(item.file?.name)}</div>
                      <div class="text-xs text-muted-foreground">${esc(item.file?.type || 'unknown')} • ${formatBytes(item.file?.size || 0)}</div>
                    </div>
                    <div class="shrink-0">
                      ${statusBadge}
                    </div>
                  </div>

                  <div class="mt-2">
                    <div class="kt-progress h-[4px]">
                      <div class="kt-progress-indicator" style="width:${item.progress || 0}%"></div>
                    </div>
                  </div>

                  ${errBlock}
                </div>

                <div class="shrink-0 flex items-center gap-2">
                  <button type="button" class="kt-btn kt-btn-sm kt-btn-light ${retryDisabled}" data-action="retry" title="Yeniden dene">
                    <i class="ki-outline ki-arrows-circle"></i>
                  </button>
                  <button type="button" class="kt-btn kt-btn-sm kt-btn-light ${removeDisabled}" data-action="remove" title="Kuyruktan kaldır">
                    <i class="ki-outline ki-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        `;
    }

    function renderQueue() {
        updateQueueInfo();
        uploadList.innerHTML = queue.map(makeRowHtml).join('');
        const hasAny = queue.length > 0;

        startBtn.disabled = !hasAny || busy;
        clearBtn.disabled = !hasAny || busy;
    }

    function addFiles(fileList) {
        const arr = [...(fileList || [])];
        if (!arr.length) return;

        arr.forEach(file => {
            const qid = crypto.randomUUID ? crypto.randomUUID() : String(Date.now() + Math.random());

            let previewUrl = '';
            let kind = 'other';

            if (isLikelyImage(file)) {
                kind = 'image';
                previewUrl = URL.createObjectURL(file);
            } else if (isLikelyVideo(file)) {
                kind = 'video';
                previewUrl = URL.createObjectURL(file);
            } else if (isLikelyPdf(file)) {
                kind = 'pdf';
                previewUrl = URL.createObjectURL(file);
            } else {
                kind = inferKindFromMimeOrExt(file?.type, file?.name);
                previewUrl = '';
            }

            queue.push({
                qid,
                file,
                kind,
                previewUrl,
                status: 'queued',
                progress: 0,
                errMsg: '',
                serverMedia: null,
            });
        });

        renderQueue();
    }

    function removeFromQueue(qid) {
        const it = queue.find(x => x.qid === qid);
        if (it?.previewUrl) {
            try { URL.revokeObjectURL(it.previewUrl); } catch (_) { }
        }
        queue = queue.filter(x => x.qid !== qid);
        renderQueue();
    }

    function updateItem(qid, patch) {
        const idx = queue.findIndex(x => x.qid === qid);
        if (idx < 0) return;
        queue[idx] = { ...queue[idx], ...patch };

        const card = uploadList.querySelector(`[data-qid="${CSS.escape(qid)}"]`);
        if (card) {
            const prog = card.querySelector('.kt-progress-indicator');
            if (prog && typeof queue[idx].progress === 'number') prog.style.width = `${queue[idx].progress}%`;

            card.outerHTML = makeRowHtml(queue[idx]);
        }
        updateQueueInfo();
    }

    // -------------------------
    // Page list
    // -------------------------
    function mediaCard(m) {
        const url = m.thumb_url || m.url;
        const kind = m.is_image ? 'image' : inferKindFromMimeOrExt(m.mime_type, m.original_name || m.url);

        const thumb = (kind === 'image')
            ? `<img src="${esc(url)}" class="w-full h-44 object-cover rounded-xl ring-1 ring-border" alt="">`
            : (kind === 'video')
                ? `<div class="w-full h-44 rounded-xl bg-muted ring-1 ring-border flex items-center justify-center">
                       <i class="ki-outline ki-video text-3xl text-muted-foreground"></i>
                   </div>`
                : (kind === 'pdf')
                    ? `<div class="w-full h-44 rounded-xl bg-muted ring-1 ring-border flex items-center justify-center">
                           <i class="ki-outline ki-file-sheet text-3xl text-muted-foreground"></i>
                       </div>`
                    : `<div class="w-full h-44 rounded-xl bg-muted ring-1 ring-border flex items-center justify-center">
                           <i class="ki-outline ki-file text-3xl text-muted-foreground"></i>
                       </div>`;

        return `
          <div class="kt-card relative">
            <div class="absolute top-2 left-2 z-10">
                <label class="inline-flex items-center gap-2 bg-background/80 backdrop-blur px-2 py-1 rounded-lg ring-1 ring-border">
                    <input type="checkbox" class="kt-checkbox kt-checkbox-sm" data-media-check="1" data-id="${esc(m.id)}">
                </label>
            </div>

            <div class="kt-card-content p-3">
              <button type="button"
                class="w-full text-left"
                data-action="open"
                data-url="${esc(m.url)}"
                data-thumb="${esc(url)}"
                data-name="${esc(m.original_name)}"
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

                    <a href="${esc(m.url)}" target="_blank" rel="noreferrer" class="kt-btn kt-btn-sm kt-btn-light">
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

        const res = await fetch(`${endpoints.list}?${qs.toString()}`, {
            headers: { 'Accept': 'application/json' }
        });
        const json = await res.json().catch(() => ({}));

        const items = Array.isArray(json?.data) ? json.data : [];
        const meta = json?.meta || {};

        grid.innerHTML = items.map(mediaCard).join('');
        empty.classList.toggle('hidden', items.length > 0);

        const from = items.length ? ((meta.current_page - 1) * meta.per_page + 1) : 0;
        const to = items.length ? (from + items.length - 1) : 0;

        info.textContent = `${from}-${to} / ${meta.total ?? items.length}`;
        renderPagination(meta);

        // restore checkbox state for this page
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

        for (let p = start; p <= end; p++) {
            parts.push(btn(p, String(p), false, p === current));
        }

        parts.push(btn(current + 1, '›', current >= last, false));

        pagination.innerHTML = `<div class="flex items-center gap-2 justify-center">${parts.join('')}</div>`;
    }

    // -------------------------
    // Upload (v2): XHR progress + chunk + concurrency
    // -------------------------
    function xhrUpload({ url, formData, onProgress }) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.responseType = 'json';
            if (csrf) xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = (e) => {
                if (!e.lengthComputable) return;
                onProgress?.(e.loaded, e.total);
            };

            xhr.onload = () => {
                const res = xhr.response;
                if (xhr.status >= 200 && xhr.status < 300) return resolve(res);
                reject({ status: xhr.status, response: res });
            };

            xhr.onerror = () => reject({ status: 0, response: null });
            xhr.send(formData);
        });
    }

    function normalizeUploadError(e) {
        const res = e?.response;
        if (res?.errors) {
            return Object.entries(res.errors)
                .map(([k, arr]) => `${k}: ${Array.isArray(arr) ? arr.join(' | ') : String(arr)}`)
                .join('\n');
        }
        if (res?.error?.message) return res.error.message;
        if (res?.message) return res.message;
        if (e?.status) return `HTTP ${e.status}`;
        return 'Upload failed';
    }

    async function uploadChunked(item) {
        const f = item.file;

        const initRes = await fetch(endpoints.chunkInit, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                original_name: f.name,
                mime: f.type,
                size: f.size,
                last_modified: f.lastModified,
                title: titleInput.value || '',
                alt: altInput.value || '',
            }),
        }).then(r => r.json());

        if (!initRes?.ok) {
            updateItem(item.qid, { status: 'error', progress: 0, errMsg: initRes?.error?.message || 'Init failed' });
            return { ok: false };
        }

        const uploadId = initRes.data.upload_id;
        const chunkSize = initRes.data.chunk_size || CHUNK_SIZE;

        const total = f.size;
        const totalChunks = Math.ceil(total / chunkSize);

        for (let idx = 0; idx < totalChunks; idx++) {
            const start = idx * chunkSize;
            const end = Math.min(total, start + chunkSize);
            const blob = f.slice(start, end);

            let attempt = 0;
            while (true) {
                try {
                    const fd = new FormData();
                    fd.append('upload_id', uploadId);
                    fd.append('index', String(idx));
                    fd.append('total', String(totalChunks));
                    fd.append('chunk', blob, `${f.name}.part${idx}`);

                    await xhrUpload({
                        url: endpoints.chunk,
                        formData: fd,
                        onProgress: (loaded) => {
                            const sent = start + loaded;
                            const p = Math.round((sent / total) * 100);
                            updateItem(item.qid, { progress: Math.min(99, p) });
                        }
                    });

                    updateItem(item.qid, { progress: Math.round((end / total) * 100) });
                    break;
                } catch (e) {
                    attempt++;
                    if (attempt > MAX_RETRY) {
                        updateItem(item.qid, { status: 'error', progress: 0, errMsg: normalizeUploadError(e) });
                        return { ok: false };
                    }
                    await new Promise(r => setTimeout(r, 250 * attempt));
                }
            }
        }

        const finRes = await fetch(endpoints.chunkFinalize, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ upload_id: uploadId, total: totalChunks }),
        }).then(r => r.json());

        if (!finRes?.ok) {
            updateItem(item.qid, { status: 'error', progress: 0, errMsg: finRes?.error?.message || 'Finalize failed' });
            return { ok: false };
        }

        const media = finRes.data;
        updateItem(item.qid, { status: 'success', progress: 100, serverMedia: media });
        if (media) {
            recent = [media, ...recent].slice(0, 8);
            renderRecent();
        }
        return { ok: true, media };
    }

    async function uploadOne(item) {
        updateItem(item.qid, { status: 'uploading', progress: 0, errMsg: '' });

        const f = item.file;

        if ((f?.size || 0) > CHUNK_THRESHOLD) {
            try {
                return await uploadChunked(item);
            } catch (e) {
                updateItem(item.qid, { status: 'error', progress: 0, errMsg: normalizeUploadError(e) });
                return { ok: false };
            }
        }

        const fd = new FormData();
        fd.append('file', f);
        fd.append('title', titleInput.value || '');
        fd.append('alt', altInput.value || '');

        try {
            const json = await xhrUpload({
                url: endpoints.upload,
                formData: fd,
                onProgress: (loaded, total) => {
                    const p = total ? Math.round((loaded / total) * 100) : 0;
                    updateItem(item.qid, { progress: p });
                }
            });

            const media = json?.data;
            if (json?.ok === false) {
                updateItem(item.qid, { status: 'error', progress: 0, errMsg: json?.error?.message || 'Upload failed' });
                return { ok: false };
            }

            updateItem(item.qid, { status: 'success', progress: 100, serverMedia: media });

            if (media) {
                recent = [media, ...recent].slice(0, 8);
                renderRecent();
            }
            return { ok: true, media };
        } catch (e) {
            updateItem(item.qid, { status: 'error', progress: 0, errMsg: normalizeUploadError(e) });
            return { ok: false };
        }
    }

    async function uploadAll() {
        if (busy) return;

        setGlobalError('');

        const pending = queue.filter(x => x.status !== 'success');
        if (!pending.length) {
            setGlobalError('Kuyruk boş. Önce dosya seç.');
            return;
        }

        busy = true;
        startBtn.disabled = true;
        clearBtn.disabled = true;

        let cursor = 0;
        async function worker() {
            while (cursor < pending.length) {
                const it = pending[cursor++];
                if (!it || it.status === 'success') continue;
                await uploadOne(it);
            }
        }

        const workers = Array.from({ length: CONCURRENCY }, () => worker());
        await Promise.all(workers);

        busy = false;
        startBtn.disabled = false;
        clearBtn.disabled = false;

        state.page = 1;
        await fetchList();
        switchTab('library');
    }

    // -------------------------
    // Recent (modal)
    // -------------------------
    function renderRecent() {
        try { localStorage.setItem('media_recent', JSON.stringify(recent)); } catch (_) { }

        if (!recent.length) {
            recentList.innerHTML = `<div class="text-xs text-muted-foreground">Henüz yükleme yok.</div>`;
            return;
        }

        recentList.innerHTML = recent.map(m => {
            const kind = m.is_image ? 'image' : inferKindFromMimeOrExt(m.mime_type, m.original_name || m.url);
            const icon = (kind === 'image')
                ? `<img src="${esc(m.thumb_url || m.url)}" class="size-10 rounded-md object-cover ring-1 ring-border" alt="">`
                : (kind === 'video')
                    ? `<div class="size-10 rounded-md bg-muted flex items-center justify-center ring-1 ring-border"><i class="ki-outline ki-video text-lg"></i></div>`
                    : (kind === 'pdf')
                        ? `<div class="size-10 rounded-md bg-muted flex items-center justify-center ring-1 ring-border"><i class="ki-outline ki-file-sheet text-lg"></i></div>`
                        : `<div class="size-10 rounded-md bg-muted flex items-center justify-center ring-1 ring-border"><i class="ki-outline ki-file text-lg"></i></div>`;

            return `
              <div class="flex items-center gap-3 py-2">
                <button type="button"
                  class="shrink-0"
                  data-action="recent-open"
                  data-url="${esc(m.url)}"
                  data-name="${esc(m.original_name || '')}"
                  data-mime="${esc(m.mime_type || '')}"
                  data-size="${esc(m.size || 0)}"
                  data-kind="${esc(kind)}">
                  ${icon}
                </button>

                <div class="min-w-0 grow">
                  <div class="text-sm font-medium truncate" title="${esc(m.original_name)}">${esc(m.original_name || '-')}</div>
                  <div class="text-xs text-muted-foreground truncate">${esc(m.mime_type || '')} • ${formatBytes(m.size || 0)}</div>
                </div>

                <a class="kt-btn kt-btn-sm kt-btn-light" href="${esc(m.url)}" target="_blank" rel="noreferrer">
                  <i class="ki-outline ki-fasten"></i>
                </a>
              </div>
            `;
        }).join('');
    }

    // -------------------------
    // Modal library search/filter (optional, v2)
    // -------------------------
    async function fetchLibraryModal() {
        if (!libResults) return;

        const qs = new URLSearchParams({
            page: '1',
            perpage: '12',
            q: (libSearch?.value || '').trim(),
            type: libType?.value || '',
        });

        const res = await fetch(`${endpoints.list}?${qs.toString()}`, { headers: { 'Accept': 'application/json' } });
        const json = await res.json().catch(() => ({}));
        const items = Array.isArray(json?.data) ? json.data : [];

        libResults.innerHTML = items.map(m => {
            const kind = m.is_image ? 'image' : inferKindFromMimeOrExt(m.mime_type, m.original_name || m.url);
            return `
              <div class="kt-card">
                <div class="kt-card-content p-3 flex items-center gap-3">
                  <button type="button"
                    class="shrink-0"
                    data-action="lib-open"
                    data-url="${esc(m.url)}"
                    data-name="${esc(m.original_name || '')}"
                    data-mime="${esc(m.mime_type || '')}"
                    data-size="${esc(m.size || 0)}"
                    data-kind="${esc(kind)}">
                    ${kind === 'image'
                ? `<img src="${esc(m.thumb_url || m.url)}" class="size-10 rounded-md object-cover ring-1 ring-border" alt="">`
                : (kind === 'video')
                    ? `<div class="size-10 rounded-md bg-muted flex items-center justify-center ring-1 ring-border"><i class="ki-outline ki-video text-lg"></i></div>`
                    : (kind === 'pdf')
                        ? `<div class="size-10 rounded-md bg-muted flex items-center justify-center ring-1 ring-border"><i class="ki-outline ki-file-sheet text-lg"></i></div>`
                        : `<div class="size-10 rounded-md bg-muted flex items-center justify-center ring-1 ring-border"><i class="ki-outline ki-file text-lg"></i></div>`
            }
                  </button>

                  <div class="min-w-0 grow">
                    <div class="text-sm font-medium truncate" title="${esc(m.original_name)}">${esc(m.original_name || '-')}</div>
                    <div class="text-xs text-muted-foreground truncate">${esc(m.mime_type || '')} • ${formatBytes(m.size || 0)}</div>
                  </div>

                  <a class="kt-btn kt-btn-sm kt-btn-light" href="${esc(m.url)}" target="_blank" rel="noreferrer">
                    <i class="ki-outline ki-fasten"></i>
                  </a>
                </div>
              </div>
            `;
        }).join('');
    }

    // -------------------------
    // Events
    // -------------------------
    tabUploadBtn.addEventListener('click', () => switchTab('upload'));
    tabLibraryBtn.addEventListener('click', () => {
        switchTab('library');
        fetchLibraryModal();
    });

    dropzone.addEventListener('click', () => filesInput.click());

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('ring-2', 'ring-primary');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('ring-2', 'ring-primary');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('ring-2', 'ring-primary');
        addFiles(e.dataTransfer?.files);
    });

    filesInput.addEventListener('change', (e) => {
        addFiles(e.target.files);
        filesInput.value = '';
    });

    uploadList.addEventListener('click', async (e) => {
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

    recentList.addEventListener('click', (e) => {
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

    if (libResults) {
        libResults.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action="lib-open"]');
            if (!btn) return;

            const cards = [...libResults.querySelectorAll('[data-action="lib-open"]')];
            const items = cards.map(b => ({
                url: b.getAttribute('data-url') || '',
                kind: b.getAttribute('data-kind') || 'other',
                title: b.getAttribute('data-name') || 'Medya',
                sub: `${b.getAttribute('data-mime') || ''} • ${formatBytes(Number(b.getAttribute('data-size') || 0))}`,
                mime: b.getAttribute('data-mime') || '',
                name: b.getAttribute('data-name') || '',
            }));
            const idx = cards.indexOf(btn);
            openLightbox(items, Math.max(0, idx));
        });
    }

    startBtn.addEventListener('click', uploadAll);

    clearBtn.addEventListener('click', () => {
        if (busy) return;
        queue.forEach(it => {
            if (it.previewUrl) {
                try { URL.revokeObjectURL(it.previewUrl); } catch (_) { }
            }
        });
        queue = [];
        renderQueue();
    });

    refreshLibraryBtn.addEventListener('click', async () => {
        state.page = 1;
        await fetchList();
        fetchLibraryModal();
    });

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            state.q = searchInput.value.trim();
            state.page = 1;
            await fetchList();
        }, 250);
    });

    typeSelect.addEventListener('change', async () => {
        state.type = typeSelect.value || '';
        state.page = 1;
        await fetchList();
    });

    pagination.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-page]');
        if (!btn) return;
        const p = Number(btn.getAttribute('data-page') || 1);
        if (!p || p === state.page) return;
        state.page = p;
        await fetchList();
    });

    // Grid checkbox selection
    grid.addEventListener('change', (e) => {
        const cb = e.target.closest('input[data-media-check="1"]');
        if (!cb) return;

        const id = String(cb.getAttribute('data-id') || '');
        if (!id) return;

        if (cb.checked) selectedIds.add(id);
        else selectedIds.delete(id);

        setBulkUI();
    });

    // Grid open/delete (delegation)
    grid.addEventListener('click', async (e) => {
        if (e.target.closest('input[data-media-check="1"]')) {
            e.stopPropagation();
            return;
        }

        const del = e.target.closest('[data-action="delete"]');
        if (del) {
            e.preventDefault();
            e.stopPropagation();

            const id = del.getAttribute('data-id');
            if (!id) return;

            if (!confirm('Bu dosya silinsin mi?')) return;

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

            await fetchList();
            recent = recent.filter(x => String(x.id) !== String(id));
            renderRecent();
            return;
        }

        const btn = e.target.closest('[data-action="open"]');
        if (!btn) return;

        const cards = [...grid.querySelectorAll('[data-action="open"]')];
        const items = cards.map(b => ({
            url: b.getAttribute('data-url') || '',
            kind: b.getAttribute('data-kind') || inferKindFromMimeOrExt(b.getAttribute('data-mime'), b.getAttribute('data-name') || b.getAttribute('data-url')),
            title: b.getAttribute('data-name') || 'Medya',
            sub: `${b.getAttribute('data-mime') || ''} • ${formatBytes(Number(b.getAttribute('data-size') || 0))}`,
            mime: b.getAttribute('data-mime') || '',
            name: b.getAttribute('data-name') || '',
        }));
        const idx = cards.indexOf(btn);
        openLightbox(items, Math.max(0, idx));
    });

    if (libSearch) {
        let libDeb = null;
        libSearch.addEventListener('input', () => {
            clearTimeout(libDeb);
            libDeb = setTimeout(fetchLibraryModal, 250);
        });
    }
    if (libType) {
        libType.addEventListener('change', fetchLibraryModal);
    }

    // Bulk bar actions
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
