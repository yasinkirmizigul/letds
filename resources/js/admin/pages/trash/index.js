export default function init() {
    const root = document.querySelector('[data-page="trash.index"]');
    if (!root) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const tbody = root.querySelector('#trashTbody');
    const searchInput = root.querySelector('#trashSearch');
    const typeSelect = root.querySelector('#trashType');

    const perSelect = root.querySelector('#trashPageSize');
    const infoEl = root.querySelector('#trashInfo');
    const pagEl = root.querySelector('#trashPagination');

    const tplEmpty = root.querySelector('#dt-empty-trash');
    const tplZero = root.querySelector('#dt-zero-trash');

    const bulkBar = root.querySelector('#trashBulkBar');
    const selectedCountEl = root.querySelector('#trashSelectedCount');
    const checkAllHead = root.querySelector('#trash_check_all_head');
    const checkAllBar = root.querySelector('#trash_check_all');

    const btnRes = root.querySelector('#trashBulkRestoreBtn');
    const btnForce = root.querySelector('#trashBulkForceDeleteBtn');


    const listUrl = root.dataset.listUrl || '/admin/trash/list';
    const bulkRestoreUrl = root.dataset.bulkRestoreUrl || '/admin/trash/bulk-restore';
    const bulkForceUrl   = root.dataset.bulkForceDeleteUrl || '/admin/trash/bulk-force-delete';

    const state = {
        q: '',
        type: 'all',
        page: 1,
        per: Number(root.dataset.perpage || 25),
        last: 1,
        total: 0,
        selected: new Map(), // key = `${type}:${id}` => {type,id}
        loading: false,
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

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, (m) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }

    function badgeType(t) {
        const map = { media: 'Media', blog: 'Blog', category: 'Category' };
        return `<span class="kt-badge kt-badge-sm kt-badge-light">${map[t] || t}</span>`;
    }

    function keyOf(it) { return `${it.type}:${it.id}`; }

    function renderEmpty(kind) {
        tbody.innerHTML = '';
        const tpl = kind === 'zero' ? tplZero : tplEmpty;
        const row = tpl?.content?.firstElementChild?.cloneNode(true);
        if (row) tbody.appendChild(row);
    }

    function rowHtml(it) {
        const k = keyOf(it);
        const checked = state.selected.has(k) ? 'checked' : '';
        const deletedText = (it.deleted_at || '').replace('T',' ').replace('Z','');

        let purgeBadge = '';
        if (typeof it.days_left === 'number') {
            if (it.days_left <= 0) {
                purgeBadge = `<span class="kt-badge kt-badge-sm kt-badge-destructive">Bugün silinir</span>`;
            } else {
                purgeBadge = `<span class="kt-badge kt-badge-sm kt-badge-light">${it.days_left} gün sonra</span>`;
            }
        }


        return `
        <tr data-row-key="${k}">
            <td class="w-[55px]">
                <input class="kt-checkbox kt-checkbox-sm trash-check"
                       type="checkbox"
                       data-type="${it.type}"
                       data-id="${it.id}"
                       ${checked}>
            </td>
            <td>${badgeType(it.type)}</td>
            <td class="font-medium">${escapeHtml(it.title || '')}</td>
            <td class="text-sm text-muted-foreground">
                <div class="flex flex-col gap-1">
                    <div>${escapeHtml(deletedText)}</div>
                    ${purgeBadge}
                </div>
            </td>
            <td class="text-center">
                <div class="inline-flex gap-2 justify-end">
                    ${it.url ? `
                        <a href="${it.url}" class="kt-btn kt-btn-sm kt-btn-light" title="Kaynağa git">
                            <i class="ki-outline ki-exit-right"></i>
                        </a>
                    ` : ''}

                    <button type="button" class="kt-btn kt-btn-sm kt-btn-success"
                            data-action="restore" data-type="${it.type}" data-id="${it.id}">
                        <i class="ki-outline ki-arrow-rotate-left"></i>
                    </button>

                    <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive"
                            data-action="force-delete" data-type="${it.type}" data-id="${it.id}">
                        <i class="ki-outline ki-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }

    function updateBulkUI() {
        const n = state.selected.size;

        bulkBar?.classList.toggle('hidden', n === 0);
        if (selectedCountEl) selectedCountEl.textContent = String(n);

        if (btnRes) btnRes.disabled = n === 0;
        if (btnForce) btnForce.disabled = n === 0;

        const boxes = [...root.querySelectorAll('input.trash-check')];
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

        pagEl.appendChild(mkBtn('‹', Math.max(1, state.page - 1), state.page <= 1));

        const start = Math.max(1, state.page - 2);
        const end = Math.min(state.last, state.page + 2);
        for (let p = start; p <= end; p++) {
            pagEl.appendChild(mkBtn(String(p), p, false, p === state.page));
        }

        pagEl.appendChild(mkBtn('›', Math.min(state.last, state.page + 1), state.page >= state.last));
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
        params.set('type', state.type);
        params.set('q', state.q);
        params.set('page', String(state.page));
        params.set('perpage', String(state.per));

        try {
            const j = await fetchJson(`${listUrl}?${params.toString()}`);
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

    function initPerSelect() {
        if (!perSelect) return;
        const opts = [10, 25, 50, 100];
        perSelect.innerHTML = opts.map(v => `<option value="${v}">${v}</option>`).join('');
        perSelect.value = String(state.per);

        perSelect.addEventListener('change', () => {
            state.per = Number(perSelect.value || 25);
            state.page = 1;
            load();
        });
    }

    // search debounce
    let t = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(() => {
            state.q = (searchInput.value || '').trim();
            state.page = 1;
            load();
        }, 250);
    });

    typeSelect?.addEventListener('change', () => {
        state.type = String(typeSelect.value || 'all');
        state.page = 1;
        state.selected.clear();
        updateBulkUI();
        load();
    });

    // checkbox changes
    root.addEventListener('change', (e) => {
        const el = e.target;

        if (el === checkAllHead || el === checkAllBar) {
            const on = !!el.checked;
            [...root.querySelectorAll('input.trash-check')].forEach(cb => {
                cb.checked = on;
                const it = { type: cb.dataset.type, id: Number(cb.dataset.id) };
                const k = `${it.type}:${it.id}`;
                if (on) state.selected.set(k, it);
                else state.selected.delete(k);
            });
            updateBulkUI();
            return;
        }

        if (el?.classList?.contains('trash-check')) {
            const it = { type: el.dataset.type, id: Number(el.dataset.id) };
            const k = `${it.type}:${it.id}`;
            if (el.checked) state.selected.set(k, it);
            else state.selected.delete(k);
            updateBulkUI();
        }
    });

    // single actions
    root.addEventListener('click', async (e) => {
        const btn = e.target?.closest?.('[data-action]');
        if (!btn) return;

        const action = btn.dataset.action;
        const type = btn.dataset.type;
        const id = Number(btn.dataset.id);

        if (!type || !id) return;

        try {
            if (action === 'restore') {
                if (!confirm('Geri yüklensin mi?')) return;
                await postJson(bulkRestoreUrl, { items: [{ type, id }] });
                state.selected.delete(`${type}:${id}`);
                notify('success', 'Geri yükleme tamam');
                load();
            }

            if (action === 'force-delete') {
                if (!confirm('KALICI silinecek. Emin misin?')) return;
                await postJson(bulkForceUrl, { items: [{ type, id }] });
                state.selected.delete(`${type}:${id}`);
                notify('success', 'Kalıcı silindi');
                load();
            }
        } catch (err) {
            notify('error', err?.message || 'İşlem başarısız');
        }
    });

    // bulk buttons
    btnRes?.addEventListener('click', async () => {
        const items = [...state.selected.values()];
        if (!items.length) return;
        if (!confirm(`${items.length} kayıt geri yüklensin mi?`)) return;

        try {
            const j = await postJson(bulkRestoreUrl, { items });
            state.selected.clear();
            notify('success', `Geri yükleme: ${j.done || 0}`);
            load();
        } catch (e) {
            notify('error', e?.message || 'Geri yükleme başarısız');
        }
    });

    btnForce?.addEventListener('click', async () => {
        const items = [...state.selected.values()];
        if (!items.length) return;
        if (!confirm(`${items.length} kayıt KALICI silinecek. Emin misin?`)) return;

        try {
            const j = await postJson(bulkForceUrl, { items });
            state.selected.clear();

            const done = j.done || 0;
            const failed = Array.isArray(j.failed) ? j.failed : [];

            if (failed.length) {
                const sample = failed.slice(0, 3).map(x => `${x.type}#${x.id}: ${x.reason || 'bloklandı'}`).join(' | ');
                notify('error', `Kalıcı silme: ${done} başarılı, ${failed.length} bloklandı. ${sample}`);
            } else {
                notify('success', `Kalıcı silme: ${done}`);
            }

            load();
        } catch (e) {
            notify('error', e?.message || 'Kalıcı silme başarısız');
        }
    });

    // boot
    initPerSelect();
    load();
}
