(function () {
    const OBSERVED = new WeakSet();

    function toTurkishInfo(text) {
        if (!text) return text;

        // Örnek: "1-10 of 32" / "1 - 10 of 32"
        const m = text.match(/(\d+)\s*-\s*(\d+)\s*of\s*(\d+)/i);
        if (m) {
            const start = Number(m[1]);
            const end = Number(m[2]);
            const total = Number(m[3]);

            if (total === 0) return 'Kayıt bulunmuyor';
            return `${start}–${end} / ${total} kayıt`;
        }

        // Bazı sürümlerde farklı olasılıklar: "No records found" vb.
        if (/no\s+records?/i.test(text)) return 'Kayıt bulunmuyor';

        return text;
    }

    function bindInfoSpan(span) {
        if (!span || OBSERVED.has(span)) return;
        OBSERVED.add(span);

        // İlk çeviri
        span.textContent = toTurkishInfo(span.textContent);

        // Metin değiştikçe çevir
        const mo = new MutationObserver(() => {
            const translated = toTurkishInfo(span.textContent);
            if (span.textContent !== translated) {
                span.textContent = translated;
            }
        });

        mo.observe(span, { childList: true, characterData: true, subtree: true });
    }

    function scan() {
        document.querySelectorAll('[data-kt-datatable-info="true"]').forEach(bindInfoSpan);
    }

    // Metronic bazen DOM’u sonra dolduruyor → birkaç kez tarayıp bırakıyoruz
    document.addEventListener('DOMContentLoaded', () => {
        scan();

        let tries = 0;
        const timer = setInterval(() => {
            scan();
            tries++;
            if (tries >= 10) clearInterval(timer); // 10*300ms = 3sn yeter
        }, 300);
    });

})();
