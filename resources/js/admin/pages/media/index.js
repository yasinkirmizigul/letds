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

    // Bulk select (page grid)
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

    const globalErr = modal?.querySelector('#mediaUploadGlobalError') || modal?.querySelector('#mediaUploadError');

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
    // Config
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
        bulkDelete: '/admin/media/bulk',
        destroyBase: '/admin/media', // /admin/media/{id}
    };

    const CONCURRENCY = 3;
    const CHUNK_SIZE = 5 * 1024 * 1024;
    const CHUNK_THRESHOLD = 20 * 1024 * 1024;
    const MAX_RETRY = 3;

    // -------------------------
    // State (page grid)
    // -------------------------
    const state = { page: 1, perpage: 24, q: '', type: '' };
    const selectedIds = new Set(); // page grid selected ids (string)
    let debounceTimer = null;

    // -------------------------
    // State (upload)
    // -------------------------
    let queue = [];    // {qid,file,previewUrl,status,progress,errMsg,serverMedia}
    let busy = false;

    // -------------------------
    // State (recent)
    // -------------------------
    let recent = [];
    try {
        const x = JSON.parse(localStorage.getItem('media_recent') || '[]');
        if (Array.isArray(x)) recent = x;
    } catch (_) { }

    // -------------------------
    // State (modal library)
    // -------------------------
    const libState = { page: 1, perpage: 12, q: '', type: '' };
    const libSelectedIds = new Set(); // modal library selected ids (string)

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

    function inferKindFromMimeOrExt(mime, nameOrUrl) {
        const m = String(mime || '').toLowerCase();
        if (m.startsWith('image/')) return 'image';
        if (m.startsWith('video/')) return 'video';
        if (m === 'application/pdf') return 'pdf';

        const s = String(nameOrUrl || '').toLowerCase();
        if (s.endsWith('.pdf')) return 'pdf';
        if (/\.(mp4|webm|ogg|mov|m4v)$/.test(s)) return 'video';
        if (/\.(png|jpe?g|gif|webp|bmp|svg)$/.test(s)) return 'image';
        return 'file';
    }

    async function httpJson(url, options = {}) {
        const res = await fetch(url, {
            ...options,
            headers: {
                'Accept': 'application/json',
                ...(options.headers || {}),
            },
        });
        const json = await res.json().catch(() => ({}));
        return { res, json };
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
    // Modal library UI (bulk bar + pagination containers created if missing)
    // -------------------------
    function ensureModalLibraryChrome() {
        if (!libraryPane) return;

        // Bulk bar container
        let bulk = libraryPane.querySelector('#mediaLibraryBulkBar');
        if (!bulk) {
            bulk = document.createElement('div');
            bulk.id = 'mediaLibraryBulkBar';
            bulk.className = 'hidden mb-3';
            bulk.innerHTML = `
              <div class="kt-card">
                <div class="kt-card-content px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                  <label class="flex items-center gap-2">
                    <input type="checkbox" class="kt-checkbox kt-checkbox-sm" id="mediaLibraryCheckAll">
                    <span class="text-sm">Tümünü seç</span>
                  </label>

                  <div class="flex items-center gap-3">
                    <div class="text-sm">
                      Seçili: <span class="font-semibold" id="mediaLibrarySelectedCount">0</span>
                    </div>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-danger" id="mediaLibraryBulkDeleteBtn">
                      <i class="ki-outline ki-trash"></i>
                      Toplu Sil
                    </button>
                  </div>
                </div>
              </div>
            `;
            // results'in üstüne koy
            if (libResults?.parentElement) {
                libResults.parentElement.insertBefore(bulk, libResults);
            } else {
                libraryPane.prepend(bulk);
            }
        }

        // Pagination container
        let pag = libraryPane.querySelector('#mediaLibraryPagination');
        if (!pag) {
            pag = document.createElement('div');
            pag.id = 'mediaLibraryPagination';
            pag.className = 'mt-3 flex items-center justify-center';
            if (libResults?.parentElement) libResults.parentElement.appendChild(pag);
            else libraryPane.appendChild(pag);
        }

        return {
            bulkBar: bulk,
            checkAll: bulk.querySelector('#mediaLibraryCheckAll'),
            selectedCount: bulk.querySelector('#mediaLibrarySelectedCount'),
            bulkDeleteBtn: bulk.querySelector('#mediaLibraryBulkDeleteBtn'),
            pagination: pag,
        };
    }

    function setModalBulkUI(chrome) {
        if (!chrome) return;
        const n = libSelectedIds.size;

        chrome.selectedCount.textContent = String(n);
        chrome.bulkBar.classList.toggle('hidden', n === 0);

        // checkAll state (tri-state yok, ama doğru duruma yakınlaştır)
        const visibleBoxes = [...(libResults?.querySelectorAll('input[data-lib-check="1"]') || [])];
        if (!visibleBoxes.length) {
            chrome.checkAll.checked = false;
            chrome.checkAll.indeterminate = false;
            return;
        }
        const checkedCount = visibleBoxes.filter(x => x.checked).length;
        chrome.checkAll.checked = checkedCount === visibleBoxes.length;
        chrome.checkAll.indeterminate = checkedCount > 0 && checkedCount < visibleBoxes.length;
    }

    function renderModalPagination(chrome, meta) {
        if (!chrome?.pagination) return;

        const cur = Number(meta?.current_page || 1);
        const last = Number(meta?.last_page || 1);

        if (last <= 1) {
            chrome.pagination.innerHTML = '';
            return;
        }

        const mkBtn = (label, page, disabled = false, active = false) => `
          <button type="button"
            class="kt-btn kt-btn-sm ${active ? 'kt-btn-primary' : 'kt-btn-light'}"
            data-action="lib-page"
            data-page="${page}"
            ${disabled ? 'disabled' : ''}>
            ${label}
          </button>
        `;

        // basit, iş gören pencere
        const windowSize = 5;
        let start = Math.max(1, cur - Math.floor(windowSize / 2));
        let end = Math.min(last, start + windowSize - 1);
        start = Math.max(1, end - windowSize + 1);

        let html = `<div class="flex items-center gap-2">`;
        html += mkBtn('<i class="ki-outline ki-arrow-left"></i>', cur - 1, cur <= 1);
        if (start > 1) {
            html += mkBtn('1', 1, false, cur === 1);
            if (start > 2) html += `<span class="px-1 text-muted-foreground">…</span>`;
        }
        for (let p = start; p <= end; p++) {
            html += mkBtn(String(p), p, false, p === cur);
        }
        if (end < last) {
            if (end < last - 1) html += `<span class="px-1 text-muted-foreground">…</span>`;
            html += mkBtn(String(last), last, false, cur === last);
        }
        html += mkBtn('<i class="ki-outline ki-arrow-right"></i>', cur + 1, cur >= last);
        html += `</div>`;

        chrome.pagination.innerHTML = html;
    }

    // -------------------------
    // Modal library fetch/render (search/filter + pagination + bulk delete + single delete)
    // -------------------------
    async function fetchLibraryModal() {
        if (!libResults) return;

        const chrome = ensureModalLibraryChrome();

        libState.q = (libSearch?.value || '').trim();
        libState.type = libType?.value || '';

        const qs = new URLSearchParams({
            page: String(libState.page || 1),
            perpage: String(libState.perpage || 12),
            q: libState.q,
            type: libState.type,
        });

        const { res, json } = await httpJson(`${endpoints.list}?${qs.toString()}`);
        if (!res.ok) {
            libResults.innerHTML = `<div class="text-sm text-danger">Liste alınamadı.</div>`;
            if (chrome?.pagination) chrome.pagination.innerHTML = '';
            return;
        }

        const items = Array.isArray(json?.data) ? json.data : [];
        const meta = json?.meta || { current_page: 1, last_page: 1 };

        libResults.innerHTML = items.map(m => {
            const kind = m.is_image ? 'image' : inferKindFromMimeOrExt(m.mime_type, m.original_name || m.url);
            const thumb = esc(m.thumb_url || m.url);
            const id = String(m.id ?? '');

            const icon = (kind === 'image')
                ? `<img src="${thumb}" class="size-10 rounded-md object-cover ring-1 ring-border" alt="">`
                : (kind === 'video')
                    ? `<div class="size-10 rounded-md bg-muted flex items-center justify-center ring-1 ring-border"><i class="ki-outline ki-video text-lg"></i></div>`
                    : (kind === 'pdf')
                        ? `<div class="size-10 rounded-md bg-muted flex items-center justify-center ring-1 ring-border"><i class="ki-outline ki-file-sheet text-lg"></i></div>`
                        : `<div class="size-10 rounded-md bg-muted flex items-center justify-center ring-1 ring-border"><i class="ki-outline ki-file text-lg"></i></div>`;

            return `
              <div class="kt-card" data-lib-item="1" data-id="${esc(id)}">
                <div class="kt-card-content p-3 flex items-center gap-3">

                  <label class="shrink-0 flex items-center">
                    <input type="checkbox"
                      class="kt-checkbox kt-checkbox-sm"
                      data-lib-check="1"
                      data-id="${esc(id)}"
                      ${libSelectedIds.has(id) ? 'checked' : ''}>
                  </label>

                  <button type="button"
                    class="shrink-0"
                    data-action="lib-open"
                    data-id="${esc(id)}"
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

                  <div class="flex items-center gap-2">
                    <a class="kt-btn kt-btn-sm kt-btn-light" href="${esc(m.url)}" target="_blank" rel="noreferrer" title="Aç">
                      <i class="ki-outline ki-fasten"></i>
                    </a>

                    <button type="button"
                      class="kt-btn kt-btn-sm kt-btn-danger"
                      data-action="lib-delete"
                      data-id="${esc(id)}"
                      title="Sil">
                      <i class="ki-outline ki-trash"></i>
                    </button>
                  </div>

                </div>
              </div>
            `;
        }).join('') || `<div class="text-sm text-muted-foreground">Kayıt bulunamadı.</div>`;

        renderModalPagination(chrome, meta);
        setModalBulkUI(chrome);
    }

    async function modalBulkDelete(ids) {
        if (!ids.length) return;
        if (!confirm(`${ids.length} medya silinsin mi?`)) return;

        const { res, json } = await httpJson(endpoints.bulkDelete, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
            body: JSON.stringify({ ids }),
        });

        if (!res.ok) {
            alert(json?.error?.message || json?.message || 'Toplu silme başarısız');
            return;
        }

        // state temizle + refresh
        ids.forEach(id => libSelectedIds.delete(String(id)));
        selectedIds.forEach(id => { if (ids.includes(id)) selectedIds.delete(id); });

        recent = recent.filter(x => !ids.includes(String(x.id)));
        renderRecent();

        // modal list refresh (sayfa boşaldıysa bir önceki sayfaya düş)
        libState.page = Math.max(1, libState.page);
        await fetchLibraryModal();

        // page grid refresh
        state.page = 1;
        await fetchList();
        setBulkUI();
    }

    async function modalSingleDelete(id) {
        const sid = String(id || '');
        if (!sid) return;
        if (!confirm(`Bu medya silinsin mi? (#${sid})`)) return;

        const { res, json } = await httpJson(`${endpoints.destroyBase}/${encodeURIComponent(sid)}`, {
            method: 'DELETE',
            headers: {
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
        });

        if (!res.ok) {
            alert(json?.error?.message || json?.message || 'Silme başarısız');
            return;
        }

        libSelectedIds.delete(sid);
        selectedIds.delete(sid);

        recent = recent.filter(x => String(x.id) !== sid);
        renderRecent();

        await fetchLibraryModal();

        state.page = 1;
        await fetchList();
        setBulkUI();
    }

    // -------------------------
    // Page grid bulk UI
    // -------------------------
    function setBulkUI() {
        if (!bulkBar || !selectedCountEl) return;
        const n = selectedIds.size;
        selectedCountEl.textContent = String(n);
        bulkBar.classList.toggle('hidden', n === 0);

        // checkAll state
        const boxes = [...grid.querySelectorAll('input[data-media-check="1"]')];
        if (!boxes.length) {
            if (checkAll) {
                checkAll.checked = false;
                checkAll.indeterminate = false;
            }
            return;
        }
        const checked = boxes.filter(x => x.checked).length;
        if (checkAll) {
            checkAll.checked = checked === boxes.length;
            checkAll.indeterminate = checked > 0 && checked < boxes.length;
        }
    }

    // -------------------------
    // Page list fetch/render (senin mevcut fonksiyonların burada vardı)
    // NOT: Aşağıya senin mevcut fetchList/renderGrid/renderPagination kodların geliyorsa
    // aynen bırak. Ben sadece modal tarafını tamamladım.
    // -------------------------

    async function fetchList() {
        const qs = new URLSearchParams({
            page: String(state.page || 1),
            perpage: String(state.perpage || 24),
            q: String(state.q || ''),
            type: String(state.type || ''),
        });

        const { res, json } = await httpJson(`${endpoints.list}?${qs.toString()}`);
        if (!res.ok) return;

        const items = Array.isArray(json?.data) ? json.data : [];
        const meta = json?.meta || {};

        renderGrid(items);
        renderPagination(meta);
        renderInfo(meta);
        setBulkUI();
    }

    function renderInfo(meta) {
        if (!info) return;
        const total = Number(meta?.total || 0);
        const cur = Number(meta?.current_page || 1);
        const per = Number(meta?.per_page || state.perpage || 24);
        const from = total ? ((cur - 1) * per + 1) : 0;
        const to = total ? Math.min(total, cur * per) : 0;
        info.textContent = `${from}-${to} / ${total}`;
    }

    function renderPagination(meta) {
        if (!pagination) return;
        const cur = Number(meta?.current_page || 1);
        const last = Number(meta?.last_page || 1);
        if (last <= 1) {
            pagination.innerHTML = '';
            return;
        }

        const mk = (label, page, disabled = false, active = false) => `
          <button type="button"
            class="kt-btn kt-btn-sm ${active ? 'kt-btn-primary' : 'kt-btn-light'}"
            data-action="page"
            data-page="${page}"
            ${disabled ? 'disabled' : ''}>
            ${label}
          </button>
        `;

        let html = `<div class="flex items-center gap-2 justify-center">`;
        html += mk('<i class="ki-outline ki-arrow-left"></i>', cur - 1, cur <= 1);

        const windowSize = 5;
        let start = Math.max(1, cur - Math.floor(windowSize / 2));
        let end = Math.min(last, start + windowSize - 1);
        start = Math.max(1, end - windowSize + 1);

        if (start > 1) {
            html += mk('1', 1, false, cur === 1);
            if (start > 2) html += `<span class="px-1 text-muted-foreground">…</span>`;
        }
        for (let p = start; p <= end; p++) html += mk(String(p), p, false, p === cur);
        if (end < last) {
            if (end < last - 1) html += `<span class="px-1 text-muted-foreground">…</span>`;
            html += mk(String(last), last, false, cur === last);
        }

        html += mk('<i class="ki-outline ki-arrow-right"></i>', cur + 1, cur >= last);
        html += `</div>`;
        pagination.innerHTML = html;
    }

    function renderGrid(items) {
        if (!grid) return;

        if (!items.length) {
            grid.innerHTML = '';
            empty.classList.remove('hidden');
            return;
        }
        empty.classList.add('hidden');

        grid.innerHTML = items.map(m => {
            const kind = m.is_image ? 'image' : inferKindFromMimeOrExt(m.mime_type, m.original_name || m.url);
            const id = String(m.id ?? '');

            const icon = (kind === 'image')
                ? `<img src="${esc(m.thumb_url || m.url)}" class="w-full h-full object-cover" alt="">`
                : (kind === 'video')
                    ? `<div class="w-full h-full flex items-center justify-center"><i class="ki-outline ki-video text-3xl"></i></div>`
                    : (kind === 'pdf')
                        ? `<div class="w-full h-full flex items-center justify-center"><i class="ki-outline ki-file-sheet text-3xl"></i></div>`
                        : `<div class="w-full h-full flex items-center justify-center"><i class="ki-outline ki-file text-3xl"></i></div>`;

            return `
              <div class="kt-card overflow-hidden" data-media-card="1">
                <div class="relative aspect-square bg-muted">
                  <label class="absolute top-2 left-2 z-10">
                    <input type="checkbox"
                      class="kt-checkbox kt-checkbox-sm"
                      data-media-check="1"
                      data-id="${esc(id)}"
                      ${selectedIds.has(id) ? 'checked' : ''}>
                  </label>

                  <button type="button"
                    class="absolute inset-0"
                    data-action="open"
                    data-id="${esc(id)}"
                    data-url="${esc(m.url)}"
                    data-name="${esc(m.original_name || '')}"
                    data-mime="${esc(m.mime_type || '')}"
                    data-size="${esc(m.size || 0)}"
                    data-kind="${esc(kind)}">
                    ${icon}
                  </button>
                </div>

                <div class="p-3">
                  <div class="text-sm font-medium truncate" title="${esc(m.original_name)}">${esc(m.original_name || '-')}</div>
                  <div class="text-xs text-muted-foreground truncate">${esc(m.mime_type || '')} • ${formatBytes(m.size || 0)}</div>
                </div>
              </div>
            `;
        }).join('');
    }

    // -------------------------
    // Tabs
    // -------------------------
    function switchTab(tab) {
        const isUpload = tab === 'upload';
        uploadPane.classList.toggle('hidden', !isUpload);
        libraryPane.classList.toggle('hidden', isUpload);

        tabUploadBtn.classList.toggle('active', isUpload);
        tabLibraryBtn.classList.toggle('active', !isUpload);

        if (!isUpload) {
            // library tab açıldıysa listeyi çek
            fetchLibraryModal();
        }
    }

    tabUploadBtn?.addEventListener('click', () => switchTab('upload'));
    tabLibraryBtn?.addEventListener('click', () => switchTab('library'));

    // -------------------------
    // Modal: search/type change + refresh
    // -------------------------
    if (libSearch) {
        let libDeb = null;
        libSearch.addEventListener('input', () => {
            clearTimeout(libDeb);
            libDeb = setTimeout(() => {
                libState.page = 1;
                fetchLibraryModal();
            }, 250);
        });
    }
    if (libType) {
        libType.addEventListener('change', () => {
            libState.page = 1;
            fetchLibraryModal();
        });
    }
    refreshLibraryBtn?.addEventListener('click', () => fetchLibraryModal());

    // -------------------------
    // Modal: delegation (checkbox/page/open/delete/bulk)
    // -------------------------
    libraryPane?.addEventListener('click', async (e) => {
        const chrome = ensureModalLibraryChrome();

        const pageBtn = e.target.closest('[data-action="lib-page"]');
        if (pageBtn) {
            const p = Number(pageBtn.getAttribute('data-page') || 1);
            libState.page = Math.max(1, p);
            await fetchLibraryModal();
            return;
        }

        const delBtn = e.target.closest('[data-action="lib-delete"]');
        if (delBtn) {
            const id = delBtn.getAttribute('data-id');
            await modalSingleDelete(id);
            return;
        }

        const bulkBtn = e.target.closest('#mediaLibraryBulkDeleteBtn');
        if (bulkBtn) {
            const ids = [...libSelectedIds];
            await modalBulkDelete(ids);
            setModalBulkUI(chrome);
            return;
        }

        const openBtn = e.target.closest('[data-action="lib-open"]');
        if (openBtn) {
            // burada senin lightbox/open mantığın neyse aynen bağlayabilirsin
            // şimdilik sadece yeni sekmede aç:
            const url = openBtn.getAttribute('data-url');
            if (url) window.open(url, '_blank', 'noreferrer');
            return;
        }
    });

    libraryPane?.addEventListener('change', (e) => {
        const chrome = ensureModalLibraryChrome();

        const cb = e.target.closest('input[data-lib-check="1"]');
        if (cb) {
            const id = String(cb.getAttribute('data-id') || '');
            if (!id) return;
            if (cb.checked) libSelectedIds.add(id);
            else libSelectedIds.delete(id);
            setModalBulkUI(chrome);
            return;
        }

        const all = e.target.closest('#mediaLibraryCheckAll');
        if (all) {
            const on = !!all.checked;
            const boxes = [...(libResults?.querySelectorAll('input[data-lib-check="1"]') || [])];
            boxes.forEach(x => {
                x.checked = on;
                const id = String(x.getAttribute('data-id') || '');
                if (!id) return;
                if (on) libSelectedIds.add(id);
                else libSelectedIds.delete(id);
            });
            setModalBulkUI(chrome);
        }
    });

    // -------------------------
    // Page grid: search/filter/pagination/bulk
    // -------------------------
    searchInput?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            state.q = (searchInput.value || '').trim();
            state.page = 1;
            fetchList();
        }, 250);
    });

    typeSelect?.addEventListener('change', () => {
        state.type = typeSelect.value || '';
        state.page = 1;
        fetchList();
    });

    pagination?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="page"]');
        if (!btn) return;
        const p = Number(btn.getAttribute('data-page') || 1);
        state.page = Math.max(1, p);
        fetchList();
    });

    grid?.addEventListener('change', (e) => {
        const cb = e.target.closest('input[data-media-check="1"]');
        if (!cb) return;
        const id = String(cb.getAttribute('data-id') || '');
        if (!id) return;
        if (cb.checked) selectedIds.add(id);
        else selectedIds.delete(id);
        setBulkUI();
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
        // page tarafındaki bulk delete de aynı endpoint
        await modalBulkDelete(ids);
    });

    // -------------------------
    // First load
    // -------------------------
    switchTab('upload');
    renderRecent();
    setBulkUI();
    fetchList();
}
