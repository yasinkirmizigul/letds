function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function jsonReq(url, method = 'GET', body = null, signal = null) {
    const res = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken() ? { 'X-CSRF-TOKEN': csrfToken() } : {}),
            ...(body ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
        credentials: 'same-origin',
        signal,
    });

    const j = await res.json().catch(() => ({}));
    return { res, j };
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

    // ---- URL’ler Blade’den gelsin (Blade’de data-* var) :contentReference[oaicite:1]{index=1}
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

        const { res, j } = await jsonReq(`${URLS.list}?${qs.toString()}`, 'GET', null, signal);

        if (!res.ok || !j?.ok) {
            tbodyEl.innerHTML = `
                <tr>
                    <td colspan="5" class="py-10 text-center text-muted-foreground">
                        Liste alınamadı.
                    </td>
                </tr>
            `;
            setInfo('');
            renderPagination({ current_page: 1, last_page: 1 });
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

        const { res, j } = await jsonReq(url, method, (method === 'POST' ? {} : null), signal);
        if (!res.ok || !j?.ok) {
            alert(j?.error?.message || j?.message || 'İşlem başarısız');
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
                return { type, id: nid, key: `${type}|${nid}` };
            })
            .filter(Boolean);

        if (!items.length) return;

        if (!confirm(confirmText)) return;

        // ---- OPTIMISTIC UI: seçili satırları DOM’dan kaldır, state’i yedekle
        const backup = {
            keys: new Set(selected),
            rows: [], // { key, node, nextSibling, parent }
        };

        const keyToRow = (key) => {
            // checkbox üzerinde data-id + data-type var
            const [t, id] = String(key).split('|');
            return tbodyEl.querySelector(`input[data-act="chk"][data-id="${CSS.escape(id)}"][data-type="${CSS.escape(t)}"]`)?.closest('tr');
        };

        backup.keys.forEach((key) => {
            const row = keyToRow(key);
            if (!row) return;

            backup.rows.push({
                key,
                node: row,
                parent: row.parentNode,
                nextSibling: row.nextSibling,
            });

            row.remove(); // optimistic remove
        });

        // UI state
        selected.clear();
        setBulkUI();

        // Eğer tabloda hiç satır kalmadıysa boş template bas (şimdilik basit)
        if (!tbodyEl.querySelector('tr')) {
            // arama varsa zero, değilse empty
            const isSearch = Boolean(state.q) || (state.type && state.type !== 'all');
            tbodyEl.innerHTML = isSearch ? cloneTpl(tplZero) : cloneTpl(tplEmpty);
        }

        try {
            const payload = { items: items.map(({ type, id }) => ({ type, id })) };
            const { res, j } = await jsonReq(url, 'POST', payload, signal);

            if (!res.ok || !j?.ok) {
                const msg = j?.error?.message || j?.message || 'İşlem başarısız';
                throw new Error(msg);
            }

            // Başarılıysa: server tarafındaki gerçek state’i çek (sayfa/total/boş durum doğru olsun)
            await fetchList();
        } catch (e) {
            // ---- ROLLBACK: kaldırdıklarını geri koy
            // önce template basıldıysa sil
            // (rows’u eski yerine insert edeceğiz)
            if (!backup.rows.length) return;

            // Eğer tbody içi template ile dolduysa temizle (basit kontrol)
            // Template'ler genelde tek satır ve colspan içerir; en garantisi:
            const onlyTemplate = tbodyEl.querySelectorAll('tr').length === 1
                && tbodyEl.querySelector('tr td[colspan]');
            if (onlyTemplate) tbodyEl.innerHTML = '';

            backup.rows.forEach(({ node, parent, nextSibling }) => {
                if (!parent) return;
                if (nextSibling) parent.insertBefore(node, nextSibling);
                else parent.appendChild(node);
            });

            // seçimi geri yükle
            selected.clear();
            backup.keys.forEach((k) => selected.add(k));

            // checkboxları tekrar işaretle
            tbodyEl.querySelectorAll('input[data-act="chk"]').forEach((chk) => {
                const id = chk.getAttribute('data-id');
                const type = chk.getAttribute('data-type') || '';
                const key = `${type}|${id}`;
                chk.checked = selected.has(key);
            });

            setBulkUI();
            alert(e?.message || 'İşlem başarısız');
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
        }, { signal });

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
        }, { signal });
    }

    if (perSel && !markBound(pageEl, 'per')) {
        perSel.addEventListener('change', () => {
            state.perpage = Number(perSel.value || 25) || 25;
            state.page = 1;
            fetchList();
        }, { signal });
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
        }, { signal });
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
        }, { signal });
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
        checkAllHead.addEventListener('change', () => toggleAll(checkAllHead.checked), { signal });
    }
    if (checkAllBar && !markBound(pageEl, 'checkall-bar')) {
        checkAllBar.addEventListener('change', () => toggleAll(checkAllBar.checked), { signal });
    }

    if (bulkRestoreBtn && !markBound(pageEl, 'bulk-restore')) {
        bulkRestoreBtn.addEventListener('click', () => {
            doBulk(URLS.bulkRestore, 'Seçili kayıtlar geri yüklensin mi?');
        }, { signal });
    }

    if (bulkForceBtn && !markBound(pageEl, 'bulk-force')) {
        bulkForceBtn.addEventListener('click', () => {
            doBulk(URLS.bulkForce, 'Seçili kayıtlar KALICI silinecek. Emin misin?');
        }, { signal });
    }

    fetchList();
}
