import initSlugManager from '@/core/slug-manager';

function openModal(modal) {
    modal?.classList.remove('hidden');
}

function closeModal(modal) {
    modal?.classList.add('hidden');
}

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

    const updateForm = root.querySelector('#category-update-form');
    const deleteForm = root.querySelector('#category-delete-form');

    root.addEventListener('click', (event) => {
        const targetBtn = event.target?.closest?.('[data-kt-modal-target]');
        if (targetBtn && root.contains(targetBtn)) {
            const modal = document.querySelector(targetBtn.getAttribute('data-kt-modal-target'));
            openModal(modal);
            return;
        }

        const closeBtn = event.target?.closest?.('[data-kt-modal-close]');
        if (closeBtn && root.contains(closeBtn)) {
            closeModal(closeBtn.closest('.kt-modal'));
            return;
        }

        if (event.target?.classList?.contains('kt-modal')) {
            closeModal(event.target);
        }
    }, { signal });

    updateForm?.addEventListener('submit', () => {
        root.querySelectorAll('button[form="category-update-form"][type="submit"]').forEach((button) => {
            button.disabled = true;
            button.classList.add('opacity-60', 'pointer-events-none');
        });
    }, { signal, once: true });

    deleteForm?.addEventListener('submit', () => {
        root.querySelectorAll('button[form="category-delete-form"][type="submit"]').forEach((button) => {
            button.disabled = true;
            button.classList.add('opacity-60', 'pointer-events-none');
        });
    }, { signal, once: true });
}
