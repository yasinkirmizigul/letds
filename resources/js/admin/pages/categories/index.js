export default function init({ root }) {
    // guard
    const tableEl = root.querySelector('#categories_table');
    if (!tableEl || typeof window.initDataTable !== 'function') return;

    window.initDataTable({
        root,
        table: '#categories_table',
        search: '#categoriesSearch',
        pageSize: '#categoriesPageSize',
        info: '#categoriesInfo',
        pagination: '#categoriesPagination',

        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        order: [[0, 'asc']],
        dom: 't',

        emptyTemplate: '#dt-empty-categories',
        zeroTemplate: '#dt-zero-categories',

        columnDefs: [
            { className: 'text-center', targets: [3] },
            { className: 'text-right', orderable: false, searchable: false, targets: [4] },
        ],
    });
}
