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

// -------- Image popover (single instance)
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

function hideImgPopover(popEl) {
    popEl.style.display = 'none';
}

// -------- Toggle publish
async function togglePublish(input) {
    const url = input.dataset.url;
    const row = input.closest('tr');
    const badgeWrap = row?.querySelector('.js-badge') ?? null;
    const publishedAt = row?.querySelector('.js-published-at') ?? null;

    const nextVal = input.checked ? 1 : 0;
    const rollback = !input.checked;

    input.disabled = true;
    row?.classList.add('opacity-50');

    try {
        const res = await fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ is_published: nextVal }),
        });

        if (!res.ok) throw new Error('HTTP ' + res.status);

        const data = await res.json();
        if (!data?.ok) throw new Error('Invalid response');

        if (badgeWrap && data.badge_html) badgeWrap.innerHTML = data.badge_html;

        if (publishedAt) {
            publishedAt.textContent = (data.is_published && data.published_at)
                ? ('Yayın Tarihi: ' + data.published_at)
                : '';
        }

        notify('success', data.is_published ? 'Yayınlandı' : 'Taslağa alındı');
    } catch (e) {
        input.checked = rollback;

        const msg =
            String(e.message).includes('HTTP 403') ? 'Yetkin yok (403).' :
                String(e.message).includes('HTTP 419') ? 'Oturum/CSRF hatası (419).' :
                    'Durum güncellenemedi.';

        notify('error', msg);
        console.error(e);
    } finally {
        input.disabled = false;
        row?.classList.remove('opacity-50');
    }
}

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

// -------- Custom pagination
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

    const selPage = root.querySelector('#blogPageSize');
    const form = root.querySelector('form[data-blog-filter-form="true"]');
    if (!selPage || !form) return;

    const options = [10, 25, 50, 100];
    selPage.innerHTML = options.map(v => `<option value="${v}">${v}</option>`).join('');
    selPage.value = String(per);

    selPage.addEventListener('change', () => {
        let hidden = form.querySelector('input[name="perpage"]');
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'perpage';
            form.appendChild(hidden);
        }
        hidden.value = selPage.value;
        form.requestSubmit();
    }, { once: false });
}

function initCategoryAutoSubmit(root) {
    const sel = root.querySelector('#blogCategoryFilter');
    if (!sel) return;
    sel.addEventListener('change', () => sel.closest('form')?.requestSubmit());
}

// ====== default page init (registry-friendly)
export default function init({ root }) {
    // 0) guard
    const tableEl = root.querySelector('#blog_table');
    if (!tableEl) return;

    const per = root?.dataset?.perpage
        ? parseInt(root.dataset.perpage, 10)
        : 25;

    const bulkBar = root.querySelector('#blogBulkBar');
    const selectedCountEl = root.querySelector('#blogSelectedCount');

    const checkAll = root.querySelector('#blog_check_all');
    const btnBulkDelete = root.querySelector('#blogBulkDeleteBtn');
    const btnBulkRestore = root.querySelector('#blogBulkRestoreBtn');
    const btnBulkForce = root.querySelector('#blogBulkForceDeleteBtn');

    // DataTables paging’de seçim kaybolmasın diye global set
    const selectedIds = new Set();

    function updateBulkUI() {
        const n = selectedIds.size;

        if (bulkBar) bulkBar.classList.toggle('hidden', n === 0);
        if (selectedCountEl) selectedCountEl.textContent = String(n);

        if (btnBulkDelete) btnBulkDelete.disabled = n === 0;
        if (btnBulkRestore) btnBulkRestore.disabled = n === 0;
        if (btnBulkForce) btnBulkForce.disabled = n === 0;

        // checkAll indeterminate
        if (checkAll) {
            const boxes = [...root.querySelectorAll('input.blog-check')];
            const checked = boxes.filter(b => b.checked).length;

            checkAll.indeterminate = checked > 0 && checked < boxes.length;
            checkAll.checked = boxes.length > 0 && checked === boxes.length;
        }
    }

    function applySelectionToCurrentPage() {
        root.querySelectorAll('input.blog-check').forEach(cb => {
            cb.checked = selectedIds.has(String(cb.value));
        });
        updateBulkUI();
    }


    // 1) popover instance + delegation
    popEl = createPopover();
    ac = new AbortController();
    const { signal } = ac;

    // listeners
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

    root.addEventListener('change', (e) => {
        const cb = e.target;
        if (!(cb instanceof HTMLInputElement)) return;
        if (!cb.classList.contains('js-publish-toggle')) return;
        togglePublish(cb);
    }, { signal });
    // 2) DataTable init
    const api = window.initDataTable?.({
        root,
        table: '#blog_table',
        search: '#blogSearch',
        info: '#blogInfo',
        pagination: '#blogPagination',

        pageLength: per,
        lengthMenu: [5, 10, 25, 50],
        order: [[1, 'desc']],
        dom: 't',

        emptyTemplate: '#dt-empty-blog',
        zeroTemplate: '#dt-zero-blog',

        columnDefs: [
            { orderable: false, searchable: false, targets: [0] }, // checkbox
            { className: 'text-right', targets: [6, 7] },
        ],

        onDraw: (dtApi) => {
            const host = root.querySelector('#blogPagination');
            renderPagination(dtApi || api, host);

            // ✅ sayfa değişince checkboxlar doğru işaretlensin
            applySelectionToCurrentPage();
        }
    });
    // Checkbox change (delegation)
    root.addEventListener('change', (e) => {
        const cb = e.target;
        if (!(cb instanceof HTMLInputElement)) return;

        // tek tek seçim
        if (cb.classList.contains('blog-check')) {
            const id = String(cb.value || '');
            if (!id) return;
            if (cb.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            updateBulkUI();
            return;
        }

        // check all
        if (cb.id === 'blog_check_all') {
            const on = !!cb.checked;
            root.querySelectorAll('input.blog-check').forEach(x => {
                x.checked = on;
                const id = String(x.value || '');
                if (!id) return;
                if (on) selectedIds.add(id);
                else selectedIds.delete(id);
            });
            updateBulkUI();
        }
    }, { signal });
    // --- Single row actions (delete / restore / force-delete) ---
    root.addEventListener('click', async (e) => {
        const btn = e.target?.closest?.('[data-action]');
        if (!btn || !root.contains(btn)) return;

        const action = btn.getAttribute('data-action');
        const id = btn.getAttribute('data-id');
        if (!action || !id) return;

        // Bulk bar butonları da data-action kullanmıyor, sorun yok.
        // double click koruması
        if (btn.dataset.busy === '1') return;
        btn.dataset.busy = '1';

        try {
            if (action === 'delete') {
                if (!confirm('Bu yazı silinsin mi?')) return;

                const res = await fetch(`/admin/blog/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });
                const j = await res.json().catch(() => ({}));
                if (!res.ok || j?.ok === false) throw new Error(j?.error?.message || 'Silme başarısız');

                notify('success', 'Silindi');
                location.reload();
                return;
            }

            if (action === 'restore') {
                if (!confirm('Bu yazı geri yüklensin mi?')) return;

                await postJson(`/admin/blog/${id}/restore`, {});
                notify('success', 'Geri yüklendi');
                location.reload();
                return;
            }

            if (action === 'force-delete') {
                if (!confirm('Bu yazı KALICI silinecek. Emin misin?')) return;

                const res = await fetch(`/admin/blog/${id}/force`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });
                const j = await res.json().catch(() => ({}));
                if (!res.ok || j?.ok === false) throw new Error(j?.error?.message || 'Kalıcı silme başarısız');

                notify('success', 'Kalıcı silindi');
                location.reload();
                return;
            }
        } catch (err) {
            notify('error', err?.message || 'İşlem başarısız');
            console.error(err);
        } finally {
            btn.dataset.busy = '0';
        }
    }, { signal });

    // Bulk actions
    btnBulkDelete?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        if (!confirm(`${ids.length} kayıt silinsin mi?`)) return;

        try {
            await postJson('/admin/blog/bulk-delete', { ids });
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
            await postJson('/admin/blog/bulk-restore', { ids });
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
            await postJson('/admin/blog/bulk-force-delete', { ids });
            notify('success', 'Kalıcı silindi');
            selectedIds.clear();
            location.reload();
        } catch (e) {
            notify('error', e?.message || 'Kalıcı silme başarısız');
        } finally {
            updateBulkUI();
        }
    });


    // 3) First render extras
    initCategoryAutoSubmit(root);
    initPageSizeFromDataset(root);
    renderPagination(api, root.querySelector('#blogPagination'));

    // 4) Optional cleanup hook (MPA’da şart değil ama düzgün)
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
