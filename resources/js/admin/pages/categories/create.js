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

export default function init({ root }) {
    const nameEl = root.querySelector('#cat_name');
    const slugEl = root.querySelector('#cat_slug');
    const autoEl = root.querySelector('#slug_auto');
    const regenEl = root.querySelector('#slug_regen');
    const previewEl = root.querySelector('#slug_preview');

    if (!slugEl) return;

    const syncPreview = () => {
        if (!previewEl) return;
        previewEl.textContent = (slugEl.value || '').trim();
    };

    const setSlugFromName = () => {
        if (!nameEl) return;
        slugEl.value = slugifyTR(nameEl.value);
        syncPreview();
    };

    // ilk render
    syncPreview();

    // auto: name -> slug
    nameEl?.addEventListener('input', () => {
        if (!autoEl?.checked) return;
        setSlugFromName();
    });

    // manuel slug yazınca preview güncelle
    slugEl.addEventListener('input', syncPreview);

    // regen
    regenEl?.addEventListener('click', setSlugFromName);
}
