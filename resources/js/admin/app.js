import '../bootstrap';
import './helpers/datatable-helper';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

import Alpine from 'alpinejs';
import { AppInit } from '../core/app-init';
import { initDateInputValues } from '../core/date-input';
import { registerPages } from './pages/index';
import { initMediaPicker } from '../core/media-picker';
import { initMediaUploadModal } from '../core/media-upload-modal';
import initFeaturedImageManager from '@/core/featured-image-manager';
import initCreateFormAccordions from '@/core/create-form-accordion';
import initAjaxForms from '@/core/ajax-forms';
import initAdminQuickSearch from '@/core/admin-quick-search';
import { initMetronicPickers } from '@/core/metronic-pickers';

window.Swal = Swal;
window.swal = Swal;

const markReady = (state = 'ready') => {
    document.documentElement.classList.remove('js-loading');
    document.documentElement.classList.add('js-ready');

    if (state === 'timeout') {
        document.documentElement.classList.add('js-ready-timeout');
        console.warn('[AppInit] Loader fallback fired before page boot completed.');
    }
};

const loaderFallback = window.setTimeout(() => markReady('timeout'), 5000);

initMediaPicker();
initMediaUploadModal();
initFeaturedImageManager();
initAjaxForms();
initAdminQuickSearch();

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-media-tab]');
    if (!btn) return;

    const modal = btn.closest('#mediaUploadModal');
    if (!modal) return;

    const tab = btn.getAttribute('data-media-tab');
    const uploadBtn = modal.querySelector('#mediaTabUpload');
    const libBtn = modal.querySelector('#mediaTabLibrary');
    const uploadPane = modal.querySelector('#mediaUploadPane');
    const libPane = modal.querySelector('#mediaLibraryPane');

    if (!uploadBtn || !libBtn || !uploadPane || !libPane) return;

    const isUpload = tab === 'upload';

    uploadBtn.setAttribute('aria-selected', isUpload ? 'true' : 'false');
    libBtn.setAttribute('aria-selected', !isUpload ? 'true' : 'false');

    uploadPane.classList.toggle('hidden', !isUpload);
    libPane.classList.toggle('hidden', isUpload);

    if (!isUpload) {
        modal.dispatchEvent(new CustomEvent('media:library:open', { bubbles: true }));
    }
});

document.addEventListener('change', (e) => {
    const input = e.target;
    if (!(input instanceof HTMLInputElement)) return;
    if (input.type !== 'file') return;

    const wrap = input.closest('[data-kt-image-input]');
    if (!wrap) return;

    if (!input.files || input.files.length === 0) {
        e.stopImmediatePropagation();
    }
}, true);

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
        initCreateFormAccordions(document);
        initDateInputValues(document);
        initMetronicPickers(document);
        await AppInit();
    } finally {
        window.clearTimeout(loaderFallback);
        markReady();
    }
});
