const dialog = document.querySelector('[data-gallery-dialog]');
const items = [...document.querySelectorAll('[data-gallery-item]')];

if (dialog && items.length) {
    const image = dialog.querySelector('[data-gallery-dialog-image]');
    const caption = dialog.querySelector('[data-gallery-dialog-caption]');
    const counter = dialog.querySelector('[data-gallery-dialog-counter]');
    const closeButton = dialog.querySelector('[data-gallery-close]');
    const previousButton = dialog.querySelector('[data-gallery-prev]');
    const nextButton = dialog.querySelector('[data-gallery-next]');
    const viewport = dialog.querySelector('[data-gallery-viewport]');
    let activeIndex = 0;
    let pointerStartX = null;

    const setLoading = (loading) => {
        dialog.classList.toggle('is-loading', loading);
    };

    const preloadAdjacentImages = () => {
        if (items.length < 2) return;

        [-1, 1].forEach((offset) => {
            const index = (activeIndex + offset + items.length) % items.length;
            const source = items[index].dataset.gallerySrc;

            if (source) {
                const preload = new Image();
                preload.src = source;
            }
        });
    };

    const render = (index) => {
        activeIndex = (index + items.length) % items.length;
        const item = items[activeIndex];
        const source = item.dataset.gallerySrc ?? '';
        const description = item.dataset.galleryCaption ?? '';

        setLoading(true);
        image.src = source;
        image.alt = description;
        caption.textContent = description;
        counter.textContent = `${activeIndex + 1} / ${items.length}`;

        if (image.complete) setLoading(false);
        preloadAdjacentImages();
    };

    const open = (index) => {
        render(index);
        document.body.classList.add('site-lightbox-open');

        if (!dialog.open) dialog.showModal();
        closeButton?.focus({ preventScroll: true });
    };

    image.addEventListener('load', () => setLoading(false));
    image.addEventListener('error', () => setLoading(false));

    items.forEach((item, index) => item.addEventListener('click', () => open(index)));
    closeButton?.addEventListener('click', () => dialog.close());
    previousButton?.addEventListener('click', () => render(activeIndex - 1));
    nextButton?.addEventListener('click', () => render(activeIndex + 1));

    const hasMultipleItems = items.length > 1;
    if (previousButton) previousButton.hidden = !hasMultipleItems;
    if (nextButton) nextButton.hidden = !hasMultipleItems;

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });

    dialog.addEventListener('close', () => {
        document.body.classList.remove('site-lightbox-open');
        setLoading(false);
    });

    dialog.addEventListener('keydown', (event) => {
        if (!hasMultipleItems) return;

        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            render(activeIndex - 1);
        }

        if (event.key === 'ArrowRight') {
            event.preventDefault();
            render(activeIndex + 1);
        }

        if (event.key === 'Home') {
            event.preventDefault();
            render(0);
        }

        if (event.key === 'End') {
            event.preventDefault();
            render(items.length - 1);
        }
    });

    viewport?.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'touch' || event.pointerType === 'pen') {
            pointerStartX = event.clientX;
        }
    });

    viewport?.addEventListener('pointerup', (event) => {
        if (pointerStartX === null || !hasMultipleItems) return;

        const distance = event.clientX - pointerStartX;
        pointerStartX = null;

        if (Math.abs(distance) < 48) return;
        render(activeIndex + (distance < 0 ? 1 : -1));
    });

    viewport?.addEventListener('pointercancel', () => {
        pointerStartX = null;
    });
}
