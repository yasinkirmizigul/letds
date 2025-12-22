// resources/js/pages/media/index.js
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
        dropzone, filesInput, titleInput, altInput, globalErr,
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
    let debounceTimer = null;

    let queue = [];    // {qid,file,previewUrl,status,progress,errMsg,serverMedia}
    let busy = false;

    let recent = [];   // server media payloads
    try {
        const x = JSON.parse(localStorage.getItem('media_recent') || '[]');
        if (Array.isArray(x)) recent = x;
    } catch (_) {}

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

    function setGlobalError(msg) {
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

    function fileExt(name) {
        const s = String(name || '');
        const i = s.lastIndexOf('.');
        return i >= 0 ? s.slice(i + 1).toLowerCase() : '';
    }

    function isLikelyImage(file) {
        const t = String(file?.type || '');
        if (t.startsWith('image/')) return true;
        return ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'].includes(fileExt(file?.name));
    }

    function makeRowHtml(item) {
        const imgPreview = item.previewUrl
            ? `<img src="${esc(item.previewUrl)}" class="size-10 rounded-md object-cover ring-1 ring-border" alt="">`
            : `<div class="size-10 rounded-md bg-muted flex items-center justify-center ring-1 ring-border">
                 <i class="ki-outline ki-file text-lg"></i>
               </div>`;

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
                ${imgPreview}
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
            const previewUrl = isLikelyImage(file) ? URL.createObjectURL(file) : '';

            queue.push({
                qid,
                file,
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
            try { URL.revokeObjectURL(it.previewUrl); } catch (_) {}
        }
        queue = queue.filter(x => x.qid !== qid);
        renderQueue();
    }

    function updateItem(qid, patch) {
        const idx = queue.findIndex(x => x.qid === qid);
        if (idx < 0) return;
        queue[idx] = { ...queue[idx], ...patch };

        // minimal DOM update
        const card = uploadList.querySelector(`[data-qid="${CSS.escape(qid)}"]`);
        if (card) {
            const prog = card.querySelector('.kt-progress-indicator');
            if (prog && typeof queue[idx].progress === 'number') prog.style.width = `${queue[idx].progress}%`;

            // badge + error block -> cheap re-render by replacing card outerHTML
            card.outerHTML = makeRowHtml(queue[idx]);
        }

        updateQueueInfo();
    }

    // -------------------------
    // Page list
    // -------------------------
    function mediaCard(m) {
        const isImg = !!m.is_image;
        const thumb = isImg
            ? `<img src="${esc(m.url)}" class="w-full h-44 object-cover rounded-xl ring-1 ring-border" alt="">`
            : `<div class="w-full h-44 rounded-xl bg-muted ring-1 ring-border flex items-center justify-center">
                 <i class="ki-outline ki-file text-3xl text-muted-foreground"></i>
               </div>`;

        return `
          <div class="kt-card">
            <div class="kt-card-content p-3">
              ${thumb}
              <div class="mt-3">
                <div class="text-sm font-medium truncate" title="${esc(m.original_name)}">${esc(m.original_name || '-')}</div>
                <div class="text-xs text-muted-foreground truncate">${esc(m.mime_type || '')} • ${formatBytes(m.size || 0)}</div>
              </div>
              <div class="mt-3 flex items-center justify-between gap-2">
                <a href="${esc(m.url)}" target="_blank" rel="noreferrer" class="kt-btn kt-btn-sm kt-btn-light">
                  <i class="ki-outline ki-eye"></i>
                  Gör
                </a>
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

        // Chunked
        if ((f?.size || 0) > CHUNK_THRESHOLD) {
            try {
                return await uploadChunked(item);
            } catch (e) {
                updateItem(item.qid, { status: 'error', progress: 0, errMsg: normalizeUploadError(e) });
                return { ok: false };
            }
        }

        // Single (XHR progress)
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
            // bazı backend'ler ok:true döner; bazıları sadece data
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

        // page list refresh
        state.page = 1;
        await fetchList();
        switchTab('library');
    }

    // -------------------------
    // Recent (modal)
    // -------------------------
    function renderRecent() {
        try { localStorage.setItem('media_recent', JSON.stringify(recent)); } catch (_) {}

        if (!recent.length) {
            recentList.innerHTML = `<div class="text-xs text-muted-foreground">Henüz yükleme yok.</div>`;
            return;
        }

        recentList.innerHTML = recent.map(m => `
          <div class="flex items-center gap-3 py-2">
            ${m.is_image
            ? `<img src="${esc(m.url)}" class="size-10 rounded-md object-cover ring-1 ring-border" alt="">`
            : `<div class="size-10 rounded-md bg-muted flex items-center justify-center ring-1 ring-border">
                     <i class="ki-outline ki-file text-lg"></i>
                   </div>`
        }
            <div class="min-w-0 grow">
              <div class="text-sm font-medium truncate" title="${esc(m.original_name)}">${esc(m.original_name || '-')}</div>
              <div class="text-xs text-muted-foreground truncate">${esc(m.mime_type || '')} • ${formatBytes(m.size || 0)}</div>
            </div>
            <a class="kt-btn kt-btn-sm kt-btn-light" href="${esc(m.url)}" target="_blank" rel="noreferrer">
              <i class="ki-outline ki-eye"></i>
            </a>
          </div>
        `).join('');
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

        libResults.innerHTML = items.map(m => `
          <div class="kt-card">
            <div class="kt-card-content p-3 flex items-center gap-3">
              ${m.is_image
            ? `<img src="${esc(m.url)}" class="size-10 rounded-md object-cover ring-1 ring-border" alt="">`
            : `<div class="size-10 rounded-md bg-muted flex items-center justify-center ring-1 ring-border"><i class="ki-outline ki-file text-lg"></i></div>`
        }
              <div class="min-w-0 grow">
                <div class="text-sm font-medium truncate" title="${esc(m.original_name)}">${esc(m.original_name || '-')}</div>
                <div class="text-xs text-muted-foreground truncate">${esc(m.mime_type || '')} • ${formatBytes(m.size || 0)}</div>
              </div>
              <a class="kt-btn kt-btn-sm kt-btn-light" href="${esc(m.url)}" target="_blank" rel="noreferrer"><i class="ki-outline ki-eye"></i></a>
            </div>
          </div>
        `).join('');
    }

    // -------------------------
    // Events
    // -------------------------
    tabUploadBtn.addEventListener('click', () => switchTab('upload'));
    tabLibraryBtn.addEventListener('click', () => {
        switchTab('library');
        // modal içi arama/filter varsa listeyi oradan doldur
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

        if (action === 'remove') {
            if (busy && it.status === 'uploading') return;
            removeFromQueue(qid);
        }

        if (action === 'retry') {
            if (busy) return;
            if (it.status === 'success') return;
            await uploadOne(it);
            state.page = 1;
            await fetchList();
        }
    });

    startBtn.addEventListener('click', uploadAll);

    clearBtn.addEventListener('click', () => {
        if (busy) return;
        queue.forEach(it => {
            if (it.previewUrl) {
                try { URL.revokeObjectURL(it.previewUrl); } catch (_) {}
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

    // Page list events
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

    // modal library search/filter (optional)
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

    // -------------------------
    // First load
    // -------------------------
    switchTab('upload');
    renderRecent();
    renderQueue();
    fetchList();
}
