const ERROR_SELECTOR = '.kt-input-invalid, .text-danger, [aria-invalid="true"]';
const HEADER_SELECTOR = ':scope > .kt-card-header, :scope > .kt-card-head';

function cleanText(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
}

function cardTitle(card, index) {
    const header = card.querySelector(HEADER_SELECTOR);

    return cleanText(
        header?.querySelector('.kt-card-title')?.textContent ||
        header?.querySelector('h3')?.textContent ||
        `Bölüm ${index + 1}`
    );
}

function cardHint(card) {
    const header = card.querySelector(HEADER_SELECTOR);

    return cleanText(
        header?.querySelector('.text-muted-foreground')?.textContent ||
        header?.querySelector('p')?.textContent ||
        ''
    );
}

function buildSummary(title, hint) {
    const summary = document.createElement('summary');
    summary.className = 'form-section-accordion__summary';

    const text = document.createElement('span');
    text.className = 'form-section-accordion__text';

    const titleEl = document.createElement('span');
    titleEl.className = 'form-section-accordion__title';
    titleEl.textContent = title;

    const hintEl = document.createElement('span');
    hintEl.className = 'form-section-accordion__hint';
    hintEl.textContent = hint || 'Bu bölümdeki ayarları düzenle.';

    const actions = document.createElement('span');
    actions.className = 'form-section-accordion__actions';

    const help = document.createElement('span');
    help.className = 'form-section-accordion__help';
    help.setAttribute('data-form-section-tooltip', hint || `${title} bölümündeki alanları yönetirsin.`);
    help.setAttribute('aria-label', hint || `${title} bölümündeki alanları yönetirsin.`);
    help.textContent = '?';

    const chevron = document.createElement('span');
    chevron.className = 'form-section-accordion__chevron';
    chevron.setAttribute('aria-hidden', 'true');
    chevron.textContent = '⌄';

    text.append(titleEl, hintEl);
    actions.append(help, chevron);
    summary.append(text, actions);

    return summary;
}

function openIfNeeded(details, index) {
    details.open = index === 0 || Boolean(details.querySelector(ERROR_SELECTOR));
}

function wrapTopLevelCards(page) {
    page.querySelectorAll('[data-site-settings-card-stack="true"]').forEach((stack) => {
        if (stack.dataset.siteSettingsAccordionsReady === 'true') return;
        stack.dataset.siteSettingsAccordionsReady = 'true';

        [...stack.children]
            .filter((child) => child.classList?.contains('kt-card'))
            .forEach((card, index) => {
                if (card.closest('.form-section-accordion')) return;

                const details = document.createElement('details');
                details.className = 'form-section-accordion';
                details.dataset.siteSettingsAccordion = 'card';

                card.classList.add('form-section-accordion__card');
                stack.insertBefore(details, card);
                details.append(buildSummary(cardTitle(card, index), cardHint(card)));
                details.append(card);

                openIfNeeded(details, index);
            });
    });
}

function wrapLocalizedSections(page) {
    page.querySelectorAll('[data-localized-section-container="true"]').forEach((container) => {
        if (container.dataset.localizedAccordionsReady === 'true') return;
        container.dataset.localizedAccordionsReady = 'true';

        let details = null;
        let panel = null;
        let sectionIndex = -1;

        [...container.children].forEach((child) => {
            if (child.matches('[data-localized-section-marker="true"]')) {
                sectionIndex += 1;

                details = document.createElement('details');
                details.className = 'form-section-accordion lg:col-span-2';
                details.dataset.siteSettingsAccordion = 'localized-section';

                panel = document.createElement('div');
                panel.className = 'grid gap-5 p-5 lg:grid-cols-2';

                const title = child.dataset.sectionTitle || 'Bölüm';
                const hint = child.dataset.sectionDescription || 'Bu gruptaki alanları düzenle.';

                container.insertBefore(details, child);
                details.append(buildSummary(title, hint));
                details.append(panel);
                child.remove();

                openIfNeeded(details, sectionIndex);
                return;
            }

            if (panel) {
                panel.append(child);
            }
        });

        container.querySelectorAll('[data-site-settings-accordion="localized-section"]').forEach((item, index) => {
            openIfNeeded(item, index);
        });
    });
}

export default function init(ctx = {}) {
    const root = ctx.root || document;
    const page = root.matches?.('[data-page="site.settings.edit"]')
        ? root
        : root.querySelector('[data-page="site.settings.edit"]');

    if (!page) return;

    wrapTopLevelCards(page);
    wrapLocalizedSections(page);
}
