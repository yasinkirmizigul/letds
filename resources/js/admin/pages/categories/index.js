export default function init() {
    const root = document.querySelector('[data-page="categories.index"]');
    if (!root) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const mode = root.dataset.mode || 'active';
    const isTrash = mode === 'trash';

    const tbody = root.querySelector('#categoriesTbody');
    const searchInput = root.querySelector('#categoriesSearch');

    const perSelect = root.querySelector('#categoriesPageSize');
    const infoEl = root.querySelector('#categoriesInfo');
    const pagEl = root.querySelector('#categoriesPagination');

    const tplEmpty = root.querySelector('#dt-empty-categories');
    const tplZero = root.querySelector('#dt-zero-categories');

    const bulkBar = root.querySelector('#categoriesBulkBar');
    const selectedCountEl = root.querySelector('#categoriesSelectedCount');

    const checkAllHead = root.querySelector('#categories_check_all_head');
    const checkAllBar = root.querySelector('#categories_check_all');

    const btnDel = root.querySelector('#categoriesBulkDeleteBtn');
    const btnRes = root.querySelector('#categoriesBulkRestoreBtn');
    const btnForce = root.querySelector('#categoriesBulkForceDeleteBtn');

    const state = {
        q: '',
        page: 1,
        per: Number(root.dataset.perpage || 25),
        last: 1,
        total: 0,
        selected: new Set(),
        loading: false
    };

    function notify(type, msg) {
        if (window.notify) return window.notify(type, msg);
        if (type === 'error') alert(msg);
        else console.log(msg);
    }

    async function fetchJson(url, opts = {}) {
        const res = await fetch(url, {
            ...opts,
            headers: {
                ...(opts.headers || {}),
                'Accept': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            }
        });
        const j = await res.json().catch(() => ({}));
        if (!res.ok || j?.ok === false) throw new Error(j?.message || 'İşlem başarısız');
        return j;
    }

    async function postJson(url, body, method = 'POST') {
        return fetchJson(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body || {})
        });
    }

    function renderEmpty(kind) {
        tbody.innerHTML = '';
        const tpl = kind === 'zero' ? tplZero : tplEmpty;
        const row = tpl?.content?.firstElementChild?.cloneNode(true);
        if (row) tbody.appendChild(row);
    }

    function rowHtml(it) {
        const checked = state.selected.has(String(it.id)) ? 'checked' : '';
        const editBtn = !isTrash
            ? `<a class="kt-btn kt-btn-light kt-btn-sm" href="/admin/categories/${it.id}/edit">Düzenle</a>`
            : '';

        const delBtn = !isTrash
            ? `<button type="button" class="kt-btn kt-btn-destructive kt-btn-sm" data-action="delete" data-id="${it.id}">Sil</button>`
            : '';

        const restoreBtn = isTrash
            ? `<button type="button" class="kt-btn kt-btn-sm kt-btn-success" data-action="restore" data-id="${it.id}">
                    <i class="ki-outline ki-arrow-circle-left"></i>
               </button>`
            : '';

        const forceBtn = isTrash
            ? `<button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" data-action="force-delete" data-id="${it.id}">
                    <i class="ki-outline ki-trash"></i>
               </button>`
            : '';

        return `
        <tr data-row-id="${it.id}">
            <td class="w-[55px]">
                <input class="kt-checkbox kt-checkbox-sm categories-check" type="checkbox" value="${it.id}" ${checked}>
            </td>
            <td class="font-medium">${escapeHtml(it.name || '')}</td>
            <td class="text-sm text-muted-foreground">${escapeHtml(it.slug || '')}</td>
            <td class="text-sm text-muted-foreground">${escapeHtml(it.parent_name || '-')}</td>
            <td class="text-sm text-secondary-foreground">${Number(it.blog_posts_count || 0)}</td>
            <td class="text-center">
                <div class="inline-flex gap-2 justify-end">
                    ${editBtn}
                    ${delBtn}
                    ${restoreBtn}
                    ${forceBtn}
                </div>
            </td>
        </tr>`;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, (m) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }

    function updateBulkUI() {
        const n = state.selected.size;

        bulkBar?.classList.toggle('hidden', n === 0);
        if (selectedCountEl) selectedCountEl.textContent = String(n);

        if (btnDel) btnDel.disabled = n === 0;
        if (btnRes) btnRes.disabled = n === 0;
        if (btnForce) btnForce.disabled = n === 0;

        const boxes = [...root.querySelectorAll('input.categories-check')];
        const checked = boxes.filter(b => b.checked).length;

        const allChecked = boxes.length > 0 && checked === boxes.length;
        const ind = checked > 0 && checked < boxes.length;

        if (checkAllHead) { checkAllHead.indeterminate = ind; checkAllHead.checked = allChecked; }
        if (checkAllBar) { checkAllBar.indeterminate = ind; checkAllBar.checked = allChecked; }
    }

    function renderPagination() {
        if (!pagEl) return;
        pagEl.innerHTML = '';

        const mkBtn = (label, page, disabled = false, active = false) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'kt-btn kt-btn-sm ' + (active ? 'kt-btn-primary' : 'kt-btn-light');
            b.textContent = label;
            b.disabled = disabled;
            b.addEventListener('click', () => {
                state.page = page;
                load();
            });
            return b;
        };

        const prev = mkBtn('‹', Math.max(1, state.page - 1), state.page <= 1);
        const next = mkBtn('›', Math.min(state.last, state.page + 1), state.page >= state.last);

        pagEl.appendChild(prev);

        // basit sayfa aralığı
        const start = Math.max(1, state.page - 2);
        const end = Math.min(state.last, state.page + 2);

        for (let p = start; p <= end; p++) {
            pagEl.appendChild(mkBtn(String(p), p, false, p === state.page));
        }

        pagEl.appendChild(next);
    }

    function renderInfo() {
        if (!infoEl) return;
        const from = state.total === 0 ? 0 : ((state.page - 1) * state.per) + 1;
        const to = Math.min(state.total, state.page * state.per);
        infoEl.textContent = `${from}-${to} / ${state.total}`;
    }

    async function load() {
        if (state.loading) return;
        state.loading = true;

        const params = new URLSearchParams();
        params.set('mode', isTrash ? 'trash' : 'active');
        params.set('q', state.q);
        params.set('page', String(state.page));
        params.set('perpage', String(state.per));

        try {
            const j = await fetchJson(`/admin/categories/list?${params.toString()}`);
            const items = Array.isArray(j.data) ? j.data : [];

            state.total = j?.meta?.total || 0;
            state.last = j?.meta?.last_page || 1;

            if (!items.length) {
                renderEmpty(state.q ? 'zero' : 'empty');
            } else {
                tbody.innerHTML = items.map(rowHtml).join('');
            }

            renderPagination();
            renderInfo();
            updateBulkUI();
        } catch (e) {
            notify('error', e?.message || 'Liste yüklenemedi');
        } finally {
            state.loading = false;
        }
    }

    // perpage select
    function initPerSelect() {
        if (!perSelect) return;
        const opts = [10, 25, 50, 100, 200];
        perSelect.innerHTML = opts.map(v => `<option value="${v}">${v}</option>`).join('');
        perSelect.value = String(state.per);

        perSelect.addEventListener('change', () => {
            state.per = Number(perSelect.value || 25);
            state.page = 1;
            load();
        });
    }

    // search (debounce)
    let t = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(() => {
            state.q = (searchInput.value || '').trim();
            state.page = 1;
            load();
        }, 250);
    });

    // selection
    root.addEventListener('change', (e) => {
        const el = e.target;

        if (el === checkAllHead || el === checkAllBar) {
            const on = !!el.checked;
            [...root.querySelectorAll('input.categories-check')].forEach(cb => {
                cb.checked = on;
                const id = String(cb.value);
                if (on) state.selected.add(id);
                else state.selected.delete(id);
            });
            updateBulkUI();
            return;
        }

        if (el?.classList?.contains('categories-check')) {
            const id = String(el.value);
            if (el.checked) state.selected.add(id);
            else state.selected.delete(id);
            updateBulkUI();
        }
    });

    // single actions (delete/restore/force)
    root.addEventListener('click', async (e) => {
        const btn = e.target?.closest?.('[data-action]');
        if (!btn) return;

        const action = btn.getAttribute('data-action');
        const id = btn.getAttribute('data-id');
        if (!id) return;

        try {
            if (action === 'delete') {
                if (!confirm('Kategori silinsin mi?')) return;
                // normal delete: form yok, fetch ile yap
                await postJson(`/admin/categories/${id}`, {}, 'DELETE');
                notify('success', 'Silindi');
                state.selected.delete(String(id));
                load();
            }

            if (action === 'restore') {
                if (!confirm('Geri yüklensin mi?')) return;
                await postJson(`/admin/categories/${id}/restore`, {});
                notify('success', 'Geri yüklendi');
                state.selected.delete(String(id));
                load();
            }

            if (action === 'force-delete') {
                if (!confirm('KALICI silinecek. Emin misin?')) return;
                await postJson(`/admin/categories/${id}/force`, {}, 'DELETE');
                notify('success', 'Kalıcı silindi');
                state.selected.delete(String(id));
                load();
            }
        } catch (err) {
            notify('error', err?.message || 'İşlem başarısız');
        }
    });

    // bulk actions
    btnDel?.addEventListener('click', async () => {
        const ids = [...state.selected];
        if (!ids.length) return;
        if (!confirm(`${ids.length} kategori silinsin mi?`)) return;

        try {
            await postJson('/admin/categories/bulk-delete', { ids });
            state.selected.clear();
            notify('success', 'Silindi');
            load();
        } catch (e) {
            notify('error', e?.message || 'Silme başarısız');
        }
    });

    btnRes?.addEventListener('click', async () => {
        const ids = [...state.selected];
        if (!ids.length) return;
        if (!confirm(`${ids.length} kategori geri yüklensin mi?`)) return;

        try {
            await postJson('/admin/categories/bulk-restore', { ids });
            state.selected.clear();
            notify('success', 'Geri yüklendi');
            load();
        } catch (e) {
            notify('error', e?.message || 'Geri yükleme başarısız');
        }
    });

    btnForce?.addEventListener('click', async () => {
        const ids = [...state.selected];
        if (!ids.length) return;
        if (!confirm(`${ids.length} kategori KALICI silinecek. Emin misin?`)) return;

        try {
            await postJson('/admin/categories/bulk-force-delete', { ids });
            state.selected.clear();
            notify('success', 'Kalıcı silindi');
            load();
        } catch (e) {
            notify('error', e?.message || 'Kalıcı silme başarısız');
        }
    });

    // boot
    initPerSelect();
    load();
}
