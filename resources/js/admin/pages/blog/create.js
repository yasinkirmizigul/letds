let ac = null;
let observer = null;
let lastObjectUrl = null;

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

function setupFeaturedPreview(root, signal) {
    const input = root.querySelector('#featured_image');
    const img = root.querySelector('#featured_preview');
    const ph = root.querySelector('#featured_placeholder');
    if (!input || !img || !ph) return;

    input.addEventListener('change', () => {
        const file = input.files && input.files[0] ? input.files[0] : null;

        if (lastObjectUrl) {
            try { URL.revokeObjectURL(lastObjectUrl); } catch {}
            lastObjectUrl = null;
        }

        if (!file) {
            img.src = '';
            img.classList.add('hidden');
            ph.classList.remove('hidden');
            return;
        }

        lastObjectUrl = URL.createObjectURL(file);
        img.src = lastObjectUrl;
        img.classList.remove('hidden');
        ph.classList.add('hidden');
    }, { signal });
}

function setupSlugAuto(root, signal) {
    const titleInput = root.querySelector('input[name="title"]');
    const slugInput = root.querySelector('#slug');
    const preview = root.querySelector('#url_slug_preview');
    if (!titleInput || !slugInput) return;

    let slugLocked = slugInput.value.trim().length > 0;

    const setSlug = (val) => {
        slugInput.value = val;
        if (preview) preview.textContent = val || '';
    };

    slugInput.addEventListener('input', () => {
        const v = slugInput.value.trim();
        slugLocked = v.length > 0;
        if (preview) preview.textContent = v;
    }, { signal });

    titleInput.addEventListener('input', () => {
        if (slugLocked) return;
        setSlug(slugifyTR(titleInput.value));
    }, { signal });

    if (preview) preview.textContent = (slugInput.value || '').trim();
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

export default async function init({ root, dataset }) {
    ac = new AbortController();
    const { signal } = ac;

    setupFeaturedPreview(root, signal);
    setupSlugAuto(root, signal);

    const selector = '#content_editor';
    const uploadUrl = dataset.uploadUrl;
    const tinymceSrc = dataset.tinymceSrc;
    const baseUrl = dataset.tinymceBase;
    const langUrl = dataset.tinymceLangUrl;

    // TinyMCE load + init
    await loadScriptOnce(tinymceSrc);
    initTiny({ selector, uploadUrl, baseUrl, langUrl });

    // theme toggle -> reinit (only on change)
    observer = observeThemeChanges(() => {
        initTiny({ selector, uploadUrl, baseUrl, langUrl });
    });
}

export function destroy() {
    try { observer?.disconnect(); } catch {}
    observer = null;

    try { ac?.abort(); } catch {}
    ac = null;

    if (lastObjectUrl) {
        try { URL.revokeObjectURL(lastObjectUrl); } catch {}
        lastObjectUrl = null;
    }

    safeTinyRemove('#content_editor');
}
