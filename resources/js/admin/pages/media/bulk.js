import { request, get, post, delete as destroy } from '@/core/http';
import { showConfirmDialog, showToastMessage } from '@/core/swal-alert';
export function attachBulkActions({
                                      root,
                                      csrf,
                                      selectedIds,
                                      setBulkUI,
                                      fetchList,
                                      state,
                                  }) {
    const bulkDeleteBtn = root.querySelector('#mediaBulkDeleteBtn');
    const bulkRestoreBtn = root.querySelector('#mediaBulkRestoreBtn');
    const bulkForceDeleteBtn = root.querySelector('#mediaBulkForceDeleteBtn');

    async function req(url, method, bodyObj) {
        try {
            return await request(url, { method, data: bodyObj, headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {}, ignoreGlobalError: true }) || {};
        } catch (err) {
            const j = err?.data || {};
            throw new Error(j?.error?.message || j?.message || 'İşlem başarısız');
        }
    }

    // Active: bulk soft delete
    bulkDeleteBtn?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        const ok = await showConfirmDialog({
            type: 'warning',
            title: 'Seçili medya silinsin mi?',
            message: `${ids.length} medya çöp kutusuna taşınacak.`,
            confirmButtonText: 'Sil',
        });
        if (!ok) return;

        try {
            await req('/admin/media/bulk-delete', 'DELETE', { ids });

            selectedIds.clear();
            setBulkUI();

            state.page = 1;
            await fetchList();
            showToastMessage('success', 'Seçili medya silindi.', { duration: 1800 });
        } catch (e) {
            showToastMessage('error', e?.message || 'Toplu silme başarısız');
        }
    });

    // Trash: bulk restore
    bulkRestoreBtn?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        const ok = await showConfirmDialog({
            type: 'success',
            title: 'Seçili medya geri yüklensin mi?',
            message: `${ids.length} medya tekrar aktif listeye alınacak.`,
            confirmButtonText: 'Geri yükle',
        });
        if (!ok) return;

        try {
            await req('/admin/media/bulk-restore', 'POST', { ids });

            selectedIds.clear();
            setBulkUI();

            state.page = 1;
            await fetchList();
            showToastMessage('success', 'Seçili medya geri yüklendi.', { duration: 1800 });
        } catch (e) {
            showToastMessage('error', e?.message || 'Toplu geri yükleme başarısız');
        }
    });

    // Trash: bulk force delete
    bulkForceDeleteBtn?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        const ok = await showConfirmDialog({
            type: 'error',
            title: 'Seçili medya kalıcı silinsin mi?',
            message: `${ids.length} medya geri alınamayacak şekilde silinecek.`,
            confirmButtonText: 'Kalıcı sil',
        });
        if (!ok) return;

        try {
            await req('/admin/media/bulk-force-delete', 'DELETE', { ids });

            selectedIds.clear();
            setBulkUI();

            state.page = 1;
            await fetchList();
            showToastMessage('success', 'Seçili medya kalıcı olarak silindi.', { duration: 1800 });
        } catch (e) {
            showToastMessage('error', e?.message || 'Toplu kalıcı silme başarısız');
        }
    });
}
