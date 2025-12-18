export default function init({ root }) {
    const table = root.querySelector('#roles_table');
    if (!table) return;

    window.initDataTable?.({
        root,
        table: '#roles_table',
        search: '#rolesSearch',
        pageSize: '#rolesPageSize',
        info: '#rolesInfo',
        pagination: '#rolesPagination',

        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        order: [[0, 'asc']],
        dom: 't',

        emptyTemplate: '#dt-empty-roles',
        zeroTemplate: '#dt-zero-roles',

        columnDefs: [
            { orderable: false, searchable: false, targets: [3] },
            { className: 'text-right', targets: [3] },
            { className: 'text-center', targets: [2] },
        ],
    });
}
