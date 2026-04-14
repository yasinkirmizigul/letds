import { request, get, post, delete as destroy } from '@/core/http';
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

        if (!confirm(`${ids.length} medya silinsin mi?`)) return;

        try {
            await req('/admin/media/bulk-delete', 'DELETE', { ids });

            selectedIds.clear();
            setBulkUI();

            state.page = 1;
            await fetchList();
        } catch (e) {
            alert(e?.message || 'Toplu silme başarısız');
        }
    });

    // Trash: bulk restore
    bulkRestoreBtn?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        if (!confirm(`${ids.length} medya geri yüklensin mi?`)) return;

        try {
            await req('/admin/media/bulk-restore', 'POST', { ids });

            selectedIds.clear();
            setBulkUI();

            state.page = 1;
            await fetchList();
        } catch (e) {
            alert(e?.message || 'Toplu geri yükleme başarısız');
        }
    });

    // Trash: bulk force delete
    bulkForceDeleteBtn?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        if (!confirm(`${ids.length} medya KALICI olarak silinecek. Emin misin?`)) return;

        try {
            await req('/admin/media/bulk-force-delete', 'DELETE', { ids });

            selectedIds.clear();
            setBulkUI();

            state.page = 1;
            await fetchList();
        } catch (e) {
            alert(e?.message || 'Toplu kalıcı silme başarısız');
        }
    });
}
