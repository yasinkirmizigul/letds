function qs(sel, root = document) {
    return root.querySelector(sel);
}

export default function init(ctx = {}) {
    const root = ctx.root || qs('[data-page="categories.index"]');
    if (!root) return;

    const pageEl = root.matches?.('[data-page="categories.index"]')
        ? root
        : qs('[data-page="categories.index"]', root);

    if (!pageEl) return;

    const tableEl = qs('#categories_table', pageEl);
    if (!tableEl) return;

    const ajaxUrl = tableEl.getAttribute('data-ajax');
    if (!ajaxUrl) return;

    if (typeof window.initDataTable !== 'function') return;

    window.initDataTable({
        root: pageEl,
        table: '#categories_table',
        serverSide: true,
        processing: true,
        ajax: ajaxUrl,

        emptyTemplate: '#dt-empty-categories',
        zeroTemplate: '#dt-zero-categories',

        search: '#categoriesSearch',
        pageSize: '#categoriesPageSize',
        info: '#categoriesInfo',
        pagination: '#categoriesPagination',

        columns: [
            { data: 'checkbox', orderable: false, searchable: false, width: '55px' },
            { data: 'name' },
            { data: 'slug' },
            { data: 'parent_name', orderable: false },
            { data: 'blog_posts_count' },
            { data: 'actions', orderable: false, searchable: false },
        ],

        order: [[1, 'asc']],
    });
}
