import { showConfirmDialog } from '@/core/swal-alert';

function createUsersFilter(root, tableEl) {
    return (settings, _data, dataIndex) => {
        if (settings.nTable !== tableEl) return true;

        const row = settings.aoData?.[dataIndex]?.nTr;
        if (!row) return true;

        const role = root.querySelector('#usersRoleFilter')?.value || 'all';
        const status = root.querySelector('#usersStatusFilter')?.value || 'all';

        if (role !== 'all') {
            const haystack = row.dataset.roleSlugs || '';
            if (!haystack.includes(`|${role}|`)) {
                return false;
            }
        }

        if (status !== 'all' && row.dataset.status !== status) {
            return false;
        }

        return true;
    };
}

export default function init(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;
    const table = root.querySelector('#users_table');
    if (!table) return;

    const filters = window.jQuery?.fn?.dataTable?.ext?.search;
    const filterFn = createUsersFilter(root, table);

    if (Array.isArray(filters)) {
        filters.push(filterFn);
        ctx.cleanup(() => {
            const index = filters.indexOf(filterFn);
            if (index >= 0) {
                filters.splice(index, 1);
            }
        });
    }

    const api = window.initDataTable?.({
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
            { className: 'text-center', targets: [4, 6, 7] },
        ],
        checkAll: '#users_check_all',
        rowChecks: '.users_row_check',
        signal,
        cleanup: (fn) => ctx.cleanup(fn),
    });

    const redraw = () => api?.draw();

    root.querySelector('#usersRoleFilter')?.addEventListener('change', redraw, { signal });
    root.querySelector('#usersStatusFilter')?.addEventListener('change', redraw, { signal });

    root.querySelector('#usersClearFiltersBtn')?.addEventListener('click', () => {
        const search = root.querySelector('#usersSearch');
        if (search) {
            search.value = '';
        }

        const role = root.querySelector('#usersRoleFilter');
        if (role) {
            role.value = 'all';
            role.dispatchEvent(new Event('change', { bubbles: true }));
        }

        const status = root.querySelector('#usersStatusFilter');
        if (status) {
            status.value = 'all';
            status.dispatchEvent(new Event('change', { bubbles: true }));
        }

        api?.search('').draw();
    }, { signal });

    root.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.matches('form[data-confirm="delete-user"]')) return;

        event.preventDefault();

        const ok = await showConfirmDialog({
            type: 'warning',
            title: 'Kullanici silinsin mi?',
            message: 'Kullanici kaydi cop kutusuna tasinacak.',
            confirmButtonText: 'Sil',
        });

        if (!ok) return;
        form.submit();
    }, { signal });
}
