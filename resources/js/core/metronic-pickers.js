const TR_LOCALE = {
    weekdays: {
        shorthand: ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt'],
        longhand: ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'],
    },
    months: {
        shorthand: ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'],
        longhand: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
    },
    firstDayOfWeek: 1,
    ordinal: () => '.',
    rangeSeparator: ' - ',
    weekAbbreviation: 'Hf',
    scrollTitle: 'Artırmak için kaydır',
    toggleTitle: 'Aç/Kapat',
    amPM: ['ÖÖ', 'ÖS'],
    time_24hr: true,
};

let observer = null;
let retryTimer = null;

function flatpickr() {
    return window.flatpickr || null;
}

function normalizeKtFormat(format, mode) {
    const raw = String(format || '').trim();

    if (!raw) {
        return mode === 'date' ? 'd.m.Y' : 'd.m.Y H:i';
    }

    return raw
        .replaceAll('YYYY', 'Y')
        .replaceAll('DD', 'd')
        .replaceAll('MM', 'm')
        .replaceAll('HH', 'H')
        .replaceAll('mm', 'i');
}

function prepareInput(input) {
    if (!(input instanceof HTMLInputElement)) return;

    const ktDatePicker = window.KTDatePicker;
    const instance = ktDatePicker?.getInstance?.(input);

    try {
        instance?.destroy?.();
        instance?.dispose?.();
    } catch (_) {
        // Best-effort cleanup for inputs that were initialized before app.js loaded.
    }

    input.removeAttribute('data-kt-date-picker');
    input.removeAttribute('data-kt-date-picker-input-mode');
    input.removeAttribute('data-kt-date-picker-position-to-input');
    input.removeAttribute('data-kt-date-picker-selection-time-mode');
    input.removeAttribute('data-kt-date-picker-locale');
    input.removeAttribute('data-kt-date-picker-first-weekday');
    input.removeAttribute('data-kt-date-picker-date-format');
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('aria-haspopup', 'dialog');
}

function pickerAnchor(input, instance = null) {
    const wrapper = input.parentElement?.classList.contains('kt-input') ? input.parentElement : null;

    return wrapper || instance?.altInput || input;
}

function positionPicker(input, instance) {
    const anchor = pickerAnchor(input, instance);
    const calendar = instance?.calendarContainer;

    if (!(anchor instanceof HTMLElement) || !(calendar instanceof HTMLElement)) return;

    const anchorRect = anchor.getBoundingClientRect();
    const calendarRect = calendar.getBoundingClientRect();
    const viewportPadding = 12;
    const maxLeft = Math.max(viewportPadding, window.innerWidth - calendarRect.width - viewportPadding);
    const left = Math.min(Math.max(anchorRect.left, viewportPadding), maxLeft);

    calendar.style.right = 'auto';
    calendar.style.left = `${left + window.scrollX}px`;
}

function createAction(label, action, variant = 'default') {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `app-date-picker__action app-date-picker__action--${variant}`;
    button.dataset.appDatePickerAction = action;
    button.textContent = label;

    return button;
}

function decoratePicker(input, instance, mode) {
    const calendar = instance.calendarContainer;
    if (!(calendar instanceof HTMLElement)) return;

    calendar.classList.add('app-date-picker');
    calendar.setAttribute('role', 'dialog');
    calendar.setAttribute(
        'aria-label',
        mode === 'datetime' ? 'Tarih ve saat seçimi' : mode === 'time' ? 'Saat seçimi' : 'Tarih seçimi',
    );

    if (instance.altInput instanceof HTMLInputElement) {
        instance.altInput.classList.add('app-date-picker__input');
        instance.altInput.setAttribute('aria-label', input.getAttribute('aria-label') || input.placeholder || 'Tarih seçin');
        instance.altInput.setAttribute('aria-haspopup', 'dialog');
        instance.altInput.setAttribute('autocomplete', 'off');
        instance.altInput.setAttribute('readonly', 'readonly');
    }

    if (calendar.querySelector('[data-app-date-picker-footer]')) return;

    const footer = document.createElement('div');
    footer.className = 'app-date-picker__footer';
    footer.dataset.appDatePickerFooter = 'true';

    const utilityActions = document.createElement('div');
    utilityActions.className = 'app-date-picker__footer-group';
    utilityActions.append(
        createAction(mode === 'time' ? 'Şimdi' : 'Bugün', 'today', 'primary'),
        createAction('Temizle', 'clear'),
    );

    const closeAction = createAction('Kapat', 'close', 'strong');
    footer.append(utilityActions, closeAction);

    footer.addEventListener('click', (event) => {
        const action = event.target.closest?.('[data-app-date-picker-action]')?.dataset.appDatePickerAction;

        if (action === 'today') {
            instance.setDate(new Date(), true);
            if (mode === 'date') instance.close();
        } else if (action === 'clear') {
            instance.clear();
            instance.close();
        } else if (action === 'close') {
            instance.close();
        }
    });

    calendar.appendChild(footer);
}

function initDatePicker(input) {
    if (!(input instanceof HTMLInputElement) || input.dataset.metronicPickerReady === 'true') return;

    const fp = flatpickr();
    if (!fp) return;

    const initialRawValue = input.value || input.dataset.initialValue || '';
    prepareInput(input);

    const mode = input.dataset.appDateMode === 'date' ? 'date' : 'datetime';
    const dateFormat = normalizeKtFormat(input.dataset.appDateFormat || input.dataset.ktDatePickerDateFormat, mode);

    const instance = fp(input, {
        allowInput: false,
        clickOpens: true,
        dateFormat,
        defaultDate: initialRawValue || null,
        disableMobile: true,
        enableTime: mode === 'datetime',
        locale: TR_LOCALE,
        minuteIncrement: Number(input.dataset.appTimeStep || 5),
        monthSelectorType: 'static',
        onClose: () => pickerAnchor(input)?.classList.remove('is-picker-open'),
        onOpen: (_, __, instance) => {
            pickerAnchor(input, instance)?.classList.add('is-picker-open');
            window.requestAnimationFrame(() => positionPicker(input, instance));
        },
        onReady: (_, __, instance) => {
            input._appDatePicker = instance;
            if (instance.input instanceof HTMLInputElement) instance.input._appDatePicker = instance;
            decoratePicker(input, instance, mode);
        },
        position: input.dataset.appDatePosition || 'auto',
        time_24hr: true,
    });

    if (instance?.setDate) {
        input._appDatePicker = instance;
        if (instance.input instanceof HTMLInputElement) instance.input._appDatePicker = instance;
    }

    input.dataset.metronicPickerReady = 'true';
}

function initTimePicker(input) {
    if (!(input instanceof HTMLInputElement) || input.dataset.metronicPickerReady === 'true') return;

    const fp = flatpickr();
    if (!fp) return;

    input.type = 'text';
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('readonly', 'readonly');

    fp(input, {
        allowInput: false,
        clickOpens: true,
        dateFormat: 'H:i',
        defaultDate: input.value || null,
        disableMobile: true,
        enableTime: true,
        locale: TR_LOCALE,
        minuteIncrement: Number(input.dataset.appTimeStep || 5),
        noCalendar: true,
        onClose: () => pickerAnchor(input)?.classList.remove('is-picker-open'),
        onOpen: (_, __, instance) => {
            pickerAnchor(input, instance)?.classList.add('is-picker-open');
            window.requestAnimationFrame(() => positionPicker(input, instance));
        },
        onReady: (_, __, instance) => {
            input._appDatePicker = instance;
            decoratePicker(input, instance, 'time');
        },
        position: input.dataset.appDatePosition || 'auto',
        time_24hr: true,
    });

    input.dataset.metronicPickerReady = 'true';
}

function initScope(scope = document) {
    if (!flatpickr()) {
        window.clearTimeout(retryTimer);
        retryTimer = window.setTimeout(() => initScope(scope), 80);
        return;
    }

    scope.querySelectorAll?.('[data-app-date-picker="true"]').forEach(initDatePicker);
    scope.querySelectorAll?.('[data-app-time-picker="true"]').forEach(initTimePicker);
}

export function initMetronicPickers(scope = document) {
    initScope(scope);

    if (observer) return;

    observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            mutation.addedNodes.forEach((node) => {
                if (!(node instanceof HTMLElement)) return;

                if (node.matches?.('[data-app-date-picker="true"], [data-app-time-picker="true"]')) {
                    initScope(node.parentElement || document);
                    return;
                }

                if (node.querySelector?.('[data-app-date-picker="true"], [data-app-time-picker="true"]')) {
                    initScope(node);
                }
            });
        }
    });

    observer.observe(document.documentElement, {
        childList: true,
        subtree: true,
    });
}
