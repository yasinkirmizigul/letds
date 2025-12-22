export default function init() {
    const root = document.querySelector('[data-page="media.index"]');
    if (!root) return;

    const grid = root.querySelector('#mediaGrid');
    const empty = root.querySelector('#mediaEmpty');
    const info = root.querySelector('#mediaInfo');
    const pagination = root.querySelector('#mediaPagination');

    const searchInput = root.querySelector('#mediaSearch');
    const typeSelect = root.querySelector('#mediaType');

    const uploadBtn = root.querySelector('#mediaUploadBtn');
    const fileInput = root.querySelector('#mediaFile');
    const titleInput = root.querySelector('#mediaTitle');
    const altInput = root.querySelector('#mediaAlt');
    const uploadError = root.querySelector('#mediaUploadError');

    let state = { page: 1, perpage: 24, q: '', type: '' };
    let debounceTimer = null;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const required = { grid, empty, info, pagination, searchInput, typeSelect, uploadBtn, fileInput, titleInput, altInput, uploadError };
    const missing = Object.entries(required).filter(([, v]) => !v).map(([k]) => k);

    if (missing.length) {
        console.error('[media.index] Missing DOM elements:', missing);
        return;
    }

    function formatBytes(bytes) {
        if (!bytes) return '0 B';
        const k = 1024, sizes = ['B','KB','MB','GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return (bytes / Math.pow(k, i)).toFixed(i === 0 ? 0 : 1) + ' ' + sizes[i];
    }

    function mediaCard(m) {
        const thumb = m.is_image
            ? `<img src="${m.url}" class="w-full h-28 object-cover rounded-lg" alt="">`
            : `<div class="w-full h-28 rounded-lg flex items-center justify-center bg-muted">
           <i class="ki-outline ki-file text-2xl"></i>
         </div>`;

        return `
      <div class="kt-card overflow-hidden">
        <div class="kt-card-content p-3 grid gap-2">
          ${thumb}
          <div class="text-xs font-medium truncate" title="${m.original_name ?? ''}">${m.original_name ?? '-'}</div>
          <div class="flex items-center justify-between text-[11px] text-muted-foreground">
            <span class="truncate">${m.mime_type}</span>
            <span>${formatBytes(m.size)}</span>
          </div>
          <div class="flex justify-end">
            <button class="kt-btn kt-btn-sm kt-btn-light" data-action="delete" data-id="${m.id}">
              <i class="ki-outline ki-trash"></i>
            </button>
          </div>
        </div>
      </div>
    `;
    }

    async function fetchList() {
        const qs = new URLSearchParams({
            page: String(state.page),
            perpage: String(state.perpage),
            q: state.q,
            type: state.type,
        });

        const res = await fetch(`/admin/media/list?${qs.toString()}`, {
            headers: { 'Accept': 'application/json' }
        });
        const json = await res.json();

        const items = json.data || [];
        grid.innerHTML = items.map(mediaCard).join('');

        const total = json.meta?.total ?? 0;
        const current = json.meta?.current_page ?? 1;
        const last = json.meta?.last_page ?? 1;

        info.textContent = `${total} dosya • Sayfa ${current}/${last}`;

        if (total === 0) {
            empty.classList.remove('hidden');
        } else {
            empty.classList.add('hidden');
        }

        renderPagination(current, last);
    }

    function renderPagination(current, last) {
        // minimal, KT pagination container kullanıyoruz
        const prevDisabled = current <= 1 ? 'opacity-50 pointer-events-none' : '';
        const nextDisabled = current >= last ? 'opacity-50 pointer-events-none' : '';

        pagination.innerHTML = `
      <div class="flex items-center gap-2">
        <button class="kt-btn kt-btn-sm kt-btn-light ${prevDisabled}" data-page="${current - 1}">
          <i class="ki-outline ki-arrow-left"></i>
        </button>
        <span class="text-sm">${current}</span>
        <button class="kt-btn kt-btn-sm kt-btn-light ${nextDisabled}" data-page="${current + 1}">
          <i class="ki-outline ki-arrow-right"></i>
        </button>
      </div>
    `;
    }

    async function upload() {
        uploadError.classList.add('hidden');
        uploadError.textContent = '';

        const file = fileInput.files?.[0];
        if (!file) {
            uploadError.textContent = 'Dosya seçmelisin.';
            uploadError.classList.remove('hidden');
            return;
        }

        const fd = new FormData();
        fd.append('file', file);
        fd.append('title', titleInput.value || '');
        fd.append('alt', altInput.value || '');

        uploadBtn.disabled = true;

        const res = await fetch('/admin/media/upload', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf ?? '' },
            body: fd
        });

        uploadBtn.disabled = false;

        if (!res.ok) {
            const t = await res.text();
            uploadError.textContent = 'Yükleme başarısız. ' + t;
            uploadError.classList.remove('hidden');
            return;
        }

        // reset form
        fileInput.value = '';
        titleInput.value = '';
        altInput.value = '';

        state.page = 1;
        await fetchList();

        // modal kapat (Metronic modal toggle/dismiss attribute'larıyla)
        const dismiss = root.querySelector('#mediaUploadModal [data-kt-modal-dismiss="true"]');
        dismiss?.click();
    }

    // events
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            state.q = searchInput.value.trim();
            state.page = 1;
            fetchList();
        }, 250);
    });

    typeSelect.addEventListener('change', () => {
        state.type = typeSelect.value;
        state.page = 1;
        fetchList();
    });

    pagination.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-page]');
        if (!btn) return;
        const p = Number(btn.getAttribute('data-page'));
        if (!Number.isFinite(p) || p < 1) return;
        state.page = p;
        fetchList();
    });

    grid.addEventListener('click', async (e) => {
        const del = e.target.closest('[data-action="delete"]');
        if (!del) return;

        const id = del.getAttribute('data-id');
        if (!id) return;

        // minimal confirm (istersen KTDialog’a taşırız)
        if (!confirm('Bu dosya silinsin mi?')) return;

        const res = await fetch(`/admin/media/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf ?? '', 'Accept': 'application/json' }
        });

        if (res.ok) fetchList();
    });

    uploadBtn.addEventListener('click', upload);

    // first load
    fetchList();
}
