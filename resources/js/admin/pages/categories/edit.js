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

function openModal(modal) {
    modal?.classList.remove('hidden');
}

function closeModal(modal) {
    modal?.classList.add('hidden');
}

export default function init({ root }) {
    const nameEl = root.querySelector('#cat_name');
    const slugEl = root.querySelector('#cat_slug');
    const autoEl = root.querySelector('#slug_auto');
    const regenEl = root.querySelector('#slug_regen');
    const previewEl = root.querySelector('#slug_preview');

    const updateForm = root.querySelector('#category-update-form');
    const deleteForm = root.querySelector('#category-delete-form');

    const syncPreview = () => {
        if (!previewEl || !slugEl) return;
        previewEl.textContent = (slugEl.value || '').trim();
    };

    const setSlugFromName = () => {
        if (!nameEl || !slugEl) return;
        slugEl.value = slugifyTR(nameEl.value);
        syncPreview();
    };

    // --- slug preview init
    syncPreview();

    // --- auto slug
    nameEl?.addEventListener('input', () => {
        if (!autoEl?.checked) return;
        setSlugFromName();
    });

    // --- manual slug
    slugEl?.addEventListener('input', syncPreview);

    // --- regen
    regenEl?.addEventListener('click', setSlugFromName);

    // --- modal (delegation)
    root.addEventListener('click', (e) => {
        const targetBtn = e.target?.closest?.('[data-kt-modal-target]');
        if (targetBtn && root.contains(targetBtn)) {
            const sel = targetBtn.getAttribute('data-kt-modal-target');
            const modal = document.querySelector(sel);
            openModal(modal);
            return;
        }

        const closeBtn = e.target?.closest?.('[data-kt-modal-close]');
        if (closeBtn && root.contains(closeBtn)) {
            closeModal(closeBtn.closest('.kt-modal'));
            return;
        }

        // backdrop click
        const modalEl = e.target?.classList?.contains('kt-modal') ? e.target : null;
        if (modalEl) closeModal(modalEl);
    });

    // --- prevent double submit (update)
    updateForm?.addEventListener('submit', () => {
        root.querySelectorAll('button[form="category-update-form"][type="submit"]').forEach(b => {
            b.disabled = true;
            b.classList.add('opacity-60', 'pointer-events-none');
        });
    });

    // --- prevent double submit (delete)
    deleteForm?.addEventListener('submit', () => {
        root.querySelectorAll('button[form="category-delete-form"][type="submit"]').forEach(b => {
            b.disabled = true;
            b.classList.add('opacity-60', 'pointer-events-none');
        });
    });
}
