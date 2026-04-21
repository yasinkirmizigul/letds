import { showToastMessage } from '@/core/swal-alert';

document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('contact-message-page');
    if (!page) return;

    const successMessage = (page.dataset.successMessage || '').trim();
    if (successMessage) {
        showToastMessage('success', successMessage, {
            title: 'Mesaj iletildi',
            duration: 2200,
        });
    }

    if (page.dataset.isMember === '1') {
        return;
    }

    const channelInputs = Array.from(page.querySelectorAll('[data-contact-channel]'));
    const fieldWrappers = {
        email: page.querySelector('[data-contact-field="email"]'),
        phone: page.querySelector('[data-contact-field="phone"]'),
    };

    const syncContactFields = () => {
        channelInputs.forEach((input) => {
            const channel = input.getAttribute('data-contact-channel');
            const wrapper = fieldWrappers[channel];
            const field = wrapper?.querySelector('input');
            const isChecked = Boolean(input.checked);

            if (!wrapper || !field) return;

            wrapper.classList.toggle('hidden', !isChecked);
            field.required = isChecked;
            field.setAttribute('aria-required', isChecked ? 'true' : 'false');
        });
    };

    channelInputs.forEach((input) => {
        input.addEventListener('change', syncContactFields);
    });

    syncContactFields();
});
