
function initStatusFeaturedUI(root, signal) {
    const pubToggle = root.querySelector('#blog_is_published');
    const pubBadge  = root.querySelector('#blog_publish_badge');

    const featToggle = root.querySelector('#blog_is_featured');
    const featBadge  = root.querySelector('#blog_featured_badge');

    const pulse = (el) => {
        if (!el) return;
        el.classList.add('animate-pulse');
        window.setTimeout(() => el.classList.remove('animate-pulse'), 450);
    };

    const setBadge = (badge, isOn, onText, offText) => {
        if (!badge) return;
        badge.classList.remove('kt-badge-light-success', 'kt-badge-light', 'text-muted-foreground');
        if (isOn) {
            badge.classList.add('kt-badge-light-success');
            badge.textContent = onText;
        } else {
            badge.classList.add('kt-badge-light', 'text-muted-foreground');
            badge.textContent = offText;
        }
    };

    const sync = () => {
        if (pubToggle && pubBadge) setBadge(pubBadge, !!pubToggle.checked, 'Yayında', 'Taslak');
        if (featToggle && featBadge) setBadge(featBadge, !!featToggle.checked, 'Anasayfada', 'Kapalı');
    };

    sync();

    pubToggle?.addEventListener('change', () => { pulse(pubBadge); sync(); }, { signal });
    featToggle?.addEventListener('change', () => { pulse(featBadge); sync(); }, { signal });
}

// resources/js/admin/pages/blog/edit.js

import initSlugManager from '@/core/slug-manager';
import initGalleryManager, {destroyGalleryManager} from '@/core/gallery-manager';
import initLibraryAttach from '@/core/library-attach';

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

    // güvenli: aynı selector tekrar init edilirse önce kaldır
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

        images_upload_handler: (blobInfo, progress) =>
            new Promise((resolve, reject) => {
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

function lockSubmitButtons(root, formId) {
    root.querySelectorAll(`button[form="${formId}"][type="submit"]`).forEach((b) => {
        b.disabled = true;
        b.classList.add('opacity-60', 'pointer-events-none');
    });
}

export default async function init(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;

    // slug manager (signal destekli)
    initSlugManager(
        root,
        {
            sourceSelector: '#title',
            slugSelector: '#slug',
            previewSelector: '#url_slug_preview',
            autoSelector: '#slug_auto',
            regenSelector: '#slug_regen',
            generateOnInit: true,
        },
        signal
    );

    // tiny setup
    const ds = root.dataset;
    const tinymceSrc = ds.tinymceSrc;
    const tinymceBase = ds.tinymceBase;
    const tinymceLangUrl = ds.tinymceLangUrl;
    const uploadUrl = ds.uploadUrl;

    let themeObserver = null;

    if (tinymceSrc && tinymceBase && tinymceLangUrl && uploadUrl) {
        await loadScriptOnce(tinymceSrc).catch(() => {
        });

        initTiny({
            selector: '#content_editor',
            uploadUrl,
            baseUrl: tinymceBase,
            langUrl: tinymceLangUrl,
        });

        themeObserver = observeThemeChanges(() => {
            initTiny({
                selector: '#content_editor',
                uploadUrl,
                baseUrl: tinymceBase,
                langUrl: tinymceLangUrl,
            });
        });

        ctx.cleanup(() => {
            try {
                themeObserver?.disconnect?.();
            } catch {
            }
            themeObserver = null;

            // editor instance temizliği
            safeTinyRemove('#content_editor');
        });
    }

    // modal helpers (page scoped)
    root.addEventListener(
        'click',
        (e) => {
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
        },
        {signal}
    );

    // submit locks
    const updateForm = root.querySelector('#blog-update-form');
    if (updateForm) {
        updateForm.addEventListener('submit', () => lockSubmitButtons(root, 'blog-update-form'), {signal, once: true});
    }

    const deleteForm = root.querySelector('#blog-delete-form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', () => lockSubmitButtons(root, 'blog-delete-form'), {signal, once: true});
    }

    // page-scoped heavy modules
    initGalleryManager(root);
    ctx.cleanup(() => destroyGalleryManager(root));

    initLibraryAttach(root);
}

export function destroy(ctx) {
    // Page-registry önce destroy(ctx), sonra ctx.destroyAll() çağırıyor.
    // Bu destroy içinde ekstra bir şey yapmana gerek yok.
    // (ctx.destroyAll cleanup stack'i çalıştırır)
}
