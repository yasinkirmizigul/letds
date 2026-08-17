const PANEL_SELECTOR = '.kt-card, [data-admin-panel]';
const CREATE_TITLE_PATTERN = /(?:^|\s)(?:yeni|ekle|oluştur|olustur|tanımla|tanimla|başlat|baslat)(?:\s|$)/iu;
const COLLECTION_TITLE_PATTERN = /(?:liste(?:si|leri)?|havuz(?:u)?|kütüphane(?:si)?|kutuphane(?:si)?|kayıt(?:lar|ları)?|kayit(?:lar|lari)?|geçmiş(?:i)?|gecmis(?:i)?|silinen|arşiv(?:i)?|arsiv(?:i)?|yönetim(?:i)?|yonetim(?:i)?)/iu;

const directTitle = (panel) => panel.querySelector(
    ':scope > .kt-card-header .kt-card-title, '
    + ':scope > .kt-card-header h1, '
    + ':scope > .kt-card-header h2, '
    + ':scope > .kt-card-header h3'
);

const hasDirectForm = (panel) => Boolean(panel.querySelector(
    ':scope > form, :scope > form.kt-card-content, :scope > .kt-card-content > form'
));

const hasCollectionStructure = (panel) => (
    panel.classList.contains('kt-card-grid')
    || Boolean(panel.querySelector(':scope > .kt-card-content table, :scope > .kt-card-content [data-kt-datatable]'))
);

const isTopLevelPanel = (panel) => !panel.parentElement?.closest('.kt-card');

const classifyPanel = (panel) => {
    if (!(panel instanceof HTMLElement)) return;
    if (panel.closest('.kt-modal, #app-lock, .swal2-container, [data-admin-panel-ignore]')) return;

    if (panel.classList.contains('admin-panel--create')) {
        panel.classList.add('admin-panel');
        panel.dataset.adminPanel = 'create';
        return;
    }

    if (panel.classList.contains('admin-panel--collection')) {
        panel.classList.add('admin-panel');
        panel.dataset.adminPanel = 'collection';
        return;
    }

    const title = directTitle(panel)?.textContent?.replace(/\s+/g, ' ').trim() ?? '';
    const createPagePanel = document.body.dataset.adminPageMode === 'create'
        && isTopLevelPanel(panel)
        && hasDirectForm(panel)
        && !hasCollectionStructure(panel);

    let type = null;
    if (createPagePanel || CREATE_TITLE_PATTERN.test(title)) {
        type = 'create';
    } else if (hasCollectionStructure(panel) || COLLECTION_TITLE_PATTERN.test(title)) {
        type = 'collection';
    }

    if (!type) return;

    panel.classList.add('admin-panel', `admin-panel--${type}`);
    panel.dataset.adminPanel = type;
};

const classifyWithin = (root) => {
    if (root instanceof Element) {
        if (root.matches(PANEL_SELECTOR)) classifyPanel(root);
        classifyPanel(root.closest(PANEL_SELECTOR));
    }

    root.querySelectorAll?.(PANEL_SELECTOR).forEach(classifyPanel);
};

export default function initAdminSemanticPanels(root = document) {
    classifyWithin(root);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof Element) classifyWithin(node);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });

    return () => observer.disconnect();
}
