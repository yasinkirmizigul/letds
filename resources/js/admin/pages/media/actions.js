import { request, get, post, delete as destroy } from '@/core/http';
import { showConfirmDialog, showToastMessage } from '@/core/swal-alert';
export function attachGridActions({
                                      root,
                                      grid,
                                      csrf,
                                      selectedIds,
                                      setBulkUI,
                                      state,
                                      fetchList,
                                      openLightbox,
                                      formatBytes,
                                      inferKindFromMimeOrExt,
                                  }) {
    async function req(url, method) {
        try {
            return await request(url, { method, headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {}, ignoreGlobalError: true }) || {};
        } catch (err) {
            const j = err?.data || {};
            throw new Error(j?.error?.message || j?.message || 'İşlem başarısız');
        }
    }

    grid?.addEventListener('click', async (e) => {
        if (e.target.closest('input[data-media-check="1"]')) return;

        const mode = root.dataset.mode || 'active';
        const isTrash = mode === 'trash';

        const delBtn = e.target.closest('[data-action="delete"]');
        if (delBtn) {
            if (isTrash) return;

            const id = delBtn.getAttribute('data-id');
            if (!id) return;

            const ok = await showConfirmDialog({
                type: 'warning',
                title: 'Medya silinsin mi?',
                message: 'Seçili medya çöp kutusuna taşınacak.',
                confirmButtonText: 'Sil',
            });
            if (!ok) return;

            try {
                await req(`/admin/media/${id}`, 'DELETE');
                selectedIds.delete(String(id));
                setBulkUI();
                state.page = 1;
                await fetchList();
                showToastMessage('success', 'Medya silindi.', { duration: 1800 });
            } catch (err) {
                showToastMessage('error', err?.message || 'Silme başarısız');
            }
            return;
        }

        const restoreBtn = e.target.closest('[data-action="restore"]');
        if (restoreBtn) {
            const id = restoreBtn.getAttribute('data-id');
            if (!id) return;

            try {
                await req(`/admin/media/${id}/restore`, 'POST');
                selectedIds.delete(String(id));
                setBulkUI();
                state.page = 1;
                await fetchList();
                showToastMessage('success', 'Medya geri yüklendi.', { duration: 1800 });
            } catch (err) {
                showToastMessage('error', err?.message || 'Geri yükleme başarısız');
            }
            return;
        }

        const forceBtn = e.target.closest('[data-action="force-delete"]');
        if (forceBtn) {
            const id = forceBtn.getAttribute('data-id');
            if (!id) return;

            const ok = await showConfirmDialog({
                type: 'error',
                title: 'Medya kalıcı silinsin mi?',
                message: 'Bu işlem geri alınamaz.',
                confirmButtonText: 'Kalıcı sil',
            });
            if (!ok) return;

            try {
                await req(`/admin/media/${id}/force`, 'DELETE');
                selectedIds.delete(String(id));
                setBulkUI();
                state.page = 1;
                await fetchList();
                showToastMessage('success', 'Medya kalıcı olarak silindi.', { duration: 1800 });
            } catch (err) {
                showToastMessage('error', err?.message || 'Kalıcı silme başarısız');
            }
            return;
        }

        // lightbox open
        const btn = e.target.closest('[data-action="open"]');
        if (!btn) return;

        const all = [...grid.querySelectorAll('[data-action="open"]')];

        const keyToIndex = new Map();
        const items = [];
        const btnToIndex = new Map();

        for (const b of all) {
            const url = b.getAttribute('data-url') || '';
            if (!url) continue;

            const key = b.getAttribute('data-id') || url;

            let uidx = keyToIndex.get(key);
            if (uidx === undefined) {
                uidx = items.length;
                keyToIndex.set(key, uidx);
                items.push({
                    url,
                    kind: b.getAttribute('data-kind') || inferKindFromMimeOrExt(b.getAttribute('data-mime'), b.getAttribute('data-name') || url),
                    title: b.getAttribute('data-name') || 'Medya',
                    sub: `${b.getAttribute('data-mime') || ''} • ${formatBytes(Number(b.getAttribute('data-size') || 0))}`,
                    mime: b.getAttribute('data-mime') || '',
                    name: b.getAttribute('data-name') || '',
                });
            }

            btnToIndex.set(b, uidx);
        }

        const idx = btnToIndex.get(btn) ?? 0;
        openLightbox(items, idx);
    });
}
