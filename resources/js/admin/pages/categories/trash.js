export default function init(ctx = {}) {
    const root = document.querySelector('[data-page="categories.trash"]');
    if (!root) return;

    window.initDataTable({
        root,
        table: '#categories_trash_table',
        serverSide: true,
        processing: true,
        ajax: root.querySelector('#categories_trash_table').dataset.ajax,

        columns: [
            { data: 'name' },
            { data: 'slug' },
            { data: 'parent_name' },
            { data: 'deleted_at' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    root.addEventListener('click', async (e) => {
        const restoreBtn = e.target.closest('[data-restore]');
        const forceBtn = e.target.closest('[data-force-delete]');

        if (restoreBtn) {
            await fetch(restoreBtn.dataset.url, { method: 'POST' });
            location.reload();
        }

        if (forceBtn) {
            if (!confirm('Kalıcı silinsin mi?')) return;
            await fetch(forceBtn.dataset.url, { method: 'DELETE' });
            location.reload();
        }
    });
}
