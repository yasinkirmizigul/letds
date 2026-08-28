let observer = null;
let retryTimer = null;
const pendingSyncs = new WeakSet();

function selectConstructor() {
    return window.KTSelect || window.ktSelect || null;
}

function dashboardSelects(scope) {
    const selects = [];

    if (scope instanceof HTMLSelectElement) {
        selects.push(scope);
    }

    scope.querySelectorAll?.('select').forEach((select) => selects.push(select));

    return selects.filter((select) => (
        select.closest('body.dash_app')
        && !select.closest('.flatpickr-calendar, [data-app-select-ignore="true"]')
        && select.dataset.ktSelect !== 'false'
    ));
}

function prepareSelect(select) {
    select.classList.remove('kt-input');
    select.classList.add('kt-select');
    select.dataset.ktSelect = 'true';
}

function syncSelect(select) {
    if (!(select instanceof HTMLSelectElement) || pendingSyncs.has(select)) return;

    pendingSyncs.add(select);
    window.queueMicrotask(() => {
        pendingSyncs.delete(select);

        const KTSelect = selectConstructor();
        if (!KTSelect || !select.isConnected) return;

        try {
            const instance = KTSelect.getInstance?.(select) || KTSelect.getOrCreateInstance?.(select);
            instance?.updateOptions?.();
            instance?.sync?.();
        } catch (error) {
            console.warn('[KTUI] Select initialization failed:', error);
        }
    });
}

function initScope(scope = document) {
    dashboardSelects(scope).forEach((select) => {
        prepareSelect(select);
        syncSelect(select);
    });
}

export function initDashboardKtSelects(scope = document) {
    initScope(scope);

    if (!selectConstructor()) {
        window.clearTimeout(retryTimer);
        retryTimer = window.setTimeout(() => initDashboardKtSelects(scope), 80);
        return;
    }

    if (observer) return;

    observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach((node) => {
                    if (node instanceof HTMLElement) initScope(node);
                });

                if (mutation.target instanceof HTMLOptionElement) {
                    syncSelect(mutation.target.closest('select'));
                } else if (mutation.target instanceof HTMLSelectElement) {
                    syncSelect(mutation.target);
                }
            }

            if (mutation.type === 'attributes' && mutation.target instanceof HTMLOptionElement) {
                syncSelect(mutation.target.closest('select'));
            }
        }
    });

    observer.observe(document.body, {
        attributeFilter: ['disabled', 'label', 'selected', 'value'],
        attributes: true,
        childList: true,
        subtree: true,
    });
}
