import initSlugManager from '@/core/slug-manager';

let ac = null;
let observer = null;

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
    try { window.tinymce?.remove?.(selector); } catch {}
}

function initTiny({ selector, uploadUrl, baseUrl, langUrl }) {
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
                try { json = JSON.parse(xhr.responseText); }
                catch { return reject('Invalid JSON'); }
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

    obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    obs.observe(document.body, { attributes: true, attributeFilter: ['class'] });

    return obs;
}

function initStatusFeaturedUI(root, signal) {
    // status badge preview
    const select = root.querySelector('[data-status-select]');
    const badge = root.querySelector('[data-status-badge]');
    let opts = {};
    try {
        opts = root.dataset.statusOptions ? JSON.parse(root.dataset.statusOptions) : {};
    } catch { opts = {}; }

    function applyStatus() {
        if (!select || !badge) return;
        const key = select.value;
        const o = opts[key] || opts['appointment_pending'] || null;

        const badgeClass = (o && o.badge) ? o.badge : 'kt-badge kt-badge-sm kt-badge-light';
        const label = (o && o.label) ? o.label : key;

        badge.className = `${badgeClass} whitespace-nowrap`;
        badge.textContent = label;
    }

    if (select && badge) {
        applyStatus();
        select.addEventListener('change', () => {
            applyStatus();
            // seçimi hissedilir yap
            badge.classList.add('animate-pulse');
            window.setTimeout(() => badge.classList.remove('animate-pulse'), 450);
        }, { signal });
    }

    // featured UI (client-only feedback)
    const ft = root.querySelector('[data-featured-toggle]');
    const lbl = root.querySelector('.js-featured-label');
    const fb = root.querySelector('.js-featured-badge');

    function applyFeatured() {
        if (!ft) return;
        const on = !!ft.checked;

        if (lbl) lbl.textContent = on ? 'Anasayfada' : 'Kapalı';

        if (fb) {
            if (on) {
                fb.hidden = false;
                requestAnimationFrame(() => {
                    fb.classList.remove('opacity-0');
                    fb.classList.add('opacity-100');
                });
            } else {
                fb.classList.remove('opacity-100');
                fb.classList.add('opacity-0');
                window.setTimeout(() => {
                    if (!ft.checked) fb.hidden = true;
                }, 200);
            }
        }
    }

    if (ft) {
        applyFeatured();
        ft.addEventListener('change', applyFeatured, { signal });
    }
}

export default async function init({ root }) {
    ac = new AbortController();
    const { signal } = ac;

    // ✅ Category/Blog ile aynı slug davranışı
    initSlugManager(root, {
        sourceSelector: '#title',
        slugSelector: '#slug',
        previewSelector: '#url_slug_preview',
        autoSelector: '#slug_auto',
        regenSelector: '#slug_regen',
        generateOnInit: true,
    }, signal);

    initStatusFeaturedUI(root, signal);

    // TinyMCE
    const ds = root.dataset;
    const tinymceSrc = ds.tinymceSrc;
    const tinymceBase = ds.tinymceBase;
    const tinymceLangUrl = ds.tinymceLangUrl;
    const uploadUrl = ds.uploadUrl;

    if (tinymceSrc && tinymceBase && tinymceLangUrl && uploadUrl) {
        await loadScriptOnce(tinymceSrc).catch(() => {});
        initTiny({ selector: '#content_editor', uploadUrl, baseUrl: tinymceBase, langUrl: tinymceLangUrl });

        observer = observeThemeChanges(() => {
            initTiny({ selector: '#content_editor', uploadUrl, baseUrl: tinymceBase, langUrl: tinymceLangUrl });
        });
    }
}

export function destroy() {
    try { ac?.abort(); } catch {}
    ac = null;

    try { observer?.disconnect?.(); } catch {}
    observer = null;

    safeTinyRemove('#content_editor');
}
