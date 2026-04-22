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

function setHintState(element, type, text) {
    if (!element) return;

    element.textContent = text || '';
    element.classList.remove('text-muted-foreground', 'text-success', 'text-danger', 'text-warning');

    if (!text) {
        element.classList.add('text-muted-foreground');
        return;
    }

    element.classList.add(
        type === 'success'
            ? 'text-success'
            : type === 'danger'
                ? 'text-danger'
                : type === 'warning'
                    ? 'text-warning'
                    : 'text-muted-foreground'
    );
}

function parseStatusOptions(root) {
    try {
        return JSON.parse(root.dataset.statusOptions || '{}');
    } catch {
        return {};
    }
}

export function initStatusFeaturedUI(root, signal) {
    const statusOptions = parseStatusOptions(root);
    const statusSelect = root.querySelector('#product_status');
    const statusBadge = root.querySelector('#product_status_badge');
    const statusHint = root.querySelector('[data-product-status-hint]');
    const featuredToggle = root.querySelector('#product_is_featured');
    const featuredBadge = root.querySelector('#product_featured_badge');
    const activeToggle = root.querySelector('#product_is_active');
    const activeBadge = root.querySelector('#product_active_badge');

    const pulse = (element) => {
        if (!element) return;
        element.classList.add('animate-pulse');
        window.setTimeout(() => element.classList.remove('animate-pulse'), 420);
    };

    const syncStatus = () => {
        const key = statusSelect?.value || '';
        const option = statusOptions[key] || null;

        if (statusBadge) {
            [...statusBadge.classList].forEach((className) => {
                if (className.startsWith('kt-badge')) {
                    statusBadge.classList.remove(className);
                }
            });

            (option?.badge || 'kt-badge kt-badge-sm kt-badge-light')
                .split(/\s+/)
                .forEach((className) => className && statusBadge.classList.add(className));

            statusBadge.textContent = option?.label || key || 'Status';
        }

        if (statusHint) {
            statusHint.textContent = key === 'active'
                ? 'Bu durum urunu operasyonel olarak canli kabul eder.'
                : key === 'appointment_pending'
                    ? 'Randevu veya teklif oncesi bekleme asamasini temsil eder.'
                    : key === 'archived'
                        ? 'Arsivlenen urunler listede kalir ama aktif akis disindadir.'
                        : 'Taslak urunler editoryal hazirlik asamasinda tutulur.';
        }
    };

    const syncFeatured = () => {
        const isOn = !!featuredToggle?.checked;

        if (!featuredBadge) return;

        featuredBadge.classList.remove('kt-badge-light-success', 'kt-badge-light', 'text-muted-foreground');

        if (isOn) {
            featuredBadge.classList.add('kt-badge-light-success');
            featuredBadge.textContent = 'Anasayfada';
            return;
        }

        featuredBadge.classList.add('kt-badge-light', 'text-muted-foreground');
        featuredBadge.textContent = 'Kapali';
    };

    const syncActive = () => {
        const isOn = !!activeToggle?.checked;

        if (!activeBadge) return;

        activeBadge.classList.remove('kt-badge-light-success', 'kt-badge-light', 'text-muted-foreground');

        if (isOn) {
            activeBadge.classList.add('kt-badge-light-success');
            activeBadge.textContent = 'Aktif';
            return;
        }

        activeBadge.classList.add('kt-badge-light', 'text-muted-foreground');
        activeBadge.textContent = 'Pasif';
    };

    syncStatus();
    syncFeatured();
    syncActive();

    statusSelect?.addEventListener('change', () => {
        pulse(statusBadge);
        syncStatus();
    }, { signal });

    featuredToggle?.addEventListener('change', () => {
        pulse(featuredBadge);
        syncFeatured();
    }, { signal });

    activeToggle?.addEventListener('change', () => {
        pulse(activeBadge);
        syncActive();
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
        const slug = trimText(slugInput.value);

        if (!slug) {
            setHintState(hintEl, 'muted', 'Slug girildiginde uygunluk kontrolu yapilir.');
            return;
        }

        setHintState(hintEl, 'muted', 'Slug kontrol ediliyor...');

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
                setHintState(hintEl, 'danger', data?.message || 'Slug kontrol edilemedi.');
                return;
            }

            if (data.normalized && data.normalized !== trimText(slugInput.value)) {
                slugInput.value = data.normalized;
                root.querySelector('#url_slug_preview')?.replaceChildren(document.createTextNode(data.normalized));
            }

            if (data.available) {
                setHintState(hintEl, 'success', data.message || 'Slug uygun.');
                return;
            }

            const suggested = data.suggested ? ` Oneri: ${data.suggested}` : '';
            setHintState(hintEl, 'warning', `${data.message || 'Slug kullanimda.'}${suggested}`);
        } catch (error) {
            setHintState(hintEl, 'danger', error?.message || 'Slug kontrolu basarisiz oldu.');
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
    return trimText(text).length;
}

function formatPrice(value, currency = 'TRY') {
    if (value === null || value === undefined || value === '') {
        return 'Fiyat bilgisi yok';
    }

    const number = Number(value);
    if (!Number.isFinite(number)) {
        return 'Fiyat bilgisi yok';
    }

    return `${number.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency || 'TRY'}`;
}

export function initSeoPanel(root, signal, getContent) {
    const titleInput = root.querySelector('#title');
    const slugInput = root.querySelector('#slug');
    const priceInput = root.querySelector('input[name="price"]');
    const currencyInput = root.querySelector('input[name="currency"]');
    const metaTitleInput = root.querySelector('input[name="meta_title"]');
    const metaDescriptionInput = root.querySelector('textarea[name="meta_description"]');
    const stockInput = root.querySelector('input[name="stock"]');
    const featuredPreview = root.querySelector('[data-featured-preview]');

    const titleCount = root.querySelector('[data-product-title-count]');
    const metaTitleCount = root.querySelector('[data-product-meta-title-count]');
    const metaDescriptionCount = root.querySelector('[data-product-meta-description-count]');
    const wordCount = root.querySelector('[data-product-word-count]');
    const readTime = root.querySelector('[data-product-read-time]');
    const seoScore = root.querySelector('[data-product-seo-score]');
    const seoSummary = root.querySelector('[data-product-seo-summary]');
    const stockBadge = root.querySelector('[data-product-stock-badge]');
    const pricePreview = root.querySelector('[data-product-price-preview]');
    const previewTitle = root.querySelector('[data-product-seo-preview-title]');
    const previewDescription = root.querySelector('[data-product-seo-preview-description]');
    const previewSlug = root.querySelector('[data-product-seo-preview-slug]');

    const sync = () => {
        const titleLength = clampTextLength(titleInput?.value);
        const metaTitleLength = clampTextLength(metaTitleInput?.value);
        const metaDescriptionLength = clampTextLength(metaDescriptionInput?.value);
        const currentSlug = trimText(slugInput?.value);
        const content = typeof getContent === 'function' ? getContent() : '';
        const words = countWords(content);
        const minutes = words > 0 ? Math.max(1, Math.ceil(words / 220)) : 0;
        const hasFeaturedImage = !!trimText(featuredPreview?.getAttribute('src'));
        const currentStock = Number(stockInput?.value || 0);
        const currentCurrency = trimText(currencyInput?.value) || 'TRY';
        const resolvedPreviewTitle =
            trimText(metaTitleInput?.value)
            || trimText(titleInput?.value)
            || 'Meta baslik burada gorunecek';
        const resolvedPreviewDescription =
            trimText(metaDescriptionInput?.value)
            || limitText(content, 155)
            || 'Meta aciklama burada gorunecek.';

        if (titleCount) titleCount.textContent = `${titleLength}/255`;
        if (metaTitleCount) metaTitleCount.textContent = `${metaTitleLength}/60 onerisi`;
        if (metaDescriptionCount) metaDescriptionCount.textContent = `${metaDescriptionLength}/160 onerisi`;
        if (wordCount) wordCount.textContent = `${words} kelime`;
        if (readTime) readTime.textContent = minutes > 0 ? `${minutes} dk` : '0 dk';
        if (pricePreview) pricePreview.textContent = formatPrice(priceInput?.value, currentCurrency);
        if (previewTitle) previewTitle.textContent = resolvedPreviewTitle;
        if (previewDescription) previewDescription.textContent = resolvedPreviewDescription;
        if (previewSlug) previewSlug.textContent = currentSlug || 'ornek-urun';

        syncRecommendedCount(metaTitleCount, metaTitleLength, 30, 60);
        syncRecommendedCount(metaDescriptionCount, metaDescriptionLength, 100, 160);

        if (stockBadge) {
            stockBadge.classList.remove('kt-badge-light-success', 'kt-badge-light-warning', 'kt-badge-light-danger');

            if (!Number.isFinite(currentStock) || currentStock <= 0) {
                stockBadge.classList.add('kt-badge-light-danger');
                stockBadge.textContent = 'Stok yok';
            } else if (currentStock <= 5) {
                stockBadge.classList.add('kt-badge-light-warning');
                stockBadge.textContent = `Dusuk stok: ${currentStock}`;
            } else {
                stockBadge.classList.add('kt-badge-light-success');
                stockBadge.textContent = `Stok iyi: ${currentStock}`;
            }
        }

        const checks = [
            titleLength > 0,
            words > 0,
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
            if (score >= 80) seoSummary.textContent = 'SEO hazirligi guclu gorunuyor.';
            else if (score >= 50) seoSummary.textContent = 'Temel alanlar iyi, birkac iyilestirme daha yapilabilir.';
            else seoSummary.textContent = 'Meta alanlari ve one cikan gorsel tarafini guclendirmek faydali olur.';
        }
    };

    [titleInput, slugInput, priceInput, currencyInput, metaTitleInput, metaDescriptionInput, stockInput].forEach((input) => {
        input?.addEventListener('input', sync, { signal });
    });

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
        height: 460,
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
