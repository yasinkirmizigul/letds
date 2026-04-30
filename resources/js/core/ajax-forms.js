import { request } from '@/core/http';
import { showToastMessage } from '@/core/swal-alert';

const SKIP_ACTION_PATTERNS = [
    /\/login(?:$|\?)/,
    /\/logout(?:$|\?)/,
    /\/forgot-password(?:$|\?)/,
    /\/reset-password(?:\/|$|\?)/,
];

function effectiveMethod(form) {
    const override = form.querySelector('input[name="_method"]')?.value;
    const method = form.getAttribute('method') || 'GET';

    return String(override || method || 'GET').toUpperCase();
}

function formMethod(form) {
    return String(form.getAttribute('method') || 'POST').toUpperCase();
}

function formAction(form) {
    const rawAction = form.getAttribute('action') || window.location.href;

    return new URL(rawAction, window.location.href).toString();
}

function shouldEnhance(form) {
    if (!(form instanceof HTMLFormElement)) return false;
    if (form.dataset.ajaxForm === 'false' || form.dataset.nativeSubmit === 'true') return false;

    const target = form.getAttribute('target') || '';
    if (target && target !== '_self') return false;

    const method = effectiveMethod(form);
    if (method === 'GET' || method === 'HEAD') return false;

    const action = formAction(form);
    if (!action) return false;

    return !SKIP_ACTION_PATTERNS.some((pattern) => pattern.test(action));
}

function formDataWithSubmitter(form, submitter) {
    try {
        return submitter ? new FormData(form, submitter) : new FormData(form);
    } catch (_) {
        const data = new FormData(form);

        if (submitter?.name && !data.has(submitter.name)) {
            data.append(submitter.name, submitter.value || '');
        }

        return data;
    }
}

function fieldNameCandidates(name) {
    const candidates = [name];

    if (name.includes('.')) {
        const parts = name.split('.');
        candidates.push(parts.shift() + parts.map((part) => `[${part}]`).join(''));
    }

    return candidates;
}

function queryField(form, name) {
    for (const candidate of fieldNameCandidates(name)) {
        const field = form.elements[candidate];

        if (field instanceof HTMLElement) {
            return field;
        }

        if (field?.[0] instanceof HTMLElement) {
            return field[0];
        }
    }

    return null;
}

function fieldWrapper(field) {
    return field.closest('.grid.gap-2, .flex.flex-col.gap-2, .grid.gap-3, .flex.flex-col')
        || field.parentElement;
}

function clearErrors(form) {
    form.querySelectorAll('[data-ajax-validation-error="true"]').forEach((node) => node.remove());
    form.querySelectorAll('.kt-input-invalid, .border-danger').forEach((field) => {
        field.classList.remove('kt-input-invalid', 'border-danger');
    });
}

function showFieldError(form, name, message) {
    const field = queryField(form, name);

    if (!field) return false;

    field.classList.add('kt-input-invalid', 'border-danger');

    const wrapper = fieldWrapper(field);
    if (!wrapper) return true;

    const error = document.createElement('div');
    error.className = 'text-xs text-danger';
    error.dataset.ajaxValidationError = 'true';
    error.textContent = message;
    wrapper.appendChild(error);

    return true;
}

function disableSubmitter(submitter, disabled) {
    if (!(submitter instanceof HTMLButtonElement) && !(submitter instanceof HTMLInputElement)) return;

    submitter.disabled = disabled;
    submitter.classList.toggle('opacity-60', disabled);
    submitter.classList.toggle('cursor-not-allowed', disabled);
}

function isSameLocation(url) {
    try {
        const target = new URL(url, window.location.origin);

        return target.pathname === window.location.pathname && target.search === window.location.search;
    } catch (_) {
        return false;
    }
}

function shouldFollowRedirect(form, payload) {
    if (!payload?.redirect_url || form.dataset.ajaxStay === 'true') return false;
    if (form.dataset.ajaxRedirect === 'true') return true;

    const method = effectiveMethod(form);

    if (method === 'POST' || method === 'PATCH' || method === 'DELETE') return true;

    return !isSameLocation(payload.redirect_url);
}

function followRedirect(url) {
    window.setTimeout(() => {
        if (isSameLocation(url)) {
            window.location.reload();
            return;
        }

        window.location.assign(url);
    }, 650);
}

function firstValidationMessage(data) {
    const errors = data?.errors || {};
    const first = Object.values(errors).flat()[0];

    return first || data?.message || 'Lütfen formdaki alanları kontrol edin.';
}

async function submitForm(form, submitter) {
    window.tinymce?.triggerSave?.();

    clearErrors(form);
    disableSubmitter(submitter, true);

    try {
        const payload = await request(formAction(form), {
            method: formMethod(form),
            data: formDataWithSubmitter(form, submitter),
            ignoreGlobalError: true,
        });

        const type = payload?.type || 'success';
        const message = payload?.message || 'İşlem başarıyla tamamlandı.';
        showToastMessage(type, message);

        if (type === 'error') {
            return;
        }

        form.dispatchEvent(new CustomEvent('ajax-form:success', {
            bubbles: true,
            detail: { payload, submitter },
        }));

        if (shouldFollowRedirect(form, payload)) {
            followRedirect(payload.redirect_url);
        }
    } catch (error) {
        if (error.status === 422) {
            const errors = error.data?.errors || {};
            let focused = false;

            Object.entries(errors).forEach(([name, messages]) => {
                const message = Array.isArray(messages) ? messages[0] : messages;
                const shown = showFieldError(form, name, message);

                if (shown && !focused) {
                    queryField(form, name)?.focus?.();
                    focused = true;
                }
            });

            showToastMessage('warning', firstValidationMessage(error.data), { title: 'Bilgileri kontrol edin' });
            return;
        }

        showToastMessage('error', error.message || 'İşlem tamamlanamadı.');
    } finally {
        disableSubmitter(submitter, false);
    }
}

export default function initAjaxForms() {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!shouldEnhance(form)) return;
        if (event.defaultPrevented) return;

        event.preventDefault();
        submitForm(form, event.submitter || document.activeElement);
    });
}
