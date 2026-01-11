import initGalleryManager from '@/core/gallery-manager';
import initFeaturedImageManager, {destroyFeaturedImageManager} from '@/core/featured-image-manager';
import { initMediaPicker } from '@/core/media-picker';
import initLibraryAttach from '@/core/library-attach';

let ac = null;
let observer = null;
let lastObjectUrl = null;
let mountedRoot = null;

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function getTheme() {
    const root = document.documentElement;
    const body = document.body;
    const isDark = root.classList.contains('dark') || body.classList.contains('dark');
    return isDark ? 'dark' : 'light';
}

function slugifyTR(str) {
    return String(str || '')
        .trim()
        .toLowerCase()
        .replaceAll('ğ', 'g').replaceAll('ü', 'u').replaceAll('ş', 's')
        .replaceAll('ı', 'i').replaceAll('ö', 'o').replaceAll('ç', 'c')
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

function loadScriptOnce(src) {
    if (!src) return Promise.reject(new Error('tinymce src missing'));
    if (document.querySelector(`script[data-once="${src}"]`)) return Promise.resolve();

    return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = src;
        s.async = true;
        s.dataset.once = src;
        s.onload = () => resolve();
        s.onerror = () => reject(new Error('Failed to load: ' + src));
        document.head.appendChild(s);
    });
}

function safeTinyRemove(selector) {
    try {
        window.tinymce?.remove?.(selector);
    } catch {
    }
}

function initTiny({selector, uploadUrl, baseUrl, langUrl}) {
    if (!window.tinymce) return;

    const theme = getTheme();
    safeTinyRemove(selector);

    window.tinymce.init({
        selector,
        height: 420,
        license_key: 'gpl',
        base_url: baseUrl,
        suffix: '.min',
        language: 'tr',
        language_url: langUrl,
        skin: theme === 'dark' ? 'oxide-dark' : 'oxide',
        content_css: theme === 'dark' ? 'dark' : 'default',
        plugins: 'lists link image code table',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link image table | code',
        menubar: false,
        branding: false,
        promotion: false,
        automatic_uploads: true,
        paste_data_images: true,
        images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', uploadUrl);
            xhr.withCredentials = true;
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) progress((e.loaded / e.total) * 100);
            };

            xhr.onload = () => {
                if (xhr.status < 200 || xhr.status >= 300) return reject('Upload failed: ' + xhr.status);

                let json;
                try {
                    json = JSON.parse(xhr.responseText);
                } catch {
                    return reject('Invalid JSON');
                }
                if (!json || typeof json.location !== 'string') return reject('No location returned');

                resolve(json.location);
            };

            xhr.onerror = () => reject('Network error');

            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            xhr.send(formData);
        }),
    });
}

function observeThemeChanges(onChange) {
    let current = getTheme();
    const obs = new MutationObserver(() => {
        const next = getTheme();
        if (next === current) return;
        current = next;
        onChange(next);
    });

    obs.observe(document.documentElement, {attributes: true, attributeFilter: ['class']});
    obs.observe(document.body, {attributes: true, attributeFilter: ['class']});
    return obs;
}

function openModal(root, selector) {
    const modal = root.querySelector(selector);
    if (!modal) return;

    // KTUI modal varsa onu kullan
    try {
        if (window.KTModal?.getOrCreateInstance) {
            const inst = window.KTModal.getOrCreateInstance(modal);
            inst?.show?.();
            return;
        }
        if (window.KTModal?.getInstance) {
            const inst = window.KTModal.getInstance(modal) || new window.KTModal(modal);
            inst?.show?.();
            return;
        }
    } catch {
    }

    // fallback
    modal.classList.remove('hidden');
}

function closeModal(modal) {
    if (!modal) return;

    try {
        if (window.KTModal?.getOrCreateInstance) {
            const inst = window.KTModal.getOrCreateInstance(modal);
            inst?.hide?.();
            return;
        }
        if (window.KTModal?.getInstance) {
            const inst = window.KTModal.getInstance(modal) || new window.KTModal(modal);
            inst?.hide?.();
            return;
        }
    } catch {
    }

    modal.classList.add('hidden');
}

function setupSlugUI(root, signal) {
    const titleInput = root.querySelector('#title') || root.querySelector('input[name="title"]');
    const slugInput = root.querySelector('#slug');
    const toggle = root.querySelector('#slug_auto_toggle');
    const regenBtn = root.querySelector('#slug_regen_btn');
    const badge = root.querySelector('#slug_mode_badge');
    const preview = root.querySelector('#url_slug_preview');

    if (!slugInput) return;

    const syncPreview = () => {
        if (preview) preview.textContent = (slugInput.value || '').trim();
    };
    const setManualMode = (isManual) => {
        if (badge) badge.classList.toggle('hidden', !isManual);
        slugInput.style.boxShadow = isManual ? '0 0 0 2px rgba(245, 158, 11, .35)' : '';
    };
    const applyAutoSlug = () => {
        if (!titleInput) return;
        slugInput.value = slugifyTR(titleInput.value);
        syncPreview();
    };

    syncPreview();
    setManualMode(false);

    slugInput.addEventListener('input', () => {
        const v = slugInput.value.trim();
        if (toggle && toggle.checked && v.length > 0) {
            toggle.checked = false;
            setManualMode(true);
        }
        syncPreview();
    }, {signal});

    if (titleInput && toggle) {
        titleInput.addEventListener('input', () => {
            if (!toggle.checked) return;
            if (slugInput.value.trim().length > 0) return;
            applyAutoSlug();
        }, {signal});

        toggle.addEventListener('change', () => {
            const isAuto = toggle.checked;
            setManualMode(!isAuto);
            if (isAuto) {
                slugInput.value = '';
                applyAutoSlug();
            }
        }, {signal});
    }

    if (regenBtn && toggle) {
        regenBtn.addEventListener('click', () => {
            toggle.checked = true;
            setManualMode(false);
            slugInput.value = '';
            applyAutoSlug();
        }, {signal});
    }
}

function lockSubmitButtons(root, formId) {
    root.querySelectorAll(`button[form="${formId}"][type="submit"]`).forEach((b) => {
        b.disabled = true;
        b.classList.add('opacity-60', 'pointer-events-none');
    });
}

export default async function init({root, dataset}) {
    mountedRoot = root;
    ac = new AbortController();
    const {signal} = ac;

    setupSlugUI(root, signal);

    // TinyMCE (blog blade: data-tinymce-src/base/lang-url + data-upload-url)
    const ds = root.dataset;
    const tinymceSrc = ds.tinymceSrc;
    const tinymceBase = ds.tinymceBase;
    const tinymceLangUrl = ds.tinymceLangUrl;
    const uploadUrl = ds.uploadUrl;

    if (tinymceSrc && tinymceBase && tinymceLangUrl && uploadUrl) {
        await loadScriptOnce(tinymceSrc).catch(() => {
        });
        initTiny({
            selector: '#content_editor',
            uploadUrl,
            baseUrl: tinymceBase,
            langUrl: tinymceLangUrl,
        });

        observer = observeThemeChanges(() => {
            initTiny({
                selector: '#content_editor',
                uploadUrl,
                baseUrl: tinymceBase,
                langUrl: tinymceLangUrl,
            });
        });
    }
    // Modal delegation
    root.addEventListener('click', (e) => {
        const toggleBtn = e.target.closest('[data-kt-modal-toggle]');
        if (toggleBtn && root.contains(toggleBtn)) {
            const sel = toggleBtn.getAttribute('data-kt-modal-toggle');
            if (sel) openModal(root, sel);
            return;
        }

        const openBtn = e.target.closest('[data-kt-modal-target]');
        if (openBtn && root.contains(openBtn)) {
            const sel = openBtn.getAttribute('data-kt-modal-target');
            if (sel) openModal(root, sel);
            return;
        }

        const closeBtn = e.target.closest('[data-kt-modal-close]');
        if (closeBtn && root.contains(closeBtn)) {
            closeModal(closeBtn.closest('.kt-modal'));
            return;
        }

        const modal = e.target.classList?.contains('kt-modal') ? e.target : null;
        if (modal) closeModal(modal);
    }, {signal});

    // Prevent double submit
    const updateForm = root.querySelector('#blog-update-form');
    if (updateForm) updateForm.addEventListener('submit', () => lockSubmitButtons(root, 'blog-update-form'), {
        signal,
        once: true
    });

    const deleteForm = root.querySelector('#blog-delete-form');
    if (deleteForm) deleteForm.addEventListener('submit', () => lockSubmitButtons(root, 'blog-delete-form'), {
        signal,
        once: true
    });

    // ✅ NEW: Gallery Manager (component + ortak JS)
    initMediaPicker();
    initGalleryManager(root);
    initLibraryAttach(root);
    initFeaturedImageManager(root);
}

export function destroy() {
    try {
        ac?.abort();
    } catch {
    }
    ac = null;

    try {
        observer?.disconnect?.();
    } catch {
    }
    observer = null;

    if (lastObjectUrl) {
        try {
            URL.revokeObjectURL(lastObjectUrl);
        } catch {
        }
        lastObjectUrl = null;
    }
    // featured-image-manager cleanup
    destroyFeaturedImageManager(mountedRoot || document);
    mountedRoot = null;
}
