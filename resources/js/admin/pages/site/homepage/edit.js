function bindColorField(field, signal) {
    const picker = field.querySelector('[data-homepage-color-picker]');
    const input = field.querySelector('[data-homepage-color-value]');

    if (!picker || !input) return;

    picker.addEventListener('input', () => {
        input.value = picker.value.toUpperCase();
    }, { signal });

    input.addEventListener('input', () => {
        const value = input.value.trim();

        if (/^#[0-9a-f]{6}$/i.test(value)) {
            picker.value = value;
        }
    }, { signal });
}

function activateMode(root, key) {
    root.querySelectorAll('[data-homepage-admin-mode-tab]').forEach((tab) => {
        const active = tab.dataset.homepageAdminModeTab === key;
        tab.classList.toggle('is-active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
        tab.tabIndex = active ? 0 : -1;
    });

    root.querySelectorAll('[data-homepage-admin-mode-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', panel.dataset.homepageAdminModePanel !== key);
    });
}

function bindModeLabels(root, signal) {
    root.querySelectorAll('[data-homepage-admin-mode-label-key]').forEach((tab) => {
        const key = tab.dataset.homepageAdminModeLabelKey;
        const input = key ? root.querySelector(`[name="${key}"]`) : null;
        const label = tab.querySelector('[data-homepage-admin-mode-label]');
        if (!input || !label) return;

        const sync = () => {
            label.textContent = input.value.trim() || tab.dataset.homepageAdminModeDefaultLabel || '';
        };

        input.addEventListener('input', sync, { signal });
        sync();
    });
}

function syncMediaField(field) {
    const input = field.querySelector('[data-homepage-media-input]');
    const preview = field.querySelector('[data-homepage-media-preview]');
    const placeholder = field.querySelector('[data-homepage-media-placeholder]');
    const hasMedia = Boolean(input?.value && preview?.getAttribute('src'));

    preview?.classList.toggle('hidden', !hasMedia);
    placeholder?.classList.toggle('hidden', hasMedia);
}

export async function init(ctx = {}) {
    const root = ctx.root || document;
    const signal = ctx.signal;

    root.querySelectorAll('[data-homepage-color-field]').forEach((field) => bindColorField(field, signal));
    root.querySelectorAll('[data-homepage-media-field]').forEach(syncMediaField);
    bindModeLabels(root, signal);

    root.addEventListener('click', (event) => {
        const modeTab = event.target.closest('[data-homepage-admin-mode-tab]');

        if (modeTab) {
            activateMode(root, modeTab.dataset.homepageAdminModeTab);
            return;
        }

        const clearButton = event.target.closest('[data-homepage-media-clear]');
        if (!clearButton) return;

        const field = clearButton.closest('[data-homepage-media-field]');
        const input = field?.querySelector('[data-homepage-media-input]');
        const preview = field?.querySelector('[data-homepage-media-preview]');

        if (input) input.value = '';
        if (preview) preview.src = '';
        if (field) syncMediaField(field);
    }, { signal });

    document.addEventListener('media:pick', () => {
        root.querySelectorAll('[data-homepage-media-field]').forEach(syncMediaField);
    }, { signal });

    const errorPanel = [...root.querySelectorAll('[data-homepage-admin-mode-panel]')]
        .find((panel) => panel.querySelector('.kt-input-invalid, .text-danger'));

    if (errorPanel) {
        activateMode(root, errorPanel.dataset.homepageAdminModePanel);
    }
}

export default { init };
