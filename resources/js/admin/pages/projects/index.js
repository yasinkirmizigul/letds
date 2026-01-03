function csrf() {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
}

async function jsonFetch(url, opts = {}) {
    const headers = opts.headers || {};
    headers['Accept'] = 'application/json';
    headers['X-Requested-With'] = 'XMLHttpRequest';
    const token = csrf();
    if (token) headers['X-CSRF-TOKEN'] = token;

    const res = await fetch(url, { ...opts, headers });
    const data = await res.json().catch(() => ({}));
    return { res, data };
}

export default function init() {
    const root = document.querySelector('[data-page="projects.index"], [data-page="projects.trash"]');
    if (!root) return;

    const mode = root.getAttribute('data-mode') || (root.getAttribute('data-page') === 'projects.trash' ? 'trash' : 'active');
    const table = root.querySelector('#projectsTable');
    if (!table) return;

    // page size dropdown (basit)
    const perSelect = root.querySelector('#projectsPageSize');
    const current = parseInt(root.getAttribute('data-perpage') || '25', 10);
    if (perSelect) {
        const opts = [10, 25, 50, 100];
        perSelect.innerHTML = opts.map(v => `<option value="${v}" ${v === current ? 'selected' : ''}>${v}</option>`).join('');
        perSelect.addEventListener('change', () => {
            const u = new URL(window.location.href);
            u.searchParams.set('perpage', perSelect.value);
            window.location.href = u.toString();
        });
    }

    // search
    const searchInput = root.querySelector('#projectsSearch');
    const searchBtn = root.querySelector('#projectsSearchBtn');
    const doSearch = () => {
        const u = new URL(window.location.href);
        const q = (searchInput?.value || '').trim();
        if (q) u.searchParams.set('q', q);
        else u.searchParams.delete('q');
        window.location.href = u.toString();
    };
    if (searchBtn) searchBtn.addEventListener('click', doSearch);
    if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                doSearch();
            }
        });
    }

    // row actions
    table.addEventListener('click', async (e) => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;

        const tr = btn.closest('tr[data-id]');
        if (!tr) return;

        const id = tr.getAttribute('data-id');
        const action = btn.getAttribute('data-action');

        if (!id) return;

        if (action === 'delete') {
            const ok = confirm('Bu projeyi silmek istiyor musun?');
            if (!ok) return;

            const { data } = await jsonFetch(`/admin/projects/${id}`, { method: 'DELETE' });
            if (data.ok) tr.remove();
            return;
        }

        if (action === 'restore') {
            const { data } = await jsonFetch(`/admin/projects/${id}/restore`, { method: 'POST' });
            if (data.ok) tr.remove();
            return;
        }

        if (action === 'force-delete') {
            const ok = confirm('Force delete geri alınamaz. Emin misin?');
            if (!ok) return;

            const { data } = await jsonFetch(`/admin/projects/${id}/force`, { method: 'DELETE' });
            if (data.ok) tr.remove();
        }
    });
}
