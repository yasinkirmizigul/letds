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
        const res = await fetch(url, {
            method,
            headers: {
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                Accept: 'application/json',
            },
        });

        if (!res.ok) {
            const j = await res.json().catch(() => ({}));
            throw new Error(j?.error?.message || j?.message || 'İşlem başarısız');
        }
        return res.json().catch(() => ({}));
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

            if (!confirm('Bu medyayı silmek istiyor musun?')) return;

            try {
                await req(`/admin/media/${id}`, 'DELETE');
                selectedIds.delete(String(id));
                setBulkUI();
                state.page = 1;
                await fetchList();
            } catch (err) {
                alert(err?.message || 'Silme başarısız');
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
            } catch (err) {
                alert(err?.message || 'Geri yükleme başarısız');
            }
            return;
        }

        const forceBtn = e.target.closest('[data-action="force-delete"]');
        if (forceBtn) {
            const id = forceBtn.getAttribute('data-id');
            if (!id) return;

            if (!confirm('Bu medya KALICI olarak silinecek. Emin misin?')) return;

            try {
                await req(`/admin/media/${id}/force`, 'DELETE');
                selectedIds.delete(String(id));
                setBulkUI();
                state.page = 1;
                await fetchList();
            } catch (err) {
                alert(err?.message || 'Kalıcı silme başarısız');
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
