// resources/js/core/media-upload-modal.js
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function preventAll(e) {
    e.preventDefault();
    e.stopPropagation();
}

function setRing(el, on) {
    el.classList.toggle('ring-2', !!on);
    el.classList.toggle('ring-border', !!on);
}

export function initMediaUploadModal(scope = document) {
    const modal = scope.querySelector('#mediaUploadModal');
    if (!modal || modal.__mediaUploadInited) return;
    modal.__mediaUploadInited = true;

    const dz = modal.querySelector('#mediaDropzone');
    const input = modal.querySelector('#mediaFiles') || modal.querySelector('#mediaFile'); // eski/yeni uyum
    const uploadList = modal.querySelector('#mediaUploadList');
    const startBtn = modal.querySelector('#mediaStartUpload');
    const clearBtn = modal.querySelector('#mediaClearQueue');
    const titleEl = modal.querySelector('#mediaTitle');
    const altEl = modal.querySelector('#mediaAlt');
    const applyTitleAllBtn = modal.querySelector('#mediaApplyTitleAll');
    const applyAltAllBtn = modal.querySelector('#mediaApplyAltAll');
    const queueInfo = modal.querySelector('#mediaQueueInfo');
    const errBox = modal.querySelector('#mediaUploadError');

    if (!dz || !input || !uploadList || !startBtn || !clearBtn) {
        // Modal HTML'i eksikse zaten bir şey yapamayız.
        return;
    }

    // Queue
    let queue = []; // {file, title, alt, status, progress, error}
    let uploading = false;

    function setError(msg) {
        if (!errBox) return;
        errBox.textContent = msg || '';
        errBox.classList.toggle('hidden', !msg);
    }

    function render() {
        if (queueInfo) queueInfo.textContent = String(queue.length);

        uploadList.innerHTML = queue.map((q, idx) => {
            const p = Math.max(0, Math.min(100, Number(q.progress || 0)));
            const statusBadge =
                q.status === 'done' ? `<span class="kt-badge kt-badge-outline">OK</span>` :
                    q.status === 'error' ? `<span class="kt-badge kt-badge-outline">Hata</span>` :
                        q.status === 'uploading' ? `<span class="kt-badge kt-badge-outline">Yükleniyor</span>` :
                            `<span class="kt-badge kt-badge-outline">Bekliyor</span>`;

            return `
        <div class="rounded-xl border border-border bg-background p-4 grid gap-3" data-i="${idx}">
          <div class="flex items-start justify-between gap-3">
            <div class="grid">
              <div class="font-medium">${escapeHtml(q.file.name)}</div>
              ${q.error ? `<div class="text-xs text-destructive whitespace-pre-wrap">${escapeHtml(q.error)}</div>` : ''}
            </div>
            <div class="flex items-center gap-2">
              ${statusBadge}
              <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-act="remove" ${uploading ? 'disabled' : ''}>
                <i class="ki-outline ki-cross"></i>
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <input class="kt-input" data-act="title" type="text" placeholder="Başlık (opsiyonel)" value="${escapeHtml(q.title || '')}" ${uploading ? 'disabled' : ''}>
            <input class="kt-input" data-act="alt" type="text" placeholder="Alt (opsiyonel)" value="${escapeHtml(q.alt || '')}" ${uploading ? 'disabled' : ''}>
          </div>

          <div class="rounded-lg border border-border bg-muted/10 overflow-hidden">
            <div style="width:${p}%" class="h-2 bg-muted"></div>
          </div>
        </div>
      `;
        }).join('');
    }
    function applyTitleAll() {
        const v = (titleEl?.value || '').trim();
        if (!queue.length) return;

        // sadece boş olanlara uygula istersen:
        queue.forEach(q => { if (!String(q.title||'').trim()) q.title = v; });
        // “hepsine zorla uygula”:
        //queue.forEach(q => { q.title = v; });
        render();
    }

    function applyAltAll() {
        const v = (altEl?.value || '').trim();
        if (!queue.length) return;

        queue.forEach(q => { if (!String(q.alt||'').trim()) q.alt = v; });
        //queue.forEach(q => { q.alt = v; });
        render();
    }

    function escapeHtml(s) {
        return String(s ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function addFiles(fileList) {
        const t = (titleEl?.value || '').trim();
        const a = (altEl?.value || '').trim();

        [...fileList].forEach(f => {
            queue.push({ file: f, title: t, alt: a, status: 'queued', progress: 0, error: '' });
        });

        setError('');
        render();
    }

    // Dropzone events
    ['dragenter','dragover','dragleave','drop'].forEach(ev => dz.addEventListener(ev, preventAll));
    dz.addEventListener('dragover', () => setRing(dz, true));
    dz.addEventListener('dragleave', () => setRing(dz, false));
    dz.addEventListener('drop', (e) => {
        setRing(dz, false);
        const files = e.dataTransfer?.files;
        if (files?.length) addFiles(files);
    });
    dz.addEventListener('click', () => input.click());

    // File picker
    input.addEventListener('change', () => {
        if (input.files?.length) addFiles(input.files);
        input.value = ''; // aynı dosyayı tekrar seçebilsin
    });

    // Inline edits
    uploadList.addEventListener('input', (e) => {
        const row = e.target.closest('[data-i]');
        if (!row) return;
        const i = Number(row.dataset.i);
        const act = e.target.getAttribute('data-act');
        if (!queue[i]) return;

        if (act === 'title') queue[i].title = e.target.value;
        if (act === 'alt') queue[i].alt = e.target.value;
    });

    uploadList.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-act="remove"]');
        if (!btn) return;
        const row = btn.closest('[data-i]');
        const i = Number(row?.dataset.i);
        if (!Number.isFinite(i)) return;
        queue.splice(i, 1);
        render();
    });


    applyTitleAllBtn?.addEventListener('click', applyTitleAll);
    applyAltAllBtn?.addEventListener('click', applyAltAll);

    clearBtn.addEventListener('click', () => {
        if (uploading) return;
        queue = [];
        setError('');
        render();
    });

    async function uploadOne(q) {
        q.status = 'uploading';
        q.progress = 30;
        q.error = '';
        render();

        const fd = new FormData();
        fd.append('file', q.file);
        if ((q.title || '').trim()) fd.append('title', q.title.trim());
        if ((q.alt || '').trim()) fd.append('alt', q.alt.trim());

        // Backend endpoint: MediaController@upload var. :contentReference[oaicite:4]{index=4}
        const res = await fetch('/admin/media/upload', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: fd,
            credentials: 'same-origin',
        });

        const j = await res.json().catch(() => ({}));

        if (!res.ok || !j?.ok) {
            q.status = 'error';
            q.progress = 0;
            q.error = j?.error?.message || j?.message || `HTTP ${res.status}`;
            render();
            return null;
        }

        q.status = 'done';
        q.progress = 100;
        render();
        return j.data;
    }

    startBtn.addEventListener('click', async () => {
        if (uploading) return;
        if (!queue.length) return;
        uploading = true;
        startBtn.disabled = true;
        clearBtn.disabled = true;
        setError('');

        // sırayla yükle (basit ve güvenilir)
        for (const q of queue) {
            if (q.status === 'done') continue;
            await uploadOne(q);
        }

        uploading = false;
        startBtn.disabled = false;
        clearBtn.disabled = false;

        // İstersen burada “library refresh” tetikleyebilirsin.
        // modal.querySelector('#mediaRefreshLibrary')?.click();
    });

    render();
}
