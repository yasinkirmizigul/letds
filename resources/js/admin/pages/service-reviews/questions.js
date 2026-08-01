function updateOptionsVisibility(form) {
    const type = form.querySelector('[data-review-question-type]')?.value;
    const options = form.querySelector('[data-review-question-options]');
    if (!options) return;

    options.classList.toggle('hidden', type !== 'single_choice');
}

export default async function init(ctx) {
    const root = ctx?.root || document;

    root.querySelectorAll('[data-review-question-form]').forEach((form) => {
        const type = form.querySelector('[data-review-question-type]');
        updateOptionsVisibility(form);
        type?.addEventListener('change', () => updateOptionsVisibility(form));
    });
}
