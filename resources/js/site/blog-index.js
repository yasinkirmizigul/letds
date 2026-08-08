const form = document.querySelector('[data-blog-filter-form]');
const searchInput = form?.querySelector('[data-blog-search]');
const categoryInput = form?.querySelector('[data-blog-category-input]');
const resultsSection = document.querySelector('[data-blog-results-section]');

if (form && searchInput && categoryInput && resultsSection) {
    let requestController;
    let debounceTimer;

    const formUrl = (pageUrl = form.action) => {
        const url = new URL(pageUrl, window.location.origin);
        const data = new FormData(form);

        if (!url.searchParams.has('page')) {
            url.searchParams.delete('page');
        }

        ['q', 'category'].forEach((key) => {
            const value = String(data.get(key) ?? '').trim();
            if (value) url.searchParams.set(key, value);
            else url.searchParams.delete(key);
        });

        return url;
    };

    const syncCategoryChips = () => {
        form.querySelectorAll('[data-blog-category]').forEach((chip) => {
            chip.classList.toggle('is-active', chip.dataset.blogCategory === categoryInput.value);
        });
    };

    const loadResults = async (pageUrl, { updateHistory = true } = {}) => {
        const url = formUrl(pageUrl);
        const browserUrl = new URL(url);
        browserUrl.searchParams.delete('fragment');
        url.searchParams.set('fragment', '1');

        requestController?.abort();
        requestController = new AbortController();

        const currentResults = resultsSection.querySelector('[data-blog-results]');
        currentResults?.classList.add('site-loading');
        currentResults?.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: requestController.signal,
            });

            if (!response.ok) throw new Error(`Blog results request failed: ${response.status}`);

            const payload = await response.json();
            currentResults?.remove();
            resultsSection.insertAdjacentHTML('beforeend', payload.html);
            resultsSection.querySelectorAll('[data-reveal]').forEach((item) => item.classList.add('is-revealed'));

            if (updateHistory) window.history.pushState({}, '', browserUrl);
        } catch (error) {
            if (error.name !== 'AbortError') console.warn('[Site] Blog search failed:', error);
            currentResults?.classList.remove('site-loading');
            currentResults?.setAttribute('aria-busy', 'false');
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadResults(form.action);
    });

    searchInput.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => loadResults(form.action), 320);
    });

    form.addEventListener('click', (event) => {
        const chip = event.target.closest('[data-blog-category]');
        if (!chip) return;

        event.preventDefault();
        categoryInput.value = chip.dataset.blogCategory ?? '';
        syncCategoryChips();
        loadResults(form.action);
    });

    resultsSection.addEventListener('click', (event) => {
        const paginationLink = event.target.closest('nav a[href]');
        if (!paginationLink) return;

        event.preventDefault();
        loadResults(paginationLink.href).then(() => {
            resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        searchInput.value = url.searchParams.get('q') ?? '';
        categoryInput.value = url.searchParams.get('category') ?? '';
        syncCategoryChips();
        loadResults(url, { updateHistory: false });
    });
}
