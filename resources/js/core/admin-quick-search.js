import { request } from '@/core/http';

const SEARCH_SELECTOR = '[data-admin-quick-search]';
const OPEN_SELECTOR = '[data-quick-search-open]';

function isTypingTarget(target) {
    if (!(target instanceof HTMLElement)) return false;

    return ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) || target.isContentEditable;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function openModal(modal) {
    try {
        window.KTModal?.getOrCreateInstance?.(modal)?.show?.();
    } catch (_) {
        //
    }

    modal.classList.add('open');
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
}

function closeModal(modal) {
    try {
        window.KTModal?.getOrCreateInstance?.(modal)?.hide?.();
    } catch (_) {
        //
    }

    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
}

function debounce(fn, wait = 220) {
    let timeout;

    return (...args) => {
        window.clearTimeout(timeout);
        timeout = window.setTimeout(() => fn(...args), wait);
    };
}

function itemMarkup(item) {
    const badgeClass = item.badge_class || 'kt-badge kt-badge-sm kt-badge-light';
    const icon = item.icon || 'ki-filled ki-magnifier';

    return `
        <a class="admin-quick-search-result" href="${escapeHtml(item.url)}" data-quick-search-result>
            <span class="admin-quick-search-result__icon">
                <i class="${escapeHtml(icon)}"></i>
            </span>
            <span class="admin-quick-search-result__main">
                <span class="admin-quick-search-result__title">${escapeHtml(item.title)}</span>
                <span class="admin-quick-search-result__subtitle">${escapeHtml(item.subtitle || '')}</span>
            </span>
            ${item.badge ? `<span class="${escapeHtml(badgeClass)} admin-quick-search-result__badge">${escapeHtml(item.badge)}</span>` : ''}
        </a>
    `;
}

function groupMarkup(group) {
    const items = group.items || [];

    return `
        <section class="admin-quick-search-group">
            <div class="admin-quick-search-group__label">${escapeHtml(group.label)}</div>
            <div class="admin-quick-search-group__items">
                ${items.map(itemMarkup).join('')}
            </div>
        </section>
    `;
}

function initAdminQuickSearch() {
    const modal = document.querySelector(SEARCH_SELECTOR);
    if (!modal) return;

    const input = modal.querySelector('[data-quick-search-input]');
    const results = modal.querySelector('[data-quick-search-results]');
    const empty = modal.querySelector('[data-quick-search-empty]');
    const error = modal.querySelector('[data-quick-search-error]');
    const spinner = modal.querySelector('[data-quick-search-spinner]');
    const url = modal.getAttribute('data-search-url');

    if (!input || !results || !url) return;

    let abortController = null;
    let activeIndex = -1;
    let initialized = false;

    const resultLinks = () => [...results.querySelectorAll('[data-quick-search-result]')];

    function setLoading(isLoading) {
        spinner?.classList.toggle('hidden', !isLoading);
    }

    function showEmpty(messageTitle, messageText) {
        results.innerHTML = '';
        error?.classList.add('hidden');
        empty?.classList.remove('hidden');
        const title = empty?.querySelector('.font-semibold');
        const text = empty?.querySelector('.text-sm');
        if (title && messageTitle) title.textContent = messageTitle;
        if (text && messageText) text.textContent = messageText;
    }

    function render(payload) {
        const groups = payload?.groups || [];
        activeIndex = -1;
        error?.classList.add('hidden');

        if (groups.length === 0 || Number(payload?.total || 0) === 0) {
            showEmpty('Sonuç bulunamadı', 'Başka bir anahtar kelime, ürün kodu, e-posta veya sipariş numarası deneyin.');
            return;
        }

        empty?.classList.add('hidden');
        results.innerHTML = groups.map(groupMarkup).join('');
    }

    async function fetchResults(query) {
        abortController?.abort();
        const controller = new AbortController();
        abortController = controller;

        setLoading(true);

        try {
            const target = new URL(url, window.location.origin);
            if (query) target.searchParams.set('q', query);
            target.searchParams.set('limit', '5');

            const payload = await request(target.toString(), {
                method: 'GET',
                signal: controller.signal,
                ignoreGlobalError: true,
            });

            if (controller.signal.aborted) return;

            render(payload);
        } catch (err) {
            if (controller.signal.aborted || err?.name === 'AbortError') return;

            results.innerHTML = '';
            empty?.classList.add('hidden');
            error?.classList.remove('hidden');
        } finally {
            if (!controller.signal.aborted) {
                setLoading(false);
            }
        }
    }

    const debouncedFetch = debounce(() => {
        fetchResults(input.value.trim());
    });

    function focusInput() {
        window.setTimeout(() => {
            input.focus();
            input.select();
        }, 80);
    }

    function openSearch() {
        openModal(modal);
        focusInput();

        if (!initialized) {
            initialized = true;
            fetchResults('');
        }
    }

    function setActive(index) {
        const links = resultLinks();
        links.forEach((link) => link.classList.remove('is-active'));

        if (links.length === 0) {
            activeIndex = -1;
            return;
        }

        activeIndex = (index + links.length) % links.length;
        links[activeIndex].classList.add('is-active');
        links[activeIndex].scrollIntoView({ block: 'nearest' });
    }

    document.addEventListener('click', (event) => {
        const opener = event.target.closest(OPEN_SELECTOR);
        if (opener) {
            event.preventDefault();
            openSearch();
            return;
        }

        if (event.target.closest('[data-quick-search-close]')) {
            closeModal(modal);
        }
    });

    document.addEventListener('keydown', (event) => {
        const key = event.key.toLowerCase();

        if ((event.ctrlKey || event.metaKey) && key === 'k') {
            event.preventDefault();
            openSearch();
            return;
        }

        if (key === '/' && !isTypingTarget(event.target)) {
            event.preventDefault();
            openSearch();
        }
    });

    input.addEventListener('input', () => {
        const query = input.value.trim();

        if (query.length > 0 && query.length < 2) {
            showEmpty('Biraz daha yaz', 'Daha doğru sonuçlar için en az 2 karakter gerekli.');
            return;
        }

        debouncedFetch();
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive(activeIndex + 1);
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive(activeIndex - 1);
            return;
        }

        if (event.key === 'Enter') {
            const links = resultLinks();
            const target = activeIndex >= 0 ? links[activeIndex] : links[0];
            if (target) {
                event.preventDefault();
                window.location.assign(target.href);
            }
            return;
        }

        if (event.key === 'Escape') {
            closeModal(modal);
        }
    });
}

export default initAdminQuickSearch;
