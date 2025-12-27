export default function init() {
    const root = document.querySelector('[data-page="galleries.index"]');
    if (!root) return;

    const state = { page: 1, perpage: 24, q: '' };

    const listEl = root.querySelector('#galleriesList');
    const emptyEl = root.querySelector('#galleriesEmpty');
    const infoEl = root.querySelector('#galleriesInfo');
    const pagEl = root.querySelector('#galleriesPagination');

    const search = root.querySelector('#galleriesSearch');
    const refresh = root.querySelector('#galleriesRefresh');

    const mode = root.dataset.mode || 'active';

    const btn = (p, label, disabled = false, active = false) =>
        `<button class="kt-btn kt-btn-sm ${active ? 'kt-btn-primary' : 'kt-btn-light'}"
             data-page="${p}" ${disabled ? 'disabled' : ''}>${label}</button>`;

    function renderPagination(meta) {
        if (!pagEl) return;
        const current = Number(meta.current_page || 1);
        const last = Number(meta.last_page || 1);
        if (last <= 1) { pagEl.innerHTML = ''; return; }

        const parts = [];
        parts.push(btn(current - 1, '‹', current <= 1));
        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);
        if (start > 1) parts.push(btn(1, '1', false, current === 1));
        if (start > 2) parts.push(`<span class="px-2">…</span>`);
        for (let p = start; p <= end; p++) parts.push(btn(p, String(p), false, p === current));
        if (end < last - 1) parts.push(`<span class="px-2">…</span>`);
        if (end < last) parts.push(btn(last, String(last), false, current === last));
        parts.push(btn(current + 1, '›', current >= last));

        pagEl.innerHTML = `<div class="flex items-center gap-2">${parts.join('')}</div>`;
    }

    async function fetchList() {
        const qs = new URLSearchParams({
            page: String(state.page),
            perpage: String(state.perpage),
            q: state.q || '',
            mode,
        });

        const res = await fetch(`/admin/galleries/list?${qs.toString()}`, { headers: { Accept: 'application/json' } });
        const j = await res.json().catch(() => ({}));
        if (!res.ok || !j?.ok) {
            listEl.innerHTML = '';
            emptyEl?.classList.remove('hidden');
            if (infoEl) infoEl.textContent = '';
            return;
        }

        const items = Array.isArray(j.data) ? j.data : [];
        const meta = j.meta || {};
        listEl.innerHTML = items.map(g => {
            const editUrl = `/admin/galleries/${g.id}/edit`;
            return `
        <div class="flex items-center justify-between border border-border rounded-md p-3">
          <div class="flex flex-col">
            <div class="flex items-center gap-2">
              <span class="kt-badge kt-badge-light">#${g.id}</span>
              <a class="font-semibold hover:underline" href="${editUrl}">${g.name}</a>
            </div>
            <div class="text-xs text-muted-foreground">${g.slug || ''}</div>
          </div>
          <a class="kt-btn kt-btn-sm kt-btn-light" href="${editUrl}">Düzenle</a>
        </div>
      `;
        }).join('');

        emptyEl?.classList.toggle('hidden', items.length > 0);

        const from = items.length ? ((meta.current_page - 1) * meta.per_page + 1) : 0;
        const to = items.length ? (from + items.length - 1) : 0;
        if (infoEl) infoEl.textContent = `${from}-${to} / ${meta.total ?? items.length}`;

        renderPagination(meta);
    }

    pagEl?.addEventListener('click', (e) => {
        const b = e.target.closest('button[data-page]');
        if (!b) return;
        const p = Number(b.dataset.page || 1);
        if (Number.isFinite(p) && p >= 1) { state.page = p; fetchList(); }
    });

    refresh?.addEventListener('click', () => fetchList());
    search?.addEventListener('input', () => { state.q = search.value.trim(); state.page = 1; fetchList(); });

    fetchList();
}
