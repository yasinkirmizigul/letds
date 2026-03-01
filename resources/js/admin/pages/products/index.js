/**
 * resources/js/admin/pages/products/index.js
 * Project list sayfasının layout/UX kalıbıyla uyumlu (kt-card header + scrollable table).
 * Backend: ProductController@index (server-rendered) + bulk/status/featured ajax aksiyonları.
 */

/* global Swal */

function csrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

async function req(url, { method = 'POST', body = null, headers = {} } = {}) {
  const h = {
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': csrfToken(),
    ...headers,
  };

  const opts = { method, headers: h, credentials: 'same-origin' };
  if (body !== null) {
    if (body instanceof FormData) opts.body = body;
    else {
      h['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
  }

  const res = await fetch(url, opts);
  const ct = res.headers.get('content-type') || '';
  const data = ct.includes('application/json') ? await res.json() : await res.text();
  if (!res.ok) {
    const msg = (data && data.message) ? data.message : `HTTP ${res.status}`;
    throw new Error(msg);
  }
  return data;
}

function buildUrlWithParams(baseUrl, params) {
  const u = new URL(baseUrl, window.location.origin);
  const cur = new URL(window.location.href);
  // mevcut query string'i taşı
  cur.searchParams.forEach((v, k) => u.searchParams.set(k, v));

  Object.entries(params).forEach(([k, v]) => {
    if (v === null || v === undefined || v === '') u.searchParams.delete(k);
    else u.searchParams.set(k, String(v));
  });

  return u.pathname + '?' + u.searchParams.toString();
}

function getMode(root) {
  const page = root?.dataset?.page || '';
  return page.endsWith('.trash') ? 'trash' : 'active';
}

function selectedIds(root) {
  const ids = [];
  root.querySelectorAll('.products-check:checked').forEach((cb) => ids.push(cb.value));
  return ids;
}

function refreshBulkBar(root) {
  const bar = root.querySelector('#productsBulkBar');
  const countEl = root.querySelector('#productsSelectedCount');
  const ids = selectedIds(root);
  if (countEl) countEl.textContent = String(ids.length);

  const btnDel = root.querySelector('#productsBulkDeleteBtn');
  const btnRes = root.querySelector('#productsBulkRestoreBtn');
  const btnForce = root.querySelector('#productsBulkForceDeleteBtn');

  [btnDel, btnRes, btnForce].forEach((b) => { if (b) b.disabled = ids.length === 0; });

  if (!bar) return;
  if (ids.length > 0) bar.classList.remove('hidden');
  else bar.classList.add('hidden');
}

function wireSearchAndPageSize(root) {
  const qInput = root.querySelector('#productsSearchInput');
  const perSel = root.querySelector('#productsPageSize');

  if (qInput) {
    let t = null;
    qInput.addEventListener('input', () => {
      // 400ms debounce -> URL güncelle + reload
      clearTimeout(t);
      t = setTimeout(() => {
        const url = buildUrlWithParams(window.location.pathname, { q: qInput.value.trim(), page: 1 });
        window.location.assign(url);
      }, 400);
    });

    qInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const url = buildUrlWithParams(window.location.pathname, { q: qInput.value.trim(), page: 1 });
        window.location.assign(url);
      }
    });
  }

  if (perSel) {
    perSel.addEventListener('change', () => {
      const url = buildUrlWithParams(window.location.pathname, { perpage: perSel.value, page: 1 });
      window.location.assign(url);
    });
  }
}

function wireSelection(root) {
  const checkAll = root.querySelector('#products_check_all');
  if (checkAll) {
    checkAll.addEventListener('change', () => {
      const checked = !!checkAll.checked;
      root.querySelectorAll('.products-check').forEach((cb) => { cb.checked = checked; });
      refreshBulkBar(root);
    });
  }

  root.querySelectorAll('.products-check').forEach((cb) => {
    cb.addEventListener('change', () => {
      if (checkAll && !cb.checked) checkAll.checked = false;
      refreshBulkBar(root);
    });
  });

  refreshBulkBar(root);
}

function confirmDialog(title, text, confirmButtonText = 'Evet') {
  if (!window.Swal) {
    return Promise.resolve(window.confirm(`${title}\n\n${text}`));
  }
  return Swal.fire({
    title,
    text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText: 'Vazgeç',
  }).then((r) => r.isConfirmed);
}

function toastSuccess(text) {
  if (!window.Swal) return;
  Swal.fire({ icon: 'success', title: text, timer: 1200, showConfirmButton: false });
}

function toastError(text) {
  if (!window.Swal) return;
  Swal.fire({ icon: 'error', title: 'Hata', text });
}

function routes() {
  // Bu pack'te route adları: admin.products.*
  return {
    destroy: (id) => `/admin/products/${id}`,
    restore: (id) => `/admin/products/${id}/restore`,
    forceDestroy: (id) => `/admin/products/${id}/force`,
    bulkDestroy: `/admin/products/bulk-destroy`,
    bulkRestore: `/admin/products/bulk-restore`,
    bulkForceDestroy: `/admin/products/bulk-force-destroy`,
    status: (id) => `/admin/products/${id}/status`,
    featured: (id) => `/admin/products/${id}/featured`,
  };
}

async function handleRowAction(root, action, id) {
  const r = routes();
  const mode = getMode(root);

  try {
    if (action === 'delete') {
      const ok = await confirmDialog('Silinsin mi?', 'Bu ürün çöp kutusuna taşınacak.', 'Sil');
      if (!ok) return;
      await req(r.destroy(id), { method: 'DELETE' });
      window.location.reload();
      return;
    }

    if (action === 'restore') {
      const ok = await confirmDialog('Geri yüklensin mi?', 'Ürün aktif listeye taşınacak.', 'Geri Yükle');
      if (!ok) return;
      await req(r.restore(id), { method: 'POST' });
      window.location.reload();
      return;
    }

    if (action === 'force-delete') {
      const ok = await confirmDialog('Kalıcı silinsin mi?', 'Bu işlem geri alınamaz.', 'Kalıcı Sil');
      if (!ok) return;
      await req(r.forceDestroy(id), { method: 'DELETE' });
      window.location.reload();
      return;
    }

    // güvenlik: trash modunda delete yok; active modda restore yok
    if (mode === 'trash' && action === 'delete') return;
    if (mode !== 'trash' && (action === 'restore' || action === 'force-delete')) return;
  } catch (e) {
    toastError(e.message || String(e));
  }
}

async function handleBulk(root, kind) {
  const ids = selectedIds(root);
  if (ids.length === 0) return;

  const r = routes();
  try {
    if (kind === 'delete') {
      const ok = await confirmDialog('Seçili ürünler silinsin mi?', 'Seçili ürünler çöp kutusuna taşınacak.', 'Sil');
      if (!ok) return;
      await req(r.bulkDestroy, { method: 'POST', body: { ids } });
      window.location.reload();
      return;
    }

    if (kind === 'restore') {
      const ok = await confirmDialog('Seçili ürünler geri yüklensin mi?', 'Ürünler aktif listeye taşınacak.', 'Geri Yükle');
      if (!ok) return;
      await req(r.bulkRestore, { method: 'POST', body: { ids } });
      window.location.reload();
      return;
    }

    if (kind === 'force') {
      const ok = await confirmDialog('Seçili ürünler kalıcı silinsin mi?', 'Bu işlem geri alınamaz.', 'Kalıcı Sil');
      if (!ok) return;
      await req(r.bulkForceDestroy, { method: 'POST', body: { ids } });
      window.location.reload();
      return;
    }
  } catch (e) {
    toastError(e.message || String(e));
  }
}

function wireBulkButtons(root) {
  const del = root.querySelector('#productsBulkDeleteBtn');
  if (del) del.addEventListener('click', () => handleBulk(root, 'delete'));

  const res = root.querySelector('#productsBulkRestoreBtn');
  if (res) res.addEventListener('click', () => handleBulk(root, 'restore'));

  const force = root.querySelector('#productsBulkForceDeleteBtn');
  if (force) force.addEventListener('click', () => handleBulk(root, 'force'));
}

function wireRowButtons(root) {
  root.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action][data-id]');
    if (!btn) return;
    e.preventDefault();
    handleRowAction(root, btn.dataset.action, btn.dataset.id);
  });
}

function wireFeatured(root) {
  const r = routes();

  root.querySelectorAll('.js-featured-toggle').forEach((cb) => {
    cb.addEventListener('change', async () => {
      const id = cb.dataset.productId;
      if (!id) return;
      cb.disabled = true;

      try {
        const data = await req(r.featured(id), { method: 'PATCH', body: { is_featured: cb.checked ? 1 : 0 } });

        const badge = cb.closest('td')?.querySelector('.js-featured-badge');
        if (badge) {
          if (cb.checked) {
            badge.classList.remove('opacity-0', 'hidden');
          } else {
            badge.classList.add('opacity-0', 'hidden');
          }
        }

        if (data?.ok === false && data?.message) {
          // backend limit uyarısı
          toastError(data.message);
          cb.checked = !cb.checked;
        } else {
          toastSuccess('Güncellendi');
        }
      } catch (e) {
        toastError(e.message || String(e));
        cb.checked = !cb.checked;
      } finally {
        cb.disabled = false;
      }
    });
  });
}

function wireStatus(root) {
  const r = routes();
  const statusOptions = (() => {
    try { return JSON.parse(root.dataset.statusOptions || '{}'); } catch { return {}; }
  })();

  // Basit dropdown: Swal select ile
  root.querySelectorAll('.js-status-trigger').forEach((btn) => {
    btn.addEventListener('click', async () => {
      if (btn.disabled) return;
      const id = btn.dataset.productId;
      if (!id) return;

      const inputOptions = {};
      Object.entries(statusOptions).forEach(([k, v]) => { inputOptions[k] = v.label; });

      try {
        const { value: newStatus } = await Swal.fire({
          title: 'Durum',
          input: 'select',
          inputOptions,
          inputValue: btn.dataset.status || '',
          showCancelButton: true,
          confirmButtonText: 'Kaydet',
          cancelButtonText: 'Vazgeç',
        });

        if (!newStatus) return;

        await req(r.status(id), { method: 'PATCH', body: { status: newStatus } });
        // En temiz yol: reload (badge classları backend'e bağlı)
        window.location.reload();
      } catch (e) {
        toastError(e.message || String(e));
      }
    });
  });
}

export default function init() {
  const root = document.querySelector('[data-page="products.index"], [data-page="products.trash"]');
  if (!root) return;

  wireSearchAndPageSize(root);
  wireSelection(root);
  wireBulkButtons(root);
  wireRowButtons(root);
  wireFeatured(root);
  if (window.Swal) wireStatus(root);
}
