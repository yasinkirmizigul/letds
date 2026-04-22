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

    await initTinyEditor(ctx, () => seoPanel.sync());
    seoPanel.sync();

    const form = root.querySelector('#project-create-form');
    if (form) {
        form.addEventListener('submit', () => lockSubmitButtons(root, 'project-create-form'), { signal, once: true });
    }
}
