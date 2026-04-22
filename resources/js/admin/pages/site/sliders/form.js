function syncPreview(root) {
    const image = root.querySelector('[data-slider-preview-image="true"]');
    const overlay = root.querySelector('[data-slider-preview-overlay="true"]');
    if (!image || !overlay) return;

    const cropX = root.querySelector('[name="crop_x"]')?.value || 50;
    const cropY = root.querySelector('[name="crop_y"]')?.value || 50;
    const cropZoom = root.querySelector('[name="crop_zoom"]')?.value || 1;
    const overlayStrength = root.querySelector('[name="overlay_strength"]')?.value || 40;
    const badge = root.querySelector('[name="badge"]')?.value?.trim() || 'Rozet Alani';
    const title = root.querySelector('[name="title"]')?.value?.trim() || 'Slider basligi burada gorunecek';
    const subtitle = root.querySelector('[name="subtitle"]')?.value?.trim() || 'Alt baslik ve destek metni burada yer alir.';

    image.style.objectPosition = `${cropX}% ${cropY}%`;
    image.style.transform = `scale(${cropZoom})`;
    overlay.style.backgroundColor = `rgba(15, 23, 42, ${Number(overlayStrength) / 100})`;
    root.querySelector('[data-slider-preview-badge="true"]')?.replaceChildren(document.createTextNode(badge));
    root.querySelector('[data-slider-preview-title="true"]')?.replaceChildren(document.createTextNode(title));
    root.querySelector('[data-slider-preview-subtitle="true"]')?.replaceChildren(document.createTextNode(subtitle));
}

export default function initSliderForm(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;

    root.addEventListener('featured-image:change', () => {
        const preview = root.querySelector('[data-featured-preview]');
        const image = root.querySelector('[data-slider-preview-image="true"]');
        if (preview && image) {
            image.setAttribute('src', preview.getAttribute('src') || '');
        }
    }, { signal });

    root.querySelectorAll('[data-slider-preview-input]').forEach((input) => {
        input.addEventListener('input', () => syncPreview(root), { signal });
        input.addEventListener('change', () => syncPreview(root), { signal });
    });

    ['[name="badge"]', '[name="title"]', '[name="subtitle"]'].forEach((selector) => {
        root.querySelector(selector)?.addEventListener('input', () => syncPreview(root), { signal });
    });

    syncPreview(root);
}
