export default function init(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;

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

        // ✅ yeni standart
        signal,
        cleanup: (fn) => ctx.cleanup(fn),
    });

    // delete confirm (signal ile otomatik cleanup)
    root.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.matches('form[data-confirm="delete-user"]')) return;

        if (!confirm('Bu kullanıcıyı silmek istiyor musunuz?')) {
            e.preventDefault();
        }
    }, { signal });
}
