/* global jQuery */
(function (w) {
    const bound = new WeakMap(); // Element -> Set(keys)

    function markBound(el, key) {
        if (!el) return false;
        let set = bound.get(el);
        if (!set) {
            set = new Set();
            bound.set(el, set);
        }
        if (set.has(key)) return true;
        set.add(key);
        return false;
    }

    function tplHtml(selector, fallbackHtml, root = document) {
        const el = root.querySelector(selector) || document.querySelector(selector);
        if (!el) return (fallbackHtml || '').trim();
        if (el.tagName === 'TEMPLATE') return (el.innerHTML || '').trim();
        return (el.innerHTML || '').trim();
    }

    function isTrTemplate(html) {
        const t = (html || '').trim().toLowerCase();
        return t.startsWith('<tr');
    }

    function normalizeRowTemplate(html, colCount) {
        const t = document.createElement('tbody');
        t.innerHTML = (html || '').trim();
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

    function renderPagination(api, hostSelector, root = document, signal) {
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
            btn.addEventListener('click', () => api.page(targetPage).draw('page'), signal ? { signal } : undefined);
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

    function safeDestroyDt(dt) {
        try {
            if (dt && typeof dt.destroy === 'function') dt.destroy(true);
        } catch (e) {
            console.warn('[initDataTable] destroy failed:', e);
        }
    }

    function resolveTableEl(table, root = document) {
        if (!table) return null;
        if (typeof table === 'string') return root.querySelector(table);
        if (table instanceof Element) return table;
        if (w.jQuery && table && table.jquery && table[0] instanceof Element) return table[0];
        return null;
    }

    /**
     * ✅ Backward compatible wrapper:
     * - initDataTable(opts)
     * - initDataTable(tableElOrSelector, opts)  <-- eski/yanlış kullanımları da kaldırır
     */
    function initDataTable(arg1, arg2) {
        let opts = null;

        if (typeof arg1 === 'string' || arg1 instanceof Element || (w.jQuery && arg1?.jquery)) {
            opts = Object.assign({}, arg2 || {});
            opts.table = arg1;
        } else {
            opts = Object.assign({}, arg1 || {});
        }

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
                autoWidth: true,
                responsive: false,
                scrollX: false,

                // ✅ server-side fields
                serverSide: false,
                processing: true,
                ajax: null,
                columns: null,

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

                signal: null,
                cleanup: null,

                dtOptions: null,
            },
            opts || {}
        );

        const root = o.root || document;
        const signal = o.signal || null;
        const cleanup = (typeof o.cleanup === 'function') ? o.cleanup : null;

        if (!w.jQuery || !jQuery.fn || !jQuery.fn.DataTable) {
            console.error('DataTables yüklenmemiş. (jQuery.fn.DataTable yok)');
            return null;
        }

        const tableEl = resolveTableEl(o.table, root);
        if (!tableEl) return null;

        const $table = jQuery(tableEl);

        // Already inited
        if (jQuery.fn.dataTable.isDataTable($table)) {
            return $table.DataTable();
        }

        const emptyHtml = o.emptyTemplate ? tplHtml(o.emptyTemplate, o.emptyFallback, root) : o.emptyFallback;
        const zeroHtml = o.zeroTemplate ? tplHtml(o.zeroTemplate, o.zeroFallback, root) : o.zeroFallback;

        if (o.headerCenter) tableEl.classList.add('dt-kt-header-center');

        const baseDtOptions = {
            pageLength: o.pageLength,
            lengthMenu: o.lengthMenu,
            order: o.order,
            autoWidth: o.autoWidth,
            dom: o.dom,
            responsive: o.responsive,
            scrollX: o.scrollX,

            // ✅ server-side support
            serverSide: !!o.serverSide,
            processing: !!o.processing,
            ajax: o.ajax || undefined,
            columns: o.columns || undefined,

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

                if (o.pagination) renderPagination(api, o.pagination, root, signal);
                if (typeof o.onDraw === 'function') o.onDraw(api);

                if (o.checkAll) {
                    const c = root.querySelector(o.checkAll);
                    if (c) c.checked = false;
                }
            },
        };

        const merged = Object.assign({}, baseDtOptions, (o.dtOptions && typeof o.dtOptions === 'object') ? o.dtOptions : {});

        const dt = $table.DataTable(merged);
        $table.removeClass('dataTable no-footer');

        if (cleanup) cleanup(() => safeDestroyDt(dt));

        // search bind (root scoped)
        if (o.search) {
            const s = root.querySelector(o.search);
            if (s && !markBound(s, `dt:search:${String(o.table)}`)) {
                s.addEventListener(
                    'input',
                    (e) => dt.search(e.target.value || '').draw(),
                    signal ? { signal } : undefined
                );
            }
        }

        // page size bind
        if (o.pageSize) {
            const sel = root.querySelector(o.pageSize);
            if (sel && !markBound(sel, `dt:pagesize:${String(o.table)}`)) {
                sel.innerHTML = '';
                (o.lengthMenu || [5, 10, 25, 50]).forEach((n) => {
                    const opt = document.createElement('option');
                    opt.value = n;
                    opt.textContent = n;
                    sel.appendChild(opt);
                });

                sel.value = String(dt.page.len());

                sel.addEventListener(
                    'change',
                    (e) => dt.page.len(Number(e.target.value)).draw(),
                    signal ? { signal } : undefined
                );
            }
        }

        // check-all (root scoped)
        if (o.checkAll && o.rowChecks) {
            const checkAllEl = root.querySelector(o.checkAll);
            if (checkAllEl && !markBound(checkAllEl, `dt:checkall:${String(o.table)}`)) {
                checkAllEl.addEventListener(
                    'change',
                    () => {
                        const checked = checkAllEl.checked;
                        root.querySelectorAll(o.rowChecks).forEach((cb) => (cb.checked = checked));
                    },
                    signal ? { signal } : undefined
                );
            }
        }

        return dt;
    }

    // ✅ global
    w.initDataTable = initDataTable;
})(window);
