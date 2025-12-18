import './bootstrap';
import './admin/helpers/datatable-helper';

import Alpine from 'alpinejs';
import { AppInit } from './core/app-init';
import { enhance } from './core/enhance';
import { registerPages } from './admin/pages/index';

window.Alpine = Alpine;
Alpine.start();

registerPages();

function domReady(fn) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
        fn();
    }
}

domReady(async () => {
    try {
        enhance(document);     // sadece DOM dekorasyonu
        await AppInit();       // ✅ KTUI + page boot burada
    } finally {
        document.documentElement.classList.remove('js-loading');
        document.documentElement.classList.add('js-ready');
    }
});
