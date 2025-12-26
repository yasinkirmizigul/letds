export function createMediaList({
                                    root,
                                    state,
                                    grid,
                                    empty,
                                    info,
                                    pagination,
                                    setGlobalError,
                                    applySelectionToGrid,
                                    mediaCard,
                                }) {
    async function fetchList() {
        const mode = root.dataset.mode || 'active';

        const qs = new URLSearchParams({
            page: String(state.page),
            perpage: String(state.perpage),
            q: state.q || '',
            type: state.type || '',
            mode,
        });

        const res = await fetch(`/admin/media/list?${qs.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!res.ok) {
            setGlobalError('Liste alınamadı.');
            return;
        }

        const j = await res.json().catch(() => ({}));
        if (!j?.ok) {
            setGlobalError(j?.error?.message || 'Liste alınamadı.');
            return;
        }

        const items = Array.isArray(j.data) ? j.data : [];
        const meta = j.meta || {};

        state.last_page = Number(meta.last_page || 1) || 1;
        state.total = Number(meta.total || 0) || 0;

        grid.innerHTML = items.map(mediaCard).join('');
        empty?.classList.toggle('hidden', items.length > 0);

        const from = items.length ? ((meta.current_page - 1) * meta.per_page + 1) : 0;
        const to = items.length ? (from + items.length - 1) : 0;
        if (info) info.textContent = `${from}-${to} / ${meta.total ?? items.length}`;

        renderPagination(meta);
        applySelectionToGrid();
    }

    function renderPagination(meta) {
        if (!pagination) return;

        const current = Number(meta.current_page || 1);
        const last = Number(meta.last_page || 1);

        if (last <= 1) {
            pagination.innerHTML = '';
            return;
        }

        const btn = (p, label, disabled = false, active = false) => `
      <button type="button"
        class="kt-btn kt-btn-sm ${active ? 'kt-btn-primary' : 'kt-btn-light'}"
        data-page="${p}"
        ${disabled ? 'disabled' : ''}>
        ${label}
      </button>
    `;

        const parts = [];
        parts.push(btn(current - 1, '‹', current <= 1, false));

        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);

        if (start > 1) parts.push(btn(1, '1', false, current === 1));
        if (start > 2) parts.push(`<span class="px-2 text-muted-foreground">…</span>`);

        for (let p = start; p <= end; p++) parts.push(btn(p, String(p), false, p === current));

        if (end < last - 1) parts.push(`<span class="px-2 text-muted-foreground">…</span>`);
        if (end < last) parts.push(btn(last, String(last), false, current === last));

        parts.push(btn(current + 1, '›', current >= last, false));

        pagination.innerHTML = `<div class="flex items-center justify-center gap-2">${parts.join('')}</div>`;
    }

    return { fetchList };
}
