function pad(value) {
    return String(value).padStart(2, '0');
}

function inferMode(input) {
    return input?.dataset?.appDateMode === 'datetime' ? 'datetime' : 'date';
}

function parseMachineDate(value, mode) {
    const text = String(value || '').trim();

    if (!text) {
        return null;
    }

    const dateMatch = text.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (dateMatch) {
        const [, year, month, day] = dateMatch;

        if (mode === 'datetime') {
            return `${year}-${month}-${day}T00:00`;
        }

        return `${year}-${month}-${day}`;
    }

    const dateTimeMatch = text.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
    if (dateTimeMatch) {
        const [, year, month, day, hour, minute] = dateTimeMatch;

        if (mode === 'date') {
            return `${year}-${month}-${day}`;
        }

        return `${year}-${month}-${day}T${hour}:${minute}`;
    }

    const displayDateMatch = text.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
    if (displayDateMatch) {
        const [, day, month, year] = displayDateMatch;

        if (mode === 'datetime') {
            return `${year}-${month}-${day}T00:00`;
        }

        return `${year}-${month}-${day}`;
    }

    const displayDateTimeMatch = text.match(/^(\d{2})\.(\d{2})\.(\d{4})\s+(\d{2}):(\d{2})$/);
    if (displayDateTimeMatch) {
        const [, day, month, year, hour, minute] = displayDateTimeMatch;

        if (mode === 'date') {
            return `${year}-${month}-${day}`;
        }

        return `${year}-${month}-${day}T${hour}:${minute}`;
    }

    return null;
}

export function toMachineDateValue(value, mode = 'date') {
    return parseMachineDate(value, mode) || '';
}

export function toDisplayDateValue(value, mode = 'date') {
    const machineValue = parseMachineDate(value, mode);

    if (!machineValue) {
        return '';
    }

    const dateTimeMatch = machineValue.match(/^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2}))?$/);

    if (!dateTimeMatch) {
        return '';
    }

    const [, year, month, day, hour = '00', minute = '00'] = dateTimeMatch;

    if (mode === 'datetime') {
        return `${day}.${month}.${year} ${hour}:${minute}`;
    }

    return `${day}.${month}.${year}`;
}

export function getDateInputValue(input) {
    if (!input) {
        return '';
    }

    return toMachineDateValue(input.value, inferMode(input));
}

export function setDateInputValue(input, value) {
    if (!input) {
        return;
    }

    input.value = toDisplayDateValue(value, inferMode(input));
}

export function clearDateInputValue(input) {
    if (!input) {
        return;
    }

    input.value = '';
}

export function todayMachineDate() {
    const now = new Date();

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

export function initDateInputValues(scope = document) {
    scope.querySelectorAll?.('[data-app-date-picker="true"]').forEach((input) => {
        const initialValue = input.dataset.initialValue || input.value;

        if (!initialValue) {
            return;
        }

        setDateInputValue(input, initialValue);
    });
}
