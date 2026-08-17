import Sortable from 'sortablejs';

const MAX_LAYOUT_COLUMNS = 3;

function qs(root, selector) {
    return (root || document).querySelector(selector);
}

function qsa(root, selector) {
    return Array.from((root || document).querySelectorAll(selector));
}

function directChildren(root, selector) {
    if (!root) return [];

    return Array.from(root.children).filter((element) => element.matches(selector));
}

function moveItem(item, direction) {
    if (!item) return;

    const list = item.closest('[data-dashboard-sort-list]');
    if (!list) return;

    if (direction === 'up') {
        const previous = item.previousElementSibling;
        if (previous) list.insertBefore(item, previous);
        return;
    }

    const next = item.nextElementSibling;
    if (next) list.insertBefore(next, item);
}

function createLayoutRow() {
    const row = document.createElement('div');
    row.className = 'dashboard-layout-row';
    row.dataset.dashboardLayoutRow = '';
    row.innerHTML = `
        <div class="dashboard-layout-row__head">
            <span class="dashboard-layout-row__title">
                <button type="button" class="dashboard-layout-row__handle kt-btn kt-btn-sm kt-btn-light kt-btn-icon" data-dashboard-row-handle title="Satırı sürükleyerek taşı" aria-label="Satırı sürükleyerek taşı">
                    <i class="ki-outline ki-menu"></i>
                </button>
                <span class="font-semibold text-foreground" data-dashboard-layout-row-number></span>
            </span>
            <span class="dashboard-layout-row__actions">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary" data-dashboard-layout-column-label></span>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-light kt-btn-icon" data-dashboard-row-move="up" title="Satırı yukarı taşı" aria-label="Satırı yukarı taşı">
                    <i class="ki-filled ki-arrow-up"></i>
                </button>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-light kt-btn-icon" data-dashboard-row-move="down" title="Satırı aşağı taşı" aria-label="Satırı aşağı taşı">
                    <i class="ki-filled ki-arrow-down"></i>
                </button>
            </span>
        </div>
        <div class="dashboard-layout-row__cells" data-dashboard-layout-cells data-columns="1"></div>
    `;

    return row;
}

function announce(builder, message) {
    const status = qs(builder, '[data-dashboard-layout-status]');
    if (!status) return;

    status.textContent = '';
    window.requestAnimationFrame(() => {
        status.textContent = message;
    });
}

function initLayoutBuilder(builder) {
    const rowsContainer = qs(builder, '[data-dashboard-layout-rows]');
    if (!rowsContainer) return () => {};

    const sortableInstances = [];

    function repairNestedRows() {
        const nestedRows = qsa(rowsContainer, '[data-dashboard-layout-row]')
            .filter((row) => row.parentElement !== rowsContainer);

        nestedRows.forEach((row) => {
            let parentRow = row.parentElement?.closest('[data-dashboard-layout-row]');

            while (parentRow && parentRow.parentElement !== rowsContainer) {
                parentRow = parentRow?.parentElement?.closest('[data-dashboard-layout-row]');
            }

            if (!parentRow) {
                rowsContainer.appendChild(row);
                return;
            }

            rowsContainer.insertBefore(row, parentRow.nextElementSibling);
        });
    }

    function initialiseCellSortable(cells) {
        if (cells.__dashboardSortable) return;

        const sortable = new Sortable(cells, {
            group: {
                name: 'dashboard-layout-items',
                pull: true,
                put(to, from, draggedElement) {
                    return draggedElement.matches('[data-dashboard-layout-item]')
                        && !draggedElement.matches('[data-dashboard-layout-row]')
                        && (to.el === from.el || directChildren(to.el, '[data-dashboard-layout-item]').length < MAX_LAYOUT_COLUMNS);
                },
            },
            animation: 180,
            draggable: '>[data-dashboard-layout-item]',
            handle: '.dashboard-sort-handle',
            ghostClass: 'dashboard-layout-ghost',
            chosenClass: 'dashboard-layout-chosen',
            dragClass: 'is-dragging',
            forceFallback: true,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            onChoose() {
                builder.classList.add('is-layout-dragging');
            },
            onMove(event) {
                if (!event.dragged.matches('[data-dashboard-layout-item]') || event.dragged.matches('[data-dashboard-layout-row]')) {
                    return false;
                }

                const targetIsFull = event.to !== event.from
                    && directChildren(event.to, '[data-dashboard-layout-item]').length >= MAX_LAYOUT_COLUMNS;

                if (targetIsFull) {
                    event.to.closest('[data-dashboard-layout-row]')?.classList.add('is-drop-blocked');
                    announce(builder, `Bir satır en fazla ${MAX_LAYOUT_COLUMNS} blok içerebilir.`);
                    return false;
                }

                qsa(builder, '.is-drop-blocked').forEach((row) => row.classList.remove('is-drop-blocked'));
                return true;
            },
            onAdd: refreshLayout,
            onUpdate: refreshLayout,
            onEnd() {
                builder.classList.remove('is-layout-dragging');
                qsa(builder, '.is-drop-blocked').forEach((row) => row.classList.remove('is-drop-blocked'));
                refreshLayout();
                announce(builder, 'Dashboard blok yerleşimi güncellendi. Kaydettiğinde uygulanacak.');
            },
        });

        cells.__dashboardSortable = sortable;
        sortableInstances.push(sortable);
    }

    function refreshLayout() {
        repairNestedRows();

        directChildren(rowsContainer, '[data-dashboard-layout-row]').forEach((row) => {
            const cells = qs(row, ':scope > [data-dashboard-layout-cells]');
            if (directChildren(cells, '[data-dashboard-layout-item]').length > 0) return;

            cells?.__dashboardSortable?.destroy();
            if (cells) cells.__dashboardSortable = null;
            row.remove();
        });

        const rows = directChildren(rowsContainer, '[data-dashboard-layout-row]');
        rows.forEach((row, rowIndex) => {
            const cells = qs(row, ':scope > [data-dashboard-layout-cells]');
            const items = directChildren(cells, '[data-dashboard-layout-item]');
            const columnCount = Math.min(items.length, MAX_LAYOUT_COLUMNS);

            initialiseCellSortable(cells);
            row.classList.toggle('is-full', columnCount >= MAX_LAYOUT_COLUMNS);
            cells.dataset.columns = String(columnCount);

            const rowNumber = qs(row, '[data-dashboard-layout-row-number]');
            const columnLabel = qs(row, '[data-dashboard-layout-column-label]');
            const upButton = qs(row, ':scope > .dashboard-layout-row__head [data-dashboard-row-move="up"]');
            const downButton = qs(row, ':scope > .dashboard-layout-row__head [data-dashboard-row-move="down"]');
            if (rowNumber) rowNumber.textContent = `Satır ${rowIndex + 1}`;
            if (columnLabel) columnLabel.textContent = `${columnCount} sütun`;
            if (upButton) upButton.disabled = rowIndex === 0;
            if (downButton) downButton.disabled = rowIndex === rows.length - 1;

            items.forEach((item, itemIndex) => {
                const input = qs(item, '[data-dashboard-layout-input]');
                if (input) input.name = `layout_rows[${rowIndex}][]`;

                const previousButton = qs(item, '[data-dashboard-layout-move="previous"]');
                const nextButton = qs(item, '[data-dashboard-layout-move="next"]');
                const separateButton = qs(item, '[data-dashboard-layout-separate]');
                if (previousButton) previousButton.disabled = itemIndex === 0;
                if (nextButton) nextButton.disabled = itemIndex === items.length - 1;
                if (separateButton) separateButton.disabled = items.length === 1;
            });
        });
    }

    const rowSortable = new Sortable(rowsContainer, {
        animation: 180,
        draggable: '>[data-dashboard-layout-row]',
        handle: '[data-dashboard-row-handle]',
        ghostClass: 'dashboard-layout-row-ghost',
        chosenClass: 'dashboard-layout-row-chosen',
        forceFallback: true,
        fallbackOnBody: true,
        onMove(event) {
            if (event.dragged.parentElement !== rowsContainer) return false;

            return !event.related
                || event.related === rowsContainer
                || event.related.parentElement === rowsContainer;
        },
        onEnd() {
            refreshLayout();
            announce(builder, 'Dashboard satır sırası güncellendi. Kaydettiğinde uygulanacak.');
        },
    });
    sortableInstances.push(rowSortable);

    qsa(builder, '[data-dashboard-new-row-zone]').forEach((zone) => {
        const zoneSortable = new Sortable(zone, {
            group: {
                name: 'dashboard-layout-items',
                pull: false,
                put(to, from, draggedElement) {
                    return draggedElement.matches('[data-dashboard-layout-item]')
                        && !draggedElement.matches('[data-dashboard-layout-row]');
                },
            },
            animation: 180,
            draggable: '>[data-dashboard-layout-item]',
            sort: false,
            forceFallback: true,
            fallbackOnBody: true,
            emptyInsertThreshold: 48,
            onAdd(event) {
                const newRow = createLayoutRow();
                const firstRow = qs(rowsContainer, '[data-dashboard-layout-row]');

                if (zone.dataset.dashboardNewRowPosition === 'before') {
                    rowsContainer.insertBefore(newRow, firstRow);
                } else {
                    rowsContainer.appendChild(newRow);
                }

                qs(newRow, '[data-dashboard-layout-cells]').appendChild(event.item);
                builder.classList.remove('is-layout-dragging');
                refreshLayout();
                announce(builder, 'Blok gruptan çıkarıldı ve yeni bir tekli satıra taşındı. Kaydettiğinde uygulanacak.');
            },
        });

        sortableInstances.push(zoneSortable);
    });

    refreshLayout();

    builder.addEventListener('click', (event) => {
        const rowMoveButton = event.target.closest('[data-dashboard-row-move]');
        const moveButton = event.target.closest('[data-dashboard-layout-move]');
        const separateButton = event.target.closest('[data-dashboard-layout-separate]');

        if (rowMoveButton) {
            const row = rowMoveButton.closest('[data-dashboard-layout-row]');
            const rows = directChildren(rowsContainer, '[data-dashboard-layout-row]');
            const rowIndex = rows.indexOf(row);

            if (rowMoveButton.dataset.dashboardRowMove === 'up' && rowIndex > 0) {
                rowsContainer.insertBefore(row, rows[rowIndex - 1]);
            } else if (rowMoveButton.dataset.dashboardRowMove === 'down' && rowIndex < rows.length - 1) {
                rowsContainer.insertBefore(rows[rowIndex + 1], row);
            }

            refreshLayout();
            announce(builder, 'Dashboard satır sırası güncellendi. Kaydettiğinde uygulanacak.');
            return;
        }

        const item = (moveButton || separateButton)?.closest('[data-dashboard-layout-item]');
        if (!item) return;

        const row = item.closest('[data-dashboard-layout-row]');
        const cells = qs(row, '[data-dashboard-layout-cells]');

        if (moveButton?.dataset.dashboardLayoutMove === 'previous' && item.previousElementSibling) {
            cells.insertBefore(item, item.previousElementSibling);
            refreshLayout();
            return;
        }

        if (moveButton?.dataset.dashboardLayoutMove === 'next' && item.nextElementSibling) {
            cells.insertBefore(item.nextElementSibling, item);
            refreshLayout();
            return;
        }

        if (separateButton) {
            const rows = directChildren(rowsContainer, '[data-dashboard-layout-row]');
            const newRow = createLayoutRow();
            rowsContainer.insertBefore(newRow, rows[rows.indexOf(row) + 1] || null);
            qs(newRow, '[data-dashboard-layout-cells]').appendChild(item);
            refreshLayout();
            announce(builder, 'Blok yeni bir tekli satıra ayrıldı. Kaydettiğinde uygulanacak.');
        }
    });

    builder.closest('form')?.addEventListener('submit', refreshLayout);

    return () => sortableInstances.forEach((sortable) => {
        try {
            sortable.destroy();
        } catch {
            // A removed empty row may already have destroyed its Sortable instance.
        }
    });
}

export default function init(ctx) {
    const root = ctx?.root || document;
    const sortableInstances = qsa(root, '[data-dashboard-sort-list]').map((list) => new Sortable(list, {
        animation: 160,
        draggable: '[data-dashboard-sort-item]',
        handle: '.dashboard-sort-handle',
        ghostClass: 'dashboard-layout-ghost',
        forceFallback: true,
        fallbackOnBody: true,
    }));
    const layoutCleanups = qsa(root, '[data-dashboard-layout-builder]').map(initLayoutBuilder);

    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-dashboard-move]');
        if (!button) return;

        moveItem(button.closest('[data-dashboard-sort-item]'), button.dataset.dashboardMove);
    });

    ctx?.cleanup?.(() => {
        sortableInstances.forEach((sortable) => sortable.destroy());
        layoutCleanups.forEach((cleanup) => cleanup());
    });
}
