const AUTO_PAGE_SELECTOR = '[data-page$=".create"], [data-page$=".edit"], [data-form-accordions="true"]';
const SECTION_CARD_SELECTOR = '.kt-card';
const HEADER_SELECTOR = ':scope > .kt-card-header, :scope > .kt-card-head';
const ERROR_SELECTOR = '.kt-input-invalid, .text-danger, [aria-invalid="true"]';
const SKIPPED_AUTO_PAGES = new Set(['site.settings.edit']);
const INTERACTIVE_HEADER_SELECTOR = 'button, a, input, select, textarea, label, [role="button"], [data-kt-menu-trigger]';

function normalizeText(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
}

function sectionHeader(card) {
    return card.querySelector(HEADER_SELECTOR);
}

function sectionTitle(card, index) {
    const header = sectionHeader(card);

    return normalizeText(
        header?.querySelector('.kt-card-title')?.textContent ||
        header?.querySelector('h3')?.textContent ||
        header?.querySelector('h2')?.textContent ||
        `Bölüm ${index + 1}`
    );
}

function sectionHint(card) {
    const header = sectionHeader(card);

    return normalizeText(
        header?.querySelector('.text-muted-foreground')?.textContent ||
        header?.querySelector('p')?.textContent ||
        ''
    );
}

function tooltipText(title, hint) {
    const titleLower = title.toLocaleLowerCase('tr-TR');
    const hintLower = hint.toLocaleLowerCase('tr-TR');

    if (titleLower.includes('seo') || titleLower.includes('meta') || hintLower.includes('seo')) {
        return 'Arama motoru başlığı, açıklaması ve anahtar kelime gibi görünürlük verilerini bu bölümden yönetirsin.';
    }

    if (hint) return hint;

    return `${title} bölümündeki alanları bu akordeondan yönetirsin.`;
}

function hasInteractiveHeader(card) {
    return Boolean(sectionHeader(card)?.querySelector(INTERACTIVE_HEADER_SELECTOR));
}

export function collectSectionCards(container) {
    const cards = [...container.querySelectorAll(SECTION_CARD_SELECTOR)]
        .filter((card) => card.querySelector(HEADER_SELECTOR))
        .filter((card) => !card.closest('.form-section-accordion'))
        .filter((card) => !card.closest('.kt-modal, [data-kt-modal]'))
        .filter((card) => card.dataset.formAccordionSkip !== 'true');

    return cards.filter((card) => !cards.some((candidate) => candidate !== card && candidate.contains(card)));
}

export function collectDirectCards(container) {
    return [...container.children]
        .filter((child) => child.classList?.contains('kt-card'))
        .filter((card) => card.querySelector(HEADER_SELECTOR))
        .filter((card) => !card.closest('.form-section-accordion'))
        .filter((card) => card.dataset.formAccordionSkip !== 'true');
}

function buildSummary(title, hint) {
    const summary = document.createElement('summary');
    summary.className = 'form-section-accordion__summary';

    const textWrap = document.createElement('span');
    textWrap.className = 'form-section-accordion__text';

    const titleEl = document.createElement('span');
    titleEl.className = 'form-section-accordion__title';
    titleEl.textContent = title;

    const hintEl = document.createElement('span');
    hintEl.className = 'form-section-accordion__hint';
    hintEl.textContent = hint || 'Bu bölümdeki ayarları düzenle.';

    textWrap.append(titleEl, hintEl);

    const actions = document.createElement('span');
    actions.className = 'form-section-accordion__actions';

    const help = document.createElement('span');
    help.className = 'form-section-accordion__help';
    help.setAttribute('data-form-section-tooltip', tooltipText(title, hint));
    help.setAttribute('aria-label', tooltipText(title, hint));
    help.textContent = '?';

    const chevron = document.createElement('span');
    chevron.className = 'form-section-accordion__chevron';
    chevron.setAttribute('aria-hidden', 'true');
    chevron.textContent = '⌄';

    actions.append(help, chevron);
    summary.append(textWrap, actions);

    return summary;
}

export function wrapCardsAsAccordions(cards, options = {}) {
    const {
        attributeName = 'data-form-section-accordion',
        openFirst = true,
    } = options;

    cards.forEach((card, index) => {
        if (!card.parentNode || card.closest('.form-section-accordion')) return;

        const title = sectionTitle(card, index);
        const hint = sectionHint(card);
        const details = document.createElement('details');

        details.className = 'form-section-accordion';
        details.setAttribute(attributeName, 'true');
        details.open = (openFirst && index === 0) || Boolean(card.querySelector(ERROR_SELECTOR));

        if (hasInteractiveHeader(card)) {
            details.classList.add('form-section-accordion--keep-card-header');
        }

        card.classList.add('form-section-accordion__card');
        card.parentNode.insertBefore(details, card);
        details.appendChild(buildSummary(title, hint));
        details.appendChild(card);
    });
}

export function wrapDirectCardStack(stack, options = {}) {
    if (!stack || stack.dataset.directAccordionsReady === 'true') return;

    stack.dataset.directAccordionsReady = 'true';
    wrapCardsAsAccordions(collectDirectCards(stack), options);
}

function findAutoPages(root) {
    const pages = [];

    if (root.matches?.(AUTO_PAGE_SELECTOR)) {
        pages.push(root);
    }

    pages.push(...root.querySelectorAll(AUTO_PAGE_SELECTOR));

    return [...new Set(pages)];
}

export default function initCreateFormAccordions(root = document) {
    findAutoPages(root).forEach((page) => {
        const pageName = page.dataset.page || '';
        if (SKIPPED_AUTO_PAGES.has(pageName) && page.dataset.formAccordions !== 'true') return;
        if (page.dataset.formAccordionsReady === 'true') return;

        page.dataset.formAccordionsReady = 'true';

        page.querySelectorAll('form').forEach((form) => {
            if (form.closest('[data-form-accordion-skip="true"]')) return;

            const cards = collectSectionCards(form);
            if (cards.length === 0) return;

            wrapCardsAsAccordions(cards);
        });
    });
}
