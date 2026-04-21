import { request } from '@/core/http';
import initSlugManager from '@/core/slug-manager';

function getTheme() {
    const root = document.documentElement;
    const body = document.body;
    const isDark = root.classList.contains('dark') || body.classList.contains('dark');

    return isDark ? 'dark' : 'light';
}

function debounce(fn, delay = 350) {
    let timer = null;

    return (...args) => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => fn(...args), delay);
    };
}

function trimText(value) {
    return String(value || '').trim();
}

function limitText(value, maxLength) {
    const text = trimText(value);
    if (!maxLength || text.length <= maxLength) {
        return text;
    }

    return `${text.slice(0, Math.max(0, maxLength - 1)).trimEnd()}...`;
}

function setToneClass(element, stateMap, activeKey) {
    if (!element) return;

    Object.values(stateMap).forEach((classNames) => {
        classNames.forEach((className) => element.classList.remove(className));
    });

    (stateMap[activeKey] || []).forEach((className) => element.classList.add(className));
}

function syncRecommendedCount(element, length, min, max) {
    if (!element) return;

    const states = {
        muted: ['text-muted-foreground'],
        success: ['text-success'],
        warning: ['text-warning'],
    };

    if (length === 0) {
        setToneClass(element, states, 'muted');
        return;
    }

    setToneClass(element, states, length >= min && length <= max ? 'success' : 'warning');
}

function setSlugHint(hintEl, type, text) {
    if (!hintEl) return;

    hintEl.textContent = text || '';
    hintEl.classList.remove('text-muted-foreground', 'text-success', 'text-danger', 'text-warning');

    if (!text) {
        hintEl.classList.add('text-muted-foreground');
        return;
    }

    hintEl.classList.add(
        type === 'success'
            ? 'text-success'
            : type === 'danger'
                ? 'text-danger'
                : type === 'warning'
                    ? 'text-warning'
                    : 'text-muted-foreground'
    );
}

export function initStatusFeaturedUI(root, signal) {
    const publishToggle = root.querySelector('#blog_is_published');
    const publishBadge = root.querySelector('#blog_publish_badge');

    const featuredToggle = root.querySelector('#blog_is_featured');
    const featuredBadge = root.querySelector('#blog_featured_badge');

    const pulse = (el) => {
        if (!el) return;
        el.classList.add('animate-pulse');
        window.setTimeout(() => el.classList.remove('animate-pulse'), 420);
    };

    const setBadge = (badge, isOn, onText, offText) => {
        if (!badge) return;

        badge.classList.remove('kt-badge-light-success', 'kt-badge-light', 'text-muted-foreground');

        if (isOn) {
            badge.classList.add('kt-badge-light-success');
            badge.textContent = onText;
            return;
        }

        badge.classList.add('kt-badge-light', 'text-muted-foreground');
        badge.textContent = offText;
    };

    const sync = () => {
        setBadge(publishBadge, !!publishToggle?.checked, 'Yayında', 'Taslak');
        setBadge(featuredBadge, !!featuredToggle?.checked, 'Anasayfada', 'Kapalı');
    };

    sync();

    publishToggle?.addEventListener('change', () => {
        pulse(publishBadge);
        sync();
    }, { signal });

    featuredToggle?.addEventListener('change', () => {
        pulse(featuredBadge);
        sync();
    }, { signal });
}

export function initSlugTools(root, signal) {
    initSlugManager(root, {
        sourceSelector: '#title',
        slugSelector: '#slug',
        previewSelector: '#url_slug_preview',
        autoSelector: '#slug_auto',
        regenSelector: '#slug_regen',
        generateOnInit: true,
    }, signal);

    const slugInput = root.querySelector('#slug');
    const hintEl = root.querySelector('#slugCheckHint');
    const checkUrl = root.dataset.slugCheckUrl;
    const ignoreId = root.dataset.slugIgnoreId || '';

    if (!slugInput || !hintEl || !checkUrl) return;

    const runCheck = debounce(async () => {
        const slug = String(slugInput.value || '').trim();

        if (!slug) {
            setSlugHint(hintEl, 'muted', 'Slug girildiğinde uygunluk kontrolü yapılır.');
            return;
        }

        setSlugHint(hintEl, 'muted', 'Slug kontrol ediliyor...');

        try {
            const query = new URLSearchParams({
                slug,
                ignore: ignoreId,
            });

            const data = await request(`${checkUrl}?${query.toString()}`, {
                method: 'GET',
                ignoreGlobalError: true,
            });

            if (!data?.ok) {
                setSlugHint(hintEl, 'danger', data?.message || 'Slug kontrol edilemedi.');
                return;
            }

            if (data.normalized && data.normalized !== slugInput.value.trim()) {
                slugInput.value = data.normalized;
                root.querySelector('#url_slug_preview')?.replaceChildren(document.createTextNode(data.normalized));
            }

            if (data.available) {
                setSlugHint(hintEl, 'success', data.message || 'Slug uygun.');
                return;
            }

            const suggested = data.suggested ? ` Öneri: ${data.suggested}` : '';
            setSlugHint(hintEl, 'warning', `${data.message || 'Slug kullanımda.'}${suggested}`);
        } catch (error) {
            setSlugHint(hintEl, 'danger', error?.message || 'Slug kontrolü başarısız oldu.');
        }
    }, 320);

    slugInput.addEventListener('input', runCheck, { signal });
    slugInput.addEventListener('blur', runCheck, { signal });

    runCheck();
}

function countWords(text) {
    const normalized = String(text || '').replace(/<[^>]*>/g, ' ');
    const matches = normalized.match(/[\p{L}\p{N}]+/gu);
    return matches ? matches.length : 0;
}

function clampTextLength(text) {
    return String(text || '').trim().length;
}

export function initSeoPanel(root, signal, getContent) {
    const titleInput = root.querySelector('#title');
    const excerptInput = root.querySelector('textarea[name="excerpt"]');
    const metaTitleInput = root.querySelector('input[name="meta_title"]');
    const metaDescriptionInput = root.querySelector('input[name="meta_description"]');
    const slugInput = root.querySelector('#slug');
    const featuredPreview = root.querySelector('[data-featured-preview]');

    const titleCount = root.querySelector('[data-blog-title-count]');
    const excerptCount = root.querySelector('[data-blog-excerpt-count]');
    const metaTitleCount = root.querySelector('[data-blog-meta-title-count]');
    const metaDescriptionCount = root.querySelector('[data-blog-meta-description-count]');
    const wordCount = root.querySelector('[data-blog-word-count]');
    const readTime = root.querySelector('[data-blog-read-time]');
    const seoScore = root.querySelector('[data-blog-seo-score]');
    const seoSummary = root.querySelector('[data-blog-seo-summary]');
    const previewTitle = root.querySelector('[data-blog-seo-preview-title]');
    const previewDescription = root.querySelector('[data-blog-seo-preview-description]');
    const previewSlug = root.querySelector('[data-blog-seo-preview-slug]');

    const sync = () => {
        const titleLength = clampTextLength(titleInput?.value);
        const excerptLength = clampTextLength(excerptInput?.value);
        const metaTitleLength = clampTextLength(metaTitleInput?.value);
        const metaDescriptionLength = clampTextLength(metaDescriptionInput?.value);
        const currentSlug = trimText(slugInput?.value);
        const content = typeof getContent === 'function' ? getContent() : '';
        const words = countWords(content);
        const minutes = words > 0 ? Math.max(1, Math.ceil(words / 200)) : 0;
        const hasFeaturedImage = !!String(featuredPreview?.getAttribute('src') || '').trim();
        const resolvedPreviewTitle =
            trimText(metaTitleInput?.value)
            || trimText(titleInput?.value)
            || 'Meta baslik burada gorunecek';
        const resolvedPreviewDescription =
            trimText(metaDescriptionInput?.value)
            || limitText(excerptInput?.value, 155)
            || 'Meta aciklama veya ozet burada gorunecek.';

        if (titleCount) titleCount.textContent = `${titleLength}/255`;
        if (excerptCount) excerptCount.textContent = `${excerptLength} karakter`;
        if (metaTitleCount) metaTitleCount.textContent = `${metaTitleLength}/60 öneri`;
        if (metaDescriptionCount) metaDescriptionCount.textContent = `${metaDescriptionLength}/160 öneri`;
        if (wordCount) wordCount.textContent = `${words} kelime`;
        if (readTime) readTime.textContent = minutes > 0 ? `${minutes} dk okuma` : '0 dk okuma';

        if (previewTitle) previewTitle.textContent = resolvedPreviewTitle;
        if (previewDescription) previewDescription.textContent = resolvedPreviewDescription;
        if (previewSlug) previewSlug.textContent = currentSlug || 'ornek-blog-yazisi';

        syncRecommendedCount(metaTitleCount, metaTitleLength, 30, 60);
        syncRecommendedCount(metaDescriptionCount, metaDescriptionLength, 100, 160);

        const checks = [
            titleLength > 0,
            excerptLength > 0,
            metaTitleLength >= 30 && metaTitleLength <= 60,
            metaDescriptionLength >= 100 && metaDescriptionLength <= 160,
            hasFeaturedImage,
        ];

        const completed = checks.filter(Boolean).length;
        const score = Math.round((completed / checks.length) * 100);

        if (seoScore) {
            seoScore.textContent = `%${score}`;
            seoScore.classList.remove('text-success', 'text-warning', 'text-danger');
            seoScore.classList.add(score >= 80 ? 'text-success' : score >= 50 ? 'text-warning' : 'text-danger');
        }

        if (seoSummary) {
            if (score >= 80) seoSummary.textContent = 'SEO hazırlığı güçlü görünüyor.';
            else if (score >= 50) seoSummary.textContent = 'SEO tarafı iyi gidiyor, birkaç alan daha güçlendirilebilir.';
            else seoSummary.textContent = 'SEO görünürlüğü için özet, meta alanları ve görsel tarafı güçlendirilmeli.';
        }
    };

    [titleInput, excerptInput, metaTitleInput, metaDescriptionInput, slugInput].forEach((input) => {
        input?.addEventListener('input', sync, { signal });
    });

    root.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (target.matches('[data-featured-input]') || target.matches('[data-featured-media-id]')) {
            sync();
        }
    }, { signal });

    root.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (target.closest('[data-featured-clear]')) {
            window.setTimeout(sync, 10);
        }
    }, { signal });

    root.addEventListener('featured-image:change', sync, { signal });

    sync();

    return { sync };
}

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function loadScriptOnce(src) {
    if (!src) return Promise.reject(new Error('TinyMCE kaynagi eksik.'));
    if (document.querySelector(`script[data-once="${src}"]`)) return Promise.resolve();

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.dataset.once = src;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Yuklenemedi: ${src}`));
        document.head.appendChild(script);
    });
}

function safeTinyRemove(selector) {
    try {
        window.tinymce?.remove?.(selector);
    } catch {}
}

function observeThemeChanges(onChange) {
    let current = getTheme();

    const observer = new MutationObserver(() => {
        const next = getTheme();
        if (next === current) return;
        current = next;
        onChange(next);
    });

    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });

    return observer;
}

function initTiny({ selector, uploadUrl, baseUrl, langUrl, onContentChange }) {
    if (!window.tinymce) return;

    safeTinyRemove(selector);

    const theme = getTheme();

    window.tinymce.init({
        selector,
        height: 480,
        license_key: 'gpl',
        base_url: baseUrl,
        suffix: '.min',
        language: 'tr',
        language_url: langUrl,
        skin: theme === 'dark' ? 'oxide-dark' : 'oxide',
        content_css: theme === 'dark' ? 'dark' : 'default',
        plugins: 'lists link image code table fullscreen autoresize',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link image table | alignleft aligncenter alignright | code fullscreen',
        menubar: false,
        branding: false,
        promotion: false,
        automatic_uploads: true,
        paste_data_images: true,
        autoresize_bottom_margin: 24,
        setup: (editor) => {
            const sync = () => {
                editor.save();
                onContentChange?.();
            };

            editor.on('init', sync);
            editor.on('change input keyup undo redo setcontent', sync);
        },
        images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', uploadUrl);
            xhr.withCredentials = true;
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());

            xhr.upload.onprogress = (event) => {
                if (event.lengthComputable) {
                    progress((event.loaded / event.total) * 100);
                }
            };

            xhr.onload = () => {
                if (xhr.status < 200 || xhr.status >= 300) {
                    reject(`Upload failed: ${xhr.status}`);
                    return;
                }

                let json = null;
                try {
                    json = JSON.parse(xhr.responseText);
                } catch {
                    reject('Invalid JSON');
                    return;
                }

                if (!json || typeof json.location !== 'string') {
                    reject('No location returned');
                    return;
                }

                resolve(json.location);
            };

            xhr.onerror = () => reject('Network error');

            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            xhr.send(formData);
        }),
    });
}

export async function initTinyEditor(ctx, onContentChange) {
    const root = ctx.root;
    const dataset = root.dataset;

    const tinymceSrc = dataset.tinymceSrc;
    const tinymceBase = dataset.tinymceBase;
    const tinymceLangUrl = dataset.tinymceLangUrl;
    const uploadUrl = dataset.uploadUrl;

    if (!tinymceSrc || !tinymceBase || !tinymceLangUrl || !uploadUrl) {
        return;
    }

    await loadScriptOnce(tinymceSrc);

    const boot = () => initTiny({
        selector: '#content_editor',
        uploadUrl,
        baseUrl: tinymceBase,
        langUrl: tinymceLangUrl,
        onContentChange,
    });

    boot();

    const observer = observeThemeChanges(boot);

    ctx.cleanup(() => {
        try { observer.disconnect(); } catch {}
        safeTinyRemove('#content_editor');
    });
}

export function setFormButtonsDisabled(root, formId, disabled = true) {
    const selector = [
        `button[form="${formId}"]`,
        `#${formId} button`,
    ].join(', ');

    root.querySelectorAll(selector).forEach((button) => {
        if (!(button instanceof HTMLButtonElement)) return;
        button.disabled = disabled;
        button.classList.toggle('opacity-60', disabled);
        button.classList.toggle('pointer-events-none', disabled);
    });
}

export function lockSubmitButtons(root, formId) {
    setFormButtonsDisabled(root, formId, true);
}
