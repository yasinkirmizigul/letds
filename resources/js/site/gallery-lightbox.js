const dialog = document.querySelector('[data-gallery-dialog]');
const items = [...document.querySelectorAll('[data-gallery-item]')];

if (dialog && items.length) {
    const image = dialog.querySelector('[data-gallery-dialog-image]');
    const caption = dialog.querySelector('[data-gallery-dialog-caption]');
    const counter = dialog.querySelector('[data-gallery-dialog-counter]');
    let activeIndex = 0;

    const render = (index) => {
        activeIndex = (index + items.length) % items.length;
        const item = items[activeIndex];
        image.src = item.dataset.gallerySrc;
        image.alt = item.dataset.galleryCaption ?? '';
        caption.textContent = item.dataset.galleryCaption ?? '';
        counter.textContent = `${activeIndex + 1} / ${items.length}`;
    };

    const open = (index) => {
        render(index);
        dialog.showModal();
    };

    items.forEach((item, index) => item.addEventListener('click', () => open(index)));
    dialog.querySelector('[data-gallery-close]')?.addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-gallery-prev]')?.addEventListener('click', () => render(activeIndex - 1));
    dialog.querySelector('[data-gallery-next]')?.addEventListener('click', () => render(activeIndex + 1));

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });

    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') render(activeIndex - 1);
        if (event.key === 'ArrowRight') render(activeIndex + 1);
    });
}
