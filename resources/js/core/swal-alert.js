import Swal from 'sweetalert2';

const TYPE_META = {
    success: {
        icon: 'success',
        title: 'İşlem tamamlandı',
        variant: 'success',
        color: '#17c653',
    },
    error: {
        icon: 'error',
        title: 'Bir sorun oluştu',
        variant: 'error',
        color: '#f1416c',
    },
    warning: {
        icon: 'warning',
        title: 'Dikkat',
        variant: 'warning',
        color: '#f6b100',
    },
    info: {
        icon: 'info',
        title: 'Bilgi',
        variant: 'info',
        color: '#3e97ff',
    },
};

function resolveSwal() {
    if (Swal?.fire) return Swal;
    if (window.Swal?.fire) return window.Swal;
    if (window.swal?.fire) return window.swal;
    return null;
}

function normalizeText(value, fallback = '') {
    if (typeof value === 'string') {
        const trimmed = value.trim();
        if (trimmed !== '') return trimmed;
    }

    if (value === null || value === undefined) return fallback;

    const normalized = String(value).trim();
    return normalized || fallback;
}

function getMeta(type) {
    return TYPE_META[type] || TYPE_META.info;
}

function buildConfirmButtonClass(type) {
    return `kt-btn swal2-kt-button swal2-kt-confirm swal2-kt-confirm--${type}`;
}

function buildToastClass(type) {
    return `swal2-kt-toast swal2-kt-toast--${type}`;
}

export function showToastMessage(type, message, options = {}) {
    const swal = resolveSwal();
    const meta = getMeta(type);
    const title = normalizeText(options.title, meta.title);
    const text = normalizeText(message, '');

    if (!swal) {
        console.warn('SweetAlert2 bulunamadı. Toast gösterilemedi.');
        return Promise.resolve();
    }

    const toast = swal.mixin({
        toast: true,
        position: options.position || 'top-end',
        showConfirmButton: false,
        timer: options.duration ?? 3200,
        timerProgressBar: options.progress ?? true,
        customClass: {
            popup: buildToastClass(meta.variant),
            title: 'swal2-kt-toast-title',
            htmlContainer: 'swal2-kt-toast-text',
            timerProgressBar: `swal2-kt-toast-progress swal2-kt-toast-progress--${meta.variant}`,
        },
        didOpen: (toastElement) => {
            toastElement.addEventListener('mouseenter', () => swal.stopTimer?.());
            toastElement.addEventListener('mouseleave', () => swal.resumeTimer?.());
        },
    });

    return toast.fire({
        icon: meta.icon,
        iconColor: meta.color,
        title,
        text: text && text !== title ? text : undefined,
    });
}

export const showAlertMessage = showToastMessage;

export async function showConfirmDialog(options = {}) {
    const swal = resolveSwal();
    const meta = getMeta(options.type || 'warning');
    const title = normalizeText(options.title, meta.title);
    const message = normalizeText(options.message, '');

    if (!swal) {
        console.warn('SweetAlert2 bulunamadı. Confirm gösterilemedi.');
        return false;
    }

    const result = await swal.fire({
        icon: meta.icon,
        iconColor: meta.color,
        title,
        text: message || undefined,
        showCancelButton: options.showCancelButton ?? true,
        showDenyButton: options.showDenyButton ?? false,
        confirmButtonText: options.confirmButtonText || 'Tamam',
        denyButtonText: options.denyButtonText || 'Hayır',
        cancelButtonText: options.cancelButtonText || 'Vazgeç',
        reverseButtons: true,
        buttonsStyling: false,
        allowOutsideClick: options.allowOutsideClick ?? false,
        allowEscapeKey: true,
        backdrop: 'rgba(15, 23, 42, 0.52)',
        customClass: {
            popup: `swal2-kt-popup swal2-kt-popup--${meta.variant}`,
            title: 'swal2-kt-title',
            htmlContainer: 'swal2-kt-text',
            actions: 'swal2-kt-actions',
            confirmButton: buildConfirmButtonClass(meta.variant),
            cancelButton: 'kt-btn swal2-kt-button swal2-kt-cancel',
            denyButton: 'kt-btn swal2-kt-button swal2-kt-deny',
        },
    });

    return Boolean(result.isConfirmed);
}
