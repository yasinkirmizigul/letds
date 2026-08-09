const SURFACE_PATTERNS = new Set([
    'none',
    'carbon',
    'micro-grid',
    'pixel-grid',
    'dots',
    'diagonal',
    'blueprint',
    'rings',
    'grain',
]);

const SURFACE_BLEND_MODES = new Set(['soft-light', 'overlay', 'normal', 'multiply', 'screen']);

function safePattern(value) {
    return SURFACE_PATTERNS.has(value) ? value : 'none';
}

function safeBlend(value) {
    return SURFACE_BLEND_MODES.has(value) ? value : 'soft-light';
}

function safeColor(value, fallback = '#ffffff') {
    return /^#[0-9a-f]{6}$/i.test(value || '') ? value : fallback;
}

function numericValue(input, fallback) {
    const value = Number(input?.value);
    return Number.isFinite(value) ? value : fallback;
}

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

function syncSurfaceEditors(root) {
    root.querySelectorAll('[data-homepage-surface-editor]').forEach((editor) => {
        const input = (role) => editor.querySelector(`[data-homepage-surface-role="${role}"]${role === 'pattern' ? ':checked' : ''}`);
        const pattern = safePattern(input('pattern')?.value || 'none');
        const patternLayer = editor.querySelector('[data-homepage-surface-pattern]');

        editor.style.setProperty('--homepage-surface-color', safeColor(input('background')?.value, '#263238'));
        editor.style.setProperty('--homepage-pattern-ink', safeColor(input('pattern-color')?.value));
        editor.style.setProperty('--homepage-pattern-opacity', String(numericValue(input('opacity'), 0) / 100));
        editor.style.setProperty('--homepage-pattern-size', `${numericValue(input('scale'), 28)}px`);
        editor.style.setProperty('--homepage-pattern-blur', `${numericValue(input('blur'), 0)}px`);
        editor.style.setProperty('--homepage-pattern-blend', safeBlend(input('blend')?.value));

        if (patternLayer) patternLayer.dataset.homepagePattern = pattern;
    });
}

function bindRangeField(field, signal) {
    const input = field.querySelector('[data-homepage-range-input]');
    const output = field.querySelector('[data-homepage-range-output]');
    if (!input || !output) return;

    const sync = () => {
        output.textContent = `${input.value}${input.dataset.homepageRangeUnit || ''}`;
    };

    input.addEventListener('input', sync, { signal });
    sync();
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

    syncBackgroundPreview(root);
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
    const preview = field.querySelector('[data-homepage-media-preview]');
    const placeholder = field.querySelector('[data-homepage-media-placeholder]');
    const backgroundPreview = field.querySelector('[data-homepage-background-preview]');
    const hasMedia = Boolean(preview?.getAttribute('src'));

    preview?.classList.toggle('hidden', !hasMedia);
    placeholder?.classList.toggle('hidden', hasMedia);
    backgroundPreview?.classList.toggle('has-media', hasMedia);
}

function syncBackgroundPreview(root) {
    const preview = root.querySelector('[data-homepage-background-preview]');
    if (!preview) return;

    const setting = (key) => root.querySelector(`[name="settings[${key}]"]:checked`)
        || root.querySelector(`[name="settings[${key}]"]`);
    const activeMode = root.querySelector('[data-homepage-admin-mode-tab].is-active')?.dataset.homepageAdminModeTab || 'analysis';
    const modeSetting = (key) => setting(activeMode === 'analysis' ? key : `${activeMode}_${key}`) || setting(key);
    const brightness = Number(setting('background_brightness')?.value || 100);
    const overlayEnabled = root.querySelector('[type="checkbox"][name="settings[background_overlay_enabled]"]')?.checked ?? true;
    const overlayOpacity = Number(setting('background_overlay_opacity')?.value || 0);
    const position = setting('background_position')?.value || 'center';
    const afterColor = modeSetting('after_background_color')?.value || '#ec6367';
    const beforeColor = modeSetting('before_background_color')?.value || '#ffffff';

    preview.style.setProperty('--homepage-preview-brightness', `${brightness}%`);
    preview.style.setProperty('--homepage-preview-opacity', overlayEnabled ? String(overlayOpacity / 100) : '0');
    preview.style.setProperty('--homepage-preview-position', position);
    preview.style.setProperty('--homepage-preview-after', afterColor);
    preview.style.setProperty('--homepage-preview-before', beforeColor);

    ['before', 'after'].forEach((side) => {
        const patternLayer = preview.querySelector(`[data-homepage-background-pattern="${side}"]`);
        if (!patternLayer) return;

        patternLayer.dataset.homepagePattern = safePattern(modeSetting(`${side}_pattern`)?.value || 'none');
        patternLayer.style.setProperty('--homepage-pattern-ink', safeColor(modeSetting(`${side}_pattern_color`)?.value));
        patternLayer.style.setProperty('--homepage-pattern-opacity', String(numericValue(modeSetting(`${side}_pattern_opacity`), 0) / 100));
        patternLayer.style.setProperty('--homepage-pattern-size', `${numericValue(modeSetting(`${side}_pattern_scale`), 28)}px`);
        patternLayer.style.setProperty('--homepage-pattern-blur', `${numericValue(modeSetting(`${side}_pattern_blur`), 0)}px`);
        patternLayer.style.setProperty('--homepage-pattern-blend', safeBlend(modeSetting(`${side}_pattern_blend`)?.value));
    });
}

export async function init(ctx = {}) {
    const root = ctx.root || document;
    const signal = ctx.signal;
    const objectUrls = new Set();

    root.querySelectorAll('[data-homepage-color-field]').forEach((field) => bindColorField(field, signal));
    root.querySelectorAll('[data-homepage-range-field]').forEach((field) => bindRangeField(field, signal));
    root.querySelectorAll('[data-homepage-media-field]').forEach(syncMediaField);
    bindModeLabels(root, signal);
    syncSurfaceEditors(root);
    syncBackgroundPreview(root);

    root.addEventListener('input', () => {
        syncSurfaceEditors(root);
        syncBackgroundPreview(root);
    }, { signal });

    root.addEventListener('change', (event) => {
        const fileInput = event.target.closest('[data-homepage-media-file]');
        if (!fileInput) {
            syncSurfaceEditors(root);
            syncBackgroundPreview(root);
            return;
        }

        const field = fileInput.closest('[data-homepage-media-field]');
        const file = fileInput.files?.[0];
        const input = field?.querySelector('[data-homepage-media-input]');
        const clearFlag = field?.querySelector('[data-homepage-media-clear-flag]');
        const preview = field?.querySelector('[data-homepage-media-preview]');
        if (!field || !file || !preview) return;

        const objectUrl = URL.createObjectURL(file);
        objectUrls.add(objectUrl);
        preview.src = objectUrl;
        if (input) input.value = '';
        if (clearFlag) clearFlag.value = '0';
        syncMediaField(field);
        syncBackgroundPreview(root);
    }, { signal });

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
        const fileInput = field?.querySelector('[data-homepage-media-file]');
        const clearFlag = field?.querySelector('[data-homepage-media-clear-flag]');
        const preview = field?.querySelector('[data-homepage-media-preview]');

        if (input) input.value = '';
        if (fileInput) fileInput.value = '';
        if (clearFlag) clearFlag.value = '1';
        if (preview) preview.src = '';
        if (field) syncMediaField(field);
    }, { signal });

    document.addEventListener('media:pick', (event) => {
        const inputSelector = event.detail?.target?.inputSel;
        const input = inputSelector ? root.querySelector(inputSelector) : null;
        const field = input?.closest('[data-homepage-media-field]');

        if (field) {
            const fileInput = field.querySelector('[data-homepage-media-file]');
            const clearFlag = field.querySelector('[data-homepage-media-clear-flag]');
            if (fileInput) fileInput.value = '';
            if (clearFlag) clearFlag.value = '0';
        }

        root.querySelectorAll('[data-homepage-media-field]').forEach(syncMediaField);
        syncBackgroundPreview(root);
    }, { signal });

    ctx.cleanup?.(() => {
        objectUrls.forEach((url) => URL.revokeObjectURL(url));
        objectUrls.clear();
    });

    const errorPanel = [...root.querySelectorAll('[data-homepage-admin-mode-panel]')]
        .find((panel) => panel.querySelector('.kt-input-invalid, .text-danger'));

    if (errorPanel) {
        activateMode(root, errorPanel.dataset.homepageAdminModePanel);
    }
}

export default { init };
