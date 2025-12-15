(function () {
    window.__KT_EMPTY_OVERLAY_LOADED__ = true;
    console.log('KT EMPTY OVERLAY LOADED');
    const STATE = new WeakMap(); // dt -> { scheduled, busy }

    function getWrap(dt) {
        return dt.querySelector('.kt-scrollable-x-auto') || dt;
    }

    function ensureOverlay(dt) {
        const wrap = dt.querySelector('.kt-scrollable-x-auto');
        if (!wrap) return null;

        let overlay = wrap.querySelector('[data-kt-empty-overlay="true"]');
        if (overlay) return overlay;

        if (getComputedStyle(wrap).position === 'static') wrap.style.position = 'relative';

        overlay = document.createElement('div');
        overlay.setAttribute('data-kt-empty-overlay', 'true');

        overlay.className = 'hidden';
        overlay.style.position = 'absolute';
        overlay.style.inset = '0';
        overlay.style.zIndex = '50';
        overlay.style.pointerEvents = 'none';

        overlay.innerHTML = `
      <div class="w-full h-full flex items-center justify-center">
        <div class="flex flex-col items-center justify-center gap-2 text-center p-8">
          <i class="ki-outline ki-search-list text-4xl text-muted-foreground"></i>
          <div class="font-medium" data-title></div>
          <div class="text-sm text-muted-foreground" data-desc></div>
        </div>
      </div>
    `;

        wrap.appendChild(overlay);
        return overlay;
    }



    function getSearchQuery(dt) {
        if (!dt.id) return '';
        const input = document.querySelector(`[data-kt-datatable-search="#${CSS.escape(dt.id)}"]`);
        return input ? (input.value || '').trim() : '';
    }

    function isNoRecordsRow(tr) {
        // Metronic’in default empty row’u genelde: <tr><td colspan="X">No records found</td></tr>
        const td = tr?.querySelector?.('td[colspan]');
        if (!td) return false;
        const t = (td.textContent || '').trim().toLowerCase();
        return t === 'no records found' || t === 'no data' || t === 'no records';
    }

    function killNoRecordsRowsEverywhere(dt) {
        dt.querySelectorAll('tbody tr').forEach(tr => {
            if (isNoRecordsRow(tr)) tr.style.display = 'none';
        });
    }

    function hasAnyVisibleRealRowEverywhere(dt) {
        const rows = dt.querySelectorAll('tbody tr');
        for (const tr of rows) {
            if (isNoRecordsRow(tr)) continue;
            if (tr.getClientRects().length > 0) return true;
        }
        return false;
    }

    function update(dt) {
        const state = STATE.get(dt);
        if (!state || state.busy) return;

        state.busy = true;

        try {
            const overlay = ensureOverlay(dt);

            const rows = Array.from(dt.querySelectorAll('tbody tr'));

            // 👉 colspan OLMAYAN gerçek satır var mı?
            const hasRealRow = rows.some(tr => !tr.querySelector('td[colspan]'));

            const empty = !hasRealRow;

            const q = getSearchQuery(dt);

            const titleEl = overlay.querySelector('[data-title]');
            const descEl  = overlay.querySelector('[data-desc]');

            const newTitle = q
                ? 'Arama kriterine uygun kayıt bulunamadı.'
                : 'Henüz kayıt bulunmuyor.';

            const newDesc = q
                ? 'Farklı bir ifade deneyin veya aramayı temizleyin.'
                : 'Yeni kayıt ekleyerek başlayabilirsiniz.';

            if (titleEl.textContent !== newTitle) titleEl.textContent = newTitle;
            if (descEl.textContent !== newDesc) descEl.textContent = newDesc;

            overlay.classList.toggle('hidden', !empty);

        } finally {
            state.busy = false;
        }
    }


    function schedule(dt, delay = 60) {
        const s = STATE.get(dt);
        if (!s) return;

        if (s.scheduled) clearTimeout(s.scheduled);

        s.scheduled = setTimeout(() => {
            // schedule tarafı busy dokunmaz!
            try {
                update(dt);
            } finally {
                s.scheduled = null;
            }
        }, delay);
    }

    function bind(dt) {
        if (STATE.has(dt)) return;
        STATE.set(dt, { scheduled: null, busy: false });

        const table = dt.querySelector('table[data-kt-datatable-table="true"]');
        const tbody = table?.querySelector('tbody');
        if (!tbody) return;

        // ✅ SADECE tbody değişince tetikle
        const mo = new MutationObserver(() => schedule(dt, 50));
        mo.observe(tbody, { childList: true, subtree: true });

        // search input
        if (dt.id) {
            const input = document.querySelector(
                `[data-kt-datatable-search="#${CSS.escape(dt.id)}"]`
            );
            if (input) {
                input.addEventListener('input', () => schedule(dt, 120));
            }
        }

        schedule(dt, 0);
    }

    function scan() {
        document.querySelectorAll('[data-kt-datatable="true"]').forEach(bind);
    }

    document.addEventListener('DOMContentLoaded', () => {
        scan();
        let i = 0;
        const t = setInterval(() => {
            scan();
            if (++i >= 8) clearInterval(t);
        }, 250);
    });
})();
