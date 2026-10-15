import Sortable from 'sortablejs';
import { request } from '@/core/http';
import { showConfirmDialog, showToastMessage } from '@/core/swal-alert';

function idsFrom(list, selector, key) {
    return [...list.querySelectorAll(`:scope > ${selector}`)]
        .map((element) => Number(element.dataset[key]))
        .filter(Boolean);
}

export default function init(ctx) {
    const root = ctx.root;
    const sortableInstances = [];
    const sectionList = root.querySelector('#homepageSectionSortable');
    const sectionReorderUrl = root.dataset.sectionReorderUrl;
    const visualForm = root.querySelector('[data-services-visual-form]');

    if (visualForm) {
        const syncPreview = (panel) => {
            const preview = panel?.querySelector('[data-services-preview]');
            const background = panel?.querySelector('[data-services-background-color]');
            if (!preview || !background) return;

            preview.style.backgroundColor = background.value;
            panel.querySelectorAll('[data-services-symbol-color]').forEach((input) => {
                const symbol = preview.querySelector(`[data-services-preview-symbol="${input.dataset.servicesSymbolColor}"]`);
                if (symbol) symbol.style.color = input.value;
            });
            panel.querySelectorAll('input[data-default-color]').forEach((input) => {
                const hex = input.closest('label')?.querySelector('[data-services-hex]');
                if (hex) hex.textContent = input.value.toUpperCase();
            });
        };

        visualForm.querySelectorAll('[data-services-theme]').forEach(syncPreview);
        visualForm.addEventListener('input', (event) => {
            if (event.target.matches('input[data-default-color]')) {
                syncPreview(event.target.closest('[data-services-theme]'));
            }
        }, { signal: ctx.signal });
        visualForm.addEventListener('click', (event) => {
            const reset = event.target.closest('[data-services-reset]');
            if (!reset) return;
            const panel = reset.closest('[data-services-theme]');
            panel?.querySelectorAll('input[data-default-color]').forEach((input) => {
                input.value = input.dataset.defaultColor;
            });
            syncPreview(panel);
        }, { signal: ctx.signal });
    }

    if (sectionList && sectionReorderUrl && sectionList.querySelector('[data-section-id]')) {
        sortableInstances.push(new Sortable(sectionList, {
            handle: '.js-section-sort-handle',
            animation: 180,
            onEnd: async () => {
                try {
                    await request(sectionReorderUrl, {
                        method: 'PATCH',
                        data: { ids: idsFrom(sectionList, '[data-section-id]', 'sectionId') },
                    });
                    showToastMessage('success', 'Bölüm sırası güncellendi.');
                } catch {}
            },
        }));
    }

    root.querySelectorAll('[data-homepage-item-sortable]').forEach((list) => {
        if (!list.dataset.reorderUrl || !list.querySelector('[data-item-id]')) return;

        sortableInstances.push(new Sortable(list, {
            handle: '.js-item-sort-handle',
            animation: 180,
            onEnd: async () => {
                try {
                    await request(list.dataset.reorderUrl, {
                        method: 'PATCH',
                        data: { ids: idsFrom(list, '[data-item-id]', 'itemId') },
                    });
                    showToastMessage('success', 'Kart sırası güncellendi.');
                } catch {}
            },
        }));
    });

    root.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-confirm-delete]');
        if (!form) return;

        event.preventDefault();
        const isSection = form.dataset.confirmDelete === 'section';
        const confirmed = await showConfirmDialog({
            type: 'warning',
            title: isSection ? 'Bölüm silinsin mi?' : 'Kart silinsin mi?',
            message: isSection
                ? 'Bölümle birlikte içindeki tüm kartlar kalıcı olarak silinecek.'
                : 'Bu içerik kartı kalıcı olarak silinecek.',
            confirmButtonText: 'Evet, sil',
            cancelButtonText: 'Vazgeç',
        });

        if (confirmed) form.submit();
    }, { signal: ctx.signal });

    ctx.cleanup(() => sortableInstances.forEach((sortable) => sortable.destroy()));
}
