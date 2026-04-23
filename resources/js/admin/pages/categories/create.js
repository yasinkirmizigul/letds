import initSlugManager from '@/core/slug-manager';

export default function init({ root, signal }) {
    initSlugManager(root, {
        sourceSelector: '#cat_name',
        slugSelector: '#cat_slug',
        previewSelector: '#url_slug_preview',
        autoSelector: '#slug_auto',
        regenSelector: '#slug_regen',
        generateOnInit: true,
    }, signal);

    root.querySelectorAll('[data-locale-slug-scope="true"]').forEach((scope) => {
        initSlugManager(scope, {
            sourceSelector: '[data-locale-title="true"]',
            slugSelector: '[data-locale-slug="true"]',
            previewSelector: '[data-slug-preview="true"]',
            autoSelector: '[data-slug-auto="true"]',
            regenSelector: '[data-slug-regen="true"]',
            generateOnInit: true,
        }, signal);
    });
}
