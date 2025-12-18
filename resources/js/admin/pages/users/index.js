export default function init({ root }) {
    const table = root.querySelector('#users_table');
    if (!table) return;

    window.initDataTable?.({
        root,
        table: '#users_table',
        search: '#usersSearch',
        pageSize: '#usersPageSize',
        info: '#usersInfo',
        pagination: '#usersPagination',

        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        order: [[1, 'asc']],
        dom: 't',

        emptyTemplate: '#dt-empty-users',
        zeroTemplate: '#dt-zero-users',

        columnDefs: [
            { orderable: false, searchable: false, targets: [0, 6, 7] },
            { className: 'text-center', targets: [4] },
        ],

        checkAll: '#users_check_all',
        rowChecks: '.users_row_check',
    });
}
