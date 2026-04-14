const TYPE_META = {
    success: {
        icon: 'success',
        title: 'Islem tamamlandi',
        variant: 'success',
        color: '#16a34a',
        softColor: '#ecfdf5',
        borderColor: '#bbf7d0',
        iconSvg: `
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
                <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        `,
    },
    error: {
        icon: 'error',
        title: 'Bir sorun olustu',
        variant: 'error',
        color: '#dc2626',
        softColor: '#fef2f2',
        borderColor: '#fecaca',
        iconSvg: `
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
                <path d="M15 9l-6 6" stroke="currentColor" stroke-width="2.25" stroke-linecap="round"></path>
                <path d="M9 9l6 6" stroke="currentColor" stroke-width="2.25" stroke-linecap="round"></path>
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"></circle>
            </svg>
        `,
    },
    warning: {
        icon: 'warning',
        title: 'Dikkat',
        variant: 'warning',
        color: '#d97706',
        softColor: '#fff7ed',
        borderColor: '#fed7aa',
        iconSvg: `
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
                <path d="M12 8v5" stroke="currentColor" stroke-width="2.25" stroke-linecap="round"></path>
                <path d="M12 16.5h.01" stroke="currentColor" stroke-width="2.25" stroke-linecap="round"></path>
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2"></path>
            </svg>
        `,
    },
    info: {
        icon: 'info',
        title: 'Bilgi',
        variant: 'info',
        color: '#2563eb',
        softColor: '#eff6ff',
        borderColor: '#bfdbfe',
        iconSvg: `
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
                <path d="M12 10v6" stroke="currentColor" stroke-width="2.25" stroke-linecap="round"></path>
                <path d="M12 7.5h.01" stroke="currentColor" stroke-width="2.25" stroke-linecap="round"></path>
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"></circle>
            </svg>
        `,
    },
}

const DOM_TOAST_CONTAINERS = new Map()
let ACTIVE_CONFIRM_DIALOG = null

function resolveSwal() {
    if (window.Swal?.fire) return window.Swal
    if (window.swal?.fire) return window.swal
    if (typeof Swal !== 'undefined' && Swal?.fire) return Swal
    if (typeof swal !== 'undefined' && swal?.fire) return swal

    return null
}

function resolveKtToast() {
    if (window.KTToast?.show) return window.KTToast
    if (typeof KTToast !== 'undefined' && KTToast?.show) return KTToast

    return null
}

function resolveKtNotify() {
    if (window.KTNotify?.show) return window.KTNotify
    if (typeof KTNotify !== 'undefined' && KTNotify?.show) return KTNotify

    return null
}

function normalizeText(value, fallback = '') {
    if (typeof value === 'string') {
        const trimmed = value.trim()
        if (trimmed !== '') return trimmed
    }

    if (value === null || value === undefined) return fallback

    const normalized = String(value).trim()
    return normalized || fallback
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
}

function buildToastMessageHtml(title, message) {
    const safeTitle = escapeHtml(title)
    const safeMessage = escapeHtml(message)
    const hasDescription = safeMessage !== '' && safeMessage !== safeTitle

    return `
        <div style="display:flex;flex-direction:column;gap:4px;min-width:0;">
            <div style="font-size:0.95rem;line-height:1.35rem;font-weight:700;color:#0f172a;">${safeTitle}</div>
            ${hasDescription ? `<div style="font-size:0.84rem;line-height:1.45rem;color:#475569;">${safeMessage}</div>` : ''}
        </div>
    `
}

function buildToastIconHtml(meta) {
    return `
        <div style="display:flex;align-items:center;justify-content:center;width:2.5rem;height:2.5rem;border-radius:999px;background:${meta.softColor};color:${meta.color};flex-shrink:0;">
            ${meta.iconSvg}
        </div>
    `
}

function styleToastElement(toast, meta) {
    if (!toast) return

    toast.style.borderRadius = '20px'
    toast.style.border = `1px solid ${meta.borderColor}`
    toast.style.background = '#ffffff'
    toast.style.boxShadow = '0 18px 40px rgba(15, 23, 42, 0.12)'
    toast.style.padding = '1rem 1.05rem'
    toast.style.alignItems = 'flex-start'
    toast.style.gap = '0.85rem'

    const iconWrap = toast.querySelector('.kt-alert-icon')
    if (iconWrap) {
        iconWrap.style.margin = '0'
        iconWrap.style.flex = '0 0 auto'
        iconWrap.style.alignSelf = 'flex-start'
    }

    const titleWrap = toast.querySelector('.kt-alert-title')
    if (titleWrap) {
        titleWrap.style.flex = '1 1 auto'
        titleWrap.style.minWidth = '0'
        titleWrap.style.margin = '0'
    }

    const toolbarWrap = toast.querySelector('.kt-alert-toolbar')
    if (toolbarWrap) {
        toolbarWrap.style.marginInlineStart = '0.25rem'
        toolbarWrap.style.alignSelf = 'flex-start'
    }

    const closeButton = toast.querySelector('.kt-alert-close, [data-kt-toast-dismiss], [data-toast-dismiss]')
    if (closeButton) {
        closeButton.style.display = 'inline-flex'
        closeButton.style.alignItems = 'center'
        closeButton.style.justifyContent = 'center'
        closeButton.style.width = '2rem'
        closeButton.style.height = '2rem'
        closeButton.style.border = '0'
        closeButton.style.borderRadius = '999px'
        closeButton.style.background = 'transparent'
        closeButton.style.color = '#64748b'
        closeButton.style.margin = '-0.2rem -0.25rem 0 0'
    }

    const progress = toast.querySelector('.kt-toast-progress')
    if (progress) {
        progress.style.backgroundColor = meta.color
        progress.style.height = '3px'
    }
}

function styleConfirmPopup(popup, color) {
    popup.style.width = '34rem'
    popup.style.maxWidth = 'calc(100vw - 2rem)'
    popup.style.borderRadius = '24px'
    popup.style.padding = '1.75rem'

    const titleEl = popup.querySelector('.swal2-title')
    if (titleEl) {
        titleEl.style.fontSize = '1.35rem'
        titleEl.style.fontWeight = '700'
        titleEl.style.lineHeight = '1.35'
        titleEl.style.margin = '0.35rem 0 0'
        titleEl.style.color = '#0f172a'
    }

    const textEl = popup.querySelector('.swal2-html-container')
    if (textEl) {
        textEl.style.fontSize = '0.98rem'
        textEl.style.lineHeight = '1.7'
        textEl.style.margin = '0.9rem 0 0'
        textEl.style.color = '#475569'
    }

    const confirmButton = popup.querySelector('.swal2-confirm')
    if (confirmButton) {
        confirmButton.style.backgroundColor = color
        confirmButton.style.color = '#ffffff'
        confirmButton.style.border = '0'
        confirmButton.style.borderRadius = '14px'
        confirmButton.style.padding = '0.8rem 1.4rem'
        confirmButton.style.fontSize = '0.95rem'
        confirmButton.style.fontWeight = '600'
        confirmButton.style.boxShadow = `0 10px 24px ${color}33`
    }

    const cancelButton = popup.querySelector('.swal2-cancel')
    if (cancelButton) {
        cancelButton.style.backgroundColor = '#e2e8f0'
        cancelButton.style.color = '#0f172a'
        cancelButton.style.border = '0'
        cancelButton.style.borderRadius = '14px'
        cancelButton.style.padding = '0.8rem 1.4rem'
        cancelButton.style.fontSize = '0.95rem'
        cancelButton.style.fontWeight = '600'
    }
}

function ensureConfirmModalStyles() {
    if (document.getElementById('codex-confirm-modal-styles')) return

    const style = document.createElement('style')
    style.id = 'codex-confirm-modal-styles'
    style.textContent = `
        .codex-confirm-modal {
            opacity: 0;
            transition: opacity 180ms ease;
        }

        .codex-confirm-modal.is-open {
            opacity: 1;
        }

        .codex-confirm-modal .codex-confirm-content {
            opacity: 0;
            transform: translateY(18px) scale(0.98);
            transition: transform 180ms ease, opacity 180ms ease;
        }

        .codex-confirm-modal.is-open .codex-confirm-content {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .codex-confirm-modal.is-closing {
            opacity: 0;
        }

        .codex-confirm-modal.is-closing .codex-confirm-content {
            opacity: 0;
            transform: translateY(14px) scale(0.98);
        }
    `

    document.head.appendChild(style)
}

function buildConfirmIconHtml(meta) {
    return `
        <div style="display:flex;align-items:center;justify-content:center;width:3.4rem;height:3.4rem;border-radius:999px;background:${meta.softColor};color:${meta.color};flex-shrink:0;">
            <div style="width:1.45rem;height:1.45rem;display:flex;align-items:center;justify-content:center;">
                ${meta.iconSvg}
            </div>
        </div>
    `
}

function getFocusableElements(root) {
    if (!root) return []

    return Array.from(
        root.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'),
    ).filter((element) => {
        if (!(element instanceof HTMLElement)) return false
        if (element.hasAttribute('disabled')) return false
        if (element.getAttribute('aria-hidden') === 'true') return false
        return true
    })
}

function closeActiveConfirmDialog(result = false) {
    const state = ACTIVE_CONFIRM_DIALOG
    if (!state || state.closed) return

    state.closed = true
    ACTIVE_CONFIRM_DIALOG = null

    document.removeEventListener('keydown', state.handleKeydown, true)

    const restoreFocus = state.restoreFocus
    const bodyOverflow = state.bodyOverflow
    const modal = state.modal

    modal.classList.remove('is-open')
    modal.classList.add('is-closing')
    modal.setAttribute('aria-hidden', 'true')

    window.setTimeout(() => {
        modal.remove()

        if (!ACTIVE_CONFIRM_DIALOG && document.body && document.body.style.overflow === 'hidden') {
            document.body.style.overflow = bodyOverflow
        }

        if (!ACTIVE_CONFIRM_DIALOG) {
            try {
                restoreFocus?.focus?.({ preventScroll: true })
            } catch {}
        }
    }, 180)

    state.resolve(Boolean(result))
}

function showConfirmViaDom(meta, title, message, options = {}) {
    if (!document.body) return Promise.resolve(false)

    closeActiveConfirmDialog(false)
    ensureConfirmModalStyles()

    const modal = document.createElement('div')
    const titleId = `codex-confirm-title-${Date.now()}`
    const descriptionId = message ? `codex-confirm-description-${Date.now()}` : ''

    modal.className = 'kt-modal open codex-confirm-modal'
    modal.setAttribute('role', 'dialog')
    modal.setAttribute('aria-modal', 'true')
    modal.setAttribute('aria-hidden', 'false')
    modal.setAttribute('aria-labelledby', titleId)
    if (descriptionId) {
        modal.setAttribute('aria-describedby', descriptionId)
    }

    modal.style.zIndex = '2000'
    modal.style.display = 'flex'
    modal.style.alignItems = 'center'
    modal.style.justifyContent = 'center'
    modal.style.padding = '1rem'
    modal.style.overflow = 'hidden'

    modal.innerHTML = `
        <div class="kt-modal-backdrop" data-confirm-backdrop="true"></div>
        <div class="kt-modal-content codex-confirm-content" style="position:relative;z-index:1;width:min(34rem, calc(100vw - 2rem));max-width:34rem;border-radius:24px;border:1px solid ${meta.borderColor};box-shadow:0 28px 60px rgba(15, 23, 42, 0.18);overflow:hidden;">
            <div class="kt-modal-header" style="align-items:flex-start;gap:1rem;padding:1.5rem 1.5rem 0.5rem;border-bottom:none;">
                ${buildConfirmIconHtml(meta)}
                <div style="flex:1 1 auto;min-width:0;padding-top:0.1rem;">
                    <h3 id="${titleId}" class="kt-modal-title" style="margin:0;font-size:1.35rem;line-height:1.35;font-weight:700;color:#0f172a;">${escapeHtml(title)}</h3>
                </div>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-confirm-close="true" aria-label="Kapat" style="margin-top:-0.25rem;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
                        <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                        <path d="M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </button>
            </div>
            <div class="kt-modal-body" style="padding:0.35rem 1.5rem 1.5rem;">
                ${message ? `<p id="${descriptionId}" style="margin:0;font-size:0.98rem;line-height:1.7;color:#475569;">${escapeHtml(message)}</p>` : ''}
            </div>
            <div class="kt-modal-footer" style="justify-content:flex-end;gap:0.75rem;padding:1rem 1.5rem 1.5rem;border-top:none;">
                <button type="button" class="kt-btn kt-btn-light" data-confirm-cancel="true" style="min-width:8.5rem;font-size:0.95rem;font-weight:600;">${escapeHtml(options.cancelButtonText || 'Vazgec')}</button>
                <button type="button" class="kt-btn" data-confirm-approve="true" style="min-width:8.5rem;font-size:0.95rem;font-weight:600;background:${meta.color};border-color:${meta.color};color:#ffffff;box-shadow:0 12px 28px; ${meta.color}33;">${escapeHtml(options.confirmButtonText || 'Evet')}</button>
            </div>
        </div>
    `

    document.body.appendChild(modal)

    const confirmButton = modal.querySelector('[data-confirm-approve="true"]')
    const cancelButton = modal.querySelector('[data-confirm-cancel="true"]')
    const closeButton = modal.querySelector('[data-confirm-close="true"]')
    const backdrop = modal.querySelector('[data-confirm-backdrop="true"]')
    const restoreFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null
    const bodyOverflow = document.body.style.overflow

    document.body.style.overflow = 'hidden'

    const handleKeydown = (event) => {
        if (!ACTIVE_CONFIRM_DIALOG || ACTIVE_CONFIRM_DIALOG.modal !== modal) return

        if (event.key === 'Escape') {
            event.preventDefault()
            closeActiveConfirmDialog(false)
            return
        }

        if (event.key !== 'Tab') return

        const focusableElements = getFocusableElements(modal)
        if (!focusableElements.length) return

        const firstElement = focusableElements[0]
        const lastElement = focusableElements[focusableElements.length - 1]
        const activeElement = document.activeElement

        if (event.shiftKey && activeElement === firstElement) {
            event.preventDefault()
            lastElement.focus()
        } else if (!event.shiftKey && activeElement === lastElement) {
            event.preventDefault()
            firstElement.focus()
        }
    }

    ACTIVE_CONFIRM_DIALOG = {
        modal,
        resolve: () => {},
        closed: false,
        handleKeydown,
        restoreFocus,
        bodyOverflow,
    }

    const resultPromise = new Promise((resolve) => {
        ACTIVE_CONFIRM_DIALOG.resolve = resolve
    })

    confirmButton?.addEventListener('click', () => closeActiveConfirmDialog(true))
    cancelButton?.addEventListener('click', () => closeActiveConfirmDialog(false))
    closeButton?.addEventListener('click', () => closeActiveConfirmDialog(false))
    backdrop?.addEventListener('click', () => {
        if (options.allowOutsideClick) {
            closeActiveConfirmDialog(false)
        }
    })

    document.addEventListener('keydown', handleKeydown, true)

    window.requestAnimationFrame(() => {
        modal.classList.add('is-open')
        if (confirmButton instanceof HTMLElement) {
            confirmButton.focus({ preventScroll: true })
        }
    })

    return resultPromise
}

function styleSwalToast(popup, meta) {
    popup.style.width = '24rem'
    popup.style.maxWidth = 'calc(100vw - 1.5rem)'
    popup.style.borderRadius = '20px'
    popup.style.padding = '1rem 1.05rem'
    popup.style.border = `1px solid ${meta.borderColor}`
    popup.style.background = '#ffffff'
    popup.style.boxShadow = '0 18px 40px rgba(15, 23, 42, 0.12)'

    const iconWrap = popup.querySelector('.swal2-icon')
    if (iconWrap) {
        iconWrap.style.width = '2.5rem'
        iconWrap.style.height = '2.5rem'
        iconWrap.style.margin = '0'
        iconWrap.style.border = '0'
        iconWrap.style.background = meta.softColor
        iconWrap.style.borderRadius = '999px'
        iconWrap.style.color = meta.color
    }

    const htmlWrap = popup.querySelector('.swal2-html-container')
    if (htmlWrap) {
        htmlWrap.style.margin = '0'
        htmlWrap.style.padding = '0'
        htmlWrap.style.textAlign = 'left'
        htmlWrap.style.fontSize = 'inherit'
    }

    const timerBar = popup.querySelector('.swal2-timer-progress-bar')
    if (timerBar) {
        timerBar.style.background = meta.color
    }
}

function closeDomToast(toast, container) {
    if (!toast || toast.dataset.closing === '1') return

    toast.dataset.closing = '1'

    const timeoutId = Number(toast.dataset.timeoutId || 0)
    if (timeoutId) {
        window.clearTimeout(timeoutId)
    }

    toast.style.animation = 'kt-toast-out 0.2s ease forwards'

    window.setTimeout(() => {
        toast.remove()
        updateDomToastLayout(container)

        if (container.childElementCount === 0) {
            DOM_TOAST_CONTAINERS.delete(container.dataset.position || 'top-end')
            container.remove()
        }
    }, 180)
}

function setToastOffset(toast, position, offset) {
    toast.style.top = ''
    toast.style.bottom = ''
    toast.style.left = ''
    toast.style.right = ''
    toast.style.insetInlineStart = ''
    toast.style.insetInlineEnd = ''
    toast.style.transform = ''

    switch (position) {
    case 'top-start':
        toast.style.top = `${offset}px`
        toast.style.insetInlineStart = '15px'
        break
    case 'top-center':
        toast.style.top = `${offset}px`
        toast.style.left = '50%'
        toast.style.transform = 'translateX(-50%)'
        break
    case 'bottom-start':
        toast.style.bottom = `${offset}px`
        toast.style.insetInlineStart = '15px'
        break
    case 'bottom-center':
        toast.style.bottom = `${offset}px`
        toast.style.left = '50%'
        toast.style.transform = 'translateX(-50%)'
        break
    default:
        if (String(position).startsWith('bottom')) {
            toast.style.bottom = `${offset}px`
        } else {
            toast.style.top = `${offset}px`
        }
        toast.style.insetInlineEnd = '15px'
        break
    }
}

function updateDomToastLayout(container) {
    if (!container) return

    const position = container.dataset.position || 'top-end'
    const gap = 10
    const baseOffset = 15
    const children = Array.from(container.children)
    const ordered = String(position).startsWith('bottom') ? [...children].reverse() : children

    let currentOffset = baseOffset
    ordered.forEach((toast) => {
        setToastOffset(toast, position, currentOffset)
        currentOffset += toast.offsetHeight + gap
    })
}

function getDomToastContainer(position) {
    if (DOM_TOAST_CONTAINERS.has(position)) {
        return DOM_TOAST_CONTAINERS.get(position)
    }

    const container = document.createElement('div')
    container.className = 'kt-toast-container'
    container.dataset.position = position
    container.style.pointerEvents = 'none'

    document.body.appendChild(container)
    DOM_TOAST_CONTAINERS.set(position, container)

    return container
}

function showToastViaKtToast(meta, title, message, options) {
    const ktToast = resolveKtToast()
    if (!ktToast) return null

    try {
        const instance = ktToast.show({
            variant: meta.variant,
            appearance: options.appearance || 'light',
            position: options.position || 'top-end',
            duration: options.duration ?? 3200,
            progress: options.progress ?? true,
            pauseOnHover: options.pauseOnHover ?? true,
            dismiss: options.dismiss ?? true,
            message: buildToastMessageHtml(title, message),
            icon: buildToastIconHtml(meta),
        })

        styleToastElement(instance?.element, meta)

        return Promise.resolve(instance)
    } catch (error) {
        console.warn('KTToast gosterimi basarisiz oldu.', error)
        return null
    }
}

function showToastViaKtNotify(meta, title, message, options) {
    const ktNotify = resolveKtNotify()
    if (!ktNotify) return null

    try {
        const content = title === message ? title : `${title}: ${message}`

        ktNotify.show({
            type: meta.variant,
            message: content,
            placement: options.position || 'top-end',
            duration: options.duration ?? 3200,
        })

        return Promise.resolve()
    } catch (error) {
        console.warn('KTNotify gosterimi basarisiz oldu.', error)
        return null
    }
}

function showToastViaSwal(meta, title, message, options) {
    const swal = resolveSwal()
    if (!swal) return null

    try {
        return swal.fire({
            toast: true,
            position: options.position || 'top-end',
            icon: meta.icon,
            iconColor: meta.color,
            html: buildToastMessageHtml(title, message),
            showConfirmButton: false,
            timer: options.duration ?? 3200,
            timerProgressBar: options.progress ?? true,
            allowEscapeKey: true,
            didOpen: (popup) => {
                styleSwalToast(popup, meta)
            },
        })
    } catch (error) {
        console.warn('Swal toast gosterimi basarisiz oldu.', error)
        return null
    }
}

function showToastViaDom(meta, title, message, options) {
    const position = options.position || 'top-end'
    const duration = options.duration ?? 3200
    const container = getDomToastContainer(position)
    const toast = document.createElement('div')

    toast.className = `kt-toast kt-alert kt-alert-light kt-alert-${meta.variant}`
    toast.setAttribute('role', options.role || 'status')
    toast.setAttribute('aria-live', 'polite')
    toast.setAttribute('aria-atomic', 'true')
    toast.style.pointerEvents = 'auto'

    toast.innerHTML = `
        <div class="kt-alert-icon">${buildToastIconHtml(meta)}</div>
        <div class="kt-alert-title">${buildToastMessageHtml(title, message)}</div>
        <div class="kt-alert-toolbar">
            <div class="kt-alert-actions">
                <button type="button" data-toast-dismiss class="kt-alert-close" aria-label="Kapat">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
                        <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                        <path d="M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </button>
            </div>
        </div>
        ${(options.progress ?? true) ? '<div class="kt-toast-progress"></div>' : ''}
    `

    styleToastElement(toast, meta)
    container.insertBefore(toast, container.firstChild)
    updateDomToastLayout(container)

    const dismissButton = toast.querySelector('[data-toast-dismiss]')
    dismissButton?.addEventListener('click', () => {
        closeDomToast(toast, container)
    })

    if ((options.progress ?? true)) {
        const progress = toast.querySelector('.kt-toast-progress')
        if (progress) {
            progress.style.animationDuration = `${duration}ms`
        }
    }

    if (duration > 0) {
        const timeoutId = window.setTimeout(() => {
            closeDomToast(toast, container)
        }, duration)

        toast.dataset.timeoutId = String(timeoutId)
    }

    return Promise.resolve()
}

export function showToastMessage(type, message, options = {}) {
    const meta = TYPE_META[type] || TYPE_META.info
    const title = normalizeText(options.title, meta.title)
    const resolvedMessage = normalizeText(message, title)

    return showToastViaKtToast(meta, title, resolvedMessage, options)
        || showToastViaDom(meta, title, resolvedMessage, options)
        || showToastViaSwal(meta, title, resolvedMessage, options)
        || showToastViaKtNotify(meta, title, resolvedMessage, options)
}

export const showAlertMessage = showToastMessage

export async function showConfirmDialog(options = {}) {
    const {
        type = 'warning',
        title,
        message,
        confirmButtonText = 'Evet',
        cancelButtonText = 'Vazgec',
        allowOutsideClick = false,
    } = options

    const meta = TYPE_META[type] || TYPE_META.warning
    const resolvedTitle = normalizeText(title, meta.title)
    const resolvedMessage = normalizeText(message, '')
    const swal = resolveSwal()

    if (swal) {
        const result = await swal.fire({
            icon: meta.icon,
            iconColor: meta.color,
            title: resolvedTitle,
            text: resolvedMessage,
            showCancelButton: true,
            confirmButtonText,
            cancelButtonText,
            reverseButtons: true,
            buttonsStyling: false,
            allowOutsideClick,
            allowEscapeKey: true,
            backdrop: 'rgba(15, 23, 42, 0.52)',
            didOpen: (popup) => {
                styleConfirmPopup(popup, meta.color)
            },
        })

        return Boolean(result.isConfirmed)
    }

    return showConfirmViaDom(meta, resolvedTitle, resolvedMessage, {
        confirmButtonText,
        cancelButtonText,
        allowOutsideClick,
    })
}
