import { post, delete as destroy } from '@/core/http';
import { showConfirmDialog, showToastMessage } from '@/core/swal-alert';

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
            { data: 'actions', orderable: false, searchable: false },
        ],
    });

    root.addEventListener('click', async (e) => {
        const restoreBtn = e.target.closest('[data-restore]');
        const forceBtn = e.target.closest('[data-force-delete]');

        if (restoreBtn) {
            await post(restoreBtn.dataset.url, null, { ignoreGlobalError: true });
            location.reload();
        }

        if (forceBtn) {
            const ok = await showConfirmDialog({
                type: 'error',
                title: 'Kategori kalıcı silinsin mi?',
                message: 'Bu işlem geri alınamaz.',
                confirmButtonText: 'Kalıcı sil',
            });
            if (!ok) return;

            try {
                await destroy(forceBtn.dataset.url, null, { ignoreGlobalError: true });
            } catch (error) {
                showToastMessage('error', error?.message || 'Kalıcı silme başarısız');
                return;
            }

            location.reload();
        }
    });
}
