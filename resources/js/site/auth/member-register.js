const root = document;

const modal = root.querySelector('[data-membership-terms-modal]');
const openButtons = root.querySelectorAll('[data-membership-terms-open]');
const closeButtons = root.querySelectorAll('[data-membership-terms-close]');
const scrollable = root.querySelector('[data-membership-terms-scrollable]');
const checkbox = root.querySelector('[data-membership-terms-checkbox]');
const readInput = root.querySelector('[data-membership-terms-read-input]');
const status = root.querySelector('[data-membership-terms-status]');
const modalStatus = root.querySelector('[data-membership-terms-modal-status]');

if (modal && checkbox && readInput) {
    const setReadState = () => {
        readInput.value = '1';
        checkbox.disabled = false;

        if (status) {
            status.textContent = 'Bilgilendirme metni okundu. Kabul kutusu artık aktif.';
        }

        if (modalStatus) {
            modalStatus.textContent = 'Metin okundu. Formdaki kabul kutusu artık aktif.';
        }
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        if (scrollable) {
            const remaining = scrollable.scrollHeight - scrollable.clientHeight;

            if (remaining <= 24) {
                setReadState();
            }
        }
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    if (readInput.value === '1') {
        checkbox.disabled = false;

        if (status) {
            status.textContent = 'Bilgilendirme metni daha önce okundu. Kabul kutusu aktif.';
        }

        if (modalStatus) {
            modalStatus.textContent = 'Metin zaten okundu. Formdaki kabul kutusu aktif.';
        }
    }

    openButtons.forEach((button) => {
        button.addEventListener('click', openModal);
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    if (scrollable) {
        scrollable.addEventListener('scroll', () => {
            const remaining = scrollable.scrollHeight - scrollable.scrollTop - scrollable.clientHeight;

            if (remaining <= 24) {
                setReadState();
            }
        });
    }
}
