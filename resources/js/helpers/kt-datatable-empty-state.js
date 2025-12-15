window.KtDatatableEmptyState = (function () {

    const DEFAULT_EMPTY_RX =
        /(no records found|no matching records found|kayıt bulunamadı|kayıt bulunmuyor)/i;

    function init(options) {
        const tableSel   = options.table;
        const templateSel = options.template;
        const emptyHtml  = options.html || null;
        const emptyRx    = options.emptyRegex || DEFAULT_EMPTY_RX;

        // 👇👇👇 İŞTE BURAYA 👇👇👇
        function appendCustomEmpty(tbody) {
            if (tbody.querySelector('[data-kt-empty-row="true"]')) return;

            if (emptyHtml) {
                tbody.insertAdjacentHTML('beforeend', emptyHtml);
                return;
            }

            const tpl = document.querySelector(templateSel);
            if (tpl) tbody.appendChild(tpl.content.cloneNode(true));
        }

        function patch() {
            const table = document.querySelector(tableSel);
            if (!table) return;

            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            const rows = Array.from(tbody.querySelectorAll('tr'));

            // KT default empty satırını sil
            rows.forEach(tr => {
                if (emptyRx.test(tr.innerText.trim())) {
                    tr.remove();
                }
            });

            // tbody BOŞSA → bizim empty
            if (tbody.querySelectorAll('tr').length === 0) {
                appendCustomEmpty(tbody);
                return;
            }

            // Veri geldiyse → bizim empty'yi kaldır
            const custom = tbody.querySelector('[data-kt-empty-row="true"]');
            if (custom && tbody.querySelectorAll('tr').length > 1) {
                custom.remove();
            }
        }

        // KT bazen tbody’yi değiştirir
        const mo = new MutationObserver(() => patch());
        mo.observe(document.documentElement, { childList: true, subtree: true });

        let tries = 0;
        (function tick() {
            patch();
            if (++tries < 60) requestAnimationFrame(tick);
        })();
    }

    return { init };
})();
