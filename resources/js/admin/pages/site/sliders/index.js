import Sortable from 'sortablejs';
import { request } from '@/core/http';
import { showToastMessage } from '@/core/swal-alert';

export default function init(ctx) {
    const root = ctx.root;
    const list = root.querySelector('#homeSliderSortable');
    const url = root.dataset.reorderUrl;

    if (!list || !url) return;

    const sortable = new Sortable(list, {
        handle: '.js-sort-handle',
        animation: 160,
        onEnd: async () => {
            const ids = [...list.querySelectorAll('[data-id]')].map((item) => Number(item.dataset.id)).filter(Boolean);
            try {
                await request(url, { method: 'PATCH', data: { ids } });
                showToastMessage('success', 'Slider sırası güncellendi.');
            } catch {}
        },
    });

    ctx.cleanup(() => sortable.destroy());
}
