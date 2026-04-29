const CREATE_PAGE_SELECTOR = '[data-page$=".create"]';
const SECTION_CARD_SELECTOR = '.kt-card';
const HEADER_SELECTOR = ':scope > .kt-card-header, :scope > .kt-card-head';
const ERROR_SELECTOR = '.kt-input-invalid, .text-danger, [aria-invalid="true"]';

function normalizeText(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
}

function sectionTitle(card, index) {
    const header = card.querySelector(HEADER_SELECTOR);

    return normalizeText(
        header?.querySelector('.kt-card-title')?.textContent ||
        header?.querySelector('h3')?.textContent ||
        header?.querySelector('h2')?.textContent ||
        `Bölüm ${index + 1}`
    );
}

function sectionHint(card) {
    const header = card.querySelector(HEADER_SELECTOR);

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

function collectSectionCards(form) {
    const cards = [...form.querySelectorAll(SECTION_CARD_SELECTOR)]
        .filter((card) => card.querySelector(HEADER_SELECTOR))
        .filter((card) => !card.closest('.form-section-accordion'))
        .filter((card) => !card.closest('.kt-modal, [data-kt-modal]'));

    return cards.filter((card) => !cards.some((candidate) => candidate !== card && candidate.contains(card)));
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

function wrapCard(card, index) {
    const title = sectionTitle(card, index);
    const hint = sectionHint(card);
    const details = document.createElement('details');

    details.className = 'form-section-accordion';
    details.dataset.formSectionAccordion = 'true';
    details.open = index === 0 || Boolean(card.querySelector(ERROR_SELECTOR));

    card.classList.add('form-section-accordion__card');
    card.parentNode.insertBefore(details, card);
    details.appendChild(buildSummary(title, hint));
    details.appendChild(card);
}

export default function initCreateFormAccordions(root = document) {
    const page = root.querySelector(CREATE_PAGE_SELECTOR);
    if (!page || page.dataset.formAccordionsReady === 'true') return;

    page.dataset.formAccordionsReady = 'true';

    page.querySelectorAll('form').forEach((form) => {
        collectSectionCards(form).forEach((card, index) => wrapCard(card, index));
    });
}
