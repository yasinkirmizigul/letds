import { request } from '@/core/http';
import { showConfirmDialog, showToastMessage } from '@/core/swal-alert';
import initGalleryManager, { destroyGalleryManager } from '@/core/gallery-manager';
import initLibraryAttach from '@/core/library-attach';
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

    await initTinyEditor(ctx, () => seoPanel.sync());
    seoPanel.sync();

    initGalleryManager(root);
    ctx.cleanup(() => destroyGalleryManager(root));

    initLibraryAttach(root);

    const updateForm = root.querySelector('#blog-update-form');
    if (updateForm) {
        updateForm.addEventListener('submit', () => lockSubmitButtons(root, 'blog-update-form'), { signal, önce: true });
    }

    const deleteForm = root.querySelector('#blog-delete-form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const ok = await showConfirmDialog({
                type: 'warning',
                title: 'Yazı silinsin mi?',
                message: 'Yazı çöp kutusuna taşınacak.',
                confirmButtonText: 'Sil',
            });

            if (!ok) return;

            setFormButtonsDisabled(root, 'blog-delete-form', true);

            try {
                const data = await request(deleteForm.action, {
                    method: 'DELETE',
                    ignoreGlobalError: true,
                    signal,
                });

                if (!data?.ok) {
                    throw new Error(data?.message || 'Silme işlemi tamamlanamadı.');
                }

                notify('success', data?.message || 'Yazı çöp kutusuna taşındı.');

                window.setTimeout(() => {
                    window.location.assign(root.dataset.blogIndexUrl || '/admin/blog');
                }, 650);
            } catch (error) {
                setFormButtonsDisabled(root, 'blog-delete-form', false);
                notify('error', error?.message || 'Silme işlemi başarısız oldu.');
            }
        }, { signal });
    }
}
