export function initMemberRegistration(root = document) {
    const termsModal = root.querySelector('[data-membership-terms-modal]');
    const termsOpenButtons = root.querySelectorAll('[data-membership-terms-open]');
    const termsCloseButtons = root.querySelectorAll('[data-membership-terms-close]');
    const termsScrollable = root.querySelector('[data-membership-terms-scrollable]');
    const termsCheckbox = root.querySelector('[data-membership-terms-checkbox]');
    const termsReadInput = root.querySelector('[data-membership-terms-read-input]');
    const termsStatus = root.querySelector('[data-membership-terms-status]');
    const termsModalStatus = root.querySelector('[data-membership-terms-modal-status]');
    const registrationModal = root.querySelector('[data-registration-modal]');
    const registrationOpenButtons = root.querySelectorAll('[data-registration-modal-open]');
    const registrationCloseButtons = root.querySelectorAll('[data-registration-modal-close]');

    const unlockTermsAcceptance = () => {
        if (!termsCheckbox || !termsReadInput) return;

        termsReadInput.value = '1';
        termsCheckbox.disabled = false;

        if (termsStatus) {
            termsStatus.textContent = 'Bilgilendirme metni okundu. Kabul kutusu artık aktif.';
        }

        if (termsModalStatus) {
            termsModalStatus.textContent = 'Metin okundu. Formdaki kabul kutusu artık aktif.';
        }
    };

    const openTermsModal = () => {
        if (!termsModal) return;

        termsModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        if (termsScrollable && termsScrollable.scrollHeight - termsScrollable.clientHeight <= 24) {
            unlockTermsAcceptance();
        }
    };

    const closeTermsModal = () => {
        if (!termsModal) return;

        termsModal.classList.add('hidden');

        if (!registrationModal?.classList.contains('is-open')) {
            document.body.classList.remove('overflow-hidden');
        }
    };

    if (termsModal && termsCheckbox && termsReadInput) {
        if (termsReadInput.value === '1') {
            termsCheckbox.disabled = false;

            if (termsStatus) {
                termsStatus.textContent = 'Bilgilendirme metni daha önce okundu. Kabul kutusu aktif.';
            }

            if (termsModalStatus) {
                termsModalStatus.textContent = 'Metin zaten okundu. Formdaki kabul kutusu aktif.';
            }
        }

        termsOpenButtons.forEach((button) => button.addEventListener('click', openTermsModal));
        termsCloseButtons.forEach((button) => button.addEventListener('click', closeTermsModal));
        termsModal.addEventListener('click', (event) => {
            if (event.target === termsModal) closeTermsModal();
        });

        termsScrollable?.addEventListener('scroll', () => {
            const remaining = termsScrollable.scrollHeight - termsScrollable.scrollTop - termsScrollable.clientHeight;

            if (remaining <= 24) unlockTermsAcceptance();
        });
    }

    if (!registrationModal) return;

    const openRegistrationModal = (event) => {
        event?.preventDefault();
        registrationModal.classList.add('is-open');
        registrationModal.removeAttribute('inert');
        registrationModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        registrationModal.querySelector('input:not([type="hidden"])')?.focus();
    };

    const closeRegistrationModal = () => {
        registrationModal.classList.remove('is-open');
        registrationModal.setAttribute('inert', '');
        registrationModal.setAttribute('aria-hidden', 'true');

        if (!termsModal || termsModal.classList.contains('hidden')) {
            document.body.classList.remove('overflow-hidden');
        }
    };

    registrationOpenButtons.forEach((button) => button.addEventListener('click', openRegistrationModal));
    registrationCloseButtons.forEach((button) => button.addEventListener('click', closeRegistrationModal));

    if (registrationModal.classList.contains('is-open')) {
        document.body.classList.add('overflow-hidden');
    }

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !registrationModal.classList.contains('is-open')) return;

        if (termsModal && !termsModal.classList.contains('hidden')) {
            closeTermsModal();
            return;
        }

        closeRegistrationModal();
    });
}
