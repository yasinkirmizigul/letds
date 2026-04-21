const TURKISH_CHAR_MAP = {
    '\u00c7': 'c',
    '\u00d6': 'o',
    '\u00dc': 'u',
    '\u011e': 'g',
    '\u0130': 'i',
    '\u015e': 's',
    '\u00e7': 'c',
    '\u00f6': 'o',
    '\u00fc': 'u',
    '\u011f': 'g',
    '\u0131': 'i',
    '\u015f': 's',
};

export function slugifyTR(str) {
    return String(str || '')
        .trim()
        .replace(/[\u00c7\u00d6\u00dc\u011e\u0130\u015e\u00e7\u00f6\u00fc\u011f\u0131\u015f]/g, (char) => TURKISH_CHAR_MAP[char] || char)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
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
        autoSelector = '#slug_auto',
        regenSelector = '#slug_regen',
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
        locked = (slug.value || '').trim().length > 0;
    };

    setPreview();

    if (generateOnInit) {
        const canAuto = auto ? auto.checked : true;
        if (canAuto && !(slug.value || '').trim()) generate();
    }

    slug.addEventListener('input', () => {
        const value = (slug.value || '').trim();
        locked = value.length > 0;
        setPreview();
    }, signal ? { signal } : undefined);

    source?.addEventListener('input', () => {
        if (auto && !auto.checked) return;
        if (!auto && locked) return;
        generate();
    }, signal ? { signal } : undefined);

    auto?.addEventListener('change', () => {
        if (!auto.checked) return;
        generate();
    }, signal ? { signal } : undefined);

    regen?.addEventListener('click', (event) => {
        event.preventDefault();
        generate();
    }, signal ? { signal } : undefined);
}
