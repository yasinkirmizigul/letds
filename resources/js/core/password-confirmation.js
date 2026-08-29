const PASSWORD_CONFIRMATION_MESSAGE = 'Şifre tekrarı yeni şifre ile aynı olmalı.';

function confirmationMessageElement(confirmation, index) {
    const document = confirmation.ownerDocument;
    const message = document.createElement('div');
    const messageId = `${confirmation.id || `password_confirmation_${index}`}_match_message`;

    message.id = messageId;
    message.className = 'rounded-lg border border-warning/30 bg-warning/10 px-3 py-2 text-xs text-warning hidden';
    message.dataset.passwordConfirmationMessage = 'true';
    message.setAttribute('aria-live', 'polite');
    message.textContent = PASSWORD_CONFIRMATION_MESSAGE;

    const describedBy = new Set((confirmation.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
    describedBy.add(messageId);
    confirmation.setAttribute('aria-describedby', [...describedBy].join(' '));

    return message;
}

function setSubmitState(buttons, invalid) {
    buttons.forEach((button) => {
        if (invalid) {
            if (!button.disabled) {
                button.disabled = true;
                button.dataset.passwordConfirmationDisabled = 'true';
            }

            return;
        }

        if (button.dataset.passwordConfirmationDisabled === 'true') {
            button.disabled = false;
            delete button.dataset.passwordConfirmationDisabled;
        }
    });
}

export function initPasswordConfirmationValidation(root = document) {
    const confirmations = root.querySelectorAll('input[name="password_confirmation"]');

    confirmations.forEach((confirmation, index) => {
        if (!(confirmation instanceof HTMLInputElement)) return;

        // Bu iki ekranın kendilerine özgü doğrulama ve gönderim akışları zaten var.
        if (confirmation.matches('[data-password-confirmation-input], [data-admin-reset-password-confirmation]')) return;

        const form = confirmation.closest('form');
        const password = form?.querySelector('input[name="password"]');

        if (!(form instanceof HTMLFormElement) || !(password instanceof HTMLInputElement)) return;
        if (form.dataset.passwordConfirmationBound === 'true') return;

        form.dataset.passwordConfirmationBound = 'true';

        const message = confirmationMessageElement(confirmation, index);
        const messageContainer = confirmation.closest('.grid.gap-2, .flex.flex-col.gap-1, .flex.flex-col.gap-2')
            || confirmation.parentElement;
        const submitButtons = [...form.querySelectorAll('button[type="submit"], input[type="submit"]')]
            .filter((button) => button.form === form);

        messageContainer?.appendChild(message);

        const sync = ({ showMissing = false } = {}) => {
            const passwordValue = password.value || '';
            const confirmationValue = confirmation.value || '';
            const hasMismatch = Boolean(confirmationValue && passwordValue !== confirmationValue);
            const isMissingConfirmation = Boolean(showMissing && passwordValue && !confirmationValue);
            const invalid = hasMismatch || isMissingConfirmation;

            confirmation.setCustomValidity(invalid ? PASSWORD_CONFIRMATION_MESSAGE : '');
            confirmation.setAttribute('aria-invalid', invalid ? 'true' : 'false');
            confirmation.classList.toggle('border-warning', invalid);
            message.classList.toggle('hidden', !invalid);
            setSubmitState(submitButtons, invalid);

            return !invalid;
        };

        password.addEventListener('input', () => sync());
        confirmation.addEventListener('input', () => sync());
        confirmation.addEventListener('blur', () => sync({ showMissing: true }));

        form.addEventListener('submit', (event) => {
            if (sync({ showMissing: true })) return;

            event.preventDefault();
            event.stopImmediatePropagation();
            confirmation.reportValidity();
            confirmation.focus();
        }, true);

        form.addEventListener('reset', () => {
            window.setTimeout(() => sync(), 0);
        });

        sync();
    });
}
