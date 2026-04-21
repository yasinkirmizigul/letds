import { request } from '@/core/http';
import { showConfirmDialog, showToastMessage } from '@/core/swal-alert';

let popEl = null;

function notify(type, text) {
    showToastMessage(type === 'error' ? 'error' : 'success', text, { duration: 1800 });
}

function resolveErrorMessage(error, fallback) {
    const validationErrors = error?.data?.errors;
    if (validationErrors && typeof validationErrors === 'object') {
        const firstError = Object.values(validationErrors).flat().find(Boolean);
        if (firstError) {
            return firstError;
        }
    }

    return error?.data?.message || error?.message || fallback;
}

function createPopover() {
    const element = document.createElement('div');
    element.style.position = 'fixed';
    element.style.zIndex = '9999';
    element.style.display = 'none';
    element.className = 'kt-card p-2 shadow-lg';
    element.innerHTML = '<img src="" style="width:220px;height:220px;object-fit:cover;border-radius:12px;">';
    document.body.appendChild(element);
    return element;
}

function showImgPopover(element, anchor, imgUrl) {
    const img = element.querySelector('img');
    img.src = imgUrl;

    const rect = anchor.getBoundingClientRect();
    const top = Math.min(window.innerHeight - 240, Math.max(10, rect.top - 10));
    const left = Math.min(window.innerWidth - 240, Math.max(10, rect.right + 12));

    element.style.top = `${top}px`;
    element.style.left = `${left}px`;
    element.style.display = 'block';
}

function hideImgPopover(element) {
    if (!element) return;
    element.style.display = 'none';
}

function redrawOwningTable(element) {
    const table = element?.closest?.('table');
    if (!table || !window.jQuery?.fn?.dataTable) return;
    if (!window.jQuery.fn.dataTable.isDataTable(table)) return;

    window.jQuery(table).DataTable().draw(false);
}

async function togglePublish(input) {
    const url = input.dataset.url;
    const row = input.closest('tr');
    const badgeWrap = row?.querySelector('.js-badge') ?? null;
    const publishedAt = row?.querySelector('.js-published-at') ?? null;

    const nextValue = input.checked ? 1 : 0;
    const rollback = !input.checked;

    input.disabled = true;
    row?.classList.add('opacity-50');

    try {
        const data = await request(url, {
            method: 'PATCH',
            data: { is_published: nextValue },
            ignoreGlobalError: true,
        });

        if (!data?.ok) {
            throw new Error(data?.message || 'Durum guncellenemedi.');
        }

        if (badgeWrap && data.badge_html) {
            badgeWrap.innerHTML = data.badge_html;
        }

        if (publishedAt) {
            publishedAt.textContent = data.is_published && data.published_at
                ? `Yayin Tarihi: ${data.published_at}`
                : '';
        }

        if (row) {
            row.dataset.published = data.is_published ? '1' : '0';
        }

        notify('success', data.message || (data.is_published ? 'Yazi yayina alindi.' : 'Yazi taslak durumuna alindi.'));
        redrawOwningTable(input);
    } catch (error) {
        input.checked = rollback;
        notify('error', resolveErrorMessage(error, 'Durum guncellenemedi.'));
    } finally {
        input.disabled = false;
        row?.classList.remove('opacity-50');
    }
}

async function toggleFeatured(input) {
    const url = input.dataset.url;
    const row = input.closest('tr');
    const badgeWrap = row?.querySelector('.js-featured-badge') ?? null;
    const featuredAt = row?.querySelector('.js-featured-at') ?? null;

    const nextValue = input.checked ? 1 : 0;
    const rollback = !input.checked;

    input.disabled = true;
    row?.classList.add('opacity-50');

    try {
        const data = await request(url, {
            method: 'PATCH',
            data: { is_featured: nextValue },
            ignoreGlobalError: true,
        });

        if (!data?.ok) {
            throw new Error(data?.message || 'One cikan durumu guncellenemedi.');
        }

        if (badgeWrap && data.badge_html) {
            badgeWrap.innerHTML = data.badge_html;
        }

        if (featuredAt) {
            featuredAt.textContent = data.is_featured && data.featured_at
                ? `Secim: ${data.featured_at}`
                : '';
        }

        if (row) {
            row.dataset.featured = data.is_featured ? '1' : '0';
        }

        notify('success', data.message || (data.is_featured ? 'Yazi anasayfaya alindi.' : 'Yazi anasayfadan kaldirildi.'));
        redrawOwningTable(input);
    } catch (error) {
        input.checked = rollback;
        notify('error', resolveErrorMessage(error, 'Islem basarisiz.'));
    } finally {
        input.disabled = false;
        row?.classList.remove('opacity-50');
    }
}

async function postJson(url, body, signal) {
    const response = await request(url, {
        method: 'POST',
        data: body,
        signal,
        ignoreGlobalError: true,
    });

    if (response?.ok === false) {
        throw new Error(response?.message || 'Islem basarisiz.');
    }

    return response;
}

function renderPagination(api, host) {
    if (!host || !api) return;

    const info = api.page.info();
    const pages = info.pages;
    const page = info.page;

    host.innerHTML = '';
    if (pages <= 1) return;

    const makeBtn = (label, targetPage, disabled = false, active = false) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = active ? 'kt-btn kt-btn-sm kt-btn-primary' : 'kt-btn kt-btn-sm kt-btn-light';
        if (disabled) btn.disabled = true;
        btn.textContent = label;
        btn.addEventListener('click', () => api.page(targetPage).draw('page'));
        return btn;
    };

    host.appendChild(makeBtn('<', Math.max(0, page - 1), page === 0));

    const start = Math.max(0, page - 2);
    const end = Math.min(pages - 1, page + 2);
    for (let index = start; index <= end; index += 1) {
        host.appendChild(makeBtn(String(index + 1), index, false, index === page));
    }

    host.appendChild(makeBtn('>', Math.min(pages - 1, page + 1), page === pages - 1));
}

function selectedCategoryIds(root) {
    const select = root.querySelector('#blogCategoryFilter');
    return Array.from(select?.selectedOptions || [])
        .map((option) => String(option.value || '').trim())
        .filter(Boolean);
}

function createBlogFilter(root, tableEl) {
    return (settings, _data, dataIndex) => {
        if (settings.nTable !== tableEl) return true;

        const row = settings.aoData?.[dataIndex]?.nTr;
        if (!row) return true;

        const selectedStatus = root.querySelector('#blogStatusFilter')?.value || 'all';
        const selectedCategories = selectedCategoryIds(root);

        if (selectedStatus === 'published' && row.dataset.published !== '1') {
            return false;
        }

        if (selectedStatus === 'draft' && row.dataset.published !== '0') {
            return false;
        }

        if (selectedStatus === 'featured' && row.dataset.featured !== '1') {
            return false;
        }

        if (selectedCategories.length > 0) {
            const haystack = row.dataset.categoryIds || '';
            const matched = selectedCategories.some((id) => haystack.includes(`|${id}|`));
            if (!matched) {
                return false;
            }
        }

        return true;
    };
}

export default function init(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;
    const tableEl = root.querySelector('#blog_table');

    if (!tableEl) return;

    const perPage = root.dataset.perpage ? parseInt(root.dataset.perpage, 10) : 25;
    const bulkBar = root.querySelector('#blogBulkBar');
    const selectedCountEl = root.querySelector('#blogSelectedCount');
    const checkAll = root.querySelector('#blog_check_all');
    const btnBulkDelete = root.querySelector('#blogBulkDeleteBtn');
    const btnBulkRestore = root.querySelector('#blogBulkRestoreBtn');
    const btnBulkForce = root.querySelector('#blogBulkForceDeleteBtn');
    const selectedIds = new Set();

    function updateBulkUI() {
        const count = selectedIds.size;

        bulkBar?.classList.toggle('hidden', count === 0);
        if (selectedCountEl) selectedCountEl.textContent = String(count);

        if (btnBulkDelete) btnBulkDelete.disabled = count === 0;
        if (btnBulkRestore) btnBulkRestore.disabled = count === 0;
        if (btnBulkForce) btnBulkForce.disabled = count === 0;

        if (!checkAll) return;

        const boxes = Array.from(root.querySelectorAll('input.blog-check'));
        const checked = boxes.filter((box) => box.checked).length;
        checkAll.indeterminate = checked > 0 && checked < boxes.length;
        checkAll.checked = boxes.length > 0 && checked === boxes.length;
    }

    function applySelectionToCurrentPage() {
        root.querySelectorAll('input.blog-check').forEach((checkbox) => {
            checkbox.checked = selectedIds.has(String(checkbox.value || ''));
        });
        updateBulkUI();
    }

    const filters = window.jQuery?.fn?.dataTable?.ext?.search;
    const filterFn = createBlogFilter(root, tableEl);

    if (Array.isArray(filters)) {
        filters.push(filterFn);
        ctx.cleanup(() => {
            const index = filters.indexOf(filterFn);
            if (index >= 0) {
                filters.splice(index, 1);
            }
        });
    }

    popEl = createPopover();
    ctx.cleanup(() => {
        try { popEl?.remove(); } catch {}
        popEl = null;
    });

    root.addEventListener('mouseover', (event) => {
        const anchor = event.target?.closest?.('.js-img-popover');
        if (!anchor || !root.contains(anchor)) return;
        const img = anchor.getAttribute('data-popover-img');
        if (img) showImgPopover(popEl, anchor, img);
    }, { signal });

    root.addEventListener('mouseout', (event) => {
        const anchor = event.target?.closest?.('.js-img-popover');
        if (!anchor || !root.contains(anchor)) return;
        const relatedTarget = event.relatedTarget;
        if (relatedTarget && anchor.contains(relatedTarget)) return;
        hideImgPopover(popEl);
    }, { signal });

    root.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement)) return;

        if (target.classList.contains('js-publish-toggle')) {
            togglePublish(target);
            return;
        }

        if (target.classList.contains('js-featured-toggle')) {
            toggleFeatured(target);
            return;
        }

        if (target.classList.contains('blog-check')) {
            const id = String(target.value || '');
            if (!id) return;

            if (target.checked) selectedIds.add(id);
            else selectedIds.delete(id);

            updateBulkUI();
            return;
        }

        if (target.id === 'blog_check_all') {
            const checked = !!target.checked;
            root.querySelectorAll('input.blog-check').forEach((checkbox) => {
                checkbox.checked = checked;

                const id = String(checkbox.value || '');
                if (!id) return;

                if (checked) selectedIds.add(id);
                else selectedIds.delete(id);
            });

            updateBulkUI();
        }
    }, { signal });

    const api = window.initDataTable?.({
        root,
        table: '#blog_table',
        search: '#blogSearch',
        pageSize: '#blogPageSize',
        info: '#blogInfo',
        pagination: '#blogPagination',
        pageLength: perPage,
        lengthMenu: [10, 25, 50, 100],
        order: [[5, 'desc']],
        dom: 't',
        emptyTemplate: '#dt-empty-blog',
        zeroTemplate: '#dt-zero-blog',
        columnDefs: [
            { orderable: false, searchable: false, targets: [0, 6, 7] },
            { className: 'text-right', targets: [6, 7] },
        ],
        signal,
        cleanup: (fn) => ctx.cleanup(fn),
        onDraw: (dtApi) => {
            renderPagination(dtApi || api, root.querySelector('#blogPagination'));
            applySelectionToCurrentPage();
        },
    });

    const searchInput = root.querySelector('#blogSearch');
    if (api && searchInput?.value) {
        api.search(searchInput.value).draw();
    }

    const redrawFilters = () => api?.draw();

    root.querySelector('#blogStatusFilter')?.addEventListener('change', redrawFilters, { signal });
    root.querySelector('#blogCategoryFilter')?.addEventListener('change', redrawFilters, { signal });

    root.querySelector('#blogClearFiltersBtn')?.addEventListener('click', () => {
        if (searchInput) {
            searchInput.value = '';
        }

        const statusFilter = root.querySelector('#blogStatusFilter');
        if (statusFilter) {
            statusFilter.value = 'all';
            statusFilter.dispatchEvent(new Event('change', { bubbles: true }));
        }

        const categoryFilter = root.querySelector('#blogCategoryFilter');
        if (categoryFilter) {
            Array.from(categoryFilter.options).forEach((option) => {
                option.selected = false;
            });
            categoryFilter.dispatchEvent(new Event('change', { bubbles: true }));
        }

        api?.search('').draw();
    }, { signal });

    root.addEventListener('click', async (event) => {
        const button = event.target?.closest?.('[data-action]');
        if (!button || !root.contains(button)) return;

        const action = button.getAttribute('data-action');
        const url = button.getAttribute('data-url');
        if (!action || !url) return;
        if (button.dataset.busy === '1') return;

        button.dataset.busy = '1';

        try {
            if (action === 'delete') {
                const ok = await showConfirmDialog({
                    type: 'warning',
                    title: 'Yazi silinsin mi?',
                    message: 'Yazi cop kutusuna tasinacak.',
                    confirmButtonText: 'Sil',
                });
                if (!ok) return;

                const response = await request(url, { method: 'DELETE', signal, ignoreGlobalError: true });
                notify('success', response?.message || 'Yazi silindi.');
                window.location.reload();
                return;
            }

            if (action === 'restore') {
                const ok = await showConfirmDialog({
                    type: 'success',
                    title: 'Yazi geri yuklensin mi?',
                    message: 'Kayit tekrar aktif listeye alinacak.',
                    confirmButtonText: 'Geri yukle',
                });
                if (!ok) return;

                const response = await postJson(url, {}, signal);
                notify('success', response?.message || 'Yazi geri yuklendi.');
                window.location.reload();
                return;
            }

            if (action === 'force-delete') {
                const ok = await showConfirmDialog({
                    type: 'error',
                    title: 'Yazi kalici olarak silinsin mi?',
                    message: 'Bu islem geri alinamaz.',
                    confirmButtonText: 'Kalici sil',
                });
                if (!ok) return;

                const response = await request(url, { method: 'DELETE', signal, ignoreGlobalError: true });
                notify('success', response?.message || 'Yazi kalici olarak silindi.');
                window.location.reload();
            }
        } catch (error) {
            notify('error', resolveErrorMessage(error, 'Islem basarisiz.'));
        } finally {
            button.dataset.busy = '0';
        }
    }, { signal });

    btnBulkDelete?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        const ok = await showConfirmDialog({
            type: 'warning',
            title: 'Secili yazilar silinsin mi?',
            message: `${ids.length} kayit cop kutusuna tasinacak.`,
            confirmButtonText: 'Sil',
        });
        if (!ok) return;

        try {
            const response = await postJson(root.dataset.bulkDeleteUrl, { ids }, signal);
            notify('success', response?.message || 'Secili yazilar silindi.');
            selectedIds.clear();
            window.location.reload();
        } catch (error) {
            notify('error', resolveErrorMessage(error, 'Silme islemi basarisiz.'));
            updateBulkUI();
        }
    }, { signal });

    btnBulkRestore?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        const ok = await showConfirmDialog({
            type: 'success',
            title: 'Secili yazilar geri yuklensin mi?',
            message: `${ids.length} kayit tekrar aktif listeye alinacak.`,
            confirmButtonText: 'Geri yukle',
        });
        if (!ok) return;

        try {
            const response = await postJson(root.dataset.bulkRestoreUrl, { ids }, signal);
            notify('success', response?.message || 'Secili yazilar geri yuklendi.');
            selectedIds.clear();
            window.location.reload();
        } catch (error) {
            notify('error', resolveErrorMessage(error, 'Geri yukleme basarisiz.'));
            updateBulkUI();
        }
    }, { signal });

    btnBulkForce?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        const ok = await showConfirmDialog({
            type: 'error',
            title: 'Secili yazilar kalici olarak silinsin mi?',
            message: `${ids.length} kayit geri alinamayacak sekilde silinecek.`,
            confirmButtonText: 'Kalici sil',
        });
        if (!ok) return;

        try {
            const response = await postJson(root.dataset.bulkForceDeleteUrl, { ids }, signal);
            notify('success', response?.message || 'Secili yazilar kalici olarak silindi.');
            selectedIds.clear();
            window.location.reload();
        } catch (error) {
            notify('error', resolveErrorMessage(error, 'Kalici silme basarisiz.'));
            updateBulkUI();
        }
    }, { signal });

    renderPagination(api, root.querySelector('#blogPagination'));
    updateBulkUI();
}

export function destroy() {
    try { popEl?.remove(); } catch {}
    popEl = null;
}
