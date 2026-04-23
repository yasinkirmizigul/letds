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
        const editör = window.tinymce?.get?.('content_editor');
        return editör ? editör.getContent({ format: 'html' }) : (root.querySelector('#content_editor')?.value || '');
    });

    await initTinyEditor(ctx, () => seoPanel.sync(), '[data-localized-content-editor="true"]');
    seoPanel.sync();

    const updateForm = root.querySelector('#product-update-form');
    if (updateForm) {
        updateForm.addEventListener('submit', () => lockSubmitButtons(root, 'product-update-form'), { signal, önce: true });
    }

    const deleteButton = root.querySelector('#productDeleteBtn');
    if (!deleteButton) return;

    deleteButton.addEventListener('click', async () => {
        const ok = await showConfirmDialog({
            type: 'warning',
            title: 'Ürün silinsin mi?',
            message: 'Ürün çöp kutusuna taşınacak.',
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
                throw new Error(data?.message || 'Silme işlemi tamamlanamadı.');
            }

            notify('success', data?.message || 'Ürün çöp kutusuna taşındı.');

            window.setTimeout(() => {
                window.location.assign(root.dataset.productIndexUrl || '/admin/products');
            }, 650);
        } catch (error) {
            setFormButtonsDisabled(root, 'product-update-form', false);
            notify('error', error?.message || 'Silme işlemi başarısız oldu.');
        }
    }, { signal });
}
