import { request } from '@/core/http';
import { showConfirmDialog, showToastMessage } from '@/core/swal-alert';

function qs(selector, root = document) {
    return root.querySelector(selector);
}

function escHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function buildUrl(base, params) {
    const url = new URL(base, window.location.origin);
    Object.entries(params || {}).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') return;
        url.searchParams.set(key, String(value));
    });
    return url.toString();
}

async function requestJson(url, { method = 'GET', body = null, signal = null } = {}) {
    return request(url, { method, data: body, signal, ignoreGlobalError: true });
}

function renderActions(item, mode) {
    const editUrl = item.edit_url || `/admin/categories/${item.id}/edit`;

    if (mode === 'trash') {
        return `
            <div class="flex items-center justify-end gap-2">
                <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-restore data-url="${escHtml(item.restore_url)}">
                    <i class="ki-outline ki-arrow-circle-left"></i>
                    Geri Yukle
                </button>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-danger" data-force data-url="${escHtml(item.force_url)}">
                    <i class="ki-outline ki-trash"></i>
                    Kalici Sil
                </button>
            </div>
        `;
    }

    return `
        <div class="flex items-center justify-end gap-2">
            <a href="${editUrl}" class="kt-btn kt-btn-sm kt-btn-warning">
                <i class="ki-outline ki-pencil"></i>
                Duzenle
            </a>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-danger" data-delete data-url="${escHtml(item.delete_url)}">
                <i class="ki-outline ki-trash"></i>
                Sil
            </button>
        </div>
    `;
}

function renderConnections(item) {
    const parts = [
        { label: 'Alt', value: Number(item.children_count || 0) },
        { label: 'Blog', value: Number(item.blog_posts_count || 0) },
        { label: 'Proje', value: Number(item.project_count || 0) },
        { label: 'Urun', value: Number(item.product_count || 0) },
    ];

    return `
        <div class="flex flex-wrap gap-2">
            ${parts.map((part) => `
                <span class="kt-badge ${part.value > 0 ? 'kt-badge-light-primary' : 'kt-badge-light'}">
                    ${part.label}: ${part.value}
                </span>
            `).join('')}
        </div>
    `;
}

function renderRow(item, mode, selectedIds) {
    return `
        <tr>
            <td class="w-[55px]">
                <input
                    class="kt-checkbox kt-checkbox-sm"
                    type="checkbox"
                    data-row-check
                    value="${item.id}"
                    ${selectedIds.has(String(item.id)) ? 'checked' : ''}>
            </td>
            <td class="min-w-[260px]">
                <div class="font-medium text-secondary-foreground">${escHtml(item.name)}</div>
            </td>
            <td class="min-w-[220px]">
                <code class="text-xs">${escHtml(item.slug)}</code>
            </td>
            <td class="min-w-[220px]">
                ${item.parent_name ? escHtml(item.parent_name) : '<span class="text-muted-foreground">-</span>'}
            </td>
            <td class="min-w-[220px]">
                ${renderConnections(item)}
            </td>
            <td class="w-[220px]">
                ${renderActions(item, mode)}
            </td>
        </tr>
    `;
}

function renderPagination(meta, host) {
    if (!host) return;

    const current = Number(meta?.current_page || 1);
    const last = Number(meta?.last_page || 1);

    const button = (page, label = null, active = false, disabled = false) => {
        const text = label ?? String(page);
        const klass = active ? 'kt-btn-primary' : 'kt-btn-light';
        return `<button type="button" class="kt-btn kt-btn-sm ${klass}" data-page="${page}" ${disabled ? 'disabled' : ''}>${text}</button>`;
    };

    if (last <= 1) {
        host.innerHTML = '';
        return;
    }

    const parts = [];
    parts.push(button(Math.max(1, current - 1), '<', false, current <= 1));

    const from = Math.max(1, current - 2);
    const to = Math.min(last, current + 2);

    if (from > 1) {
        parts.push(button(1, '1', current === 1));
        if (from > 2) parts.push('<span class="px-2 text-muted-foreground">...</span>');
    }

    for (let page = from; page <= to; page += 1) {
        parts.push(button(page, null, page === current));
    }

    if (to < last) {
        if (to < last - 1) parts.push('<span class="px-2 text-muted-foreground">...</span>');
        parts.push(button(last, String(last), current === last));
    }

    parts.push(button(Math.min(last, current + 1), '>', false, current >= last));
    host.innerHTML = `<div class="flex items-center gap-2 flex-wrap">${parts.join('')}</div>`;
}

export default function init(ctx = {}) {
    const root = ctx.root || qs('[data-page="categories.index"]');
    if (!root) return;

    const pageEl = root.matches?.('[data-page="categories.index"]')
        ? root
        : qs('[data-page="categories.index"]', root);

    if (!pageEl) return;

    const mode = (pageEl.getAttribute('data-mode') || 'active').toLowerCase();
    const listUrl = pageEl.getAttribute('data-list-url') || '/admin/categories/list';
    const bulkDeleteUrl = pageEl.getAttribute('data-bulk-delete-url') || '/admin/categories/bulk-delete';
    const bulkRestoreUrl = pageEl.getAttribute('data-bulk-restore-url') || '/admin/categories/bulk-restore';
    const bulkForceUrl = pageEl.getAttribute('data-bulk-force-delete-url') || '/admin/categories/bulk-force-delete';

    const tbody = qs('#categoriesTbody', pageEl);
    const searchEl = qs('#categoriesSearch', pageEl);
    const perEl = qs('#categoriesPageSize', pageEl);
    const infoEl = qs('#categoriesInfo', pageEl);
    const paginationEl = qs('#categoriesPagination', pageEl);
    const checkAllHead = qs('#categories_check_all_head', pageEl);
    const checkAllBar = qs('#categories_check_all', pageEl);
    const bulkBar = qs('#categoriesBulkBar', pageEl);
    const selectedCountEl = qs('#categoriesSelectedCount', pageEl);
    const bulkDeleteBtn = qs('#categoriesBulkDeleteBtn', pageEl);
    const bulkRestoreBtn = qs('#categoriesBulkRestoreBtn', pageEl);
    const bulkForceBtn = qs('#categoriesBulkForceDeleteBtn', pageEl);

    if (!tbody) return;

    const selectedIds = new Set();
    const state = {
        q: '',
        page: 1,
        perpage: Number(pageEl.getAttribute('data-perpage') || 25) || 25,
    };

    let abort = null;
    let debounce = null;
    let lastMeta = null;

    if (perEl) {
        perEl.innerHTML = [10, 25, 50, 100]
            .map((value) => `<option value="${value}" ${value === state.perpage ? 'selected' : ''}>${value}</option>`)
            .join('');
    }

    function setBulkUI() {
        const count = selectedIds.size;
        bulkBar?.classList.toggle('hidden', count === 0);
        if (selectedCountEl) selectedCountEl.textContent = String(count);

        if (bulkDeleteBtn) bulkDeleteBtn.disabled = count === 0;
        if (bulkRestoreBtn) bulkRestoreBtn.disabled = count === 0;
        if (bulkForceBtn) bulkForceBtn.disabled = count === 0;

        const boxes = [...pageEl.querySelectorAll('[data-row-check]')];
        const checked = boxes.filter((box) => box.checked).length;

        if (checkAllHead) {
            checkAllHead.indeterminate = checked > 0 && checked < boxes.length;
            checkAllHead.checked = boxes.length > 0 && checked === boxes.length;
        }

        if (checkAllBar) {
            checkAllBar.indeterminate = checkAllHead?.indeterminate || false;
            checkAllBar.checked = checkAllHead?.checked || false;
        }
    }

    async function load() {
        if (abort) abort.abort();
        abort = new AbortController();

        const url = buildUrl(listUrl, {
            mode,
            q: state.q,
            page: state.page,
            perpage: state.perpage,
        });

        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="py-10 text-center text-muted-foreground">Yukleniyor...</td>
            </tr>
        `;

        try {
            const json = await requestJson(url, { signal: abort.signal });
            if (!json || json.ok !== true) {
                throw new Error(json?.message || 'Liste alinamadi');
            }

            const rows = Array.isArray(json.data) ? json.data : [];
            lastMeta = json.meta || null;

            const visibleIds = new Set(rows.map((item) => String(item.id)));
            Array.from(selectedIds).forEach((id) => {
                if (!visibleIds.has(id)) selectedIds.delete(id);
            });

            if (rows.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="py-10 text-center text-muted-foreground">
                            ${state.q ? 'Sonuc bulunamadi.' : 'Henuz kayit yok.'}
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = rows.map((item) => renderRow(item, mode, selectedIds)).join('');
            }

            if (infoEl && lastMeta) {
                const total = Number(lastMeta.total || 0);
                const perPage = Number(lastMeta.per_page || state.perpage);
                const currentPage = Number(lastMeta.current_page || 1);
                const start = total === 0 ? 0 : (currentPage - 1) * perPage + 1;
                const end = Math.min(currentPage * perPage, total);
                infoEl.textContent = total === 0 ? 'Kayit yok' : `${start}-${end} / ${total}`;
            }

            renderPagination(lastMeta, paginationEl);
            setBulkUI();
        } catch (error) {
            if (error?.name === 'AbortError') return;
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="py-10 text-center text-danger">${escHtml(error?.message || 'Hata olustu')}</td>
                </tr>
            `;
        }
    }

    async function performAction(url, method, successMessage, confirmOptions = null) {
        if (!url) return false;

        if (confirmOptions) {
            const ok = await showConfirmDialog(confirmOptions);
            if (!ok) return false;
        }

        try {
            const json = await requestJson(url, { method, body: method === 'POST' ? {} : null, signal: ctx.signal });
            showToastMessage('success', json?.message || successMessage, { duration: 1800 });
            await load();
            return true;
        } catch (error) {
            const message = error?.data?.message || error?.message || 'Islem basarisiz.';
            showToastMessage('error', message);
            return false;
        }
    }

    async function performBulk(url, successMessage, confirmOptions = null) {
        const ids = [...selectedIds].map((id) => Number(id)).filter((id) => Number.isFinite(id));
        if (ids.length === 0) return;

        if (confirmOptions) {
            const ok = await showConfirmDialog(confirmOptions);
            if (!ok) return;
        }

        try {
            const json = await requestJson(url, { method: 'POST', body: { ids }, signal: ctx.signal });
            selectedIds.clear();
            setBulkUI();

            const failed = Array.isArray(json?.failed) ? json.failed.length : 0;
            if (failed > 0) {
                showToastMessage('warning', `${json.done || 0} kayit islendi, ${failed} kayit atlandi.`, { duration: 2600 });
            } else {
                showToastMessage('success', json?.message || successMessage, { duration: 1800 });
            }

            await load();
        } catch (error) {
            showToastMessage('error', error?.data?.message || error?.message || 'Toplu islem basarisiz.');
        }
    }

    function toggleAll(checked) {
        pageEl.querySelectorAll('[data-row-check]').forEach((checkbox) => {
            const value = String(checkbox.value || '');
            checkbox.checked = checked;
            if (!value) return;

            if (checked) selectedIds.add(value);
            else selectedIds.delete(value);
        });

        setBulkUI();
    }

    if (searchEl) {
        searchEl.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = window.setTimeout(() => {
                state.q = String(searchEl.value || '').trim();
                state.page = 1;
                load();
            }, 250);
        }, ctx.signal ? { signal: ctx.signal } : undefined);
    }

    if (perEl) {
        perEl.addEventListener('change', () => {
            state.perpage = Number(perEl.value || 25) || 25;
            state.page = 1;
            load();
        }, ctx.signal ? { signal: ctx.signal } : undefined);
    }

    paginationEl?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-page]');
        if (!button) return;

        const page = Number(button.getAttribute('data-page') || 1) || 1;
        state.page = page;
        load();
    }, ctx.signal ? { signal: ctx.signal } : undefined);

    checkAllHead?.addEventListener('change', () => toggleAll(!!checkAllHead.checked), ctx.signal ? { signal: ctx.signal } : undefined);
    checkAllBar?.addEventListener('change', () => toggleAll(!!checkAllBar.checked), ctx.signal ? { signal: ctx.signal } : undefined);

    pageEl.addEventListener('change', (event) => {
        const checkbox = event.target.closest('[data-row-check]');
        if (!checkbox) return;

        const value = String(checkbox.value || '');
        if (!value) return;

        if (checkbox.checked) selectedIds.add(value);
        else selectedIds.delete(value);

        setBulkUI();
    }, ctx.signal ? { signal: ctx.signal } : undefined);

    pageEl.addEventListener('click', async (event) => {
        const del = event.target.closest('[data-delete]');
        const restore = event.target.closest('[data-restore]');
        const force = event.target.closest('[data-force]');

        if (del) {
            event.preventDefault();
            await performAction(del.getAttribute('data-url'), 'DELETE', 'Kategori silindi.', {
                type: 'warning',
                title: 'Kategori silinsin mi?',
                message: 'Kayit cop kutusuna tasinacak.',
                confirmButtonText: 'Sil',
            });
            return;
        }

        if (restore) {
            event.preventDefault();
            await performAction(restore.getAttribute('data-url'), 'POST', 'Kategori geri yuklendi.', {
                type: 'success',
                title: 'Kategori geri yuklensin mi?',
                message: 'Kayit tekrar aktif listeye alinacak.',
                confirmButtonText: 'Geri yukle',
            });
            return;
        }

        if (force) {
            event.preventDefault();
            await performAction(force.getAttribute('data-url'), 'DELETE', 'Kategori kalici olarak silindi.', {
                type: 'error',
                title: 'Kategori kalici olarak silinsin mi?',
                message: 'Bu islem geri alinamaz.',
                confirmButtonText: 'Kalici sil',
            });
        }
    }, ctx.signal ? { signal: ctx.signal } : undefined);

    bulkDeleteBtn?.addEventListener('click', () => {
        performBulk(bulkDeleteUrl, 'Secili kategoriler silindi.', {
            type: 'warning',
            title: 'Secili kategoriler silinsin mi?',
            message: `${selectedIds.size} kayit cop kutusuna tasinacak.`,
            confirmButtonText: 'Sil',
        });
    }, ctx.signal ? { signal: ctx.signal } : undefined);

    bulkRestoreBtn?.addEventListener('click', () => {
        performBulk(bulkRestoreUrl, 'Secili kategoriler geri yuklendi.', {
            type: 'success',
            title: 'Secili kategoriler geri yuklensin mi?',
            message: `${selectedIds.size} kayit tekrar aktif listeye alinacak.`,
            confirmButtonText: 'Geri yukle',
        });
    }, ctx.signal ? { signal: ctx.signal } : undefined);

    bulkForceBtn?.addEventListener('click', () => {
        performBulk(bulkForceUrl, 'Secili kategoriler kalici olarak silindi.', {
            type: 'error',
            title: 'Secili kategoriler kalici olarak silinsin mi?',
            message: `${selectedIds.size} kayit geri alinamayacak sekilde silinecek.`,
            confirmButtonText: 'Kalici sil',
        });
    }, ctx.signal ? { signal: ctx.signal } : undefined);

    ctx.cleanup?.(() => {
        if (debounce) clearTimeout(debounce);
        if (abort) abort.abort();
    });

    load();
}
