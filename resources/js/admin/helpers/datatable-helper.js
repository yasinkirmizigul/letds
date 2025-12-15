/* global jQuery */

(function (w) {
    function tplHtml(selector, fallbackHtml) {
        const el = document.querySelector(selector);
        if (!el) return fallbackHtml || '';
        // template veya normal element fark etmesin
        if (el.tagName === 'TEMPLATE') return el.innerHTML.trim();
        return el.innerHTML.trim();
    }

    function renderPagination(api, hostSelector) {
        const host = document.querySelector(hostSelector);
        if (!host) return;

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

        host.appendChild(makeBtn('‹', Math.max(0, page - 1), page === 0));

        const start = Math.max(0, page - 2);
        const end = Math.min(pages - 1, page + 2);
        for (let i = start; i <= end; i++) host.appendChild(makeBtn(String(i + 1), i, false, i === page));

        host.appendChild(makeBtn('›', Math.min(pages - 1, page + 1), page === pages - 1));
    }

    /**
     * initMetronicDataTable
     * - DataTables.net core + senin Metronic header/footer entegrasyonu
     */
    function initMetronicDataTable(opts) {
        const o = Object.assign(
            {
                table: null,              // '#blog_table'
                search: null,             // '#blogSearch'
                pageSize: null,           // '#blogPageSize'
                info: null,               // '#blogInfo'
                pagination: null,         // '#blogPagination'
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [[0, 'desc']],
                dom: 't',
                autoWidth: false,

                emptyTemplate: null,      // '#dt-empty-blog'
                zeroTemplate: null,       // '#dt-zero-blog'
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

                // draw sonrası senin custom init’lerin
                onDraw: null,             // function(api) {}
                checkAll: null,   // '#users_check_all'
                rowChecks: null,  // '.users_row_check'
            },
            opts || {}
        );

        if (!o.table) return null;

        if (!w.jQuery || !jQuery.fn || !jQuery.fn.DataTable) {
            console.error('DataTables yüklenmemiş. (jQuery.fn.DataTable yok)');
            return null;
        }

        const $table = jQuery(o.table);
        if (!$table.length) return null;

        // çift init engeli
        if (jQuery.fn.dataTable.isDataTable($table)) return $table.DataTable();

        const emptyHtml = o.emptyTemplate ? tplHtml(o.emptyTemplate, o.emptyFallback) : o.emptyFallback;
        const zeroHtml  = o.zeroTemplate  ? tplHtml(o.zeroTemplate,  o.zeroFallback)  : o.zeroFallback;

        const dt = $table.DataTable({
            pageLength: o.pageLength,
            lengthMenu: o.lengthMenu,
            order: o.order,
            autoWidth: o.autoWidth,
            dom: o.dom,

            columnDefs: o.columnDefs,

            language: Object.assign(
                {
                    emptyTable: emptyHtml,
                    zeroRecords: zeroHtml,
                    infoEmpty: 'Kayıt yok',
                },
                o.language || {}
            ),

            drawCallback: function () {
                const api = this.api();

                // info (bağlama göre)
                if (o.info) {
                    const info = api.page.info();
                    const hasSearch = (api.search() || '').trim().length > 0;
                    const infoEl = document.querySelector(o.info);

                    if (infoEl) {
                        if (info.recordsTotal === 0) infoEl.textContent = 'Henüz kayıt yok';
                        else if (info.recordsDisplay === 0 && hasSearch) infoEl.textContent = 'Sonuç yok';
                        else infoEl.textContent = `${info.start + 1}-${info.end} / ${info.recordsDisplay}`;
                    }
                }

                // pagination
                if (o.pagination) renderPagination(api, o.pagination);

                // custom hook
                if (typeof o.onDraw === 'function') o.onDraw(api);
                // sayfa değişince header checkbox sıfırla
                if (o.checkAll) {
                    const c = document.querySelector(o.checkAll);
                    if (c) c.checked = false;
                }
            },
        });
        $table.removeClass('dataTable');     // DataTables’ın default class’ı
        $table.removeClass('no-footer');

        // search bind
        if (o.search) {
            const s = document.querySelector(o.search);
            if (s && !s._dtBound) {
                s._dtBound = true;
                s.addEventListener('input', (e) => dt.search(e.target.value || '').draw());
            }
        }

        // page size bind
        if (o.pageSize) {
            const sel = document.querySelector(o.pageSize);
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
// ✅ check-all (tek seferlik bind)
        if (o.checkAll && o.rowChecks) {
            const checkAllEl = document.querySelector(o.checkAll);
            if (checkAllEl && !checkAllEl._dtBound) {
                checkAllEl._dtBound = true;

                checkAllEl.addEventListener('change', () => {
                    const checked = checkAllEl.checked;
                    document.querySelectorAll(o.rowChecks).forEach(cb => (cb.checked = checked));
                });
            }
        }
        // ilk draw
        dt.draw();

        return dt;
    }

    w.initMetronicDataTable = initMetronicDataTable;
})(window);
