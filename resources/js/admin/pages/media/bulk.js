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
        const res = await fetch(url, {
            method,
            headers: {
                ...(bodyObj ? { 'Content-Type': 'application/json' } : {}),
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                Accept: 'application/json',
            },
            body: bodyObj ? JSON.stringify(bodyObj) : undefined,
        });

        if (!res.ok) {
            const j = await res.json().catch(() => ({}));
            throw new Error(j?.error?.message || j?.message || 'İşlem başarısız');
        }

        return res.json().catch(() => ({}));
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
