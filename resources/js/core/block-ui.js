const BLOCKING_METHODS = new Set(['POST', 'PUT', 'PATCH', 'DELETE']);
const MINIMUM_VISIBLE_MS = 220;
const activeOperations = new Map();

let overlay = null;
let shownAt = 0;
let hideTimer = 0;
let initialized = false;

function operationCopy(method) {
    if (method === 'DELETE') {
        return {
            title: 'Silme işlemi yapılıyor',
            message: 'Kayıtlar güvenli bir şekilde temizleniyor. Lütfen bekleyin.',
        };
    }

    return {
        title: 'İşleniyor',
        message: 'İşleminiz tamamlanıyor. Lütfen bekleyin.',
    };
}

function ensureOverlay() {
    if (overlay?.isConnected) return overlay;

    overlay = document.createElement('div');
    overlay.className = 'app-block-ui';
    overlay.dataset.appBlockUi = 'true';
    overlay.hidden = true;
    overlay.setAttribute('role', 'status');
    overlay.setAttribute('aria-live', 'polite');
    overlay.setAttribute('aria-atomic', 'true');
    overlay.innerHTML = `
        <div class="app-block-ui__panel">
            <span class="app-block-ui__spinner" aria-hidden="true"></span>
            <span class="app-block-ui__content">
                <strong class="app-block-ui__title" data-block-ui-title>İşleniyor</strong>
                <span class="app-block-ui__message" data-block-ui-message>Lütfen bekleyin.</span>
            </span>
        </div>
    `;

    (document.body || document.documentElement).appendChild(overlay);

    return overlay;
}

function renderLatestOperation() {
    const current = Array.from(activeOperations.values()).at(-1) || operationCopy('POST');
    const element = ensureOverlay();

    element.querySelector('[data-block-ui-title]').textContent = current.title;
    element.querySelector('[data-block-ui-message]').textContent = current.message;
}

export function showBlockUi(options = {}) {
    const method = String(options.method || 'POST').toUpperCase();
    const defaults = operationCopy(method);
    const token = Symbol('block-ui-operation');

    activeOperations.set(token, {
        title: options.title || defaults.title,
        message: options.message || defaults.message,
    });

    window.clearTimeout(hideTimer);
    renderLatestOperation();

    const element = ensureOverlay();
    if (element.hidden) {
        shownAt = performance.now();
        element.hidden = false;
        document.documentElement.classList.add('app-block-ui-active');
        document.body?.setAttribute('aria-busy', 'true');
        window.requestAnimationFrame(() => element.classList.add('is-visible'));
    }

    return token;
}

export function hideBlockUi(token) {
    if (token) activeOperations.delete(token);
    if (activeOperations.size > 0) {
        renderLatestOperation();
        return;
    }

    const element = ensureOverlay();
    const delay = Math.max(0, MINIMUM_VISIBLE_MS - (performance.now() - shownAt));

    window.clearTimeout(hideTimer);
    hideTimer = window.setTimeout(() => {
        if (activeOperations.size > 0) return;

        element.classList.remove('is-visible');
        document.documentElement.classList.remove('app-block-ui-active');
        document.body?.removeAttribute('aria-busy');

        window.setTimeout(() => {
            if (!element.classList.contains('is-visible')) element.hidden = true;
        }, 180);
    }, delay);
}

function effectiveFormMethod(form) {
    const override = form.querySelector('input[name="_method"]')?.value;
    return String(override || form.method || 'GET').toUpperCase();
}

function shouldBlockForm(form) {
    if (!(form instanceof HTMLFormElement)) return false;
    if (form.dataset.noBlockUi === 'true') return false;

    const target = form.getAttribute('target');
    if (target && target !== '_self') return false;

    return BLOCKING_METHODS.has(effectiveFormMethod(form)) || form.dataset.blockUi === 'true';
}

function blockForm(form) {
    if (!shouldBlockForm(form) || form.dataset.blockUiActive === 'true') return;

    form.dataset.blockUiActive = 'true';
    showBlockUi({
        method: effectiveFormMethod(form),
        title: form.dataset.blockUiTitle,
        message: form.dataset.blockUiMessage,
    });
}

function installFormInterceptor() {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        window.queueMicrotask(() => {
            if (!event.defaultPrevented) blockForm(form);
        });
    });

    const nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function submitWithBlockUi() {
        blockForm(this);
        return nativeSubmit.call(this);
    };
}

function installFetchInterceptor() {
    if (typeof window.fetch !== 'function') return;

    const nativeFetch = window.fetch.bind(window);

    window.fetch = async (input, init = {}) => {
        const method = String(init.method || input?.method || 'GET').toUpperCase();
        const shouldBlock = init.blockUi !== false && BLOCKING_METHODS.has(method);
        const token = shouldBlock ? showBlockUi({ method }) : null;

        try {
            return await nativeFetch(input, init);
        } finally {
            if (token) hideBlockUi(token);
        }
    };
}

function installXhrInterceptor() {
    if (typeof XMLHttpRequest === 'undefined') return;

    const nativeOpen = XMLHttpRequest.prototype.open;
    const nativeSend = XMLHttpRequest.prototype.send;
    const methodKey = Symbol('block-ui-method');

    XMLHttpRequest.prototype.open = function openWithBlockUi(method, ...args) {
        this[methodKey] = String(method || 'GET').toUpperCase();
        return nativeOpen.call(this, method, ...args);
    };

    XMLHttpRequest.prototype.send = function sendWithBlockUi(...args) {
        const method = this[methodKey] || 'GET';
        const shouldBlock = this.blockUi !== false && BLOCKING_METHODS.has(method);
        const token = shouldBlock ? showBlockUi({ method }) : null;

        if (token) {
            this.addEventListener('loadend', () => hideBlockUi(token), { once: true });
        }

        try {
            return nativeSend.apply(this, args);
        } catch (error) {
            if (token) hideBlockUi(token);
            throw error;
        }
    };
}

export function initGlobalBlockUi() {
    if (initialized) return;
    initialized = true;

    installFormInterceptor();
    installFetchInterceptor();
    installXhrInterceptor();

    window.addEventListener('pageshow', () => {
        activeOperations.clear();
        hideBlockUi();
    });
}
