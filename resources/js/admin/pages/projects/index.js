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

function createImgPopover() {
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

function postJson(url, body, method = 'POST') {
    return fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(body),
    }).then(async (res) => {
        const j = await res.json().catch(() => ({}));
        if (!res.ok || j?.ok === false) {
            const msg = j?.error?.message || 'İşlem başarısız';
            const err = new Error(msg);
            err.status = res.status;
            err.payload = j;
            throw err;
        }
        return j;
    });
}

// --- status menu ---
const STATUS_OPTIONS = [
    ['appointment_pending', 'Randevu Bekliyor',   'kt-badge kt-badge-sm kt-badge-light-warning'],
    ['appointment_scheduled', 'Randevu Planlandı','kt-badge kt-badge-sm kt-badge-light-primary'],
    ['appointment_done', 'Randevu Tamamlandı',    'kt-badge kt-badge-sm kt-badge-light-success'],
    ['dev_pending', 'Geliştirme Bekliyor',        'kt-badge kt-badge-sm kt-badge-light-warning'],
    ['dev_in_progress', 'Geliştirme Devam',       'kt-badge kt-badge-sm kt-badge-primary'],
    ['delivered', 'Teslim Edildi',                'kt-badge kt-badge-sm kt-badge-info'],
    ['approved', 'Onaylandı',                     'kt-badge kt-badge-sm kt-badge-light-success'],
    ['closed', 'Kapatıldı',                       'kt-badge kt-badge-sm kt-badge-light'],
];

function createStatusPopover() {
    const el = document.createElement('div');
    el.style.position = 'fixed';
    el.style.zIndex = 10000;
    el.style.display = 'none';

    // animasyon için hazır class
    el.className = 'kt-card shadow-lg p-2 w-[260px] transition transform duration-150 ease-out';

    el.innerHTML = `
      <div class="text-xs text-muted-foreground px-2 py-1">Durum seç</div>
      <div class="grid gap-1" data-status-menu></div>
    `;

    document.body.appendChild(el);
    return el;
}

function showStatusPopover(pop, anchor, projectId, currentStatus) {
    const menu = pop.querySelector('[data-status-menu]');
    if (!menu) return;

    menu.innerHTML = STATUS_OPTIONS.map(([key, label, cls]) => {
        const isActive = key === currentStatus;

        const base =
            'w-full flex items-center justify-between gap-2 px-2 py-2 rounded-lg ' +
            'transition duration-150 ease-out ' +
            'hover:bg-muted/40 hover:scale-[1.01] active:scale-[0.99] ' +
            'cursor-pointer ' +
            'focus:outline-none focus-visible:ring-2 focus-visible:ring-border';

        const active = 'bg-muted/40 ring-1 ring-border';

        return `
          <button type="button"
                  class="${base} ${isActive ? active : ''}"
                  data-status-pick="${key}"
                  data-project-id="${projectId}"
                  aria-current="${isActive ? 'true' : 'false'}"
                  ${isActive ? 'data-active="1"' : ''}>
            <span class="${cls}">${label}</span>

            <span class="text-muted-foreground ${isActive ? '' : 'opacity-0'} transition-opacity duration-150">
              <i class="ki-outline ki-check-circle"></i>
            </span>
          </button>
        `;
    }).join('');

    const r = anchor.getBoundingClientRect();
    const top = Math.min(window.innerHeight - 320, Math.max(10, r.bottom + 6));
    const left = Math.min(window.innerWidth - 280, Math.max(10, r.left));

    pop.style.top = top + 'px';
    pop.style.left = left + 'px';

    // açılış animasyonu
    pop.style.opacity = '0';
    pop.style.transform = 'translateY(-4px)';
    pop.style.display = 'block';

    requestAnimationFrame(() => {
        pop.style.opacity = '1';
        pop.style.transform = 'translateY(0)';

        // seçili olana focus (seçili belli olsun)
        const activeBtn = pop.querySelector('[data-active="1"]');
        activeBtn?.focus?.();
    });
}

function hideStatusPopover(pop) {
    if (!pop) return;
    if (pop.style.display !== 'block') return;

    // kapanış animasyonu
    pop.style.opacity = '0';
    pop.style.transform = 'translateY(-4px)';
    window.setTimeout(() => {
        pop.style.display = 'none';
    }, 120);
}

// --- pagination ---
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

    const per = root?.dataset?.perpage ? parseInt(root.dataset.perpage, 10) : 25;

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

    ac = new AbortController();
    const { signal } = ac;

    // popover delegation (image preview)
    popEl = createImgPopover();
    const statusPop = createStatusPopover();

    // popover click (status select)
    statusPop.addEventListener('click', async (e) => {
        const pick = e.target?.closest?.('[data-status-pick]');
        if (!pick) return;

        const status = pick.getAttribute('data-status-pick');
        const pid = pick.getAttribute('data-project-id');
        if (!pid || !status) return;

        try {
            const j = await postJson(`/admin/projects/${pid}/status`, { status }, 'PATCH');

            const btn = root.querySelector(`.js-status-trigger[data-project-id="${pid}"]`);
            if (btn) {
                btn.setAttribute('data-status', j.data.status);
                btn.className = `${j.data.status_badge} js-status-trigger`;
                btn.innerHTML = `${j.data.status_label} <i class="ki-outline ki-down ml-1"></i>`;
            }

            notify('success', 'Durum güncellendi');
            hideStatusPopover(statusPop);
        } catch (err) {
            notify('error', err?.message || 'Durum güncellenemedi');
        }
    }, { signal });

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

    // datatable init
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

        // ⚠️ targets: senin gerçek kolonlarına göre gerekirse düzelt
        columnDefs: [
            { orderable: false, searchable: false, targets: [0, 6] },
            { className: 'text-right', targets: [5, 6] },
            { className: 'text-center', targets: [4] },
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

    // close status popover on outside click
    document.addEventListener('click', (e) => {
        if (statusPop.style.display === 'block') {
            const inside = statusPop.contains(e.target);
            const trig = e.target?.closest?.('.js-status-trigger');
            if (!inside && !trig) hideStatusPopover(statusPop);
        }
    }, { signal });

    root.addEventListener('click', async (e) => {
        // open status menu
        const trig = e.target?.closest?.('.js-status-trigger');
        if (trig && root.contains(trig)) {
            const pid = trig.getAttribute('data-project-id');
            const st = trig.getAttribute('data-status') || 'appointment_pending';

            // önce kapat (aynı anda iki popover açık kalmasın)
            hideStatusPopover(statusPop);
            showStatusPopover(statusPop, trig, pid, st);
            return;
        }

        // row actions
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

    root.addEventListener('change', async (e) => {
        const t = e.target;

        const ft = t?.closest?.('.js-featured-toggle');
        if (ft && root.contains(ft)) {
            const pid = ft.getAttribute('data-project-id');
            if (!pid) return;

            const on = !!ft.checked;
            ft.disabled = true;

            try {
                await postJson(`/admin/projects/${pid}/featured`, { is_featured: on }, 'PATCH');
                updateFeaturedBadge(ft, on);
                notify('success', on ? 'Anasayfa için işaretlendi' : 'Anasayfadan kaldırıldı');
            } catch (err) {
                ft.checked = !on;
                updateFeaturedBadge(ft, !on);
                notify('error', err?.message || 'İşlem başarısız');
            } finally {
                ft.disabled = false;
            }
        }
    }, { signal });

    function updateFeaturedBadge(ft, isOn) {
        const wrap = ft.closest('.flex.items-center.gap-2') || ft.parentElement;
        const badge = wrap?.querySelector?.('.js-featured-badge');
        if (!badge) return;

        if (isOn) {
            badge.hidden = false;
            requestAnimationFrame(() => {
                badge.classList.remove('opacity-0');
                badge.classList.add('opacity-100');
            });
        } else {
            badge.classList.remove('opacity-100');
            badge.classList.add('opacity-0');

            window.setTimeout(() => {
                if (!ft.checked) badge.hidden = true;
            }, 200);
        }
    }

    // ✅ bulk actions (NEW ROUTES)
    btnBulkDelete?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        if (!confirm(`${ids.length} kayıt silinsin mi?`)) return;

        try {
            await postJson('/admin/projects/bulk-destroy', { ids });
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
            await postJson('/admin/projects/bulk-force-destroy', { ids });
            notify('success', 'Kalıcı silindi');
            selectedIds.clear();
            location.reload();
        } catch (e) {
            notify('error', e?.message || 'Kalıcı silme başarısız');
        } finally {
            updateBulkUI();
        }
    });

    initPageSizeFromDataset(root);
    renderPagination(api, root.querySelector('#projectsPagination'));

    window.addEventListener('beforeunload', () => {
        try { ac.abort(); } catch {}
        try { popEl.remove(); } catch {}
        try { statusPop.remove(); } catch {}
    }, { once: true });
}

export function destroy() {
    try { ac?.abort(); } catch {}
    try { popEl?.remove(); } catch {}
    ac = null;
    popEl = null;
}
