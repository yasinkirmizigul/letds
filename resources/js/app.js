import './bootstrap';
import './admin/helpers/datatable-helper';

import Alpine from 'alpinejs';
import { AppInit } from './core/app-init';
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
        await AppInit();
    } finally {
        document.documentElement.classList.remove('js-loading');
        document.documentElement.classList.add('js-ready');
    }
});
