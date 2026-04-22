import { request } from '@/core/http';
import { showConfirmDialog, showToastMessage } from '@/core/swal-alert';
import {
    initSeoPanel,
    initSlugTools,
    initStatusFeaturedUI,
    initTinyEditor,
    lockSubmitButtons,
    setFormButtonsDisabled,
} from './form-shared';

function notify(type, text) {
    showToastMessage(type, text, { duration: 2200 });
}

export default async function init(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;

    initStatusFeaturedUI(root, signal);
    initSlugTools(root, signal);

    const seoPanel = initSeoPanel(root, signal, () => {
        const editor = window.tinymce?.get?.('content_editor');
        return editor ? editor.getContent({ format: 'html' }) : (root.querySelector('#content_editor')?.value || '');
    });

    await initTinyEditor(ctx, () => seoPanel.sync());
    seoPanel.sync();

    const updateForm = root.querySelector('#product-update-form');
    if (updateForm) {
        updateForm.addEventListener('submit', () => lockSubmitButtons(root, 'product-update-form'), { signal, once: true });
    }

    const deleteButton = root.querySelector('#productDeleteBtn');
    if (!deleteButton) return;

    deleteButton.addEventListener('click', async () => {
        const ok = await showConfirmDialog({
            type: 'warning',
            title: 'Urun silinsin mi?',
            message: 'Urun cop kutusuna tasinacak.',
            confirmButtonText: 'Sil',
        });

        if (!ok) return;

        setFormButtonsDisabled(root, 'product-update-form', true);

        try {
            const data = await request(root.dataset.productDeleteUrl, {
                method: 'DELETE',
                ignoreGlobalError: true,
                signal,
            });

            if (!data?.ok) {
                throw new Error(data?.message || 'Silme islemi tamamlanamadi.');
            }

            notify('success', data?.message || 'Urun cop kutusuna tasindi.');

            window.setTimeout(() => {
                window.location.assign(root.dataset.productIndexUrl || '/admin/products');
            }, 650);
        } catch (error) {
            setFormButtonsDisabled(root, 'product-update-form', false);
            notify('error', error?.message || 'Silme islemi basarisiz oldu.');
        }
    }, { signal });
}
