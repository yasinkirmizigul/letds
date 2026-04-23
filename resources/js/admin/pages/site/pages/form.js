import initSlugManager from '@/core/slug-manager';
import { initTinyEditor } from '../../blog/form-shared';

function syncPreview(root) {
    const title = root.querySelector('#title')?.value?.trim() || 'Meta baslik burada gorunecek';
    const slug = root.querySelector('#slug')?.value?.trim() || 'ornek-sayfa';
    const excerpt = root.querySelector('#excerpt')?.value?.trim() || 'Meta aciklama burada gorunecek.';
    const metaTitle = root.querySelector('input[name="meta_title"]')?.value?.trim();
    const metaDescription = root.querySelector('textarea[name="meta_description"]')?.value?.trim();

    root.querySelector('[data-page-seo-preview-title]')?.replaceChildren(document.createTextNode(metaTitle || title));
    root.querySelector('[data-page-seo-preview-slug]')?.replaceChildren(document.createTextNode(slug));
    root.querySelector('[data-page-seo-preview-description]')?.replaceChildren(document.createTextNode(metaDescription || excerpt));
}

function bindIconChips(root, signal) {
    const input = root.querySelector('input[name="icon_class"]');
    if (!input) return;

    root.querySelectorAll('.js-icon-chip').forEach((button) => {
        button.addEventListener('click', () => {
            input.value = button.dataset.iconValue || '';
        }, { signal });
    });
}

export default async function initPageForm(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;

    initSlugManager(root, {
        sourceSelector: '#title',
        slugSelector: '#slug',
        previewSelector: '#url_slug_preview',
        autoSelector: '#slug_auto',
        regenSelector: '#slug_regen',
        generateOnInit: true,
    }, signal);

    root.querySelectorAll('[data-locale-slug-scope="true"]').forEach((scope) => {
        const titleInput = scope.querySelector('[data-locale-title="true"]');
        const slugInput = scope.querySelector('[data-locale-slug="true"]');

        if (!titleInput || !slugInput) {
            return;
        }

        initSlugManager(scope, {
            sourceSelector: '[data-locale-title="true"]',
            slugSelector: '[data-locale-slug="true"]',
            previewSelector: '[data-slug-preview="true"]',
            autoSelector: '[data-slug-auto="true"]',
            regenSelector: '[data-slug-regen="true"]',
            generateOnInit: true,
        }, signal);
    });

    ['#title', '#slug', '#excerpt', 'input[name="meta_title"]', 'textarea[name="meta_description"]']
        .forEach((selector) => {
            root.querySelector(selector)?.addEventListener('input', () => syncPreview(root), { signal });
        });

    bindIconChips(root, signal);
    await initTinyEditor(ctx, () => syncPreview(root), '[data-localized-content-editor="true"], [data-page-content-editor="true"]');
    syncPreview(root);
}
