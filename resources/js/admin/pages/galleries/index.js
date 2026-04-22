import { request, get } from '@/core/http';
import { showConfirmDialog, showToastMessage } from '@/core/swal-alert';

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

export default function init(ctx = {}) {
    const root = ctx.root?.querySelector?.('[data-page="galleries.index"]')
        || (ctx.root?.matches?.('[data-page="galleries.index"]') ? ctx.root : document.querySelector('[data-page="galleries.index"]'));

    if (!root) return;

    const state = { page: 1, perpage: 24, q: '' };
    const listUrl = root.dataset.listUrl || '/admin/galleries/list';
    const mode = root.dataset.mode || 'active';

    const listEl = root.querySelector('#galleriesList');
    const emptyEl = root.querySelector('#galleriesEmpty');
    const infoEl = root.querySelector('#galleriesInfo');
    const paginationEl = root.querySelector('#galleriesPagination');
    const search = root.querySelector('#galleriesSearch');
    const refresh = root.querySelector('#galleriesRefresh');

    let debounce = null;

    const button = (page, label, disabled = false, active = false) => `
        <button
            type="button"
            class="kt-btn kt-btn-sm ${active ? 'kt-btn-primary' : 'kt-btn-light'}"
            data-page="${page}"
            ${disabled ? 'disabled' : ''}>
            ${label}
        </button>
    `;

    function renderPagination(meta) {
        if (!paginationEl) return;

        const current = Number(meta.current_page || 1);
        const last = Number(meta.last_page || 1);

        if (last <= 1) {
            paginationEl.innerHTML = '';
            return;
        }

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
        paginationEl.innerHTML = `<div class="flex items-center gap-1">${parts.join('')}</div>`;
    }

    async function fetchList() {
        const query = new URLSearchParams({
            page: String(state.page),
            perpage: String(state.perpage),
            q: state.q || '',
            mode,
        });

        const json = await get(`${listUrl}?${query.toString()}`, { ignoreGlobalError: true }) || {};
        if (!json?.ok) {
            listEl.innerHTML = '';
            emptyEl?.classList.remove('hidden');
            if (infoEl) infoEl.textContent = '';
            renderPagination({ current_page: 1, last_page: 1 });
            return;
        }

        const items = Array.isArray(json.data) ? json.data : [];
        const meta = json.meta || {};

        listEl.innerHTML = items.map((gallery) => {
            const editUrl = gallery.edit_url || `/admin/galleries/${gallery.id}/edit`;
            const deleteButton = mode === 'trash'
                ? ''
                : `
                    <button type="button"
                            class="kt-btn kt-btn-sm kt-btn-danger"
                            data-action="delete"
                            data-url="${escapeHtml(gallery.delete_url || `/admin/galleries/${gallery.id}`)}">
                        <i class="ki-outline ki-trash"></i>
                        Sil
                    </button>
                `;

            return `
                <div class="rounded-2xl border border-border bg-background px-4 py-4 flex items-start justify-between gap-4">
                    <div class="grid gap-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="${editUrl}" class="font-medium hover:underline">${escapeHtml(gallery.name)}</a>
                            <span class="kt-badge kt-badge-outline">#${gallery.id}</span>
                            ${mode === 'trash' ? '<span class="kt-badge kt-badge-outline">Çöp</span>' : ''}
                        </div>

                        <div class="text-xs text-muted-foreground">${escapeHtml(gallery.slug || '')}</div>

                        ${gallery.description ? `<div class="text-sm">${escapeHtml(gallery.description)}</div>` : ''}

                        <div class="flex flex-wrap gap-2">
                            <span class="kt-badge kt-badge-light">Öge: ${Number(gallery.items_count || 0)}</span>
                            <span class="kt-badge ${Number(gallery.attached_count || 0) > 0 ? 'kt-badge-light-primary' : 'kt-badge-light'}">
                                Bağlı içerik: ${Number(gallery.attached_count || 0)}
                            </span>
                            <span class="kt-badge kt-badge-light">Güncelleme: ${escapeHtml(gallery.updated_at || '-')}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="${editUrl}" class="kt-btn kt-btn-sm kt-btn-light">
                            <i class="ki-outline ki-pencil"></i> Düzenle
                        </a>
                        ${deleteButton}
                    </div>
                </div>
            `;
        }).join('');

        emptyEl?.classList.toggle('hidden', items.length > 0);

        const from = items.length ? ((Number(meta.current_page || 1) - 1) * Number(meta.per_page || state.perpage) + 1) : 0;
        const to = items.length ? (from + items.length - 1) : 0;
        if (infoEl) infoEl.textContent = `${from}-${to} / ${meta.total ?? items.length}`;

        renderPagination(meta);
    }

    paginationEl?.addEventListener('click', (event) => {
        const trigger = event.target.closest('button[data-page]');
        if (!trigger) return;

        const page = Number(trigger.dataset.page || 1);
        if (Number.isFinite(page) && page >= 1) {
            state.page = page;
            fetchList();
        }
    }, ctx.signal ? { signal: ctx.signal } : undefined);

    refresh?.addEventListener('click', () => fetchList(), ctx.signal ? { signal: ctx.signal } : undefined);

    search?.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = window.setTimeout(() => {
            state.q = search.value.trim();
            state.page = 1;
            fetchList();
        }, 250);
    }, ctx.signal ? { signal: ctx.signal } : undefined);

    root.addEventListener('click', async (event) => {
        const actionButton = event.target.closest('[data-action="delete"]');
        if (!actionButton) return;

        const ok = await showConfirmDialog({
            type: 'warning',
            title: 'Galeri silinsin mi?',
            message: 'Galeri çöp kutusuna taşınacak.',
            confirmButtonText: 'Sil',
        });
        if (!ok) return;

        try {
            const json = await request(actionButton.getAttribute('data-url'), {
                method: 'DELETE',
                signal: ctx.signal,
                ignoreGlobalError: true,
            });

            showToastMessage('success', json?.message || 'Galeri silindi.', { duration: 1800 });
            await fetchList();
        } catch (error) {
            showToastMessage('error', error?.data?.message || error?.message || 'Galeri silinemedi.');
        }
    }, ctx.signal ? { signal: ctx.signal } : undefined);

    ctx.cleanup?.(() => {
        if (debounce) clearTimeout(debounce);
    });

    fetchList();
}
