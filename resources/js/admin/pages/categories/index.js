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

function badgeState(state) {
    const s = String(state || '').toLowerCase();
    if (s === 'active') return `<span class="kt-badge kt-badge-outline bg-green-500">Aktif</span>`;
    if (s === 'passive') return `<span class="kt-badge kt-badge-outline bg-muted">Pasif</span>`;
    return `<span class="kt-badge kt-badge-outline">-</span>`;
}

// idempotent binding without DOM flags
const bound = new WeakMap(); // root -> Set(keys)
function markBound(root, key) {
    let s = bound.get(root);
    if (!s) { s = new Set(); bound.set(root, s); }
    if (s.has(key)) return true;
    s.add(key);
    return false;
}

export default function init(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;

    // page guard
    const pageEl = root.querySelector('[data-page="categories.index"]');
    if (!pageEl) return;

    const listEl = pageEl.querySelector('#categoriesList');
    const infoEl = pageEl.querySelector('#categoriesInfo');
    const pagEl = pageEl.querySelector('#categoriesPagination');

    const qInput = pageEl.querySelector('#categoriesSearch');
    const stateSel = pageEl.querySelector('#categoriesState');
    const perSel = pageEl.querySelector('#categoriesPerPage');

    const btnCreate = pageEl.querySelector('#categoriesCreateBtn');

    if (!listEl) return;

    const state = {
        q: '',
        state: '',
        page: 1,
        perpage: Number(perSel?.value || 25) || 25,
        last_page: 1,
        total: 0,
    };

    let debounceTimer = null;

    function setInfo(text) {
        if (!infoEl) return;
        infoEl.textContent = text || '';
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
        return `
          <div class="kt-card">
            <div class="kt-card-content p-4 flex items-start justify-between gap-4">
              <div class="grid gap-1">
                <div class="font-medium">${esc(item.name || '-')}</div>
                <div class="text-xs text-muted-foreground">#${esc(item.id)} • ${esc(item.slug || '')}</div>
              </div>

              <div class="flex items-center gap-2">
                ${badgeState(item.state)}
                <a class="kt-btn kt-btn-sm kt-btn-light" href="${esc(item.edit_url || '#')}">
                  <i class="ki-outline ki-pencil"></i> Düzenle
                </a>
              </div>
            </div>
          </div>
        `;
    }

    async function fetchList() {
        const qs = new URLSearchParams({
            q: state.q || '',
            state: state.state || '',
            page: String(state.page),
            perpage: String(state.perpage),
        });

        const { res, j } = await jsonReq(`/admin/categories/list?${qs.toString()}`, 'GET', null, signal);

        if (!res.ok || !j?.ok) {
            listEl.innerHTML = `<div class="text-sm text-muted-foreground">Liste alınamadı.</div>`;
            setInfo('');
            renderPagination({ current_page: 1, last_page: 1 });
            return;
        }

        const items = Array.isArray(j.data) ? j.data : [];
        const meta = j.meta || {};

        state.last_page = Number(meta.last_page || 1) || 1;
        state.total = Number(meta.total || 0) || 0;

        listEl.innerHTML = items.length ? items.map(rowHtml).join('') : `<div class="text-sm text-muted-foreground">Kayıt yok.</div>`;

        if (meta && typeof meta.from !== 'undefined') {
            setInfo(`${meta.from || 0}-${meta.to || 0} / ${meta.total || 0}`);
        } else {
            setInfo(state.total ? `Toplam: ${state.total}` : '');
        }

        renderPagination(meta);
    }

    // ---- bindings (idempotent + signal)
    if (qInput && !markBound(pageEl, 'q')) {
        qInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                state.q = (qInput.value || '').trim();
                state.page = 1;
                fetchList();
            }, 250);
        }, { signal });

        ctx.cleanup(() => {
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = null;
        });
    }

    if (stateSel && !markBound(pageEl, 'state')) {
        stateSel.addEventListener('change', () => {
            state.state = stateSel.value || '';
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

    // Create button - sadece guard (varsa)
    if (btnCreate && !markBound(pageEl, 'create')) {
        btnCreate.addEventListener('click', () => {
            // burada özel bir şey yok; link/button zaten çalışır.
        }, { signal });
    }

    fetchList();
}
