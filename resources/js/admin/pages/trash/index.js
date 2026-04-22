import Swal from 'sweetalert2';
import { request } from '@/core/http';
import { showConfirmDialog, showToastMessage } from '@/core/swal-alert';

const bound = new WeakMap();

const TYPE_META = {
    success: { icon: 'success', color: '#17c653' },
    error: { icon: 'error', color: '#f1416c' },
    warning: { icon: 'warning', color: '#f6b100' },
    info: { icon: 'info', color: '#3e97ff' },
};

function resolveSwal() {
    if (Swal?.fire) return Swal;
    if (window.Swal?.fire) return window.Swal;
    return null;
}

function esc(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function markBound(root, key) {
    let state = bound.get(root);
    if (!state) {
        state = new Set();
        bound.set(root, state);
    }

    if (state.has(key)) return true;
    state.add(key);
    return false;
}

async function jsonReq(url, method = 'GET', body = null, signal = null) {
    try {
        const payload = await request(url, { method, data: body, signal, ignoreGlobalError: true });
        return { res: { ok: true, status: 200 }, j: payload || {} };
    } catch (error) {
        return { res: { ok: false, status: error?.status || 0 }, j: error?.data || {} };
    }
}

function buildDialogButtonClass(type) {
    return `kt-btn swal2-kt-button swal2-kt-confirm swal2-kt-confirm--${type}`;
}

async function showNoticeDialog({
    title = 'Bilgi',
    message = '',
    html = '',
    type = 'info',
    confirmButtonText = 'Tamam',
}) {
    const swal = resolveSwal();
    if (!swal) {
        console.warn('SweetAlert2 bulunamadi. Mesaj gosterilemedi.');
        return;
    }

    const meta = TYPE_META[type] || TYPE_META.info;

    await swal.fire({
        icon: meta.icon,
        iconColor: meta.color,
        title,
        text: html ? undefined : (message || undefined),
        html: html || undefined,
        confirmButtonText,
        buttonsStyling: false,
        allowOutsideClick: true,
        allowEscapeKey: true,
        backdrop: 'rgba(15, 23, 42, 0.52)',
        customClass: {
            popup: `swal2-kt-popup swal2-kt-popup--${type}`,
            title: 'swal2-kt-title',
            htmlContainer: 'swal2-kt-text text-start',
            actions: 'swal2-kt-actions',
            confirmButton: buildDialogButtonClass(type),
        },
    });
}

function buildErrorMessage(json, fallback = 'Islem basarisiz.') {
    if (!json) return fallback;

    const validationErrors = json?.errors;
    if (validationErrors && typeof validationErrors === 'object') {
        const firstError = Object.values(validationErrors).flat().find(Boolean);
        if (firstError) return firstError;
    }

    let message = json.message || json.error?.message || fallback;
    if (json.usage?.summary) {
        message += `\nKullanim: ${json.usage.summary}`;
    }

    return message;
}

function showUsageDialog(json, fallbackTitle = 'Islem engellendi') {
    const message = buildErrorMessage(json, fallbackTitle);
    let html = `<div class="whitespace-pre-line">${esc(message).replace(/\n/g, '<br>')}</div>`;

    const details = json?.usage?.details;
    if (details && typeof details === 'object' && Object.keys(details).length > 0) {
        const rows = Object.entries(details)
            .map(([label, value]) => `
                <tr class="border-t border-border">
                    <td class="py-2 pr-3 text-muted-foreground">${esc(label)}</td>
                    <td class="py-2 text-end font-medium">${esc(value)}</td>
                </tr>
            `)
            .join('');

        html += `
            <div class="mt-4">
                <div class="text-sm font-medium mb-2">Kullanim detayi</div>
                <div class="kt-card kt-card-border">
                    <div class="kt-card-body p-0">
                        <table class="w-full text-sm">
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    return showNoticeDialog({
        title: fallbackTitle,
        html,
        type: 'warning',
    });
}

function cloneTpl(template) {
    const node = template?.content?.firstElementChild;
    return node ? node.outerHTML : '';
}

function renderPagination(meta, host) {
    if (!host) return;

    const current = Number(meta.current_page || 1) || 1;
    const last = Number(meta.last_page || 1) || 1;

    if (last <= 1) {
        host.innerHTML = '';
        return;
    }

    const button = (page, label, disabled = false, active = false) => `
        <button
            type="button"
            class="kt-btn kt-btn-sm ${active ? 'kt-btn-primary' : 'kt-btn-light'}"
            data-page="${page}"
            ${disabled ? 'disabled' : ''}>
            ${label}
        </button>
    `;

    const parts = [];
    parts.push(button(current - 1, '<', current <= 1));

    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);

    if (start > 1) parts.push(button(1, '1', false, current === 1));
    if (start > 2) parts.push('<span class="px-2 text-muted-foreground">...</span>');

    for (let page = start; page <= end; page += 1) {
        parts.push(button(page, String(page), false, page === current));
    }

    if (end < last - 1) parts.push('<span class="px-2 text-muted-foreground">...</span>');
    if (end < last) parts.push(button(last, String(last), false, current === last));

    parts.push(button(current + 1, '>', current >= last));
    host.innerHTML = `<div class="flex items-center gap-1 justify-center">${parts.join('')}</div>`;
}

function rowKey(type, id) {
    return `${String(type || '')}|${String(id || '')}`;
}

function rowHtml(item, selected) {
    const key = rowKey(item.type, item.id);
    const checked = selected.has(key) ? 'checked' : '';
    const restoreUrl = esc(item.restore_url || '');
    const forceUrl = esc(item.force_url || '');

    return `
        <tr data-row-key="${esc(key)}">
            <td class="w-[55px]">
                <input
                    type="checkbox"
                    class="kt-checkbox kt-checkbox-sm"
                    data-act="chk"
                    data-id="${esc(item.id)}"
                    data-type="${esc(item.type)}"
                    ${checked}>
            </td>
            <td class="min-w-[160px]">
                <span class="kt-badge kt-badge-light">${esc(item.type || '-')}</span>
            </td>
            <td class="min-w-[320px]">
                <div class="font-medium">${esc(item.title || item.name || '-')}</div>
                <div class="text-xs text-muted-foreground">${esc(item.sub || '')}</div>
            </td>
            <td class="min-w-[180px]">
                <span class="text-sm text-muted-foreground">${esc(item.deleted_at || '')}</span>
            </td>
            <td class="w-[160px] text-end">
                <div class="flex items-center justify-end gap-2">
                    <button
                        type="button"
                        class="kt-btn kt-btn-sm kt-btn-light"
                        data-act="restore"
                        data-url="${restoreUrl}"
                        ${restoreUrl ? '' : 'disabled'}>
                        <i class="ki-outline ki-arrow-circle-left"></i>
                    </button>
                    <button
                        type="button"
                        class="kt-btn kt-btn-sm kt-btn-danger"
                        data-act="force"
                        data-url="${forceUrl}"
                        ${forceUrl ? '' : 'disabled'}>
                        <i class="ki-outline ki-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
}

export default function init(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;

    const pageEl = root?.matches?.('[data-page="trash.index"]')
        ? root
        : root?.querySelector?.('[data-page="trash.index"]');

    if (!pageEl) return;

    const tbodyEl = pageEl.querySelector('#trashTbody');
    const infoEl = pageEl.querySelector('#trashInfo');
    const paginationEl = pageEl.querySelector('#trashPagination');
    const searchInput = pageEl.querySelector('#trashSearch');
    const typeSelect = pageEl.querySelector('#trashType');
    const perPageSelect = pageEl.querySelector('#trashPageSize');
    const bulkBar = pageEl.querySelector('#trashBulkBar');
    const checkAllHead = pageEl.querySelector('#trash_check_all_head');
    const checkAllBar = pageEl.querySelector('#trash_check_all');
    const selectedCountEl = pageEl.querySelector('#trashSelectedCount');
    const bulkRestoreBtn = pageEl.querySelector('#trashBulkRestoreBtn');
    const bulkForceBtn = pageEl.querySelector('#trashBulkForceDeleteBtn');
    const emptyTpl = pageEl.querySelector('#dt-empty-trash');
    const zeroTpl = pageEl.querySelector('#dt-zero-trash');

    if (!tbodyEl) return;

    const initialType = pageEl.dataset.initialType || 'all';
    if (typeSelect && Array.from(typeSelect.options).some((option) => option.value === initialType)) {
        typeSelect.value = initialType;
    }

    const urls = {
        list: pageEl.dataset.listUrl || '/admin/trash/list',
        bulkRestore: pageEl.dataset.bulkRestoreUrl || '/admin/trash/bulk-restore',
        bulkForce: pageEl.dataset.bulkForceDeleteUrl || '/admin/trash/bulk-force-delete',
    };

    const state = {
        q: '',
        type: typeSelect?.value || initialType || 'all',
        page: 1,
        perpage: Number(pageEl.dataset.perpage || perPageSelect?.value || 25) || 25,
        total: 0,
    };

    const selected = new Set();
    let debounceTimer = null;

    const setBulkUI = () => {
        const count = selected.size;

        if (selectedCountEl) selectedCountEl.textContent = String(count);
        if (bulkRestoreBtn) bulkRestoreBtn.disabled = count === 0;
        if (bulkForceBtn) bulkForceBtn.disabled = count === 0;
        bulkBar?.classList.toggle('hidden', count === 0);

        const boxes = Array.from(tbodyEl.querySelectorAll('input[data-act="chk"]'));
        const checkedCount = boxes.filter((box) => box.checked).length;

        if (checkAllHead) {
            checkAllHead.indeterminate = checkedCount > 0 && checkedCount < boxes.length;
            checkAllHead.checked = boxes.length > 0 && checkedCount === boxes.length;
        }

        if (checkAllBar) {
            checkAllBar.indeterminate = checkAllHead?.indeterminate || false;
            checkAllBar.checked = checkAllHead?.checked || false;
        }
    };

    const fillPerPageSelect = () => {
        if (!perPageSelect) return;

        perPageSelect.innerHTML = [10, 25, 50, 100]
            .map((value) => `<option value="${value}" ${value === state.perpage ? 'selected' : ''}>${value}</option>`)
            .join('');
    };

    const setInfo = (text = '') => {
        if (infoEl) infoEl.textContent = text;
    };

    const fetchList = async () => {
        const params = new URLSearchParams({
            q: state.q || '',
            page: String(state.page),
            perpage: String(state.perpage),
        });

        if (state.type && state.type !== 'all') {
            params.set('type', state.type);
        }

        const { res, j } = await jsonReq(`${urls.list}?${params.toString()}`, 'GET', null, signal);
        if (!res.ok || !j?.ok) {
            tbodyEl.innerHTML = `
                <tr>
                    <td colspan="5" class="py-10 text-center text-muted-foreground">
                        Liste alinamadi.
                    </td>
                </tr>
            `;
            setInfo('');
            renderPagination({ current_page: 1, last_page: 1 }, paginationEl);
            selected.clear();
            setBulkUI();
            return;
        }

        const items = Array.isArray(j.data) ? j.data : [];
        const meta = j.meta || {};
        state.total = Number(meta.total || 0) || 0;

        const visibleKeys = new Set(items.map((item) => rowKey(item.type, item.id)));
        Array.from(selected).forEach((key) => {
            if (!visibleKeys.has(key)) {
                selected.delete(key);
            }
        });

        if (items.length === 0) {
            tbodyEl.innerHTML = (state.q || state.type !== 'all')
                ? cloneTpl(zeroTpl)
                : cloneTpl(emptyTpl);
        } else {
            tbodyEl.innerHTML = items.map((item) => rowHtml(item, selected)).join('');
        }

        setInfo(state.total ? `Toplam: ${state.total}` : '');
        renderPagination(meta, paginationEl);
        setBulkUI();
    };

    const showRequestError = async (json, title = 'Islem basarisiz') => {
        if (json?.usage?.summary || (json?.usage?.details && Object.keys(json.usage.details).length > 0)) {
            await showUsageDialog(json, title);
            return;
        }

        await showNoticeDialog({
            title,
            html: `<div class="whitespace-pre-line">${esc(buildErrorMessage(json, title)).replace(/\n/g, '<br>')}</div>`,
            type: 'error',
        });
    };

    const doSingleAction = async (url, method, confirmOptions, successMessage) => {
        if (!url) return;

        const ok = await showConfirmDialog(confirmOptions);
        if (!ok) return;

        const { res, j } = await jsonReq(url, method, method === 'POST' ? {} : null, signal);
        if (!res.ok || !j?.ok) {
            await showRequestError(j, 'Islem engellendi');
            return;
        }

        showToastMessage('success', j?.message || successMessage, { duration: 1800 });
        await fetchList();
    };

    const doBulk = async (url, confirmOptions, successTitle) => {
        const items = Array.from(selected)
            .map((key) => {
                const [type, id] = String(key).split('|');
                const numericId = Number(id);

                if (!type || !Number.isFinite(numericId)) return null;
                return { type, id: numericId, key };
            })
            .filter(Boolean);

        if (items.length === 0) return;

        const ok = await showConfirmDialog(confirmOptions);
        if (!ok) return;

        const backup = new Set(selected);
        selected.clear();
        setBulkUI();

        const { res, j } = await jsonReq(url, 'POST', {
            items: items.map(({ type, id }) => ({ type, id })),
        }, signal);

        if (!res.ok || !j?.ok) {
            backup.forEach((key) => selected.add(key));
            setBulkUI();
            await showRequestError(j, 'Toplu islem basarisiz');
            return;
        }

        let html = `<div class="font-medium">${esc(`${j.done || 0} kayit islendi.`)}</div>`;

        if (Array.isArray(j.failed) && j.failed.length > 0) {
            const failedRows = j.failed
                .slice(0, 8)
                .map((item) => `
                    <li class="flex items-start justify-between gap-3">
                        <span class="text-muted-foreground">${esc(`${item.type}#${item.id}`)}</span>
                        <span class="text-end">${esc(item.reason || 'Hata')}</span>
                    </li>
                `)
                .join('');

            html += `
                <div class="mt-3">
                    <div class="text-sm font-medium mb-2">${esc(`${j.failed.length} kayit engellendi`)}</div>
                    <ul class="space-y-1 text-sm">${failedRows}</ul>
                </div>
            `;
        }

        if (Array.isArray(j.denied) && j.denied.length > 0) {
            html += `<div class="mt-3 text-sm text-muted-foreground">${esc(`${j.denied.length} kayit icin yetki yok.`)}</div>`;
        }

        await showNoticeDialog({
            title: successTitle,
            html,
            type: Array.isArray(j.failed) && j.failed.length > 0 ? 'warning' : 'success',
        });

        await fetchList();
    };

    const toggleAll = (checked) => {
        tbodyEl.querySelectorAll('input[data-act="chk"]').forEach((checkbox) => {
            const id = checkbox.getAttribute('data-id');
            const type = checkbox.getAttribute('data-type');
            if (!id || !type) return;

            checkbox.checked = checked;
            const key = rowKey(type, id);

            if (checked) selected.add(key);
            else selected.delete(key);
        });

        setBulkUI();
    };

    fillPerPageSelect();

    if (searchInput && !markBound(pageEl, 'search')) {
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(() => {
                state.q = String(searchInput.value || '').trim();
                state.page = 1;
                fetchList();
            }, 250);
        }, { signal });

        ctx.cleanup?.(() => {
            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }
            debounceTimer = null;
        });
    }

    if (typeSelect && !markBound(pageEl, 'type')) {
        typeSelect.addEventListener('change', () => {
            state.type = typeSelect.value || 'all';
            state.page = 1;
            fetchList();
        }, { signal });
    }

    if (perPageSelect && !markBound(pageEl, 'perpage')) {
        perPageSelect.addEventListener('change', () => {
            state.perpage = Number(perPageSelect.value || 25) || 25;
            state.page = 1;
            fetchList();
        }, { signal });
    }

    if (paginationEl && !markBound(pageEl, 'pagination')) {
        paginationEl.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-page]');
            if (!button) return;

            const nextPage = Number(button.getAttribute('data-page') || 1);
            if (!Number.isFinite(nextPage) || nextPage < 1 || nextPage === state.page) return;

            state.page = nextPage;
            fetchList();
        }, { signal });
    }

    if (!markBound(pageEl, 'tbody-actions')) {
        tbodyEl.addEventListener('click', (event) => {
            const actionButton = event.target.closest('button[data-act]');
            if (actionButton) {
                const action = actionButton.getAttribute('data-act');
                const url = actionButton.getAttribute('data-url') || '';

                if (action === 'restore') {
                    doSingleAction(url, 'POST', {
                        type: 'success',
                        title: 'Bu kayit geri yuklensin mi?',
                        message: 'Kayit tekrar aktif listeye alinacak.',
                        confirmButtonText: 'Geri yukle',
                    }, 'Kayit geri yuklendi.');
                    return;
                }

                if (action === 'force') {
                    doSingleAction(url, 'DELETE', {
                        type: 'error',
                        title: 'Bu kayit kalici olarak silinsin mi?',
                        message: 'Bu islem geri alinamaz.',
                        confirmButtonText: 'Kalici sil',
                    }, 'Kayit kalici olarak silindi.');
                    return;
                }
            }

            const checkbox = event.target.closest('input[data-act="chk"]');
            if (!checkbox) return;

            const id = checkbox.getAttribute('data-id');
            const type = checkbox.getAttribute('data-type');
            if (!id || !type) return;

            const key = rowKey(type, id);
            if (checkbox.checked) selected.add(key);
            else selected.delete(key);

            setBulkUI();
        }, { signal });
    }

    if (checkAllHead && !markBound(pageEl, 'checkall-head')) {
        checkAllHead.addEventListener('change', () => toggleAll(checkAllHead.checked), { signal });
    }

    if (checkAllBar && !markBound(pageEl, 'checkall-bar')) {
        checkAllBar.addEventListener('change', () => toggleAll(checkAllBar.checked), { signal });
    }

    if (bulkRestoreBtn && !markBound(pageEl, 'bulk-restore')) {
        bulkRestoreBtn.addEventListener('click', () => {
            doBulk(urls.bulkRestore, {
                type: 'success',
                title: 'Secili kayitlar geri yuklensin mi?',
                message: 'Secili kayitlar tekrar aktif listeye alinacak.',
                confirmButtonText: 'Geri yukle',
            }, 'Toplu geri yukleme sonucu');
        }, { signal });
    }

    if (bulkForceBtn && !markBound(pageEl, 'bulk-force')) {
        bulkForceBtn.addEventListener('click', () => {
            doBulk(urls.bulkForce, {
                type: 'error',
                title: 'Secili kayitlar kalici olarak silinsin mi?',
                message: 'Bu islem geri alinamaz.',
                confirmButtonText: 'Kalici sil',
            }, 'Toplu silme sonucu');
        }, { signal });
    }

    fetchList();
}
