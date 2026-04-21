export default function init(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;

    const tableEl = root.querySelector('#messages_table');
    if (!tableEl || !window.jQuery?.fn?.dataTable) return;

    const filters = window.jQuery.fn.dataTable.ext.search;
    const filterFn = (settings, _data, dataIndex) => {
        if (settings.nTable !== tableEl) return true;

        const row = settings.aoData?.[dataIndex]?.nTr;
        if (!row) return true;

        const selectedPriority = root.querySelector('#messagesPriorityFilter')?.value || '';
        const selectedRecipient = root.querySelector('#messagesRecipientFilter')?.value || '';
        const selectedRead = root.querySelector('#messagesReadFilter')?.value || '';

        if (selectedPriority && row.dataset.priority !== selectedPriority) {
            return false;
        }

        if (selectedRecipient && row.dataset.recipientId !== selectedRecipient) {
            return false;
        }

        if (selectedRead && row.dataset.read !== selectedRead) {
            return false;
        }

        return true;
    };

    filters.push(filterFn);
    ctx.cleanup(() => {
        const index = filters.indexOf(filterFn);
        if (index >= 0) {
            filters.splice(index, 1);
        }
    });

    const dt = window.initDataTable?.({
        root,
        table: '#messages_table',
        search: '#messagesSearch',
        pageSize: '#messagesPageSize',
        info: '#messagesInfo',
        pagination: '#messagesPagination',
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        order: [[6, 'desc']],
        dom: 't',
        emptyTemplate: '#dt-empty-messages',
        zeroTemplate: '#dt-zero-messages',
        columnDefs: [
            { orderable: false, searchable: false, targets: [7] },
            { className: 'text-center', targets: [0, 4, 7] },
        ],
        signal,
        cleanup: (fn) => ctx.cleanup(fn),
    });

    const redraw = () => dt?.draw();

    ['#messagesPriorityFilter', '#messagesRecipientFilter', '#messagesReadFilter'].forEach((selector) => {
        const element = root.querySelector(selector);
        if (!element) return;

        element.addEventListener('change', redraw, { signal });
    });
}
