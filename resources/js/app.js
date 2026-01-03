import './bootstrap';
import './admin/helpers/datatable-helper';

import Alpine from 'alpinejs';
import { AppInit } from './core/app-init';
import { registerPages } from './admin/pages/index';
import { initMediaPicker } from './core/media-picker';
import { initMediaUploadModal } from './core/media-upload-modal';
initMediaPicker();
initMediaUploadModal();
// Global: Media modal tab switch (upload/library)
// Yeni dosya yok, sayfa bağımsız çalışır.
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-media-tab]');
    if (!btn) return;

    // Modal içinde çalış
    const modal = btn.closest('#mediaUploadModal');
    if (!modal) return;

    const tab = btn.getAttribute('data-media-tab'); // upload | library
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
        // library tab açıldı: listeyi yenilemek isteyen kodlar bunu yakalayabilir
        modal.dispatchEvent(new CustomEvent('media:library:open', { bubbles: true }));
    }
});

// KTUI ImageInput: file dialog cancel guard
document.addEventListener('change', (e) => {
    const input = e.target;
    if (!(input instanceof HTMLInputElement)) return;
    if (input.type !== 'file') return;

    const wrap = input.closest('[data-kt-image-input]');
    if (!wrap) return;

    // Kullanıcı dosya seçmeyi iptal ettiyse files boş gelir.
    // KTUI bazen bunu kontrol etmiyor ve readAsDataURL(undefined) ile patlıyor.
    if (!input.files || input.files.length === 0) {
        e.stopImmediatePropagation(); // KTUI handler'ını engelle
        // opsiyonel: input.value = '';  // state temizliği istersen
    }
}, true); // CAPTURE: KTUI'den önce yakala

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
