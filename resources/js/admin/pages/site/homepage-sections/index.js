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
