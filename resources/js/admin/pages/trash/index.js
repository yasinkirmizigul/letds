function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function jsonReq(url, method = 'GET', body = null, signal = null) {
    const res = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken() ? {'X-CSRF-TOKEN': csrfToken()} : {}),
            ...(body ? {'Content-Type': 'application/json'} : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
        credentials: 'same-origin',
        signal,
    });

    const j = await res.json().catch(() => ({}));
    return {res, j};
}

function esc(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

const bound = new WeakMap(); // root->Set(keys)
function markBound(root, key) {
    let s = bound.get(root);
    if (!s) {
        s = new Set();
        bound.set(root, s);
    }
    if (s.has(key)) return true;
    s.add(key);
    return false;
}

/**
 * ✅ KTUI modal (dependency-free) — sayfayı bozmadan kullan.
 */
let __trashModalEl = null;

function ensureTrashModal() {
    if (__trashModalEl) return __trashModalEl;

    const el = document.createElement('div');
    el.id = 'trashNoticeModal';
    el.className = 'fixed inset-0 z-[9999] hidden';
    el.innerHTML = `
<div class="absolute inset-0 bg-black/50"></div>
<div class="absolute inset-0 flex items-center justify-center p-4">
  <div class="kt-card w-full max-w-2xl shadow-lg rounded-2xl overflow-hidden">
    <div class="kt-card-header py-4 px-5 flex items-center justify-between">
      <div class="flex items-center gap-3 min-w-0">
        <span class="size-2.5 rounded-full bg-primary/80 shrink-0" data-tone-dot></span>
        <h3 class="kt-card-title text-base font-semibold truncate" data-title>Bilgi</h3>
      </div>
      <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-close>
        <i class="ki-outline ki-cross"></i>
      </button>
    </div>
    <div class="kt-card-body px-5 py-4 text-sm text-foreground" data-body></div>
    <div class="kt-card-footer px-5 py-4 flex items-center justify-end gap-2">
      <button type="button" class="kt-btn kt-btn-light" data-close>Kapat</button>
    </div>
  </div>
</div>
`;
    document.body.appendChild(el);

    const close = () => {
        el.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    el.addEventListener('click', (e) => {
        const t = e.target;
        if (t?.closest?.('[data-close]')) close();
        if (t === el.firstElementChild) close(); // backdrop
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !el.classList.contains('hidden')) close();
    });

    __trashModalEl = el;
    return el;
}

function showTrashModal({title = 'Bilgi', html = '', tone = 'info'} = {}) {
    const el = ensureTrashModal();

    const titleEl = el.querySelector('[data-title]');
    const bodyEl = el.querySelector('[data-body]');
    const dotEl = el.querySelector('[data-tone-dot]');

    if (titleEl) titleEl.textContent = title;
    if (bodyEl) bodyEl.innerHTML = html;

    if (dotEl) {
        dotEl.classList.remove('bg-primary/80', 'bg-green-500/80', 'bg-red-500/80', 'bg-yellow-500/80');
        if (tone === 'success') dotEl.classList.add('bg-green-500/80');
        else if (tone === 'danger' || tone === 'error') dotEl.classList.add('bg-red-500/80');
        else if (tone === 'warning') dotEl.classList.add('bg-yellow-500/80');
        else dotEl.classList.add('bg-primary/80');
    }

    el.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function buildErrorMessage(j, fallback = 'İşlem başarısız') {
    if (!j) return fallback;
    let msg = j.message || j.error?.message || fallback;

    if (j.usage?.summary) {
        msg += '\nKullanım: ' + j.usage.summary;
    }

    return msg;
}

function showUsageModal(j, fallbackTitle = 'İşlem engellendi') {
    const msg = buildErrorMessage(j, fallbackTitle);

    let html = `<div class="whitespace-pre-line">${esc(msg).replace(/\n/g, '<br>')}</div>`;

    const details = j?.usage?.details;
    if (details && typeof details === 'object' && Object.keys(details).length) {
        const rows = Object.entries(details)
            .map(([k, v]) => `
<tr class="border-t border-border">
  <td class="py-2 pr-3 text-muted-foreground">${esc(k)}</td>
  <td class="py-2 text-end font-medium">${esc(v)}</td>
</tr>
            `.trim())
            .join('');

        html += `
<div class="mt-4">
  <div class="text-sm font-medium mb-2">Kullanım Detayı</div>
  <div class="kt-card kt-card-border">
    <div class="kt-card-body p-0">
      <table class="w-full text-sm">
        <tbody>${rows}</tbody>
      </table>
    </div>
  </div>
</div>
        `.trim();
    }

    showTrashModal({title: fallbackTitle, html, tone: 'warning'});
}

export default function init(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;

    // 🔥 KRİTİK FIX:
    // ctx.root bazen zaten page container oluyor.
    const pageEl = (root?.matches?.('[data-page="trash.index"]'))
        ? root
        : root.querySelector?.('[data-page="trash.index"]');

    if (!pageEl) return;

    // ---- Blade id’leri (trash/index.blade.php ile uyumlu)
    const tbodyEl = pageEl.querySelector('#trashTbody');
    const infoEl = pageEl.querySelector('#trashInfo');
    const pagEl = pageEl.querySelector('#trashPagination');

    const qInput = pageEl.querySelector('#trashSearch');
    const typeSel = pageEl.querySelector('#trashType');
    const perSel = pageEl.querySelector('#trashPageSize');

    const bulkBar = pageEl.querySelector('#trashBulkBar');
    const checkAllHead = pageEl.querySelector('#trash_check_all_head');
    const checkAllBar = pageEl.querySelector('#trash_check_all');
    const selectedCountEl = pageEl.querySelector('#trashSelectedCount');
    const bulkRestoreBtn = pageEl.querySelector('#trashBulkRestoreBtn');
    const bulkForceBtn = pageEl.querySelector('#trashBulkForceDeleteBtn');

    const tplEmpty = pageEl.querySelector('#dt-empty-trash');
    const tplZero = pageEl.querySelector('#dt-zero-trash');

    if (!tbodyEl) return;

    // ---- URL’ler Blade’den gelsin (Blade’de data-* var)
    const URLS = {
        list: pageEl.dataset.listUrl || '/admin/trash/list',
        bulkRestore: pageEl.dataset.bulkRestoreUrl || '/admin/trash/bulk-restore',
        bulkForce: pageEl.dataset.bulkForceDeleteUrl || '/admin/trash/bulk-force-delete',
    };

    // ---- state
    const state = {
        q: '',
        type: (typeSel?.value || 'all'),
        page: 1,
        perpage: Number(pageEl.dataset.perpage || perSel?.value || 25) || 25,
        last_page: 1,
        total: 0,
    };

    const selected = new Set();
    let debounceTimer = null;

    function setInfo(text) {
        if (!infoEl) return;
        infoEl.textContent = text || '';
    }

    function setBulkUI() {
        const c = selected.size;

        if (selectedCountEl) selectedCountEl.textContent = String(c);
        if (bulkRestoreBtn) bulkRestoreBtn.disabled = c === 0;
        if (bulkForceBtn) bulkForceBtn.disabled = c === 0;

        if (bulkBar) {
            if (c > 0) bulkBar.classList.remove('hidden');
            else bulkBar.classList.add('hidden');
        }

        if (checkAllHead) {
            const totalCbs = tbodyEl.querySelectorAll('input[data-act="chk"]').length;
            checkAllHead.checked = c > 0 && totalCbs > 0 && totalCbs === c;
        }
        if (checkAllBar) checkAllBar.checked = checkAllHead?.checked || false;
    }

    function cloneTpl(tpl) {
        if (!tpl) return '';
        const node = tpl.content?.firstElementChild;
        return node ? node.outerHTML : '';
    }

    function renderPagination(meta) {
        if (!pagEl) return;

        const current = Number(meta.current_page || 1) || 1;
        const last = Number(meta.last_page || 1) || 1;

        if (last <= 1) {
            pagEl.innerHTML = '';
            return;
        }

        const btn = (p, label, disabled = false, active = false) => `
            <button type="button"
                    class="kt-btn kt-btn-sm ${active ? 'kt-btn-primary' : 'kt-btn-light'}"
                    data-page="${p}"
                    ${disabled ? 'disabled' : ''}>${label}</button>
        `;

        const parts = [];
        parts.push(btn(current - 1, '‹', current <= 1));

        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);

        if (start > 1) parts.push(btn(1, '1', false, current === 1));
        if (start > 2) parts.push(`<span class="px-2 text-muted-foreground">…</span>`);

        for (let p = start; p <= end; p++) parts.push(btn(p, String(p), false, p === current));

        if (end < last - 1) parts.push(`<span class="px-2 text-muted-foreground">…</span>`);
        if (end < last) parts.push(btn(last, String(last), false, current === last));

        parts.push(btn(current + 1, '›', current >= last));

        pagEl.innerHTML = `<div class="flex items-center gap-1 justify-center">${parts.join('')}</div>`;
    }

    function rowHtml(item) {
        const id = esc(item.id);
        const type = esc(item.type || '-');
        const title = esc(item.title || item.name || '-');
        const deletedAt = esc(item.deleted_at || '');

        const restoreUrl = esc(item.restore_url || '');
        const forceUrl = esc(item.force_url || '');

        const restoreDisabled = restoreUrl ? '' : 'disabled';
        const forceDisabled = forceUrl ? '' : 'disabled';

        const checked = selected.has(String(item.id)) ? 'checked' : '';

        return `
            <tr data-row-id="${id}">
                <td class="w-[55px]">
                    <input type="checkbox"
                       class="kt-checkbox kt-checkbox-sm"
                       data-act="chk"
                       data-id="${id}"
                       data-type="${esc(item.type || '')}"
                       ${checked}>
                </td>

                <td class="min-w-[160px]">
                    <span class="kt-badge kt-badge-light">${type}</span>
                </td>

                <td class="min-w-[320px]">
                    <div class="font-medium">${title}</div>
                    <div class="text-xs text-muted-foreground">#${id}</div>
                </td>

                <td class="min-w-[180px]">
                    <span class="text-sm text-muted-foreground">${deletedAt}</span>
                </td>

                <td class="w-[160px] text-end">
                    <div class="flex items-center justify-end gap-2">
                        <button type="button"
                                class="kt-btn kt-btn-sm kt-btn-light"
                                data-act="restore"
                                data-id="${id}"
                                data-url="${restoreUrl}"
                                ${restoreDisabled}>
                            <i class="ki-outline ki-arrow-circle-left"></i>
                        </button>

                        <button type="button"
                                class="kt-btn kt-btn-sm kt-btn-danger"
                                data-act="force"
                                data-id="${id}"
                                data-url="${forceUrl}"
                                ${forceDisabled}>
                            <i class="ki-outline ki-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    function fillPerPageSelect() {
        if (!perSel) return;

        const options = [10, 25, 50, 100];
        perSel.innerHTML = options.map((n) => {
            const sel = n === state.perpage ? 'selected' : '';
            return `<option value="${n}" ${sel}>${n}</option>`;
        }).join('');
    }

    async function fetchList() {
        const type = (state.type === 'all') ? null : state.type;

        const qs = new URLSearchParams({
            q: state.q || '',
            page: String(state.page),
            perpage: String(state.perpage),
        });

        if (type) qs.set('type', type);

        const {res, j} = await jsonReq(`${URLS.list}?${qs.toString()}`, 'GET', null, signal);

        if (!res.ok || !j?.ok) {
            tbodyEl.innerHTML = `
                <tr>
                    <td colspan="5" class="py-10 text-center text-muted-foreground">
                        Liste alınamadı.
                    </td>
                </tr>
            `;
            setInfo('');
            renderPagination({current_page: 1, last_page: 1});
            selected.clear();
            setBulkUI();
            return;
        }

        const items = Array.isArray(j.data) ? j.data : [];
        const meta = j.meta || {};

        state.last_page = Number(meta.last_page || 1) || 1;
        state.total = Number(meta.total || 0) || 0;

        const visibleIds = new Set(items.map((x) => String(x?.id)));
        for (const id of Array.from(selected)) {
            if (!visibleIds.has(id)) selected.delete(id);
        }

        if (!items.length) {
            const isSearch = Boolean(state.q) || Boolean(type);
            tbodyEl.innerHTML = isSearch ? cloneTpl(tplZero) : cloneTpl(tplEmpty);
        } else {
            tbodyEl.innerHTML = items.map(rowHtml).join('');
        }

        if (meta && typeof meta.from !== 'undefined') {
            setInfo(`${meta.from || 0}-${meta.to || 0} / ${meta.total || 0}`);
        } else {
            setInfo(state.total ? `Toplam: ${state.total}` : '');
        }

        renderPagination(meta);
        setBulkUI();
    }

    async function doSingleAction(url, method, confirmText) {
        if (!url) return;
        if (!confirm(confirmText)) return;

        const {res, j} = await jsonReq(url, method, (method === 'POST' ? {} : null), signal);

        if (!res.ok || !j?.ok) {
            // ✅ alert yerine modal (usage varsa tablo da gösterir)
            if (j?.usage?.summary || (j?.usage?.details && Object.keys(j.usage.details).length)) {
                showUsageModal(j, 'İşlem engellendi');
            } else {
                showTrashModal({
                    title: 'İşlem başarısız',
                    html: `<div class="whitespace-pre-line">${esc(buildErrorMessage(j)).replace(/\n/g, '<br>')}</div>`,
                    tone: 'danger'
                });
            }
            return;
        }

        await fetchList();
    }

    async function doBulk(url, confirmText) {
        const items = Array.from(selected)
            .map((k) => {
                const [type, id] = String(k).split('|');
                const nid = Number(id);
                if (!type || !Number.isFinite(nid)) return null;
                return {type, id: nid, key: `${type}|${nid}`};
            })
            .filter(Boolean);

        if (!items.length) return;
        if (!confirm(confirmText)) return;

        const backup = {
            keys: new Set(selected),
            rows: [],
        };

        const keyToRow = (key) => {
            const [t, id] = String(key).split('|');
            return tbodyEl.querySelector(`input[data-act="chk"][data-id="${CSS.escape(id)}"][data-type="${CSS.escape(t)}"]`)?.closest('tr');
        };

        backup.keys.forEach((key) => {
            const row = keyToRow(key);
            if (!row) return;
            backup.rows.push({key, node: row, parent: row.parentNode, nextSibling: row.nextSibling});
            row.remove();
        });

        selected.clear();
        setBulkUI();

        try {
            const payload = {items: items.map(({type, id}) => ({type, id}))};
            const {res, j} = await jsonReq(url, 'POST', payload, signal);

            if (!res.ok || !j?.ok) throw new Error(buildErrorMessage(j));

            // ✅ alert yerine modal + özet
            let html = `<div class="font-medium">${esc(`${j.done || 0} kayıt işlendi.`)}</div>`;

            if (j.failed?.length) {
                const list = j.failed.slice(0, 8).map(f => `
                    <li class="flex items-start justify-between gap-3">
                        <span class="text-muted-foreground">${esc(`${f.type}#${f.id}`)}</span>
                        <span class="text-end">${esc(f.reason || 'Hata')}</span>
                    </li>
                `).join('');
                html += `
                    <div class="mt-3">
                        <div class="text-sm font-medium mb-2">${esc(`${j.failed.length} kayıt engellendi`)}</div>
                        <ul class="space-y-1 text-sm">${list}</ul>
                    </div>
                `;
            }

            if (j.denied?.length) {
                html += `<div class="mt-3 text-sm text-muted-foreground">${esc(`${j.denied.length} kayıt için yetki yok.`)}</div>`;
            }

            showTrashModal({title: 'Toplu işlem sonucu', html, tone: 'info'});
            await fetchList();

        } catch (e) {
            if (backup.rows.length) {
                tbodyEl.innerHTML = '';
                backup.rows.forEach(({node, parent, nextSibling}) => {
                    if (nextSibling) parent.insertBefore(node, nextSibling);
                    else parent.appendChild(node);
                });

                selected.clear();
                backup.keys.forEach((k) => selected.add(k));
                setBulkUI();
            }

            showTrashModal({
                title: 'İşlem başarısız',
                html: `<div class="whitespace-pre-line">${esc(e?.message || 'İşlem başarısız').replace(/\n/g, '<br>')}</div>`,
                tone: 'danger'
            });
        }
    }

    // ---- bindings
    fillPerPageSelect();

    if (qInput && !markBound(pageEl, 'q')) {
        qInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                state.q = (qInput.value || '').trim();
                state.page = 1;
                fetchList();
            }, 250);
        }, {signal});

        ctx.cleanup?.(() => {
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = null;
        });
    }

    if (typeSel && !markBound(pageEl, 'type')) {
        typeSel.addEventListener('change', () => {
            state.type = typeSel.value || 'all';
            state.page = 1;
            fetchList();
        }, {signal});
    }

    if (perSel && !markBound(pageEl, 'per')) {
        perSel.addEventListener('change', () => {
            state.perpage = Number(perSel.value || 25) || 25;
            state.page = 1;
            fetchList();
        }, {signal});
    }

    if (pagEl && !markBound(pageEl, 'pagination')) {
        pagEl.addEventListener('click', (e) => {
            const b = e.target.closest('button[data-page]');
            if (!b) return;

            const p = Number(b.getAttribute('data-page') || 1);
            if (!Number.isFinite(p) || p < 1) return;

            if (p === state.page) return;
            state.page = p;
            fetchList();
        }, {signal});
    }

    if (!markBound(pageEl, 'tbody-actions')) {
        tbodyEl.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-act]');
            if (btn) {
                const act = btn.getAttribute('data-act');
                const url = btn.getAttribute('data-url') || '';

                if (act === 'restore') doSingleAction(url, 'POST', 'Bu kayıt geri yüklensin mi?');
                if (act === 'force') doSingleAction(url, 'DELETE', 'Bu kayıt KALICI silinecek. Emin misin?');
                return;
            }

            const chk = e.target.closest('input[type="checkbox"][data-act="chk"]');
            if (chk) {
                const id = chk.getAttribute('data-id');
                const type = chk.getAttribute('data-type') || '';
                if (!id || !type) return;

                const key = `${type}|${id}`;

                if (chk.checked) selected.add(key);
                else selected.delete(key);

                setBulkUI();
            }
        }, {signal});
    }

    const toggleAll = (checked) => {
        tbodyEl.querySelectorAll('input[type="checkbox"][data-act="chk"]').forEach((c) => {
            c.checked = checked;
            const id = c.getAttribute('data-id');
            const type = c.getAttribute('data-type') || '';
            if (!id || !type) return;

            const key = `${type}|${id}`;

            if (checked) selected.add(key);
            else selected.delete(key);

        });
        setBulkUI();
    };

    if (checkAllHead && !markBound(pageEl, 'checkall-head')) {
        checkAllHead.addEventListener('change', () => toggleAll(checkAllHead.checked), {signal});
    }
    if (checkAllBar && !markBound(pageEl, 'checkall-bar')) {
        checkAllBar.addEventListener('change', () => toggleAll(checkAllBar.checked), {signal});
    }

    if (bulkRestoreBtn && !markBound(pageEl, 'bulk-restore')) {
        bulkRestoreBtn.addEventListener('click', () => {
            doBulk(URLS.bulkRestore, 'Seçili kayıtlar geri yüklensin mi?');
        }, {signal});
    }

    if (bulkForceBtn && !markBound(pageEl, 'bulk-force')) {
        bulkForceBtn.addEventListener('click', () => {
            doBulk(URLS.bulkForce, 'Seçili kayıtlar KALICI silinecek. Emin misin?');
        }, {signal});
    }

    fetchList();
}
