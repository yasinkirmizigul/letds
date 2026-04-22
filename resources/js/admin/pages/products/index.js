import { request } from '@/core/http';
import { showConfirmDialog, showToastMessage } from '@/core/swal-alert';

let imagePopover = null;
let statusPopover = null;

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

function createImagePopover() {
    const element = document.createElement('div');
    element.style.position = 'fixed';
    element.style.zIndex = '9999';
    element.style.display = 'none';
    element.className = 'kt-card p-2 shadow-lg';
    element.innerHTML = '<img src="" style="width:220px;height:220px;object-fit:cover;border-radius:12px;">';
    document.body.appendChild(element);

    return element;
}

function showImagePopover(element, anchor, imageUrl) {
    const image = element.querySelector('img');
    image.src = imageUrl;

    const rect = anchor.getBoundingClientRect();
    const top = Math.min(window.innerHeight - 240, Math.max(10, rect.top - 10));
    const left = Math.min(window.innerWidth - 240, Math.max(10, rect.right + 12));

    element.style.top = `${top}px`;
    element.style.left = `${left}px`;
    element.style.display = 'block';
}

function hideImagePopover(element) {
    if (!element) return;
    element.style.display = 'none';
}

function parseStatusOptions(root) {
    try {
        return Object.entries(JSON.parse(root.dataset.statusOptions || '{}'))
            .map(([key, option]) => ({
                key,
                label: option?.label || key,
                badge: option?.badge || 'kt-badge kt-badge-sm kt-badge-light',
                order: Number(option?.order ?? 0),
            }))
            .sort((left, right) => left.order - right.order);
    } catch {
        return [];
    }
}

function createStatusPopover() {
    const element = document.createElement('div');
    element.style.position = 'fixed';
    element.style.zIndex = '10000';
    element.style.display = 'none';
    element.className = 'kt-card shadow-lg p-2 w-[280px] transition transform duration-150 ease-out';
    element.innerHTML = `
        <div class="text-xs text-muted-foreground px-2 py-1">Workflow durumu seç</div>
        <div class="grid gap-1" data-status-menu></div>
    `;
    document.body.appendChild(element);

    return element;
}

function showStatusPopover(element, anchor, currentStatus, statusOptions, statusUrl) {
    const menu = element.querySelector('[data-status-menu]');
    if (!menu) return;

    menu.innerHTML = statusOptions.map((option) => {
        const isActive = option.key === currentStatus;

        return `
            <button
                type="button"
                class="w-full flex items-center justify-between gap-2 px-2 py-2 rounded-lg transition duration-150 ease-out hover:bg-muted/40 cursor-pointer ${isActive ? 'bg-muted/40 ring-1 ring-border' : ''}"
                data-status-pick="${option.key}"
                data-status-url="${statusUrl}"
                ${isActive ? 'data-active="1"' : ''}
            >
                <span class="${option.badge}">${option.label}</span>
                <span class="text-muted-foreground ${isActive ? '' : 'opacity-0'} transition-opacity duration-150">
                    <i class="ki-outline ki-check-circle"></i>
                </span>
            </button>
        `;
    }).join('');

    const rect = anchor.getBoundingClientRect();
    const top = Math.min(window.innerHeight - 320, Math.max(10, rect.bottom + 6));
    const left = Math.min(window.innerWidth - 300, Math.max(10, rect.left));

    element.style.top = `${top}px`;
    element.style.left = `${left}px`;
    element.style.opacity = '0';
    element.style.transform = 'translateY(-4px)';
    element.style.display = 'block';

    requestAnimationFrame(() => {
        element.style.opacity = '1';
        element.style.transform = 'translateY(0)';
        element.querySelector('[data-active="1"]')?.focus?.();
    });
}

function hideStatusPopover(element) {
    if (!element || element.style.display !== 'block') return;

    element.style.opacity = '0';
    element.style.transform = 'translateY(-4px)';

    window.setTimeout(() => {
        element.style.display = 'none';
    }, 120);
}

function renderPagination(api, host) {
    if (!host || !api) return;

    const info = api.page.info();
    const pages = info.pages;
    const page = info.page;

    host.innerHTML = '';
    if (pages <= 1) return;

    const makeBtn = (label, targetPage, disabled = false, active = false) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = active ? 'kt-btn kt-btn-sm kt-btn-primary' : 'kt-btn kt-btn-sm kt-btn-light';
        if (disabled) button.disabled = true;
        button.textContent = label;
        button.addEventListener('click', () => api.page(targetPage).draw('page'));

        return button;
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
    const select = root.querySelector('#productsCategoryFilter');

    return Array.from(select?.selectedOptions || [])
        .map((option) => String(option.value || '').trim())
        .filter(Boolean);
}

function createProductFilter(root, tableEl) {
    return (settings, _data, dataIndex) => {
        if (settings.nTable !== tableEl) return true;

        const row = settings.aoData?.[dataIndex]?.nTr;
        if (!row) return true;

        const selectedStatus = root.querySelector('#productsStatusFilter')?.value || 'all';
        const selectedCategories = selectedCategoryIds(root);

        if (selectedStatus !== 'all' && row.dataset.status !== selectedStatus) {
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

async function postJson(url, body, signal, method = 'POST') {
    const response = await request(url, {
        method,
        data: body,
        signal,
        ignoreGlobalError: true,
    });

    if (response?.ok === false) {
        throw new Error(response?.message || 'İşlem başarısız.');
    }

    return response;
}

function redrawOwningTable(element) {
    const table = element?.closest?.('table');
    if (!table || !window.jQuery?.fn?.dataTable) return;
    if (!window.jQuery.fn.dataTable.isDataTable(table)) return;

    window.jQuery(table).DataTable().draw(false);
}

function setFeaturedState(row, isFeatured, featuredAt) {
    if (!row) return;

    row.dataset.featured = isFeatured ? '1' : '0';

    const badge = row.querySelector('.js-featured-badge');
    const featuredAtText = row.querySelector('.js-featured-at');
    const toggle = row.querySelector('.js-featured-toggle');

    if (toggle) {
        toggle.checked = !!isFeatured;
    }

    if (badge) {
        badge.classList.remove('kt-badge-light-success', 'kt-badge-light', 'text-muted-foreground');
        if (isFeatured) {
            badge.classList.add('kt-badge-light-success');
            badge.textContent = 'Anasayfada';
        } else {
            badge.classList.add('kt-badge-light', 'text-muted-foreground');
            badge.textContent = 'Kapalı';
        }
    }

    if (featuredAtText) {
        featuredAtText.textContent = isFeatured && featuredAt
            ? `Seçim: ${featuredAt}`
            : 'Seçim yapılmamış';
    }
}

function setStatusState(button, row, payload) {
    const data = payload?.data || {};
    if (!button || !row) return;

    button.dataset.status = data.status || button.dataset.status || '';
    row.dataset.status = data.status || row.dataset.status || '';

    button.className = `${data.status_badge || 'kt-badge kt-badge-sm kt-badge-light'} js-status-trigger`;
    button.innerHTML = `${data.status_label || data.status || 'Status'} <i class="ki-outline ki-down ml-1"></i>`;
}

export default function init(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;
    const tableEl = root.querySelector('#products_table');

    if (!tableEl) return;

    const perPage = root.dataset.perpage ? parseInt(root.dataset.perpage, 10) : 25;
    const bulkBar = root.querySelector('#productsBulkBar');
    const selectedCountEl = root.querySelector('#productsSelectedCount');
    const checkAll = root.querySelector('#products_check_all');
    const btnBulkDelete = root.querySelector('#productsBulkDeleteBtn');
    const btnBulkRestore = root.querySelector('#productsBulkRestoreBtn');
    const btnBulkForce = root.querySelector('#productsBulkForceDeleteBtn');
    const selectedIds = new Set();
    const statusOptions = parseStatusOptions(root);

    function updateBulkUI() {
        const count = selectedIds.size;

        bulkBar?.classList.toggle('hidden', count === 0);
        if (selectedCountEl) selectedCountEl.textContent = String(count);

        if (btnBulkDelete) btnBulkDelete.disabled = count === 0;
        if (btnBulkRestore) btnBulkRestore.disabled = count === 0;
        if (btnBulkForce) btnBulkForce.disabled = count === 0;

        if (!checkAll) return;

        const boxes = Array.from(root.querySelectorAll('input.products-check'));
        const checked = boxes.filter((box) => box.checked).length;
        checkAll.indeterminate = checked > 0 && checked < boxes.length;
        checkAll.checked = boxes.length > 0 && checked === boxes.length;
    }

    function applySelectionToCurrentPage() {
        root.querySelectorAll('input.products-check').forEach((checkbox) => {
            checkbox.checked = selectedIds.has(String(checkbox.value || ''));
        });
        updateBulkUI();
    }

    const filters = window.jQuery?.fn?.dataTable?.ext?.search;
    const filterFn = createProductFilter(root, tableEl);

    if (Array.isArray(filters)) {
        filters.push(filterFn);
        ctx.cleanup(() => {
            const index = filters.indexOf(filterFn);
            if (index >= 0) {
                filters.splice(index, 1);
            }
        });
    }

    imagePopover = createImagePopover();
    statusPopover = createStatusPopover();

    ctx.cleanup(() => {
        try { imagePopover?.remove(); } catch {}
        try { statusPopover?.remove(); } catch {}
        imagePopover = null;
        statusPopover = null;
    });

    statusPopover.addEventListener('click', async (event) => {
        const pick = event.target?.closest?.('[data-status-pick]');
        if (!pick) return;

        const status = pick.getAttribute('data-status-pick');
        const statusUrl = pick.getAttribute('data-status-url');
        if (!status || !statusUrl) return;

        try {
            const response = await postJson(statusUrl, { status }, signal, 'PATCH');
            const trigger = document.querySelector(`.js-status-trigger[data-status-url="${statusUrl}"]`);
            const row = trigger?.closest('tr');

            setStatusState(trigger, row, response);
            notify('success', response?.message || 'Durum güncellendi.');
            hideStatusPopover(statusPopover);
            redrawOwningTable(trigger);
        } catch (error) {
            notify('error', resolveErrorMessage(error, 'Durum güncellenemedi.'));
        }
    }, { signal });

    root.addEventListener('mouseover', (event) => {
        const anchor = event.target?.closest?.('.js-img-popover');
        if (!anchor || !root.contains(anchor)) return;
        const imageUrl = anchor.getAttribute('data-popover-img');
        if (imageUrl) showImagePopover(imagePopover, anchor, imageUrl);
    }, { signal });

    root.addEventListener('mouseout', (event) => {
        const anchor = event.target?.closest?.('.js-img-popover');
        if (!anchor || !root.contains(anchor)) return;
        const relatedTarget = event.relatedTarget;
        if (relatedTarget && anchor.contains(relatedTarget)) return;
        hideImagePopover(imagePopover);
    }, { signal });

    root.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement)) return;

        if (target.classList.contains('products-check')) {
            const id = String(target.value || '');
            if (!id) return;

            if (target.checked) selectedIds.add(id);
            else selectedIds.delete(id);

            updateBulkUI();
            return;
        }

        if (target.id === 'products_check_all') {
            const checked = !!target.checked;
            root.querySelectorAll('input.products-check').forEach((checkbox) => {
                checkbox.checked = checked;

                const id = String(checkbox.value || '');
                if (!id) return;
                if (checked) selectedIds.add(id);
                else selectedIds.delete(id);
            });

            updateBulkUI();
            return;
        }

        if (target.classList.contains('js-featured-toggle')) {
            const row = target.closest('tr');
            const rollback = !target.checked;

            target.disabled = true;

            postJson(target.dataset.url, { is_featured: target.checked ? 1 : 0 }, signal, 'PATCH')
                .then((response) => {
                    setFeaturedState(row, !!response?.data?.is_featured, response?.data?.featured_at || null);
                    notify('success', response?.message || (target.checked ? 'Ürün anasayfaya alındı.' : 'Ürün anasayfadan kaldırıldı.'));
                    redrawOwningTable(target);
                })
                .catch((error) => {
                    target.checked = rollback;
                    notify('error', resolveErrorMessage(error, 'Vitrin durumu güncellenemedi.'));
                })
                .finally(() => {
                    target.disabled = false;
                });
        }
    }, { signal });

    const api = window.initDataTable?.({
        root,
        table: '#products_table',
        search: '#productsSearch',
        pageSize: '#productsPageSize',
        info: '#productsInfo',
        pagination: '#productsPagination',
        pageLength: perPage,
        lengthMenu: [10, 25, 50, 100],
        order: [[5, 'desc']],
        dom: 't',
        emptyTemplate: '#dt-empty-products',
        zeroTemplate: '#dt-zero-products',
        columnDefs: [
            { orderable: false, searchable: false, targets: [0, 6, 7] },
            { className: 'text-right', targets: [6, 7] },
        ],
        signal,
        cleanup: (fn) => ctx.cleanup(fn),
        onDraw: (dtApi) => {
            renderPagination(dtApi || api, root.querySelector('#productsPagination'));
            applySelectionToCurrentPage();
        },
    });

    const searchInput = root.querySelector('#productsSearch');
    if (api && searchInput?.value) {
        api.search(searchInput.value).draw();
    }

    const redrawFilters = () => api?.draw();
    root.querySelector('#productsStatusFilter')?.addEventListener('change', redrawFilters, { signal });
    root.querySelector('#productsCategoryFilter')?.addEventListener('change', redrawFilters, { signal });

    root.querySelector('#productsClearFiltersBtn')?.addEventListener('click', () => {
        if (searchInput) {
            searchInput.value = '';
        }

        const statusFilter = root.querySelector('#productsStatusFilter');
        if (statusFilter) {
            statusFilter.value = 'all';
            statusFilter.dispatchEvent(new Event('change', { bubbles: true }));
        }

        const categoryFilter = root.querySelector('#productsCategoryFilter');
        if (categoryFilter) {
            Array.from(categoryFilter.options).forEach((option) => {
                option.selected = false;
            });
            categoryFilter.dispatchEvent(new Event('change', { bubbles: true }));
        }

        api?.search('').draw();
    }, { signal });

    root.addEventListener('click', async (event) => {
        const trigger = event.target?.closest?.('.js-status-trigger');
        if (trigger && root.contains(trigger)) {
            hideStatusPopover(statusPopover);
            showStatusPopover(
                statusPopover,
                trigger,
                trigger.dataset.status || '',
                statusOptions,
                trigger.dataset.statusUrl || ''
            );
            return;
        }

        const actionButton = event.target?.closest?.('[data-action]');
        if (!actionButton || !root.contains(actionButton)) return;

        const action = actionButton.getAttribute('data-action');
        const url = actionButton.getAttribute('data-url');
        if (!action || !url) return;
        if (actionButton.dataset.busy === '1') return;

        actionButton.dataset.busy = '1';

        try {
            if (action === 'delete') {
                const ok = await showConfirmDialog({
                    type: 'warning',
                    title: 'Ürün silinsin mi?',
                    message: 'Ürün çöp kutusuna taşınacak.',
                    confirmButtonText: 'Sil',
                });
                if (!ok) return;

                const response = await request(url, { method: 'DELETE', signal, ignoreGlobalError: true });
                notify('success', response?.message || 'Ürün silindi.');
                window.location.reload();
                return;
            }

            if (action === 'restore') {
                const ok = await showConfirmDialog({
                    type: 'success',
                    title: 'Ürün geri yüklensin mi?',
                    message: 'Kayıt tekrar aktif listeye alınacak.',
                    confirmButtonText: 'Geri yükle',
                });
                if (!ok) return;

                const response = await postJson(url, {}, signal);
                notify('success', response?.message || 'Ürün geri yüklendi.');
                window.location.reload();
                return;
            }

            if (action === 'force-delete') {
                const ok = await showConfirmDialog({
                    type: 'error',
                    title: 'Ürün kalıcı olarak silinsin mi?',
                    message: 'Bu işlem geri alınamaz.',
                    confirmButtonText: 'Kalıcı sil',
                });
                if (!ok) return;

                const response = await request(url, { method: 'DELETE', signal, ignoreGlobalError: true });
                notify('success', response?.message || 'Ürün kalıcı olarak silindi.');
                window.location.reload();
            }
        } catch (error) {
            notify('error', resolveErrorMessage(error, 'İşlem başarısız.'));
        } finally {
            actionButton.dataset.busy = '0';
        }
    }, { signal });

    document.addEventListener('click', (event) => {
        if (statusPopover?.style.display !== 'block') return;

        const insidePopover = statusPopover.contains(event.target);
        const insideTrigger = event.target?.closest?.('.js-status-trigger');

        if (!insidePopover && !insideTrigger) {
            hideStatusPopover(statusPopover);
        }
    }, { signal });

    btnBulkDelete?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        const ok = await showConfirmDialog({
            type: 'warning',
            title: 'Seçili ürünler silinsin mi?',
            message: `${ids.length} kayıt çöp kutusuna taşınacak.`,
            confirmButtonText: 'Sil',
        });
        if (!ok) return;

        try {
            const response = await postJson(root.dataset.bulkDeleteUrl, { ids }, signal);
            notify('success', response?.message || 'Seçili ürünler silindi.');
            selectedIds.clear();
            window.location.reload();
        } catch (error) {
            notify('error', resolveErrorMessage(error, 'Silme işlemi başarısız.'));
            updateBulkUI();
        }
    }, { signal });

    btnBulkRestore?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        const ok = await showConfirmDialog({
            type: 'success',
            title: 'Seçili ürünler geri yüklensin mi?',
            message: `${ids.length} kayıt tekrar aktif listeye alınacak.`,
            confirmButtonText: 'Geri yükle',
        });
        if (!ok) return;

        try {
            const response = await postJson(root.dataset.bulkRestoreUrl, { ids }, signal);
            notify('success', response?.message || 'Seçili ürünler geri yüklendi.');
            selectedIds.clear();
            window.location.reload();
        } catch (error) {
            notify('error', resolveErrorMessage(error, 'Geri yükleme başarısız.'));
            updateBulkUI();
        }
    }, { signal });

    btnBulkForce?.addEventListener('click', async () => {
        const ids = [...selectedIds];
        if (!ids.length) return;

        const ok = await showConfirmDialog({
            type: 'error',
            title: 'Seçili ürünler kalıcı olarak silinsin mi?',
            message: `${ids.length} kayıt geri alinamayacak sekilde silinecek.`,
            confirmButtonText: 'Kalıcı sil',
        });
        if (!ok) return;

        try {
            const response = await postJson(root.dataset.bulkForceDeleteUrl, { ids }, signal);
            notify('success', response?.message || 'Seçili ürünler kalıcı olarak silindi.');
            selectedIds.clear();
            window.location.reload();
        } catch (error) {
            notify('error', resolveErrorMessage(error, 'Kalıcı silme başarısız.'));
            updateBulkUI();
        }
    }, { signal });

    root.querySelectorAll('tr[data-status]').forEach((row) => {
        setFeaturedState(row, row.dataset.featured === '1', row.querySelector('.js-featured-at')?.textContent?.replace(/^Seçim:\s*/, '') || null);
    });

    renderPagination(api, root.querySelector('#productsPagination'));
    updateBulkUI();
}

export function destroy() {
    try { imagePopover?.remove(); } catch {}
    try { statusPopover?.remove(); } catch {}

    imagePopover = null;
    statusPopover = null;
}
