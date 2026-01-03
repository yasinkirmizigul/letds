export function initMediaPicker() {
    let state = { page: 1, perpage: 24, q: '', type: '' };
    let currentTarget = null; // {inputSel, previewSel, mime}

    const modal = document.getElementById('mediaPickerModal');
    if (!modal) return;

    const grid = modal.querySelector('#mediaPickerGrid');
    const search = modal.querySelector('#mediaPickerSearch');
    const type = modal.querySelector('#mediaPickerType');

    let debounceTimer = null;

    function openPicker(opts) {
        currentTarget = opts;
        state.page = 1;
        state.q = '';
        state.type = (opts?.mime && opts.mime.startsWith('image/')) ? 'image' : '';
        if (search) search.value = '';
        if (type) type.value = state.type;

        fetchList();
        // modal aç
        const opener = modal.querySelector('[data-kt-modal-toggle="#mediaPickerModal"]');
        if (opener) opener.click();
        else {
            // fallback: modal attribute ile açılmıyorsa, senin KT modal init'e göre açma gerekebilir
            // ama çoğu setup'ta toggle butonu olacak.
        }
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

        grid.innerHTML = items.map(m => {
            const thumb = m.is_image
                ? `<img src="${m.url}" class="w-full h-28 object-cover rounded-lg" alt="">`
                : `<div class="w-full h-28 rounded-lg flex items-center justify-center bg-muted">
             <i class="ki-outline ki-file text-2xl"></i>
           </div>`;

            return `
        <button type="button"
                class="kt-card overflow-hidden text-left"
                data-pick="1"
                data-id="${m.id}"
                data-url="${m.url}"
                data-mime="${m.mime_type}">
          <div class="kt-card-content p-3 grid gap-2">
            ${thumb}
            <div class="text-xs font-medium truncate">${m.original_name ?? '-'}</div>
          </div>
        </button>
      `;
        }).join('');
    }

    // Trigger: herhangi bir yerde data-media-picker="true" olan buton
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-media-picker="true"]');
        if (!btn) return;

        const inputSel = btn.getAttribute('data-media-picker-target');
        const previewSel = btn.getAttribute('data-media-picker-preview');
        const mime = btn.getAttribute('data-media-picker-mime') || '';

        openPicker({ inputSel, previewSel, mime });
    });

    // Seçim
    grid.addEventListener('click', (e) => {
        const pick = e.target.closest('[data-pick="1"]');
        if (!pick || !currentTarget) return;

        const id = pick.getAttribute('data-id');
        const url = pick.getAttribute('data-url');
        const mime = pick.getAttribute('data-mime') || '';

        // mime kısıtı (avatar: image/*)
        if (currentTarget.mime && currentTarget.mime.startsWith('image/') && !mime.startsWith('image/')) {
            alert('Sadece görsel seçebilirsin.');
            return;
        }

        const input = document.querySelector(currentTarget.inputSel);
        if (input) input.value = id;

        const prev = document.querySelector(currentTarget.previewSel);
        if (prev && url) {
            if (prev.tagName === 'IMG') {
                prev.src = url;
                prev.classList.remove('hidden');
            } else {
                prev.style.backgroundImage = `url('${url}')`;
            }
        }

        // modal kapat
        modal.querySelector('[data-kt-modal-dismiss="true"]')?.click();
    });

    // filtreler
    if (search) {
        search.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                state.q = search.value.trim();
                state.page = 1;
                fetchList();
            }, 250);
        });
    }
    if (type) {
        type.addEventListener('change', () => {
            state.type = type.value;
            state.page = 1;
            fetchList();
        });
    }
}
