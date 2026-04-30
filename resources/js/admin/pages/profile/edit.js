import { showConfirmDialog } from '@/core/swal-alert';

export default {
    init() {
        if (window.__letdsProfileEditBound) return;
        window.__letdsProfileEditBound = true;

        const form = document.querySelector('[data-page="profile.edit"] [data-profile-form="true"]');
        const passwordInput = form?.querySelector('[data-password-input="true"]');
        const confirmationInput = form?.querySelector('[data-password-confirmation-input="true"]');
        const confirmationMessage = form?.querySelector('[data-password-confirmation-message="true"]');
        const submitButton = form?.querySelector('[data-profile-submit="true"]');

        const fieldElement = (name) => {
            const field = form?.elements?.[name];
            return field instanceof HTMLElement ? field : field?.[0] || null;
        };

        const clearClientErrors = () => {
            form?.querySelectorAll('[data-client-validation-error="true"]').forEach((item) => item.remove());
            form?.querySelectorAll('.kt-input-invalid, .border-danger').forEach((item) => {
                item.classList.remove('kt-input-invalid', 'border-danger');
            });
        };

        const showFieldError = (name, message) => {
            const field = fieldElement(name);
            if (!field) return;

            const wrapper = field.closest('.flex.flex-col.gap-2') || field.parentElement;
            field.classList.add('kt-input-invalid', 'border-danger');

            if (!wrapper) return;

            const error = document.createElement('div');
            error.className = 'text-xs text-danger';
            error.dataset.clientValidationError = 'true';
            error.textContent = message;
            wrapper.appendChild(error);
        };

        const syncPasswordConfirmation = (force = false) => {
            if (!passwordInput || !confirmationInput) return true;

            const password = passwordInput.value || '';
            const confirmation = confirmationInput.value || '';
            const hasMismatch = Boolean(password && confirmation && password !== confirmation);
            const isMissingConfirmation = Boolean(force && password && !confirmation);
            const invalid = hasMismatch || isMissingConfirmation;

            confirmationInput.setCustomValidity(invalid ? 'Şifre tekrarı yeni şifre ile aynı olmalı.' : '');
            confirmationInput.classList.toggle('border-warning', invalid);
            confirmationMessage?.classList.toggle('hidden', !invalid);

            if (submitButton) {
                submitButton.disabled = hasMismatch;
                submitButton.classList.toggle('opacity-60', hasMismatch);
                submitButton.classList.toggle('cursor-not-allowed', hasMismatch);
            }

            return !invalid;
        };

        const submitPasswordChange = async () => {
            if (!form || !submitButton) return;

            clearClientErrors();
            syncPasswordConfirmation();
            submitButton.disabled = true;
            submitButton.classList.add('opacity-60', 'cursor-not-allowed');

            try {
                const action = new URL(form.getAttribute('action') || window.location.href, window.location.href).toString();

                const response = await fetch(action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                });

                const payload = await response.json().catch(() => ({}));

                if (response.status === 422) {
                    const errors = payload.errors || {};
                    const firstMessage = Object.values(errors).flat()[0] || 'Lütfen formdaki alanları kontrol edin.';

                    Object.entries(errors).forEach(([name, messages]) => {
                        showFieldError(name, Array.isArray(messages) ? messages[0] : messages);
                    });

                    await showConfirmDialog({
                        type: 'warning',
                        title: 'Bilgileri kontrol edin',
                        message: firstMessage,
                        confirmButtonText: 'Tamam',
                        showCancelButton: false,
                    });
                    return;
                }

                if (!response.ok) {
                    throw new Error(payload.message || 'Şifre değiştirme işlemi tamamlanamadı.');
                }

                await showConfirmDialog({
                    type: 'success',
                    title: 'Şifre değiştirildi',
                    message: payload.message || 'Şifreniz başarıyla değiştirildi. Güvenlik için tekrar giriş yapmanız gerekiyor.',
                    confirmButtonText: 'Çıkış Yap ve Giriş Sayfasına Git',
                    showCancelButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                });

                window.location.assign(payload.redirect_url || '/login');
            } catch (error) {
                await showConfirmDialog({
                    type: 'error',
                    title: 'İşlem tamamlanamadı',
                    message: error.message || 'Beklenmeyen bir hata oluştu.',
                    confirmButtonText: 'Tamam',
                    showCancelButton: false,
                });
            } finally {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        };

        passwordInput?.addEventListener('input', syncPasswordConfirmation);
        confirmationInput?.addEventListener('input', syncPasswordConfirmation);
        confirmationInput?.addEventListener('blur', syncPasswordConfirmation);
        form?.addEventListener('submit', (event) => {
            if (!syncPasswordConfirmation(true)) {
                event.preventDefault();
                confirmationInput?.reportValidity();
                confirmationInput?.focus();
                return;
            }

            if (passwordInput?.value) {
                event.preventDefault();
                submitPasswordChange();
            }
        });

        document.addEventListener('click', (event) => {
            const item = event.target.closest('[data-media-id]');
            if (!item) return;

            const mediaId = item.getAttribute('data-media-id');
            const input = document.getElementById('avatar_media_id');
            const form = document.getElementById('avatarForm');

            if (!mediaId || !input || !form) return;

            const fileInput = form.querySelector('input[type="file"][name="avatar_file"]');
            if (fileInput?.files?.length) {
                fileInput.value = '';
            }

            input.value = mediaId;
        });
    }
};
