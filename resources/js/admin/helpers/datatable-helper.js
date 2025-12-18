/* global jQuery */
(function (w) {
    function tplHtml(selector, fallbackHtml, root = document) {
        const el = root.querySelector(selector) || document.querySelector(selector);
        if (!el) return (fallbackHtml || '').trim();
        if (el.tagName === 'TEMPLATE') return (el.innerHTML || '').trim();
        return (el.innerHTML || '').trim();
    }

    function isTrTemplate(html) {
        const t = (html || '').trim().toLowerCase();
        return t.startsWith('<tr') || t.startsWith('<tr ');
    }

    function normalizeRowTemplate(html, colCount) {
        const t = document.createElement('tbody');
        t.innerHTML = html.trim();

        const tr = t.querySelector('tr');
        if (!tr) return null;

        const tds = tr.querySelectorAll('td,th');
        if (tds.length === 1) tds[0].setAttribute('colspan', String(colCount));
        return tr.outerHTML;
    }

    function applyEmptyState(api, tableEl, emptyHtml, zeroHtml) {
        const info = api.page.info();
        const hasSearch = (api.search() || '').trim().length > 0;

        const colCount = api.columns().count();
        const tbody = tableEl.querySelector('tbody');
        if (!tbody) return;

        // Normal durumda dokunma
        if (info.recordsTotal > 0 && info.recordsDisplay > 0) return;

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

        if (rowHtml) tbody.innerHTML = rowHtml;
    }

    function renderPagination(api, hostSelector, root = document) {
        const host = root.querySelector(hostSelector);
        if (!host || !api) return;

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
            btn.className = active ? 'kt-btn kt-btn-sm kt-btn-primary' : 'kt-btn kt-btn-sm kt-btn-light';
            if (disabled) {
                btn.disabled = true;
                btn.className += ' opacity-60 pointer-events-none';
            }
            btn.textContent = label;
            btn.addEventListener('click', () => api.page(targetPage).draw('page'));
            return btn;
        };

        wrap.appendChild(makeBtn('«', 0, page === 0));
        wrap.appendChild(makeBtn('‹', Math.max(0, page - 1), page === 0));

        const windowSize = 5;
        let start = Math.max(0, page - 2);
        let end = Math.min(pages - 1, start + windowSize - 1);
        start = Math.max(0, end - windowSize + 1);

        for (let i = start; i <= end; i++) {
            wrap.appendChild(makeBtn(String(i + 1), i, false, i === page));
        }

        wrap.appendChild(makeBtn('›', Math.min(pages - 1, page + 1), page === pages - 1));
        wrap.appendChild(makeBtn('»', pages - 1, page === pages - 1));

        host.appendChild(wrap);
    }

    function initDataTable(opts) {
        const o = Object.assign(
            {
                root: document,

                table: null,
                search: null,
                pageSize: null,
                info: null,
                pagination: null,

                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [[1, 'desc']],
                dom: 't',
                autoWidth: false,

                emptyTemplate: null,
                zeroTemplate: null,
                emptyFallback: `
          <div class="flex flex-col items-center justify-center gap-2 text-center py-12 text-muted-foreground">
            <i class="ki-outline ki-folder-open text-4xl mb-2"></i>
            <div class="font-medium text-secondary-foreground">Henüz kayıt bulunmuyor.</div>
            <div class="text-sm">Yeni kayıt ekleyerek başlayabilirsiniz.</div>
          </div>
        `,
                zeroFallback: `
          <div class="flex flex-col items-center justify-center gap-2 text-center py-12 text-muted-foreground">
            <i class="ki-outline ki-search-list text-4xl mb-2"></i>
            <div class="font-medium text-secondary-foreground">Sonuç bulunamadı.</div>
            <div class="text-sm">Arama kriterlerini değiştirip tekrar deneyin.</div>
          </div>
        `,

                columnDefs: [],
                language: {},
                onDraw: null,

                checkAll: null,
                rowChecks: null,

                headerCenter: true,
            },
            opts || {}
        );

        const root = o.root || document;

        if (!o.table) return null;

        if (!w.jQuery || !jQuery.fn || !jQuery.fn.DataTable) {
            console.error('DataTables yüklenmemiş. (jQuery.fn.DataTable yok)');
            return null;
        }

        const tableEl = root.querySelector(o.table);
        if (!tableEl) return null;

        const $table = jQuery(tableEl);

        if (jQuery.fn.dataTable.isDataTable($table)) return $table.DataTable();

        const emptyHtml = o.emptyTemplate ? tplHtml(o.emptyTemplate, o.emptyFallback, root) : o.emptyFallback;
        const zeroHtml  = o.zeroTemplate  ? tplHtml(o.zeroTemplate,  o.zeroFallback,  root) : o.zeroFallback;

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

        // search bind (root scoped)
        if (o.search) {
            const s = root.querySelector(o.search);
            if (s && !s._dtBound) {
                s._dtBound = true;
                s.addEventListener('input', (e) => dt.search(e.target.value || '').draw());
            }
        }

        // page size bind (client-side mode only)
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

        // check-all (root scoped)
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
