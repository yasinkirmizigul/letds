import { Calendar } from '@fullcalendar/core'
import interactionPlugin from '@fullcalendar/interaction'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import { getDateInputValue, setDateInputValue } from '@/core/date-input'
import { get, request } from '@/core/http'
import { showConfirmDialog, showToastMessage } from '@/core/swal-alert'

let calendar = null
let lastLoadedEvents = []
let selectedEventId = null
let blockModalMode = 'create'
let activeContextEvent = null
let isSavingBlock = false
let blockModalState = {
    mode: 'create',
    entityId: '',
    startAt: '',
    endAt: '',
    reason: '',
    blockType: 'manual',
}
let appointmentModalState = {
    entityId: '',
    providerId: '',
    startAt: '',
    blocks: 1,
    memberName: '',
    statusLabel: '',
    notesInternal: '',
    cancelReason: '',
}
function qs(root, sel) {
    return (root || document).querySelector(sel)
}

function qsa(root, sel) {
    return Array.from((root || document).querySelectorAll(sel))
}

function buildEventsUrl(providerId, from, to) {
    const u = new URL('/admin/appointments/calendar/events', window.location.origin)
    if (providerId) u.searchParams.set('provider_id', providerId)
    if (from) u.searchParams.set('from', from)
    if (to) u.searchParams.set('to', to)
    return u.toString()
}

async function postJson(url, data) {
    try {
        return await request(url, { method: 'POST', data, ignoreGlobalError: true })
    } catch (err) {
        const payload = err?.data || {}
        const msg = payload?.message || payload?.errors?.slot?.[0] || payload?.errors?.appointment?.[0] || payload?.errors?.reason?.[0] || 'İşlem başarısız.'
        const e = new Error(msg)
        e.payload = payload
        throw e
    }
}

async function fetchJson(url) {
    return get(url, { ignoreGlobalError: true })
}

async function loadBlockDetail(id) {
    return await fetchJson(`/admin/appointments/blocks/${id}`)
}

function legacyRenderHistory(root, items = []) {
    const historyWrap = qs(root, '#panelHistory')
    if (!historyWrap) return

    if (!items.length) {
        historyWrap.innerHTML = `<div class="text-xs text-gray-500 dark:text-zinc-400">Geçmiş kayıt yok.</div>`
        return
    }

    historyWrap.innerHTML = items.map((item) => `
        <div class="rounded-xl border border-gray-200 bg-white px-3 py-3 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <div class="text-sm font-medium text-gray-900 dark:text-zinc-100">${item.start_at} - ${item.end_at}</div>
                <div class="text-xs ${item.is_current ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-zinc-400'}">
                    ${item.is_current ? 'Aktif' : item.status}
                </div>
            </div>
            <div class="mt-1 text-xs text-gray-500 dark:text-zinc-400">
                ${item.provider_name || '-'} • ${item.member_name || '-'}
            </div>
        </div>
    `).join('')
}
async function loadAppointmentDetail(id) {
    return await fetchJson(`/admin/appointments/${id}`)
}

function openAppointmentModal(root, data) {

    const modal = qs(root, '#appointmentModal')
    if (!modal) return

    appointmentModalState = {
        entityId: String(data.id || ''),
        providerId: String(data.provider_id || ''),
        startAt: data.start_at || '',
        blocks: Number(data.blocks || 1),
        memberName: data.member_name || '',
        statusLabel: data.status_label || data.status || '',
        notesInternal: data.notes_internal || '',
        cancelReason: data.cancel_reason || '',
    }

    const member = qs(root, '#appointmentMemberName')
    const status = qs(root, '#appointmentStatusLabel')
    const provider = qs(root, '#appointmentProviderId')
    const startAt = qs(root, '#appointmentStartAt')
    const blocks = qs(root, '#appointmentBlocks')
    const notes = qs(root, '#appointmentNotesInternal')
    const cancel = qs(root, '#appointmentCancelReason')

    if (member) member.value = appointmentModalState.memberName
    if (status) status.value = appointmentModalState.statusLabel
    if (provider) {
        syncSelectValue(
            provider,
            resolveTransferProviderValue(provider, appointmentModalState.providerId),
            { emitEvents: false }
        )
    }
    setDateInputValue(startAt, appointmentModalState.startAt)
    if (blocks) blocks.value = String(appointmentModalState.blocks)
    if (notes) notes.value = appointmentModalState.notesInternal
    if (cancel) cancel.value = appointmentModalState.cancelReason

    modal.classList.remove('hidden')
    document.body.classList.add('overflow-hidden')
}

function closeAppointmentModal(root) {
    const modal = qs(root, '#appointmentModal')
    if (!modal) return

    appointmentModalState = {
        entityId: '',
        providerId: '',
        startAt: '',
        blocks: 1,
        memberName: '',
        statusLabel: '',
        notesInternal: '',
        cancelReason: '',
    }

    modal.classList.add('hidden')
    document.body.classList.remove('overflow-hidden')
}
async function loadHistory(root, eventId) {
    try {
        const data = await fetchJson(`/admin/appointments/${eventId}/history`)
        renderHistory(root, Array.isArray(data) ? data : [])
    } catch (_) {
        renderHistory(root, [])
    }
}

function calcBlocks(start, end) {
    if (!start || !end) return 1
    const minutes = Math.round((end.getTime() - start.getTime()) / 60000)
    return Math.max(1, Math.round(minutes / 30))
}

function formatDateRange(start, end) {
    if (!start || !end) return '-'

    const fmt = new Intl.DateTimeFormat('tr-TR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })

    return `${fmt.format(start)} - ${fmt.format(end)}`
}

function formatDateTime(date) {
    if (!date) return '-'

    return new Intl.DateTimeFormat('tr-TR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date)
}

function legacyBuildEventContent(info) {
    const entityType = info.event.extendedProps?.entity_type || 'appointment'
    const statusLabel = info.event.extendedProps?.status_label || ''
    const title = info.event.title || '-'

    const wrapper = document.createElement('div')
    wrapper.className = 'fc-event-custom flex flex-col gap-1 px-1 py-[2px]'

    const badge = document.createElement('div')
    badge.className = 'text-[10px] font-semibold uppercase tracking-wide opacity-90'
    badge.textContent = entityType === 'time_off' ? statusLabel : `Randevu • ${statusLabel}`

    const text = document.createElement('div')
    text.className = 'text-[11px] font-medium leading-4 truncate'
    text.textContent = title

    wrapper.appendChild(badge)
    wrapper.appendChild(text)

    return { domNodes: [wrapper] }
}

function formatTimeRange(start, end) {
    if (!start || !end) return '-'
    return `${formatDateTime(start)} → ${formatDateTime(end)}`
}
function normalizeProviderId(value) {
    if (value === null || value === undefined) return null

    const normalized = String(value).trim()
    return normalized !== '' ? normalized : null
}

function getSelectedProviderId(providerSelect) {
    return normalizeProviderId(providerSelect?.value)
}

function getEventProviderId(event) {
    return normalizeProviderId(event?.extendedProps?.provider_id)
}

function rangesOverlap(startA, endA, startB, endB) {
    if (!startA || !endA || !startB || !endB) return false
    return startA < endB && endA > startB
}

function hasVisibleEventConflict(event, nextStart, nextEnd) {
    if (!calendar || !event || !nextStart || !nextEnd) return false

    const providerId = getEventProviderId(event)
    if (!providerId) return false

    return calendar.getEvents().some((candidate) => {
        if (candidate.id === event.id) return false
        if (getEventProviderId(candidate) !== providerId) return false

        return rangesOverlap(nextStart, nextEnd, candidate.start, candidate.end)
    })
}

function ensureProviderSelection(providerSelect) {
    if (getSelectedProviderId(providerSelect)) return true

    notifyError('Randevu takvimini düzenlemek için önce kişi seç.')
    return false
}

function setİşlemDisabled(element, disabled) {
    if (!element) return

    element.disabled = disabled
    element.classList.toggle('opacity-50', disabled)
    element.classList.toggle('pointer-events-none', disabled)
}

function updateCalendarInteractionState(root, providerSelect) {
    const hasProvider = Boolean(getSelectedProviderId(providerSelect))
    const hint = qs(root, '#calendarInteractionHint')

    calendar?.setOption('selectable', hasProvider)
    calendar?.setOption('editable', hasProvider)
    calendar?.setOption('eventStartEditable', hasProvider)
    calendar?.setOption('eventDurationEditable', hasProvider)

    setİşlemDisabled(qs(root, '#btnCancelAppointment'), !hasProvider)
    setİşlemDisabled(qs(root, '#btnAppointmentSave'), !hasProvider)
    setİşlemDisabled(qs(root, '#btnAppointmentCancel'), !hasProvider)
    setİşlemDisabled(qs(root, '#btnSaveBlock'), !hasProvider)
    setİşlemDisabled(qs(root, '#ctxEditBlock'), !hasProvider)
    setİşlemDisabled(qs(root, '#ctxDeleteBlock'), !hasProvider)

    if (hint) {
        hint.classList.toggle('hidden', hasProvider)
    }
}

function notifyScheduleConflict() {
    notifyError('Seçilen zaman aralığında aynı kişi için randevu veya blokaj var.')
}

function resolveTransferProviderValue(selectEl, currentProviderId) {
    if (!selectEl) return ''

    const currentId = normalizeProviderId(currentProviderId)
    const hasCurrent = Array.from(selectEl.options).some((opt) => normalizeProviderId(opt.value) === currentId)

    if (hasCurrent) {
        return currentId
    }

    if (Array.from(selectEl.options).some((opt) => opt.value === '')) {
        return ''
    }

    return normalizeProviderId(selectEl.options[0]?.value) || ''
}

function syncSelectValue(selectEl, value, options = {}) {
    if (!selectEl) return
    const { emitEvents = true } = options

    const nextValue = value === null || value === undefined ? '' : String(value)
    const matchedOption = Array.from(selectEl.options).find((opt) => String(opt.value) === nextValue)

    if (!matchedOption) return

    Array.from(selectEl.options).forEach((opt) => {
        opt.selected = opt === matchedOption
    })

    selectEl.value = matchedOption.value

    try {
        const KT = window.KTSelect || window.ktSelect
        const instance =
            (KT?.getInstance && KT.getInstance(selectEl)) ||
            (KT?.getOrCreateInstance && KT.getOrCreateInstance(selectEl)) ||
            (selectEl.ktSelectInstance ?? null)

        if (instance) {
            if (typeof instance.setValue === 'function') instance.setValue(matchedOption.value)
            if (typeof instance.update === 'function') instance.update()
            if (typeof instance.render === 'function') instance.render()
        }
    } catch (_) {}

    const wrapper = selectEl.closest('[data-kt-select-wrapper], .kt-select-wrapper')
    if (wrapper) {
        const display =
            wrapper.querySelector('[data-kt-select-placeholder]') ||
            wrapper.querySelector('[data-kt-select-display]') ||
            wrapper.querySelector('.kt-select-display')

        if (display) {
            display.textContent = matchedOption.textContent?.trim() || matchedOption.value
        }

        wrapper.querySelectorAll('[data-kt-select-option]').forEach((item) => {
            const isSelected = item.getAttribute('data-value') === matchedOption.value
            item.setAttribute('aria-selected', isSelected ? 'true' : 'false')
            item.classList.toggle('is-selected', isSelected)
            item.classList.toggle('selected', isSelected)
        })
    }

    if (emitEvents) {
        selectEl.dispatchEvent(new Event('input', { bubbles: true }))
        selectEl.dispatchEvent(new Event('change', { bubbles: true }))
    }
}

function setNativeSelectValue(selectEl, value) {
    if (!selectEl) return

    const nextValue = value || 'manual'
    const exists = Array.from(selectEl.options).some((opt) => opt.value === nextValue)
    syncSelectValue(selectEl, exists ? nextValue : 'manual')
}
function openBlockModal(root, options = {}) {
    const modal = qs(root, '#blockModal')
    const title = qs(root, '#blockModalTitle')
    const reasonInput = qs(root, '#blockReason')
    const typeInput = qs(root, '#blockType')
    const startPreview = qs(root, '#blockStartPreview')
    const endPreview = qs(root, '#blockEndPreview')
    const saveBtnText = qs(root, '#btnSaveBlock .btn-text')

    if (!modal) return

    blockModalState = {
        mode: options.mode || 'create',
        entityId: options.entityId ? String(options.entityId).trim() : '',
        startAt: options.start ? toLocalIsoString(options.start) : '',
        endAt: options.end ? toLocalIsoString(options.end) : '',
        reason: options.reason || '',
        blockType: options.blockType || 'manual',
    }

    blockModalMode = blockModalState.mode

    if (title) {
        title.textContent = blockModalState.mode === 'edit'
            ? 'Takvim Blokajını Düzenle'
            : 'Takvim Blokajı Oluştur'
    }

    if (saveBtnText) {
        saveBtnText.textContent = blockModalState.mode === 'edit' ? 'Güncelle' : 'Kaydet'
    }

    if (reasonInput) {
        reasonInput.value = blockModalState.reason
    }

    if (startPreview) {
        startPreview.textContent = options.start ? formatDateTime(options.start) : '-'
    }

    if (endPreview) {
        endPreview.textContent = options.end ? formatDateTime(options.end) : '-'
    }

    modal.classList.remove('hidden')
    document.body.classList.add('overflow-hidden')

    requestAnimationFrame(() => {
        if (typeInput) {
            setNativeSelectValue(typeInput, blockModalState.blockType)
        }
    })

    setBlockSaveLoading(root, false)
}
function closeBlockModal(root) {
    const modal = qs(root, '#blockModal')
    const reasonInput = qs(root, '#blockReason')
    const typeInput = qs(root, '#blockType')
    const startPreview = qs(root, '#blockStartPreview')
    const endPreview = qs(root, '#blockEndPreview')
    const title = qs(root, '#blockModalTitle')
    const saveBtnText = qs(root, '#btnSaveBlock .btn-text')

    blockModalState = {
        mode: 'create',
        entityId: '',
        startAt: '',
        endAt: '',
        reason: '',
        blockType: 'manual',
    }

    blockModalMode = 'create'

    if (reasonInput) reasonInput.value = ''
    if (typeInput) setNativeSelectValue(typeInput, 'manual')
    if (startPreview) startPreview.textContent = '-'
    if (endPreview) endPreview.textContent = '-'
    if (title) title.textContent = 'Takvim Blokajı'
    if (saveBtnText) saveBtnText.textContent = 'Kaydet'

    setBlockSaveLoading(root, false)

    if (!modal) return

    modal.classList.add('hidden')
    document.body.classList.remove('overflow-hidden')
}

function legacyFillDetailPanel(root, event) {
    const panelEmpty = qs(root, '#panelEmpty')
    const panelContent = qs(root, '#panelContent')
    const selectedAppointmentId = qs(root, '#selectedAppointmentId')
    const pMember = qs(root, '#pMember')
    const pWhen = qs(root, '#pWhen')
    const pDuration = qs(root, '#pDuration')
    const pStatus = qs(root, '#pStatus')
    const cancelReason = qs(root, '#cancelReason')
    const btnCancelAppointment = qs(root, '#btnCancelAppointment')

    if (!panelEmpty || !panelContent || !selectedAppointmentId || !pMember || !pWhen || !pDuration || !pStatus) {
        return
    }

    const entityType = event.extendedProps?.entity_type || 'appointment'
    const blocks = Number(event.extendedProps?.blocks || 1)
    const minutes = entityType === 'appointment'
        ? blocks * 30
        : Math.round((event.end.getTime() - event.start.getTime()) / 60000)

    selectedEventId = event.id
    selectedAppointmentId.value = event.id

    panelEmpty.classList.add('hidden')
    panelContent.classList.remove('hidden')

    if (entityType === 'time_off') {
        pMember.textContent = event.title || 'Kapalı'
        pStatus.textContent = event.extendedProps?.status_label || 'Kapalı'
        if (cancelReason) {
            cancelReason.value = event.extendedProps?.reason || ''
        }
        if (btnCancelAppointment) {
            btnCancelAppointment.textContent = 'Blokajı Sil'
        }
    } else {
        pMember.textContent = event.extendedProps?.member_name || event.title || '-'
        pStatus.textContent = event.extendedProps?.status_label || event.extendedProps?.status || '-'
        if (cancelReason) {
            cancelReason.value = ''
        }
        if (btnCancelAppointment) {
            btnCancelAppointment.textContent = 'Randevuyu İptal Et'
        }
    }

    pWhen.textContent = formatDateRange(event.start, event.end)
    pDuration.textContent = `${minutes} dk`

    if (entityType === 'appointment' && event.extendedProps?.entity_id) {
        loadHistory(root, event.extendedProps.entity_id)
    } else {
        renderHistory(root, [])
    }
}

function resetDetailPanel(root) {
    const panelEmpty = qs(root, '#panelEmpty')
    const panelContent = qs(root, '#panelContent')
    const selectedAppointmentId = qs(root, '#selectedAppointmentId')
    const cancelReason = qs(root, '#cancelReason')

    selectedEventId = null

    if (selectedAppointmentId) selectedAppointmentId.value = ''
    if (cancelReason) cancelReason.value = ''

    if (panelEmpty) panelEmpty.classList.remove('hidden')
    if (panelContent) panelContent.classList.add('hidden')

    const historyWrap = qs(root, '#panelHistory')
    if (historyWrap) {
        historyWrap.innerHTML = ''
    }
}

function toLocalIsoString(date) {
    if (!date) return null

    const pad = (n) => String(n).padStart(2, '0')

    const year = date.getFullYear()
    const month = pad(date.getMonth() + 1)
    const day = pad(date.getDate())
    const hours = pad(date.getHours())
    const minutes = pad(date.getMinutes())
    const seconds = pad(date.getSeconds())

    const offsetMinutes = -date.getTimezoneOffset()
    const sign = offsetMinutes >= 0 ? '+' : '-'
    const abs = Math.abs(offsetMinutes)
    const offsetHours = pad(Math.floor(abs / 60))
    const offsetMins = pad(abs % 60)

    return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}${sign}${offsetHours}:${offsetMins}`
}

function notifyError(message) {
    showToastMessage('error', message, {
        title: 'İşlem başarısız',
    })
}

function notifySuccess(message) {
    showToastMessage('success', message, {
        title: 'İşlem tamamlandı',
    })
}
function resetBlockModalState(root) {
    const entityIdInput = qs(root, '#blockEntityId')
    const startInput = qs(root, '#blockStartAt')
    const endInput = qs(root, '#blockEndAt')
    const reasonInput = qs(root, '#blockReason')
    const typeInput = qs(root, '#blockType')
    const startPreview = qs(root, '#blockStartPreview')
    const endPreview = qs(root, '#blockEndPreview')
    const title = qs(root, '#blockModalTitle')
    const saveBtnText = qs(root, '#btnSaveBlock .btn-text')

    blockModalMode = 'create'

    if (entityIdInput) entityIdInput.value = ''
    if (startInput) startInput.value = ''
    if (endInput) endInput.value = ''
    if (reasonInput) reasonInput.value = ''
    if (typeInput) setNativeSelectValue(typeInput, 'manual')
    if (startPreview) startPreview.textContent = '-'
    if (endPreview) endPreview.textContent = '-'
    if (title) title.textContent = 'Takvim Blokajı Oluştur'
    if (saveBtnText) saveBtnText.textContent = 'Kaydet'

    setBlockSaveLoading(root, false)
}
function legacySetBlockSaveLoading(root, loading) {
    const btn = qs(root, '#btnSaveBlock')
    const text = qs(root, '#btnSaveBlock .btn-text')

    isSavingBlock = loading

    if (!btn) return

    btn.disabled = loading
    btn.classList.toggle('opacity-60', loading)
    btn.classList.toggle('pointer-events-none', loading)

    if (text) {
        text.textContent = loading
            ? (blockModalMode === 'edit' ? 'Güncelleniyor...' : 'Kaydediliyor...')
            : 'Kaydet'
    }
}

function openContextMenu(root, event, x, y) {
    const menu = qs(root, '#calendarContextMenu')
    if (!menu) return

    activeContextEvent = event
    menu.style.left = `${x}px`
    menu.style.top = `${y}px`
    menu.classList.remove('hidden')
}

function closeContextMenu(root) {
    const menu = qs(root, '#calendarContextMenu')
    if (!menu) return

    activeContextEvent = null
    menu.classList.add('hidden')
    menu.style.left = ''
    menu.style.top = ''
}

function showDragTooltip(root, text, x = null, y = null) {
    const tip = qs(root, '#calendarDragTooltip')
    if (!tip) return

    tip.textContent = text
    tip.classList.remove('hidden')

    if (x !== null && y !== null) {
        tip.style.left = `${x + 14}px`
        tip.style.top = `${y + 14}px`
    }
}

function moveDragTooltip(root, x, y) {
    const tip = qs(root, '#calendarDragTooltip')
    if (!tip || tip.classList.contains('hidden')) return

    tip.style.left = `${x + 14}px`
    tip.style.top = `${y + 14}px`
}

function hideDragTooltip(root) {
    const tip = qs(root, '#calendarDragTooltip')
    if (!tip) return

    tip.classList.add('hidden')
    tip.style.left = ''
    tip.style.top = ''
    tip.textContent = ''
}

function renderHistory(root, items = []) {
    const historyWrap = qs(root, '#panelHistory')
    if (!historyWrap) return

    if (!items.length) {
        historyWrap.innerHTML = `<div class="text-xs text-gray-500 dark:text-zinc-400">Geçmiş kayıt yok.</div>`
        return
    }

    historyWrap.innerHTML = items.map((item) => `
        <div class="rounded-xl border border-gray-200 bg-white px-3 py-3 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <div class="text-sm font-medium text-gray-900 dark:text-zinc-100">${item.start_at} - ${item.end_at}</div>
                <div class="text-xs ${item.is_current ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-zinc-400'}">
                    ${item.is_current ? 'Aktif' : (item.status_label || item.status)}
                </div>
            </div>
            <div class="mt-1 text-xs text-gray-500 dark:text-zinc-400">
                ${item.provider_name || '-'} | ${item.member_name || '-'}
            </div>
        </div>
    `).join('')
}

function formatDurationText(totalMinutes) {
    const safe = Math.max(0, Number(totalMinutes || 0))
    const hours = Math.floor(safe / 60)
    const minutes = safe % 60

    if (hours && minutes) return `${hours} sa ${minutes} dk`
    if (hours) return `${hours} saat`
    return `${minutes} dk`
}

function getSelectedProviderMeta(providerSelect) {
    const option = providerSelect?.selectedOptions?.[0]

    if (!providerSelect || !option) {
        return {
            name: 'Tüm aktif kişiler',
            title: 'Super admin görünümü',
        }
    }

    return {
        name: option.dataset?.providerName || option.textContent?.trim() || 'Tüm aktif kişiler',
        title: option.dataset?.providerTitle || '',
    }
}

function viewLabel(viewType) {
    return {
        timeGridDay: 'Günlük görünüm',
        timeGridWeek: 'Haftalık görünüm',
        dayGridMonth: 'Aylık görünüm',
    }[viewType] || 'Takvim görünümü'
}

function updateViewButtons(root) {
    const activeView = calendar?.view?.type || ''

    qsa(root, '[data-view]').forEach((button) => {
        const isActive = button.getAttribute('data-view') === activeView
        button.classList.toggle('kt-btn-primary', isActive)
        button.classList.toggle('kt-btn-light', !isActive)
    })
}

function updateCalendarHeader(root, providerSelect) {
    const rangeEl = qs(root, '#calendarCurrentRange')
    const viewLabelEl = qs(root, '#calendarCurrentViewLabel')
    const providerLabelEl = qs(root, '#calendarSelectedProviderName')
    const surfaceHintEl = qs(root, '#calendarSurfaceHint')
    const providerMeta = getSelectedProviderMeta(providerSelect)
    const hasProviderSelection = Boolean(getSelectedProviderId(providerSelect))

    if (rangeEl) {
        rangeEl.textContent = calendar?.view?.title || 'Takvim aralığı'
    }

    if (viewLabelEl) {
        viewLabelEl.textContent = viewLabel(calendar?.view?.type)
    }

    if (providerLabelEl) {
        providerLabelEl.textContent = providerMeta.name
    }

    if (surfaceHintEl) {
        surfaceHintEl.textContent = hasProviderSelection
            ? `${providerMeta.name} için düzenleme aktif`
            : 'Salt görünüm modu'
    }

    updateViewButtons(root)
}

function renderCalendarMetrics(root, events = lastLoadedEvents, providerSelect) {
    const appointmentEvents = (events || []).filter((item) => item?.extendedProps?.entity_type !== 'time_off')
    const blockEvents = (events || []).filter((item) => item?.extendedProps?.entity_type === 'time_off')
    const totalAppointmentMinutes = appointmentEvents.reduce((total, item) => {
        const start = item?.start ? new Date(item.start) : null
        const end = item?.end ? new Date(item.end) : null
        if (!start || !end) return total
        return total + Math.max(0, Math.round((end.getTime() - start.getTime()) / 60000))
    }, 0)

    const now = new Date()
    const upcoming = [...(events || [])]
        .filter((item) => item?.start && new Date(item.start) >= now)
        .sort((left, right) => new Date(left.start).getTime() - new Date(right.start).getTime())[0] || null

    const appointmentCountEl = qs(root, '#calendarMetricAppointments')
    const blockCountEl = qs(root, '#calendarMetricBlocks')
    const busyHoursEl = qs(root, '#calendarMetricBusyHours')
    const nextEl = qs(root, '#calendarMetricNext')
    const hintEl = qs(root, '#calendarMetricHint')

    if (appointmentCountEl) appointmentCountEl.textContent = String(appointmentEvents.length)
    if (blockCountEl) blockCountEl.textContent = String(blockEvents.length)
    if (busyHoursEl) busyHoursEl.textContent = formatDurationText(totalAppointmentMinutes)

    if (nextEl) {
        nextEl.textContent = upcoming
            ? `${upcoming.title || 'Kayıt'}`
            : '-'
    }

    if (hintEl) {
        if (!upcoming) {
            hintEl.textContent = 'Yaklaşan kayıt bulunmuyor'
            return
        }

        const providerMeta = getSelectedProviderMeta(providerSelect)
        const typeLabel = upcoming?.extendedProps?.entity_type === 'time_off'
            ? (upcoming?.extendedProps?.status_label || 'Blokaj')
            : 'Randevu'

        hintEl.textContent = `${typeLabel} | ${formatDateTime(upcoming.start)} | ${providerMeta.name}`
    }
}

function hydrateAppointmentEvent(event, data) {
    if (!event || !data) return

    if (data.member_name) {
        event.setProp('title', data.member_name)
    }

    event.setExtendedProp('provider_id', data.provider_id)
    event.setExtendedProp('provider_name', data.provider_name)
    event.setExtendedProp('provider_title', data.provider_title)
    event.setExtendedProp('member_id', data.member_id)
    event.setExtendedProp('member_name', data.member_name)
    event.setExtendedProp('member_email', data.member_email)
    event.setExtendedProp('member_phone', data.member_phone)
    event.setExtendedProp('status', data.status)
    event.setExtendedProp('status_label', data.status_label)
    event.setExtendedProp('blocks', data.blocks)
    event.setExtendedProp('notes_internal', data.notes_internal || '')
    event.setExtendedProp('cancel_reason', data.cancel_reason || '')
    event.setExtendedProp('parent_id', data.parent_id)
    event.setExtendedProp('is_transferred', Boolean(data.is_transferred))
}

function hydrateBlockEvent(event, data) {
    if (!event || !data) return

    event.setExtendedProp('provider_id', data.provider_id)
    event.setExtendedProp('reason', data.reason || '')
    event.setExtendedProp('block_type', data.block_type || 'manual')
}

function buildEventContent(info) {
    const entityType = info.event.extendedProps?.entity_type || 'appointment'
    const statusLabel = info.event.extendedProps?.status_label || ''
    const title = info.event.title || '-'

    const wrapper = document.createElement('div')
    wrapper.className = 'fc-event-custom flex flex-col gap-1 px-1 py-[2px]'

    const badge = document.createElement('div')
    badge.className = 'text-[10px] font-semibold uppercase tracking-wide opacity-90'
    badge.textContent = entityType === 'time_off' ? statusLabel : `Randevu - ${statusLabel}`

    const text = document.createElement('div')
    text.className = 'text-[11px] font-medium leading-4 truncate'
    text.textContent = title

    wrapper.appendChild(badge)
    wrapper.appendChild(text)

    return { domNodes: [wrapper] }
}

function fillDetailPanel(root, event) {
    const panelEmpty = qs(root, '#panelEmpty')
    const panelContent = qs(root, '#panelContent')
    const selectedAppointmentId = qs(root, '#selectedAppointmentId')
    const pMember = qs(root, '#pMember')
    const pProvider = qs(root, '#pProvider')
    const pWhen = qs(root, '#pWhen')
    const pDuration = qs(root, '#pDuration')
    const pStatus = qs(root, '#pStatus')
    const pTransfer = qs(root, '#pTransfer')
    const pNotes = qs(root, '#pNotes')
    const cancelReason = qs(root, '#cancelReason')
    const cancelReasonLabel = qs(root, '#cancelReasonLabel')
    const cancelReasonHelp = qs(root, '#cancelReasonHelp')
    const btnCancelAppointment = qs(root, '#btnCancelAppointment')
    const panelMetaBadge = qs(root, '#panelMetaBadge')

    if (!panelEmpty || !panelContent || !selectedAppointmentId || !pMember || !pWhen || !pDuration || !pStatus) {
        return
    }

    const entityType = event.extendedProps?.entity_type || 'appointment'
    const blocks = Number(event.extendedProps?.blocks || 1)
    const minutes = entityType === 'appointment'
        ? blocks * 30
        : Math.round((event.end.getTime() - event.start.getTime()) / 60000)

    selectedEventId = event.id
    selectedAppointmentId.value = event.id

    panelEmpty.classList.add('hidden')
    panelContent.classList.remove('hidden')

    if (entityType === 'time_off') {
        pMember.textContent = event.title || 'Kapalı zaman'
        if (pProvider) {
            const providerName = event.extendedProps?.provider_name || 'Seçili kişi'
            const providerTitle = event.extendedProps?.provider_title || 'Takvim blokaji'
            pProvider.textContent = `${providerName} | ${providerTitle}`
        }
        pStatus.textContent = event.extendedProps?.status_label || 'Kapalı'
        if (pTransfer) pTransfer.textContent = 'Takvim blokaji'
        if (pNotes) pNotes.textContent = event.extendedProps?.reason || 'Açıklama yok'
        if (cancelReason) cancelReason.value = event.extendedProps?.reason || ''
        if (cancelReasonLabel) cancelReasonLabel.textContent = 'Blokaj açıklamasi'
        if (cancelReasonHelp) cancelReasonHelp.textContent = 'Silme öncesi referans olarak kullanabilirsin.'
        if (btnCancelAppointment) btnCancelAppointment.textContent = 'Blokaji sil'
        if (panelMetaBadge) {
            panelMetaBadge.className = 'kt-badge kt-badge-sm kt-badge-light-warning'
            panelMetaBadge.textContent = event.extendedProps?.status_label || 'Blokaj'
        }
    } else {
        pMember.textContent = event.extendedProps?.member_name || event.title || '-'
        if (pProvider) {
            const providerName = event.extendedProps?.provider_name || 'Kişi bilgisi yok'
            const providerTitle = event.extendedProps?.provider_title || 'Unvan yok'
            const contact = [event.extendedProps?.member_phone, event.extendedProps?.member_email].filter(Boolean).join(' | ')
            pProvider.textContent = contact ? `${providerName} | ${providerTitle} | ${contact}` : `${providerName} | ${providerTitle}`
        }
        pStatus.textContent = event.extendedProps?.status_label || event.extendedProps?.status || '-'
        if (pTransfer) {
            pTransfer.textContent = event.extendedProps?.is_transferred
                ? 'Transfer geçmişi var'
                : 'Orijinal rezervasyon'
        }
        if (pNotes) pNotes.textContent = event.extendedProps?.notes_internal || 'Ic not yok'
        if (cancelReason) cancelReason.value = event.extendedProps?.cancel_reason || ''
        if (cancelReasonLabel) cancelReasonLabel.textContent = 'İptal nedeni'
        if (cancelReasonHelp) cancelReasonHelp.textContent = 'İptal aksiyonunda bu alan gonderilir.'
        if (btnCancelAppointment) btnCancelAppointment.textContent = 'Randevuyu iptal et'
        if (panelMetaBadge) {
            panelMetaBadge.className = 'kt-badge kt-badge-sm kt-badge-light-primary'
            panelMetaBadge.textContent = event.extendedProps?.status_label || 'Randevu'
        }
    }

    pWhen.textContent = formatDateRange(event.start, event.end)
    pDuration.textContent = formatDurationText(minutes)

    if (entityType === 'appointment' && event.extendedProps?.entity_id) {
        loadHistory(root, event.extendedProps.entity_id)
    } else {
        renderHistory(root, [])
    }
}

function setBlockSaveLoading(root, loading) {
    const btn = qs(root, '#btnSaveBlock')
    const text = qs(root, '#btnSaveBlock .btn-text')

    isSavingBlock = loading

    if (!btn) return

    btn.disabled = loading
    btn.classList.toggle('opacity-60', loading)
    btn.classList.toggle('pointer-events-none', loading)

    if (text) {
        text.textContent = loading
            ? (blockModalMode === 'edit' ? 'Güncelleniyor...' : 'Kaydediliyor...')
            : (blockModalMode === 'edit' ? 'Güncelle' : 'Kaydet')
    }
}

export default async function init(ctx) {
    const root = ctx?.root || document
    const el = qs(root, '#appointmentsCalendar')
    if (!el) return

    const providerSelect = qs(root, '#providerSelect')
    const initialProviderId = normalizeProviderId(providerSelect?.dataset?.initialProviderId)
    const btnCancelAppointment = qs(root, '#btnCancelAppointment')
    const cancelReason = qs(root, '#cancelReason')

    if (providerSelect && initialProviderId) {
        syncSelectValue(providerSelect, initialProviderId)
        requestAnimationFrame(() => syncSelectValue(providerSelect, initialProviderId, { emitEvents: false }))
        setTimeout(() => syncSelectValue(providerSelect, initialProviderId, { emitEvents: false }), 50)
    }

    const blockModal = qs(root, '#blockModal')
    const btnSaveBlock = qs(root, '#btnSaveBlock')
    const blockType = qs(root, '#blockType')
    const blockReason = qs(root, '#blockReason')
    if (blockType) {
        blockType.addEventListener('change', (e) => {
            blockModalState.blockType = e.target.value || 'manual'
        })
    }

    if (blockReason) {
        blockReason.addEventListener('input', (e) => {
            blockModalState.reason = e.target.value || ''
        })
    }
    const blockStartAt = qs(root, '#blockStartAt')
    const blockEndAt = qs(root, '#blockEndAt')
    const blockEntityId = qs(root, '#blockEntityId')

    const ctxEditBlock = qs(root, '#ctxEditBlock')
    const ctxDeleteBlock = qs(root, '#ctxDeleteBlock')
    const appointmentModal = qs(root, '#appointmentModal')
    const btnAppointmentSave = qs(root, '#btnAppointmentSave')
    const btnAppointmentCancel = qs(root, '#btnAppointmentCancel')
    const appointmentProviderId = qs(root, '#appointmentProviderId')
    const appointmentStartAt = qs(root, '#appointmentStartAt')
    const appointmentBlocks = qs(root, '#appointmentBlocks')
    const appointmentNotesInternal = qs(root, '#appointmentNotesInternal')
    const appointmentCancelReason = qs(root, '#appointmentCancelReason')

    calendar = new Calendar(el, {
        plugins: [interactionPlugin, dayGridPlugin, timeGridPlugin],

        locale: 'tr',
        firstDay: 1,
        timeZone: 'local',

        initialView: 'timeGridWeek',
        height: 'auto',

        slotDuration: '00:30:00',
        snapDuration: '00:30:00',
        nowIndicator: true,
        allDaySlot: false,
        headerToolbar: false,
        selectable: true,
        editable: true,
        eventStartEditable: true,
        eventDurationEditable: true,

        eventAllow(dropInfo, draggedEvent) {
            if (dropInfo.start < new Date()) {
                return false
            }

            if (!getSelectedProviderId(providerSelect)) {
                return false
            }

            if (!draggedEvent) {
                return true
            }

            return !hasVisibleEventConflict(draggedEvent, dropInfo.start, dropInfo.end)
        },

        select(info) {
            const providerId = providerSelect?.value
            if (!providerId) {
                notifyError('Önce kişi seç.')
                return
            }

            if (info.start < new Date()) {
                notifyError('Geçmiş zaman aralığı kapatılamaz.')
                return
            }

            openBlockModal(root, {
                mode: 'create',
                start: info.start,
                end: info.end,
                reason: '',
                blockType: 'manual',
            })

            calendar?.unselect()
        },

        eventContent(info) {
            return buildEventContent(info)
        },

        allDayText: 'Tüm Gün',

        slotLabelFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        },

        dayHeaderFormat: {
            weekday: 'short',
            day: '2-digit',
            month: '2-digit',
        },

        titleFormat: {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        },

        datesSet() {
            updateCalendarHeader(root, providerSelect)
            renderCalendarMetrics(root, lastLoadedEvents, providerSelect)
        },

        events: async (fetchInfo, success, failure) => {
            try {
                const url = buildEventsUrl(providerSelect?.value, fetchInfo.startStr, fetchInfo.endStr)
                const data = await get(url, { ignoreGlobalError: true })
                lastLoadedEvents = Array.isArray(data) ? data : []
                success(lastLoadedEvents)
                requestAnimationFrame(() => renderCalendarMetrics(root, lastLoadedEvents, providerSelect))
            } catch (e) {
                failure(e)
            }
        },

        eventClick: async (info) => {
            fillDetailPanel(root, info.event)

            if (info.event.extendedProps?.entity_type === 'time_off') {
                if (!getSelectedProviderId(providerSelect)) {
                    return
                }

                try {
                    const block = await loadBlockDetail(info.event.extendedProps.entity_id)
                    hydrateBlockEvent(info.event, block)
                    fillDetailPanel(root, info.event)

                    openBlockModal(root, {
                        mode: 'edit',
                        entityId: block.id,
                        start: block.start_at ? new Date(block.start_at) : info.event.start,
                        end: block.end_at ? new Date(block.end_at) : info.event.end,
                        reason: block.reason || '',
                        blockType: block.block_type || 'manual',
                    })
                } catch (e) {
                    notifyError(e.message || 'Blokaj detayı alınamadı.')
                }

                return
            }

            if (info.event.extendedProps?.entity_type === 'appointment') {
                if (!getSelectedProviderId(providerSelect)) {
                    return
                }

                try {
                    const appointment = await loadAppointmentDetail(info.event.extendedProps.entity_id)
                    hydrateAppointmentEvent(info.event, appointment)
                    fillDetailPanel(root, info.event)
                    openAppointmentModal(root, appointment)
                } catch (e) {
                    notifyError(e.message || 'Randevu detayı alınamadı.')
                }
            }
        },

        eventDidMount(info) {
            const entityType = info.event.extendedProps?.entity_type || 'appointment'

            if (entityType === 'time_off' && info.el) {
                info.el.addEventListener('contextmenu', (e) => {
                    e.preventDefault()
                    fillDetailPanel(root, info.event)

                    if (!getSelectedProviderId(providerSelect)) {
                        return
                    }

                    openContextMenu(root, info.event, e.clientX, e.clientY)
                })
            }
        },

        eventDragStart(info) {
            closeContextMenu(root)
            showDragTooltip(root, formatTimeRange(info.event.start, info.event.end))
        },

        eventDragStop() {
            hideDragTooltip(root)
        },

        eventResizeStart(info) {
            closeContextMenu(root)
            showDragTooltip(root, formatTimeRange(info.event.start, info.event.end))
        },

        eventResizeStop() {
            hideDragTooltip(root)
        },

        eventDrop: async (info) => {
            const entityType = info.event.extendedProps?.entity_type || 'appointment'

            try {
                if (!ensureProviderSelection(providerSelect)) {
                    info.revert()
                    return
                }

                if (hasVisibleEventConflict(info.event, info.event.start, info.event.end)) {
                    info.revert()
                    notifyScheduleConflict()
                    return
                }

                if (entityType === 'time_off') {
                    await postJson(`/admin/appointments/blocks/${info.event.extendedProps.entity_id}/move`, {
                        start_at: toLocalIsoString(info.event.start),
                        end_at: toLocalIsoString(info.event.end),
                    })

                    fillDetailPanel(root, info.event)
                    calendar?.refetchEvents()
                    notifySuccess('Blokaj saati güncellendi.')
                    return
                }

                await postJson(`/admin/appointments/${info.event.extendedProps.entity_id}/transfer`, {
                    new_provider_id: providerSelect?.value || null,
                    new_start_at: toLocalIsoString(info.event.start),
                    blocks: calcBlocks(info.event.start, info.event.end),
                })

                fillDetailPanel(root, info.event)
                calendar?.refetchEvents()
                notifySuccess('Randevu saati güncellendi.')
            } catch (e) {
                info.revert()
                notifyError(e.message || 'İşlem başarısız.')
            } finally {
                hideDragTooltip(root)
            }
        },

        eventResize: async (info) => {
            const entityType = info.event.extendedProps?.entity_type || 'appointment'

            try {
                if (!ensureProviderSelection(providerSelect)) {
                    info.revert()
                    return
                }

                if (hasVisibleEventConflict(info.event, info.event.start, info.event.end)) {
                    info.revert()
                    notifyScheduleConflict()
                    return
                }

                if (entityType === 'time_off') {
                    await postJson(`/admin/appointments/blocks/${info.event.extendedProps.entity_id}/resize`, {
                        start_at: toLocalIsoString(info.event.start),
                        end_at: toLocalIsoString(info.event.end),
                    })

                    fillDetailPanel(root, info.event)
                    calendar?.refetchEvents()
                    notifySuccess('Blokaj süresi güncellendi.')
                    return
                }

                await postJson(`/admin/appointments/${info.event.extendedProps.entity_id}/resize`, {
                    blocks: calcBlocks(info.event.start, info.event.end),
                })

                fillDetailPanel(root, info.event)
                calendar?.refetchEvents()
                notifySuccess('Randevu süresi güncellendi.')
            } catch (e) {
                info.revert()
                notifyError(e.message || 'İşlem başarısız.')
            } finally {
                hideDragTooltip(root)
            }
        },
    })

    calendar.render()
    updateCalendarHeader(root, providerSelect)
    renderCalendarMetrics(root, lastLoadedEvents, providerSelect)
    updateCalendarInteractionState(root, providerSelect)

    if (providerSelect) {
        providerSelect.addEventListener('change', () => {
            closeContextMenu(root)
            closeAppointmentModal(root)
            closeBlockModal(root)
            hideDragTooltip(root)
            resetDetailPanel(root)
            updateCalendarInteractionState(root, providerSelect)
            updateCalendarHeader(root, providerSelect)
            lastLoadedEvents = []
            renderCalendarMetrics(root, [], providerSelect)
            calendar?.refetchEvents()
        })
    }

    if (btnSaveBlock) {
        btnSaveBlock.addEventListener('click', async () => {
            if (isSavingBlock) return
            if (!ensureProviderSelection(providerSelect)) return

            try {
                setBlockSaveLoading(root, true)

                if (blockModalState.mode === 'edit') {
                    if (!blockModalState.entityId) {
                        notifyError('Düzenlenecek blok kaydı bulunamadı.')
                        return
                    }

                    await postJson(`/admin/appointments/blocks/${blockModalState.entityId}/update`, {
                        reason: (blockModalState.reason || '').trim() || 'Kapalı',
                        block_type: blockModalState.blockType || 'manual',
                    })

                    closeBlockModal(root)
                    calendar?.refetchEvents()
                    notifySuccess('Blokaj güncellendi.')
                    return
                }

                const providerId = providerSelect?.value
                if (!providerId) {
                    notifyError('Önce kişi seç.')
                    return
                }

                if (!blockModalState.startAt || !blockModalState.endAt) {
                    notifyError('Blokaj zaman bilgisi eksik.')
                    return
                }

                await postJson('/admin/appointments/blocks', {
                    provider_id: providerId,
                    start_at: blockModalState.startAt,
                    end_at: blockModalState.endAt,
                    reason: (blockModalState.reason || '').trim() || 'Kapalı',
                    block_type: blockModalState.blockType || 'manual',
                })

                closeBlockModal(root)
                calendar?.refetchEvents()
                notifySuccess('Takvim blokajı oluşturuldu.')
            } catch (e) {
                notifyError(e.message || 'Blokaj kaydedilemedi.')
            } finally {
                setBlockSaveLoading(root, false)
            }
        })
    }

    if (btnCancelAppointment) {
        btnCancelAppointment.addEventListener('click', async () => {
            if (!ensureProviderSelection(providerSelect)) return
            if (!selectedEventId) {
                notifyError('Önce bir kayıt seç.')
                return
            }

            const event = calendar?.getEventById(selectedEventId)
            const entityType = event?.extendedProps?.entity_type || 'appointment'

            const ok = await showConfirmDialog({
                type: entityType === 'time_off' ? 'warning' : 'error',
                title: entityType === 'time_off' ? 'Blokaj silinsin mi?' : 'Randevu iptal edilsin mi?',
                message: entityType === 'time_off'
                    ? 'Seçili blokaj kaydı takvimden kaldırılacak.'
                    : 'Seçili randevu iptal edilecek.',
                confirmButtonText: entityType === 'time_off' ? 'Blokajı sil' : 'Randevuyu iptal et',
                cancelButtonText: 'Vazgeç',
            })

            if (!ok) return

            try {
                if (entityType === 'time_off') {
                    await postJson(`/admin/appointments/blocks/${event.extendedProps.entity_id}/delete`, {})
                    closeBlockModal(root)
                    resetDetailPanel(root)
                    closeContextMenu(root)
                    calendar?.refetchEvents()
                    notifySuccess('Blokaj silindi.')
                    return
                }

                await postJson(`/admin/appointments/${event.extendedProps.entity_id}/cancel`, {
                    reason: cancelReason?.value?.trim() || null,
                })

                resetDetailPanel(root)
                calendar?.refetchEvents()
                notifySuccess('Randevu iptal edildi.')
            } catch (e) {
                notifyError(e.message || 'İşlem başarısız.')
            }
        })
    }

    if (btnAppointmentSave) {
        btnAppointmentSave.addEventListener('click', async () => {
            if (!ensureProviderSelection(providerSelect)) return
            if (!appointmentModalState.entityId) {
                notifyError('Randevu kaydı bulunamadı.')
                return
            }

            try {
                const nextProviderId = normalizeProviderId(appointmentProviderId?.value)

                await postJson(`/admin/appointments/${appointmentModalState.entityId}/transfer`, {
                    new_provider_id: nextProviderId,
                    new_start_at: getDateInputValue(appointmentStartAt),
                    blocks: Number(appointmentBlocks?.value || 1),
                    notes_internal: appointmentNotesInternal?.value || null,
                })

                closeAppointmentModal(root)
                resetDetailPanel(root)
                calendar?.refetchEvents()
                notifySuccess('Randevu güncellendi.')
            } catch (e) {
                notifyError(e.message || 'Randevu güncellenemedi.')
            }
        })
    }

    if (btnAppointmentCancel) {
        btnAppointmentCancel.addEventListener('click', async () => {
            if (!ensureProviderSelection(providerSelect)) return
            if (!appointmentModalState.entityId) {
                notifyError('Randevu kaydı bulunamadı.')
                return
            }

            const ok = await showConfirmDialog({
                type: 'error',
                title: 'Randevu iptal edilsin mi?',
                message: 'Seçili randevu iptal edilecek.',
                confirmButtonText: 'Randevuyu iptal et',
                cancelButtonText: 'Vazgeç',
            })
            if (!ok) return

            try {
                await postJson(`/admin/appointments/${appointmentModalState.entityId}/cancel`, {
                    reason: appointmentCancelReason?.value?.trim() || null,
                })

                closeAppointmentModal(root)
                resetDetailPanel(root)
                calendar?.refetchEvents()
                notifySuccess('Randevu iptal edildi.')
            } catch (e) {
                notifyError(e.message || 'Randevu iptal edilemedi.')
            }
        })
    }

    qsa(root, '[data-appointment-modal-close]').forEach((btn) => {
        btn.addEventListener('click', () => closeAppointmentModal(root))
    })

    if (appointmentModal) {
        appointmentModal.addEventListener('click', (e) => {
            if (e.target.matches('[data-appointment-modal-close]')) {
                closeAppointmentModal(root)
            }
        })
    }

    if (ctxEditBlock) {
        ctxEditBlock.addEventListener('click', async () => {
            if (!ensureProviderSelection(providerSelect)) return
            if (!activeContextEvent) return

            try {
                const block = await loadBlockDetail(activeContextEvent.extendedProps.entity_id)

                openBlockModal(root, {
                    mode: 'edit',
                    entityId: block.id,
                    start: block.start_at ? new Date(block.start_at) : activeContextEvent.start,
                    end: block.end_at ? new Date(block.end_at) : activeContextEvent.end,
                    reason: block.reason || '',
                    blockType: block.block_type || 'manual',
                })

                closeContextMenu(root)
            } catch (e) {
                notifyError(e.message || 'Blokaj detayı alınamadı.')
            }
        })
    }

    if (ctxDeleteBlock) {
        ctxDeleteBlock.addEventListener('click', async () => {
            if (!ensureProviderSelection(providerSelect)) return
            if (!activeContextEvent) return

            const ok = await showConfirmDialog({
                type: 'warning',
                title: 'Blokaj silinsin mi?',
                message: 'Seçili blokaj kaydı takvimden kaldırılacak.',
                confirmButtonText: 'Blokajı sil',
                cancelButtonText: 'Vazgeç',
            })
            if (!ok) return

            try {
                await postJson(`/admin/appointments/blocks/${activeContextEvent.extendedProps.entity_id}/delete`, {})
                closeContextMenu(root)
                closeBlockModal(root)
                resetDetailPanel(root)
                calendar?.refetchEvents()
                notifySuccess('Blokaj silindi.')
            } catch (e) {
                notifyError(e.message || 'Blokaj silinemedi.')
            }
        })
    }

    qsa(root, '[data-block-modal-close]').forEach((btn) => {
        btn.addEventListener('click', () => closeBlockModal(root))
    })

    if (blockModal) {
        blockModal.addEventListener('click', (e) => {
            if (e.target.matches('[data-block-modal-close]')) {
                closeBlockModal(root)
            }
        })
    }

    document.addEventListener('click', (e) => {
        const menu = qs(root, '#calendarContextMenu')
        if (!menu) return

        if (!menu.contains(e.target)) {
            closeContextMenu(root)
        }
    })

    document.addEventListener('scroll', () => {
        closeContextMenu(root)
    }, true)

    document.addEventListener('mousemove', (e) => {
        moveDragTooltip(root, e.clientX, e.clientY)
    })

    qsa(root, '[data-cal]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const action = btn.getAttribute('data-cal')
            if (!calendar) return
            if (action === 'today') calendar.today()
            if (action === 'prev') calendar.prev()
            if (action === 'next') calendar.next()
        })
    })

    qsa(root, '[data-view]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const view = btn.getAttribute('data-view')
            if (!calendar || !view) return
            calendar.changeView(view)
        })
    })

    if (typeof ctx?.onDestroy === 'function') {
        ctx.onDestroy(() => destroy())
    }
}

export function destroy() {
    if (calendar) {
        try { calendar.destroy() } catch (_) {}
        calendar = null
    }

    lastLoadedEvents = []
    selectedEventId = null
    blockModalMode = 'create'
    activeContextEvent = null
    isSavingBlock = false
}
