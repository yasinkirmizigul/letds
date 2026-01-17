// resources/js/core/slug-manager.js

export function slugifyTR(str) {
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

/**
 * Slug sync (Blog/Project/Category unified)
 *
 * - If slug has value (or user typed) -> locked (won't be overwritten)
 * - If slug cleared -> unlock
 * - If auto checkbox exists:
 *     - when auto OFF -> never overwrite slug on source typing
 *     - when auto ON -> overwrite (unless locked is desired; here auto implies overwrite)
 * - Regen always generates (ignores lock)
 */
export default function initSlugManager(root, opts = {}, signal) {
    if (!root) return;

    const {
        sourceSelector = '#title',
        slugSelector = '#slug',
        previewSelector = '#url_slug_preview',
        autoSelector = '#slug_auto',     // optional
        regenSelector = '#slug_regen',   // optional
        generateOnInit = false,
    } = opts;

    const source = root.querySelector(sourceSelector);
    const slug = root.querySelector(slugSelector);
    const preview = root.querySelector(previewSelector);
    const auto = root.querySelector(autoSelector);
    const regen = root.querySelector(regenSelector);

    if (!slug) return;

    let locked = (slug.value || '').trim().length > 0;

    const setPreview = () => {
        if (!preview) return;
        preview.textContent = (slug.value || '').trim();
    };

    const generate = () => {
        if (!source) return;
        slug.value = slugifyTR(source.value);
        setPreview();
        locked = (slug.value || '').trim().length > 0; // keep consistent
    };

    // init preview
    setPreview();

    // optional initial generate
    if (generateOnInit) {
        const canAuto = auto ? auto.checked : true;
        if (canAuto && !(slug.value || '').trim()) generate();
    }

    // slug manual typing -> lock/unlock
    slug.addEventListener('input', () => {
        const v = (slug.value || '').trim();
        locked = v.length > 0;
        setPreview();
    }, signal ? { signal } : undefined);

    // source typing
    source?.addEventListener('input', () => {
        // if auto exists and is OFF => do nothing
        if (auto && !auto.checked) return;

        // if auto doesn't exist: keep old behavior (don't overwrite if locked)
        if (!auto && locked) return;

        // if auto exists and is ON: generate even if locked (auto means follow source)
        generate();
    }, signal ? { signal } : undefined);

    // auto toggle: when ON -> generate immediately
    auto?.addEventListener('change', () => {
        if (!auto.checked) return;
        generate();
    }, signal ? { signal } : undefined);

    // regen button: always generate
    regen?.addEventListener('click', (e) => {
        e.preventDefault();
        generate();
    }, signal ? { signal } : undefined);
}
