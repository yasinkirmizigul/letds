

let ac = null;

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
        .replace(/^-+|-+$/g, '');
}

function debounce(fn, wait = 300) {
    let t = null;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), wait);
    };
}

async function checkSlug({checkUrl, slug, ignoreId, hintEl, signal}) {
    if (!checkUrl || !hintEl) return;

    const qs = new URLSearchParams();
    qs.set('slug', slug || '');
    if (ignoreId) qs.set('ignore', String(ignoreId));

    hintEl.textContent = slug ? 'Kontrol ediliyor...' : '';
    hintEl.classList.remove('text-danger', 'text-success');
    hintEl.classList.add('text-muted-foreground');

    try {
        const res = await fetch(`${checkUrl}?${qs.toString()}`, {
            method: 'GET',
            headers: {'Accept': 'application/json'},
            signal,
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data = await res.json();
        const msg = data?.message ?? '';

        hintEl.textContent = msg;
        hintEl.classList.remove('text-muted-foreground');

        if (data?.ok && data?.available) {
            hintEl.classList.add('text-success');
            hintEl.classList.remove('text-danger');
        } else {
            hintEl.classList.add('text-danger');
            hintEl.classList.remove('text-success');
        }
    } catch (e) {
        if (signal?.aborted) return;
        hintEl.textContent = 'Slug kontrolü başarısız.';
        hintEl.classList.remove('text-muted-foreground', 'text-success');
        hintEl.classList.add('text-danger');
    }
}

/**
 * initSlugManager
 * - duplicate id / global DOM yok: root altında query yapar
 * - "auto" açıkken title => slug
 * - slug elle yazılırsa auto kilitlenir (kategori mantığıyla aynı)
 * - slugify butonu elle üretir
 * - preview textContent günceller
 * - (opsiyonel) checkUrl varsa slug uygunluk kontrolü yapar
 */
export function initSlugManager({
                                    root,
                                    titleSelector = 'input[name="title"]',
                                    slugSelector = '#slug',
                                    previewSelector = '#url_slug_preview',
                                    autoSelector = '#slug_auto',
                                    slugifyBtnSelector = '#slugifyBtn',
                                    hintSelector = '#slugCheckHint',
                                    checkUrl = null,
                                    ignoreId = null,
                                } = {}) {
    if (!root) return;

    ac = new AbortController();
    const {signal} = ac;

    const titleEl = root.querySelector(titleSelector);
    const slugEl = root.querySelector(slugSelector);
    const previewEl = root.querySelector(previewSelector);
    const autoEl = root.querySelector(autoSelector);
    const slugifyBtn = root.querySelector(slugifyBtnSelector);
    const hintEl = root.querySelector(hintSelector);

    if (!slugEl) return;

    const setPreview = () => {
        if (!previewEl) return;
        previewEl.textContent = (slugEl.value || '').trim();
    };

    let locked = (slugEl.value || '').trim().length > 0;

    const doCheck = debounce(() => {
        const v = (slugEl.value || '').trim();
        if (!v) {
            if (hintEl) hintEl.textContent = '';
            return;
        }
        checkSlug({checkUrl, slug: v, ignoreId, hintEl, signal});
    }, 350);

    // init preview + initial check
    setPreview();
    if (checkUrl && (slugEl.value || '').trim()) doCheck();

    // slug typing => lock + preview + check
    slugEl.addEventListener('input', () => {
        const v = (slugEl.value || '').trim();
        locked = v.length > 0;
        setPreview();
        if (checkUrl) doCheck();
    }, {signal});

    // title typing => if auto enabled + not locked => update slug
    if (titleEl) {
        titleEl.addEventListener('input', () => {
            const autoOn = autoEl ? !!autoEl.checked : true;
            if (!autoOn) return;
            if (locked) return;

            slugEl.value = slugifyTR(titleEl.value);
            setPreview();
            if (checkUrl) doCheck();
        }, {signal});
    }

    // auto toggle off => stop generating, on => generate immediately (if not locked)
    if (autoEl) {
        autoEl.addEventListener('change', () => {
            if (!autoEl.checked) return;
            if (!titleEl) return;
            if (locked) return;

            slugEl.value = slugifyTR(titleEl.value);
            setPreview();
            if (checkUrl) doCheck();
        }, {signal});
    }

    // slugify button
    if (slugifyBtn) {
        slugifyBtn.addEventListener('click', () => {
            if (!titleEl) return;

            slugEl.value = slugifyTR(titleEl.value);
            locked = true; // butona basınca artık “elle set edilmiş” say
            setPreview();
            if (checkUrl) doCheck();
        }, {signal});
    }
}

export function destroySlugManager() {
    try {
        ac?.abort();
    } catch {
    }
    ac = null;
}
