function paintStars(group, selected) {
    group.querySelectorAll('[data-star-value]').forEach((star) => {
        const active = Number(star.dataset.starValue || 0) <= selected;
        star.classList.toggle('border-warning', active);
        star.classList.toggle('bg-warning/10', active);
        star.classList.toggle('text-warning', active);
        star.classList.toggle('border-border', !active);
        star.classList.toggle('text-muted-foreground', !active);
    });

    const output = group.querySelector('[data-star-output]');
    if (output) output.textContent = selected ? `${selected} / 5` : 'Puanınızı seçin';
}

export function initReviewStars(root = document) {
    root.querySelectorAll('[data-star-rating]').forEach((group) => {
        const checked = group.querySelector('input[type="radio"]:checked');
        paintStars(group, Number(checked?.value || 0));

        group.querySelectorAll('input[type="radio"]').forEach((input) => {
            input.addEventListener('change', () => paintStars(group, Number(input.value || 0)));
        });
    });
}
