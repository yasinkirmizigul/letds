function qs(sel, root = document) {
    return root.querySelector(sel);
}

function escHtml(str) {
    return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function requestJson(url, { method = 'GET', body = null, signal = null } = {}) {
    const headers = {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    };

    // Laravel method + csrf
    const token = csrfToken();
    if (token) headers['X-CSRF-TOKEN'] = token;

    const opts = { method, headers, signal };

    if (body) {
        headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(body);
    }

    const res = await fetch(url, opts);
    const json = await res.json().catch(() => null);

    if (!res.ok) {
        const msg = json?.message || `İstek başarısız (${res.status})`;
        throw new Error(msg);
    }

    return json;
}

function buildUrl(base, params) {
    const u = new URL(base, window.location.origin);
    Object.entries(params || {}).forEach(([k, v]) => {
        if (v === null || v === undefined || v === '') return;
        u.searchParams.set(k, String(v));
    });
    return u.toString();
}

function renderActions(item, mode) {
    const editUrl = item.edit_url || `/admin/categories/${item.id}/edit`;
    const deleteUrl = item.delete_url || `/admin/categories/${item.id}`;
    const restoreUrl = item.restore_url;
    const forceUrl   = item.force_url;

    if (mode === 'trash') {
        return `
<div class="flex items-center justify-end gap-2">
  <button type="button"
          class="kt-btn kt-btn-sm kt-btn-light"
          data-restore
          data-url="${restoreUrl}">
      <i class="ki-outline ki-arrows-circle"></i>
      Geri Yükle
  </button>

  <button type="button"
          class="kt-btn kt-btn-sm kt-btn-danger"
          data-force
          data-url="${forceUrl}">
      <i class="ki-outline ki-trash"></i>
      Kalıcı Sil
  </button>
</div>`;
    }

    return `
<div class="flex items-center justify-end gap-2">
  <a href="${editUrl}" class="kt-btn kt-btn-sm kt-btn-warning">
      <i class="ki-outline ki-pencil"></i>
      Düzenle
  </a>

  <button type="button"
          class="kt-btn kt-btn-sm kt-btn-danger"
          data-delete
          data-url="${deleteUrl}">
      <i class="ki-outline ki-trash"></i>
      Sil
  </button>
</div>`;
}


function renderRow(item, mode) {
    const id = item.id;

    return `
<tr>
  <td class="w-[55px]">
    <input class="kt-checkbox kt-checkbox-sm" type="checkbox" data-row-check value="${id}">
  </td>

  <td class="min-w-[260px]">
    <div class="font-medium text-secondary-foreground">${escHtml(item.name)}</div>
  </td>

  <td class="min-w-[240px]">
    <code class="text-xs">${escHtml(item.slug)}</code>
  </td>

  <td class="min-w-[220px]">
    ${item.parent_name ? escHtml(item.parent_name) : '<span class="text-muted-foreground">—</span>'}
  </td>

  <td class="w-[90px] text-right">
    ${Number(item.blog_posts_count || 0)}
  </td>

  <td class="w-[220px]">
    ${renderActions(item, mode)}
  </td>
</tr>`;
}

function renderPagination(meta, host) {
    if (!host) return;

    const current = Number(meta?.current_page || 1);
    const last = Number(meta?.last_page || 1);

    // basit pagination: « 1 2 3 »
    const btn = (p, label = null, active = false, disabled = false) => {
        const text = label ?? String(p);
        const cls = active ? 'kt-btn-primary' : 'kt-btn-light';
        const dis = disabled ? 'disabled' : '';
        return `<button type="button" class="kt-btn kt-btn-sm ${cls}" data-page="${p}" ${dis}>${text}</button>`;
    };

    const parts = [];
    parts.push(btn(Math.max(1, current - 1), '‹', false, current <= 1));

    // sayfa aralığı (kurumsal: 5’li pencere)
    const from = Math.max(1, current - 2);
    const to = Math.min(last, current + 2);

    if (from > 1) {
        parts.push(btn(1));
        if (from > 2) parts.push(`<span class="px-2 text-muted-foreground">…</span>`);
    }

    for (let p = from; p <= to; p++) {
        parts.push(btn(p, null, p === current));
    }

    if (to < last) {
        if (to < last - 1) parts.push(`<span class="px-2 text-muted-foreground">…</span>`);
        parts.push(btn(last));
    }

    parts.push(btn(Math.min(last, current + 1), '›', false, current >= last));

    host.innerHTML = `<div class="flex items-center gap-2 flex-wrap">${parts.join('')}</div>`;
}

export default function init(ctx = {}) {
    const root = ctx.root || qs('[data-page="categories.index"]');
    if (!root) return;

    const pageEl = root.matches?.('[data-page="categories.index"]')
        ? root
        : qs('[data-page="categories.index"]', root);

    if (!pageEl) return;

    const mode = (pageEl.getAttribute('data-mode') || 'active').toLowerCase(); // active | trash
    const listUrl =
        pageEl.getAttribute('data-list-url') ||
        '/admin/categories/list';

    const tbody = qs('#categoriesTbody', pageEl);
    const searchEl = qs('#categoriesSearch', pageEl);
    const perEl = qs('#categoriesPageSize', pageEl);
    const infoEl = qs('#categoriesInfo', pageEl);
    const pagHost = qs('#categoriesPagination', pageEl);
    const checkAll = qs('#categories_check_all_head', pageEl);

    if (!tbody) return;

    let state = {
        q: '',
        page: 1,
        perpage: Number(pageEl.getAttribute('data-perpage') || 25) || 25,
    };

    // sync perpage select if exists
    if (perEl) perEl.value = String(state.perpage);

    let lastMeta = null;
    let abort = null;

    async function load() {
        if (abort) abort.abort();
        abort = new AbortController();

        const url = buildUrl(listUrl, {
            mode,
            q: state.q,
            page: state.page,
            perpage: state.perpage,
        });

        // loading state
        tbody.innerHTML = `
<tr><td colspan="6" class="py-10 text-center text-muted-foreground">Yükleniyor…</td></tr>`;

        try {
            const json = await requestJson(url, { signal: abort.signal });

            if (!json || json.ok !== true) {
                throw new Error(json?.message || 'Liste alınamadı');
            }

            const rows = Array.isArray(json.data) ? json.data : [];
            lastMeta = json.meta || null;

            if (rows.length === 0) {
                tbody.innerHTML = `
<tr><td colspan="6" class="py-10 text-center text-muted-foreground">
  ${state.q ? 'Sonuç bulunamadı.' : 'Henüz kayıt yok.'}
</td></tr>`;
            } else {
                tbody.innerHTML = rows.map((it) => renderRow(it, mode)).join('');
            }

            // info
            if (infoEl && lastMeta) {
                const total = Number(lastMeta.total || 0);
                const per = Number(lastMeta.per_page || state.perpage);
                const cur = Number(lastMeta.current_page || 1);
                const start = total === 0 ? 0 : (cur - 1) * per + 1;
                const end = Math.min(cur * per, total);
                infoEl.textContent = total === 0 ? 'Kayıt yok' : `${start}-${end} / ${total}`;
            }

            // pagination
            renderPagination(lastMeta, pagHost);

            // check-all reset
            if (checkAll) checkAll.checked = false;

        } catch (e) {
            if (e?.name === 'AbortError') return;
            tbody.innerHTML = `
<tr><td colspan="6" class="py-10 text-center text-danger">
  ${escHtml(e?.message || 'Hata oluştu')}
</td></tr>`;
        }
    }

    // Search debounce
    let t = null;
    if (searchEl) {
        searchEl.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => {
                state.q = String(searchEl.value || '').trim();
                state.page = 1;
                load();
            }, 250);
        }, ctx.signal ? { signal: ctx.signal } : undefined);
    }

    // Perpage
    if (perEl) {
        perEl.addEventListener('change', () => {
            state.perpage = Number(perEl.value || 25) || 25;
            state.page = 1;
            load();
        }, ctx.signal ? { signal: ctx.signal } : undefined);
    }

    // Pagination click
    if (pagHost) {
        pagHost.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-page]');
            if (!btn) return;
            const p = Number(btn.getAttribute('data-page') || 1) || 1;
            state.page = p;
            load();
        }, ctx.signal ? { signal: ctx.signal } : undefined);
    }

    // Check-all
    if (checkAll) {
        checkAll.addEventListener('change', () => {
            const checked = !!checkAll.checked;
            pageEl.querySelectorAll('[data-row-check]').forEach((cb) => {
                cb.checked = checked;
            });
        }, ctx.signal ? { signal: ctx.signal } : undefined);
    }

    // Row actions (delete/restore/force)
    pageEl.addEventListener('click', async (e) => {
        const del = e.target.closest('[data-delete]');
        const restore = e.target.closest('[data-restore]');
        const force = e.target.closest('[data-force]');

        try {
            if (del) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                const url = del.getAttribute('data-url');
                if (!url) return;
                if (!confirm('Silinsin mi? (Çöp kutusuna taşınacak)')) return;
                await requestJson(url, { method: 'DELETE' });
                await load();
            }

            if (restore) {
                const url = restore.getAttribute('data-url');
                if (!url) return;
                await requestJson(url, { method: 'POST' });
                await load();
            }

            if (force) {
                const url = force.getAttribute('data-url');
                if (!url) return;
                if (!confirm('Kalıcı silinsin mi? Bu işlem geri alınamaz.')) return;
                await requestJson(url, { method: 'DELETE' });
                await load();
            }
        } catch (err) {
            alert(err?.message || 'İşlem başarısız');
        }
    }, ctx.signal ? { signal: ctx.signal } : undefined);

    // first load
    load();
}
