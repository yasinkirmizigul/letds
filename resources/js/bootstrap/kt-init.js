(function () {
    const queue = [];
    let ranOnce = false;

    function whenDepsReady() {
        return !!(window.KTDom && window.KTComponents);
    }

    function run() {
        if (!whenDepsReady()) return;

        // KTUI core init (menü, datepicker vs)
        try { window.KTComponents?.init?.(); } catch (e) {}

        // queued page initializers
        while (queue.length) {
            const fn = queue.shift();
            try { fn(); } catch (e) { console.error(e); }
        }

        // show page
        document.documentElement.setAttribute('data-js', 'ready');
        ranOnce = true;
    }

    // public API
    window.AppInit = {
        onReady(fn) {
            queue.push(fn);
            run();
        },
        rerun() { run(); }
    };

    // DOM ready sonrası dene
    document.addEventListener('DOMContentLoaded', run);

    // Livewire varsa navigated sonrası tekrar
    document.addEventListener('livewire:navigated', () => {
        // tekrar loading yapma; sadece initleri yeniden çalıştır
        run();
    });

    // KTUI daha geç yüklenirse diye kısa polling (maks 2sn)
    const started = Date.now();
    const t = setInterval(() => {
        run();
        if (ranOnce || (Date.now() - started) > 2000) clearInterval(t);
    }, 50);
})();
