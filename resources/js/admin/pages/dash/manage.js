function qs(root, selector) {
    return (root || document).querySelector(selector);
}

function qsa(root, selector) {
    return Array.from((root || document).querySelectorAll(selector));
}

function getDragAfterElement(list, y) {
    const items = qsa(list, '[data-dashboard-sort-item]:not(.is-dragging)');

    return items.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - (box.height / 2);

        if (offset < 0 && offset > closest.offset) {
            return { offset, element: child };
        }

        return closest;
    }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
}

function moveItem(item, direction) {
    if (!item) return;

    const list = item.closest('[data-dashboard-sort-list]');
    if (!list) return;

    if (direction === 'up') {
        const previous = item.previousElementSibling;
        if (previous) {
            list.insertBefore(item, previous);
        }
        return;
    }

    const next = item.nextElementSibling;
    if (next) {
        list.insertBefore(next, item);
    }
}

function initSortableList(list) {
    let dragging = null;

    qsa(list, '[data-dashboard-sort-item]').forEach((item) => {
        item.addEventListener('dragstart', (event) => {
            dragging = item;
            item.classList.add('is-dragging');

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', qs(item, 'input[name="section_order[]"]')?.value || '');
            }
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('is-dragging');
            dragging = null;
        });
    });

    list.addEventListener('dragover', (event) => {
        if (!dragging) return;

        event.preventDefault();
        const afterElement = getDragAfterElement(list, event.clientY);

        if (!afterElement) {
            list.appendChild(dragging);
            return;
        }

        list.insertBefore(dragging, afterElement);
    });
}

export default function init(ctx) {
    const root = ctx?.root || document;

    qsa(root, '[data-dashboard-sort-list]').forEach(initSortableList);

    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-dashboard-move]');
        if (!button) return;

        moveItem(button.closest('[data-dashboard-sort-item]'), button.dataset.dashboardMove);
    });
}
