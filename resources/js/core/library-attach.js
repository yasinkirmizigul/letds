// resources/js/core/library-attach.js

import { initMediaUploadModal } from '@/core/media-upload-modal';
import { post } from '@/core/http';

export default function initLibraryAttach(root = document) {
    // modal global => document scope
    initMediaUploadModal(document);

    const modal = document.getElementById('mediaUploadModal');
    if (!modal) return;

    // idempotent
    if (modal.__libraryAttachBound) return;
    modal.__libraryAttachBound = true;

    // son tıklanan attach butonu hedefi belirler
    let currentAttach = null;

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-library-attach="true"]');
        if (!btn) return;

        const url = btn.getAttribute('data-library-attach-url');
        if (!url) return;

        currentAttach = {
            url,
            payloadKey: btn.getAttribute('data-library-attach-payload') || 'media_ids',
        };
    }, true);

    async function hideModal() {
        const dismiss = modal.querySelector('[data-kt-modal-dismiss="true"]');
        if (dismiss) {
            dismiss.click();
            return;
        }
        try {
            const inst = window.KTModal?.getOrCreateInstance?.(modal);
            inst?.hide?.();
        } catch {}
    }

    modal.addEventListener('media:library:useSelected', async (e) => {
        const ids = e?.detail?.ids || [];
        if (!Array.isArray(ids) || ids.length === 0) return;

        if (!currentAttach?.url) {
            console.warn('[library-attach] attach target not set (missing button attrs)');
            return;
        }

        const useBtn = modal.querySelector('#mediaLibraryUseSelectedBtn');
        const oldHtml = useBtn?.innerHTML;

        if (useBtn) {
            useBtn.disabled = true;
            useBtn.innerHTML = `<i class="ki-outline ki-loading"></i> Ekleniyor (${ids.length})`;
        }

        const body = { [currentAttach.payloadKey]: ids };

        let j;
        try {
            j = await post(currentAttach.url, body, { ignoreGlobalError: true });
        } catch (err) {
            console.error(err);
            j = err?.data || {};
        }

        const ok = !!j?.ok;

        if (useBtn) {
            useBtn.innerHTML = ok
                ? `<i class="ki-outline ki-check-circle text-success"></i> Eklendi`
                : `<i class="ki-outline ki-cross-circle text-destructive"></i> Hata`;
        }

        if (!ok) {
            if (useBtn) {
                setTimeout(() => {
                    useBtn.innerHTML = oldHtml || 'Seçilenleri Kullan';
                    useBtn.disabled = false;
                }, 1200);
            }
            return;
        }

        // selection temizle (upload-modal bunu dinliyor)
        modal.dispatchEvent(new CustomEvent('media:library:clearSelection', { bubbles: true }));

        // modal kapat
        await hideModal();

        // sayfalara haber ver: attach bitti
        document.dispatchEvent(new CustomEvent('media:library:attached', {
            bubbles: true,
            detail: { ids, url: currentAttach.url },
        }));

        // btn reset
        if (useBtn) {
            setTimeout(() => {
                useBtn.innerHTML = oldHtml || 'Seçilenleri Kullan';
                useBtn.disabled = false;
            }, 300);
        }
    });
}
