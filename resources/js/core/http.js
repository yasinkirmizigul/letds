import { showToastMessage } from '@/core/swal-alert';

class HttpError extends Error {
    constructor(message, { status = 0, data = null, response = null, url = '' } = {}) {
        super(message);
        this.name = 'HttpError';
        this.status = status;
        this.data = data;
        this.response = response;
        this.url = url;
    }
}

let authRedirectLocked = false;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || window.Laravel?.csrfToken
        || '';
}

function notify(type, message) {
    window.dispatchEvent(new CustomEvent('http:notify', { detail: { type, message } }));
    showToastMessage(type, message, { duration: 2600 });
}

function resolveLoginUrl() {
    const path = window.location.pathname || '';

    if (path === '/randevu-al' || path.startsWith('/member/')) {
        return '/member/login';
    }

    return '/login';
}

function resolveMessage(status, data) {
    if (data?.message) return data.message;
    if (data?.error?.message) return data.error.message;

    if (status === 401 || status === 419) return 'Oturum süresi doldu. Lütfen tekrar giriş yapın.';
    if (status === 403) return 'Bu işlem için yetkiniz bulunmuyor.';
    if (status === 422) return 'Gönderilen veriler doğrulanamadı.';

    return 'Beklenmeyen bir hata oluştu. Lütfen tekrar deneyin.';
}

function handleGlobalAuthExpiry(message) {
    notify('warning', message || 'Oturum süresi doldu. Yönlendiriliyorsunuz...');

    if (authRedirectLocked) return;
    authRedirectLocked = true;

    setTimeout(() => {
        const loginUrl = resolveLoginUrl();
        const next = `${window.location.pathname}${window.location.search}${window.location.hash}`;
        window.location.assign(`${loginUrl}?next=${encodeURIComponent(next)}`);
    }, 600);
}

async function parseResponseBody(response) {
    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
        return response.json().catch(() => null);
    }

    const text = await response.text().catch(() => '');
    return text || null;
}

export async function request(url, options = {}) {
    const {
        method = 'GET',
        data,
        headers = {},
        signal,
        credentials = 'include',
        ignoreGlobalError = false,
    } = options;

    const upperMethod = String(method).toUpperCase();
    const hasBody = data !== undefined && data !== null;
    const isFormData = typeof FormData !== 'undefined' && data instanceof FormData;

    const reqHeaders = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...headers,
    };

    if (upperMethod !== 'GET' && upperMethod !== 'HEAD') {
        const token = csrfToken();
        if (token && !reqHeaders['X-CSRF-TOKEN']) {
            reqHeaders['X-CSRF-TOKEN'] = token;
        }
    }

    let body;
    if (hasBody) {
        if (isFormData) {
            body = data;
        } else if (typeof data === 'string') {
            body = data;
        } else {
            if (!reqHeaders['Content-Type']) {
                reqHeaders['Content-Type'] = 'application/json';
            }
            body = reqHeaders['Content-Type']?.includes('application/json') ? JSON.stringify(data) : data;
        }
    }

    let response;
    try {
        response = await fetch(url, {
            method: upperMethod,
            headers: reqHeaders,
            credentials,
            body,
            signal,
        });
    } catch (error) {
        const err = new HttpError('Ağ bağlantısı kurulamadı.', { status: 0, data: null, url });
        if (!ignoreGlobalError) notify('error', err.message);
        throw err;
    }

    const parsed = await parseResponseBody(response);

    if (!response.ok) {
        const status = response.status;
        const message = resolveMessage(status, parsed);
        const err = new HttpError(message, { status, data: parsed, response, url });

        if (!ignoreGlobalError) {
            if (status === 401 || status === 419) {
                handleGlobalAuthExpiry(message);
            } else if (status === 403) {
                notify('error', message);
            } else if (status !== 422) {
                notify('error', message);
            }
        }

        throw err;
    }

    return parsed;
}

export function get(url, options = {}) {
    return request(url, { ...options, method: 'GET' });
}

export function post(url, data, options = {}) {
    return request(url, { ...options, method: 'POST', data });
}

export function put(url, data, options = {}) {
    return request(url, { ...options, method: 'PUT', data });
}

function destroy(url, data, options = {}) {
    return request(url, { ...options, method: 'DELETE', data });
}

export { destroy as delete, HttpError };
