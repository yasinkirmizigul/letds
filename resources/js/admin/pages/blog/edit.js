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

function openModal(root, selector) {
    const modal = root.querySelector(selector);
    if (modal) modal.classList.remove('hidden');
}

function closeModal(modal) {
    if (modal) modal.classList.add('hidden');
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
            // edit sayfasında mevcut görsel olabilir → sadece “placeholder”a dönme
            // eğer img.src boşsa placeholder göster
            if (!img.getAttribute('src')) {
                img.src = '';
                img.classList.add('hidden');
                ph.classList.remove('hidden');
            }
            return;
        }

        lastObjectUrl = URL.createObjectURL(file);
        img.src = lastObjectUrl;
        img.classList.remove('hidden');
        ph.classList.add('hidden');
    }, { signal });
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

    // başlangıç: toggle checked ama slug dolu → otomatik “override” etmeyeceğiz
    syncPreview();
    setManualMode(false);

    // slug input: manuel yazarsa auto kapat
    slugInput.addEventListener('input', () => {
        const v = slugInput.value.trim();
        if (toggle && toggle.checked && v.length > 0) {
            toggle.checked = false;
            setManualMode(true);
        }
        syncPreview();
    }, { signal });

    // title input: sadece auto açıkken ve slug boşken üret
    if (titleInput && toggle) {
        titleInput.addEventListener('input', () => {
            if (!toggle.checked) return;
            if (slugInput.value.trim().length > 0) return; // doluyu ezme
            applyAutoSlug();
        }, { signal });

        toggle.addEventListener('change', () => {
            const isAuto = toggle.checked;
            setManualMode(!isAuto);
            if (isAuto) {
                // “auto”ya dönünce slug’u başlıktan yeniden üretelim
                slugInput.value = '';
                applyAutoSlug();
            }
        }, { signal });
    }

    if (regenBtn && toggle) {
        regenBtn.addEventListener('click', () => {
            toggle.checked = true;
            setManualMode(false);
            slugInput.value = '';
            applyAutoSlug();
        }, { signal });
    }
}

function lockSubmitButtons(root, formId) {
    root.querySelectorAll(`button[form="${formId}"][type="submit"]`).forEach((b) => {
        b.disabled = true;
        b.classList.add('opacity-60', 'pointer-events-none');
    });
}

export default async function init({ root, dataset }) {
    ac = new AbortController();
    const { signal } = ac;

    setupFeaturedPreview(root, signal);
    setupSlugUI(root, signal);

    // Modal delegation
    root.addEventListener('click', (e) => {
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
    }, { signal });

    // Prevent double submit
    const updateForm = root.querySelector('#blog-update-form');
    if (updateForm) {
        updateForm.addEventListener('submit', () => lockSubmitButtons(root, 'blog-update-form'), { signal, once: true });
    }

    const deleteForm = root.querySelector('#blog-delete-form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', () => lockSubmitButtons(root, 'blog-delete-form'), { signal, once: true });
    }

    // TinyMCE
    const selector = '#content_editor';
    const uploadUrl = dataset.uploadUrl;
    const tinymceSrc = dataset.tinymceSrc;
    const baseUrl = dataset.tinymceBase;
    const langUrl = dataset.tinymceLangUrl;

    await loadScriptOnce(tinymceSrc);
    initTiny({ selector, uploadUrl, baseUrl, langUrl });

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
