// resources/js/admin/pages/projects/index.js
let ac = null;
let popEl = null;

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function notify(type, text) {
    if (window.KTNotify?.show) {
        window.KTNotify.show({ type, message: text, placement: 'top-end', duration: 1800 });
        return;
    }
    if (window.Swal?.mixin) {
        window.Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true,
        }).fire({ icon: type === 'error' ? 'error' : 'success', title: text });
        return;
    }
    console.log(type.toUpperCase() + ': ' + text);
}

function createPopover() {
    const el = document.createElement('div');
    el.style.position = 'fixed';
    el.style.zIndex = 9999;
    el.style.display = 'none';
    el.className = 'kt-card p-2 shadow-lg';
    el.innerHTML = `<img src="" style="width:220px;height:220px;object-fit:cover;border-radius:12px;">`;
    document.body.appendChild(el);
    return el;
}
function showImgPopover(popEl, anchor, imgUrl) {
    const img = popEl.querySelector('img');
    img.src = imgUrl;

    const r = anchor.getBoundingClientRect();
    const top = Math.min(window.innerHeight - 240, Math.max(10, r.top - 10));
    const left = Math.min(window.innerWidth - 240, Math.max(10, r.right + 12));

    popEl.style.top = top + 'px';
    popEl.style.left = left + 'px';
    popEl.style.display = 'block';
}
function hideImgPopover(popEl) { popEl.style.display = 'none'; }

function postJson(url, body) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(body),
    }).then(async (res) => {
        const j = await res.json().catch(() => ({}));
        if (!res.ok || j?.ok === false) throw new Error(j?.error?.message || 'İşlem başarısız');
        return j;
    });
}

function renderPagination(api, host) {
    if (!host || !api) return;

    const info = api.page.info();
    const pages = info.pages;
    const page = info.page;

    host.innerHTML = '';
    if (pages <= 1) return;

    const makeBtn = (label, targetPage, disabled = false, active = false) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = active ? 'kt-btn kt-btn-sm kt-btn-primary' : 'kt-btn kt-btn-sm kt-btn-light';
        if (disabled) btn.disabled = true;
        btn.textContent = label;
        btn.addEventListener('click', () => api.page(targetPage).draw('page'));
        return btn;
    };

    host.appendChild(makeBtn('‹', Math.max(0, page - 1), page === 0));

    const start = Math.max(0, page - 2);
    const end = Math.min(pages - 1, page + 2);
    for (let i = start; i <= end; i++) host.appendChild(makeBtn(String(i + 1), i, false, i === page));

    host.appendChild(makeBtn('›', Math.min(pages - 1, page + 1), page === pages - 1));
}

function initPageSizeFromDataset(root) {
    const per = root?.dataset?.perpage ? parseInt(root.dataset.perpage, 10) : 25;
    const sel = root.querySelector('#projectsPageSize');
    if (!sel) return;

    const options = [10, 25, 50, 100];
    sel.innerHTML = options.map(v => `<option value="${v}">${v}</option>`).join('');
    sel.value = String(per);

    sel.addEventListener('change', () => {
        const u = new URL(window.location.href);
        u.searchParams.set('perpage', sel.value);
        window.location.href = u.toString();
    });
}

export default function init({ root }) {
    const tableEl = root.querySelector('#projects_table');
    if (!tableEl) return;

    const mode = root.getAttribute('data-page') === 'projects.trash' ? 'trash' : 'active';

    const per = root?.dataset?.perpage
        ? parseInt(root.dataset.perpage, 10)
        : 25;

    const bulkBar = root.querySelector('#projectsBulkBar');
    const selectedCountEl = root.querySelector('#projectsSelectedCount');

    const checkAll = root.querySelector('#projects_check_all');

    const btnBulkDelete = root.querySelector('#projectsBulkDeleteBtn');
    const btnBulkRestore = root.querySelector('#projectsBulkRestoreBtn');
    const btnBulkForce = root.querySelector('#projectsBulkForceDeleteBtn');

    const selectedIds = new Set();

    function updateBulkUI() {
        const n = selectedIds.size;

        bulkBar?.classList.toggle('hidden', n === 0);
        if (selectedCountEl) selectedCountEl.textContent = String(n);

        if (btnBulkDelete) btnBulkDelete.disabled = n === 0;
        if (btnBulkRestore) btnBulkRestore.disabled = n === 0;
        if (btnBulkForce) btnBulkForce.disabled = n === 0;

        if (checkAll) {
            const boxes = [...root.querySelectorAll('input.projects-check')];
            const checked = boxes.filter(b => b.checked).length;

            checkAll.indeterminate = checked > 0 && checked < boxes.length;
            checkAll.checked = boxes.length > 0 && checked === boxes.length;
        }
    }

    function applySelectionToCurrentPage() {
        root.querySelectorAll('input.projects-check').forEach(cb => {
            cb.checked = selectedIds.has(String(cb.value));
        });
        updateBulkUI();
    }

    // popover delegation
    popEl = createPopover();
    ac = new AbortController();
    const { signal } = ac;

    root.addEventListener('mouseover', (e) => {
        const a = e.target?.closest?.('.js-img-popover');
        if (!a || !root.contains(a)) return;
        const img = a.getAttribute('data-popover-img');
        if (img) showImgPopover(popEl, a, img);
    }, { signal });

    root.addEventListener('mouseout', (e) => {
        const a = e.target?.closest?.('.js-img-popover');
        if (!a || !root.contains(a)) return;
        const rt = e.relatedTarget;
        if (rt && a.contains(rt)) return;
        hideImgPopover(popEl);
    }, { signal });

    // datatable init (same pattern as blog)
    const api = window.initDataTable?.({
        root,
        table: '#projects_table',
        search: '#projectsSearch',
        info: '#projectsInfo',
        pagination: '#projectsPagination',

        pageLength: per,
        lengthMenu: [5, 10, 25, 50],
        order: [[1, 'desc']],
        dom: 't',

        emptyTemplate: '#dt-empty-projects',
        zeroTemplate: '#dt-zero-projects',

        columnDefs: [
            { orderable: false, searchable: false, targets: [0, 4] },
            { className: 'text-right', targets: [3, 4] },
        ],

        onDraw: (dtApi) => {
            renderPagination(dtApi || api, root.querySelector('#projectsPagination'));
            applySelectionToCurrentPage();
        }
    });

    // checkbox selection
    root.addEventListener('change', (e) => {
        const cb = e.target;
        if (!(cb instanceof HTMLInputElement)) return;

        if (cb.classList.contains('projects-check')) {
            const id = String(cb.value || '');
            if (!id) return;

            if (cb.checked) selectedIds.add(id);
            else selectedIds.delete(id);

            updateBulkUI();
            return;
        }

        if (cb.id === 'projects_check_all') {
            const on = !!cb.checked;
            root.querySelectorAll('input.projects-check').forEach(x => {
                x.checked = on;
                const id = String(x.value || '');
                if (!id) return;
                if (on) selectedIds.add(id);
                else selectedIds.delete(id);
            });
            updateBulkUI();
        }
    }, { signal });

    // single row actions (same blog pattern)
    root.addEventListener('click', async (e) => {
        const btn = e.target?.closest?.('[data-action]');
        if (!btn || !root.contains(btn)) return;

        const action = btn.getAttribute('data-action');
        const id = btn.getAttribute('data-id') || btn.closest('tr')?.getAttribute('data-id');
        if (!action || !id) return;

        if (btn.dataset.busy === '1') return;
        btn.dataset.busy = '1';

        try {
            if (action === 'delete') {
                if (!confirm('Bu proje silinsin mi?')) return;

                const res = await fetch(`/admin/projects/${id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                });
                const j = await res.json().catch(() => ({}));
                if (!res.ok || j?.ok === false) throw new Error(j?.error?.message || 'Silme başarısız');

                notify('success', 'Silindi');
                location.reload();
                return;
            }

            if (action === 'restore') {
                if (!confirm('Bu proje geri yüklensin mi?')) return;

                await postJson(`/admin/projects/${id}/restore`, {});
                notify('success', 'Geri yüklendi');
                location.reload();
                return;
            }

            if (action === 'force-delete') {
                if (!confirm('Bu proje KALICI silinecek. Emin misin?')) return;

                const res = await fetch(`/admin/projects/${id}/force`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                });
                const j = await res.json().catch(() => ({}));
                if (!res.ok || j?.ok === false) throw new Error(j?.error?.message || 'Kalıcı silme başarısız');

                notify('success', 'Kalıcı silindi');
                location.reload();
            }
        } catch (err) {
            notify('error', err?.message || 'İşlem başarısız');
            console.error(err);
        } finally {
            btn.dataset.busy = '0';
        }
    }, { signal });

    // bulk actions endpoints -> controller’da yoksa ekleyeceğiz
    btnBulkDelete?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        if (!confirm(`${ids.length} kayıt silinsin mi?`)) return;

        try {
            await postJson('/admin/projects/bulk-delete', { ids });
            notify('success', 'Silindi');
            selectedIds.clear();
            location.reload();
        } catch (e) {
            notify('error', e?.message || 'Silme başarısız');
        } finally {
            updateBulkUI();
        }
    });

    btnBulkRestore?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        if (!confirm(`${ids.length} kayıt geri yüklensin mi?`)) return;

        try {
            await postJson('/admin/projects/bulk-restore', { ids });
            notify('success', 'Geri yüklendi');
            selectedIds.clear();
            location.reload();
        } catch (e) {
            notify('error', e?.message || 'Geri yükleme başarısız');
        } finally {
            updateBulkUI();
        }
    });

    btnBulkForce?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        if (!confirm(`${ids.length} kayıt KALICI silinecek. Emin misin?`)) return;

        try {
            await postJson('/admin/projects/bulk-force-delete', { ids });
            notify('success', 'Kalıcı silindi');
            selectedIds.clear();
            location.reload();
        } catch (e) {
            notify('error', e?.message || 'Kalıcı silme başarısız');
        } finally {
            updateBulkUI();
        }
    });

    // init extras
    initPageSizeFromDataset(root);
    renderPagination(api, root.querySelector('#projectsPagination'));

    window.addEventListener('beforeunload', () => {
        try { ac.abort(); } catch {}
        try { popEl.remove(); } catch {}
    }, { once: true });
}

export function destroy() {
    try { ac?.abort(); } catch {}
    try { popEl?.remove(); } catch {}
    ac = null;
    popEl = null;
}
