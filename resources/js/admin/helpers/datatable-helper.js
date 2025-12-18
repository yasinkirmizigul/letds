/* global jQuery */

(function (w) {
    function tplHtml(selector, fallbackHtml) {
        const el = document.querySelector(selector);
        if (!el) return (fallbackHtml || '').trim();
        if (el.tagName === 'TEMPLATE') return (el.innerHTML || '').trim();
        return (el.innerHTML || '').trim();
    }

    function isTrTemplate(html) {
        const t = (html || '').trim().toLowerCase();
        return t.startsWith('<tr') || t.startsWith('<tr ');
    }

    function normalizeRowTemplate(html, colCount) {
        // <tr> verilmişse aynen kullan; colspan eksikse zorla
        const t = document.createElement('tbody');
        t.innerHTML = html.trim();

        const tr = t.querySelector('tr');
        if (!tr) return null;

        // colspan garanti
        const tds = tr.querySelectorAll('td,th');
        if (tds.length === 1) {
            tds[0].setAttribute('colspan', String(colCount));
        }
        return tr.outerHTML;
    }

    function applyEmptyState(api, tableEl, emptyHtml, zeroHtml) {
        const info = api.page.info();
        const hasSearch = (api.search() || '').trim().length > 0;

        const colCount = api.columns().count();
        const tbody = tableEl.querySelector('tbody');
        if (!tbody) return;

        // Normal durumda DataTables kendi satırlarını basar, biz dokunmayız.
        if (info.recordsTotal > 0 && info.recordsDisplay > 0) return;

        // Kayıt yok / arama sonucu yok durumunda tbody'yi tek satırla yönet.
        let rowHtml = '';
        if (info.recordsTotal === 0 && !hasSearch) {
            rowHtml = isTrTemplate(emptyHtml)
                ? normalizeRowTemplate(emptyHtml, colCount)
                : `<tr data-kt-empty-row="true"><td colspan="${colCount}">${emptyHtml}</td></tr>`;
        } else if (info.recordsDisplay === 0 && hasSearch) {
            rowHtml = isTrTemplate(zeroHtml)
                ? normalizeRowTemplate(zeroHtml, colCount)
                : `<tr data-kt-zero-row="true"><td colspan="${colCount}">${zeroHtml}</td></tr>`;
        }

        if (!rowHtml) return;

        tbody.innerHTML = rowHtml;
    }

    function renderPagination(api, hostSelector, root = document) {
        const host = root.querySelector(hostSelector);
        if (!host) return;

        const info = api.page.info();
        const pages = info.pages;
        const page = info.page;

        host.innerHTML = '';
        if (pages <= 1) return;

        const wrap = document.createElement('div');
        wrap.className = 'inline-flex gap-1';

        const makeBtn = (label, targetPage, disabled = false, active = false) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = active
                ? 'kt-btn kt-btn-sm kt-btn-primary'
                : 'kt-btn kt-btn-sm kt-btn-light';

            if (disabled) {
                btn.disabled = true;
                btn.className += ' opacity-60 pointer-events-none';
            }

            btn.textContent = label;
            btn.addEventListener('click', () => api.page(targetPage).draw('page'));
            return btn;
        };

        // First / Prev
        wrap.appendChild(makeBtn('«', 0, page === 0));
        wrap.appendChild(makeBtn('‹', Math.max(0, page - 1), page === 0));

        // window
        const windowSize = 5;
        let start = Math.max(0, page - 2);
        let end = Math.min(pages - 1, start + windowSize - 1);
        start = Math.max(0, end - windowSize + 1);

        for (let i = start; i <= end; i++) {
            wrap.appendChild(makeBtn(String(i + 1), i, false, i === page));
        }

        // Next / Last
        wrap.appendChild(makeBtn('›', Math.min(pages - 1, page + 1), page === pages - 1));
        wrap.appendChild(makeBtn('»', pages - 1, page === pages - 1));

        host.appendChild(wrap);
    }

    /**
     * initDataTable
     * - DataTables.net core + senin Metronic header/footer entegrasyonu
     */
    function initDataTable(opts) {
        const o = Object.assign({ /* ... aynı ... */ }, opts || {});
        const root = o.root || document;

        if (!o.table) return null;

        if (!w.jQuery || !jQuery.fn || !jQuery.fn.DataTable) {
            console.error('DataTables yüklenmemiş. (jQuery.fn.DataTable yok)');
            return null;
        }

        const tableEl = root.querySelector(o.table);
        if (!tableEl) return null;

        const $table = jQuery(tableEl);

        // çift init engeli
        if (jQuery.fn.dataTable.isDataTable($table)) return $table.DataTable();

        const emptyHtml = o.emptyTemplate ? tplHtml(o.emptyTemplate, o.emptyFallback) : o.emptyFallback;
        const zeroHtml  = o.zeroTemplate  ? tplHtml(o.zeroTemplate,  o.zeroFallback)  : o.zeroFallback;

        if (o.headerCenter) tableEl.classList.add('dt-kt-header-center');

        const dt = $table.DataTable({
            pageLength: o.pageLength,
            lengthMenu: o.lengthMenu,
            order: o.order,
            autoWidth: o.autoWidth,
            dom: o.dom,
            columnDefs: o.columnDefs,
            language: Object.assign(
                { emptyTable: '', zeroRecords: '', infoEmpty: 'Kayıt yok' },
                o.language || {}
            ),
            drawCallback: function () {
                const api = this.api();

                applyEmptyState(api, tableEl, emptyHtml, zeroHtml);

                if (o.info) {
                    const info = api.page.info();
                    const hasSearch = (api.search() || '').trim().length > 0;
                    const infoEl = root.querySelector(o.info);

                    if (infoEl) {
                        if (info.recordsTotal === 0 && !hasSearch) infoEl.textContent = 'Henüz kayıt yok';
                        else if (info.recordsDisplay === 0 && hasSearch) infoEl.textContent = 'Sonuç yok';
                        else infoEl.textContent = `${info.start + 1}-${info.end} / ${info.recordsDisplay}`;
                    }
                }

                if (o.pagination) renderPagination(api, o.pagination, root);

                if (typeof o.onDraw === 'function') o.onDraw(api);

                if (o.checkAll) {
                    const c = root.querySelector(o.checkAll);
                    if (c) c.checked = false;
                }
            },
        });

        $table.removeClass('dataTable no-footer');

        // search bind
        if (o.search) {
            const s = root.querySelector(o.search);
            if (s && !s._dtBound) {
                s._dtBound = true;
                s.addEventListener('input', (e) => dt.search(e.target.value || '').draw());
            }
        }

        // page size bind
        if (o.pageSize) {
            const sel = root.querySelector(o.pageSize);
            if (sel && !sel._dtBound) {
                sel._dtBound = true;

                sel.innerHTML = '';
                (o.lengthMenu || [5, 10, 25, 50]).forEach((n) => {
                    const opt = document.createElement('option');
                    opt.value = n;
                    opt.textContent = n;
                    sel.appendChild(opt);
                });

                sel.value = String(dt.page.len());
                sel.addEventListener('change', (e) => dt.page.len(Number(e.target.value)).draw());
            }
        }

        // check-all
        if (o.checkAll && o.rowChecks) {
            const checkAllEl = root.querySelector(o.checkAll);
            if (checkAllEl && !checkAllEl._dtBound) {
                checkAllEl._dtBound = true;
                checkAllEl.addEventListener('change', () => {
                    const checked = checkAllEl.checked;
                    root.querySelectorAll(o.rowChecks).forEach(cb => (cb.checked = checked));
                });
            }
        }
        return dt;
    }


    w.initDataTable = initDataTable;
})(window);
