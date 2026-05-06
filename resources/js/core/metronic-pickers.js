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
}

function initDatePicker(input) {
    if (!(input instanceof HTMLInputElement) || input.dataset.metronicPickerReady === 'true') return;

    const fp = flatpickr();
    if (!fp) return;

    prepareInput(input);

    const mode = input.dataset.appDateMode === 'date' ? 'date' : 'datetime';
    const dateFormat = normalizeKtFormat(input.dataset.appDateFormat || input.dataset.ktDatePickerDateFormat, mode);

    fp(input, {
        allowInput: false,
        clickOpens: true,
        dateFormat,
        defaultDate: input.value || input.dataset.initialValue || null,
        disableMobile: true,
        enableTime: mode === 'datetime',
        locale: TR_LOCALE,
        minuteIncrement: Number(input.dataset.appTimeStep || 5),
        time_24hr: true,
    });

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
