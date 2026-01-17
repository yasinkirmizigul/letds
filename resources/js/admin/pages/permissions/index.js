export default function init(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;

    const table = root.querySelector('#permissions_table');
    if (!table) return;

    window.initDataTable?.({
        root,
        table: '#permissions_table',
        search: '#permissionsSearch',
        pageSize: '#permissionsPageSize',
        info: '#permissionsInfo',
        pagination: '#permissionsPagination',

        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        order: [[0, 'asc']],
        dom: 't',

        emptyTemplate: '#dt-empty-permissions',
        zeroTemplate: '#dt-zero-permissions',

        columnDefs: [
            { className: 'text-center', targets: [2] },
        ],

        // ✅ yeni standart
        signal,
        cleanup: (fn) => ctx.cleanup(fn),
    });
}
