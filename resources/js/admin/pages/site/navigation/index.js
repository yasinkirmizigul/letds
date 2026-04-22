import Sortable from 'sortablejs';
import { request } from '@/core/http';
import { showToastMessage } from '@/core/swal-alert';

function toggleLinkFields(scope) {
    const select = scope.querySelector('[data-link-type-select="true"], [data-link-type-select]');
    if (!select) return;

    const sync = () => {
        const value = select.value;
        scope.querySelectorAll('[data-link-field="page"]').forEach((field) => {
            field.classList.toggle('hidden', value !== 'page');
        });
        scope.querySelectorAll('[data-link-field="url"]').forEach((field) => {
            field.classList.toggle('hidden', value !== 'custom');
        });
    };

    sync();
    select.addEventListener('change', sync);
}

function serializeTree(list) {
    return [...list.querySelectorAll(':scope > .js-navigation-item')].map((item) => {
        const childList = item.querySelector(':scope > .js-navigation-children');
        const children = childList
            ? [...childList.querySelectorAll(':scope > .js-navigation-item')].map((child) => ({
                id: Number(child.dataset.id),
            }))
            : [];

        return {
            id: Number(item.dataset.id),
            children,
        };
    });
}

export default function init(ctx) {
    const root = ctx.root;
    const treeUrl = root.dataset.treeUrl;
    if (!treeUrl) return;

    root.querySelectorAll('form, details').forEach((scope) => toggleLinkFields(scope));

    const syncAllTrees = async () => {
        const rootLists = [...root.querySelectorAll('.js-navigation-list[data-root-list="true"]')];

        for (const rootList of rootLists) {
            const location = rootList.dataset.location;
            if (!location) continue;

            await request(treeUrl, {
                method: 'PATCH',
                data: {
                    location,
                    tree: serializeTree(rootList),
                },
            });
        }
    };

    const sortables = [];
    root.querySelectorAll('.js-navigation-list').forEach((list) => {
        const location = list.dataset.location;
        const sortable = new Sortable(list, {
            group: `navigation-${location}`,
            handle: '.js-navigation-handle',
            animation: 160,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            onMove: ({ dragged, to }) => {
                const targetIsChildList = to?.dataset?.childList === 'true';
                const draggedHasChildren = !!dragged?.querySelector?.('.js-navigation-children .js-navigation-item');

                if (targetIsChildList && draggedHasChildren) {
                    return false;
                }

                return true;
            },
            onEnd: async () => {
                try {
                    await syncAllTrees();
                    showToastMessage('success', 'Menü sırası güncellendi.');
                } catch {}
            },
        });

        sortables.push(sortable);
    });

    ctx.cleanup(() => sortables.forEach((sortable) => sortable.destroy()));
}
