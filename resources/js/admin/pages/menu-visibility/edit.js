function qsa(root, selector) {
    return Array.from((root || document).querySelectorAll(selector));
}

function normalize(value) {
    return String(value || '').toLocaleLowerCase('tr-TR').trim();
}

function syncCard(card) {
    const parent = card.querySelector('[data-menu-parent-toggle]');
    const status = card.querySelector('[data-menu-status]');
    const children = card.querySelector('[data-menu-children]');
    const note = card.querySelector('[data-menu-parent-note]');
    const isVisible = Boolean(parent?.checked);

    if (status) {
        status.textContent = isVisible ? 'Görünür' : 'Gizli';
        status.classList.toggle('kt-badge-light-success', isVisible);
        status.classList.toggle('kt-badge-light', !isVisible);
    }

    children?.classList.toggle('opacity-70', !isVisible);

    if (note) {
        note.hidden = isVisible;
    }
}

function effectiveVisibleCount(cards) {
    return cards.reduce((count, card) => {
        const parent = card.querySelector('[data-menu-parent-toggle]');
        if (!parent?.checked) return count;

        const visibleChildren = qsa(card, '[data-menu-child-toggle]:checked').length;
        return count + 1 + visibleChildren;
    }, 0);
}

export default function init(ctx) {
    const root = ctx?.root || document;
    const cards = qsa(root, '[data-menu-card]');
    const count = root.querySelector('[data-menu-visible-count]');
    const search = root.querySelector('[data-menu-search]');
    const empty = root.querySelector('[data-menu-empty]');
    const resetForm = root.querySelector('[data-menu-reset-form]');

    const sync = () => {
        cards.forEach(syncCard);

        if (count) {
            count.textContent = String(effectiveVisibleCount(cards));
        }
    };

    root.addEventListener('change', (event) => {
        if (!(event.target instanceof HTMLInputElement)) return;
        if (!event.target.matches('[data-menu-parent-toggle], [data-menu-child-toggle]')) return;
        sync();
    });

    root.addEventListener('click', (event) => {
        const action = event.target.closest('[data-menu-action]')?.dataset.menuAction;
        if (!action) return;

        const checked = action === 'show-all';
        qsa(root, '[data-menu-parent-toggle], [data-menu-child-toggle]').forEach((input) => {
            input.checked = checked;
        });
        sync();
    });

    search?.addEventListener('input', () => {
        const query = normalize(search.value);
        let visibleCards = 0;

        cards.forEach((card) => {
            const matches = query === '' || normalize(card.dataset.menuSearchText).includes(query);
            card.classList.toggle('hidden', !matches);
            if (matches) visibleCards += 1;
        });

        empty?.classList.toggle('hidden', visibleCards > 0);
    });

    resetForm?.addEventListener('submit', (event) => {
        if (!window.confirm('Tüm menü öğeleri yeniden görünür yapılsın mı?')) {
            event.preventDefault();
        }
    });

    sync();
}
