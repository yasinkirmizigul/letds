import {
    initSeoPanel,
    initSlugTools,
    initStatusFeaturedUI,
    initTinyEditor,
    lockSubmitButtons,
} from './form-shared';

export default async function init(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;

    initStatusFeaturedUI(root, signal);
    initSlugTools(root, signal);

    const seoPanel = initSeoPanel(root, signal, () => {
        const editor = window.tinymce?.get?.('content_editor');
        return editor ? editor.getContent({ format: 'html' }) : (root.querySelector('#content_editor')?.value || '');
    });

    await initTinyEditor(ctx, () => seoPanel.sync(), '[data-localized-content-editor="true"]');
    seoPanel.sync();

    const createForm = root.querySelector('#product-create-form');
    if (createForm) {
        createForm.addEventListener('submit', () => lockSubmitButtons(root, 'product-create-form'), { signal, once: true });
    }
}
