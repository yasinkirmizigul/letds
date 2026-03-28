import { Calendar } from '@fullcalendar/core'
import interactionPlugin from '@fullcalendar/interaction'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'

let calendar = null
let selectedEventId = null

function qs(root, sel) {
    return (root || document).querySelector(sel)
}

function qsa(root, sel) {
    return Array.from((root || document).querySelectorAll(sel))
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
}

function buildEventsUrl(providerId, from, to) {
    const u = new URL('/admin/appointments/calendar/events', window.location.origin)
    if (providerId) u.searchParams.set('provider_id', providerId)
    if (from) u.searchParams.set('from', from)
    if (to) u.searchParams.set('to', to)
    return u.toString()
}

async function postJson(url, data) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(data),
    })

    const payload = await res.json().catch(() => ({}))

    if (!res.ok) {
        const msg =
            payload?.message ||
            payload?.errors?.slot?.[0] ||
            payload?.errors?.appointment?.[0] ||
            'İşlem başarısız.'
        const err = new Error(msg)
        err.payload = payload
        throw err
    }

    return payload
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

function fillDetailPanel(root, event) {
    const panelEmpty = qs(root, '#panelEmpty')
    const panelContent = qs(root, '#panelContent')
    const selectedAppointmentId = qs(root, '#selectedAppointmentId')
    const pMember = qs(root, '#pMember')
    const pWhen = qs(root, '#pWhen')
    const pDuration = qs(root, '#pDuration')
    const pStatus = qs(root, '#pStatus')

    if (!panelEmpty || !panelContent || !selectedAppointmentId || !pMember || !pWhen || !pDuration || !pStatus) {
        return
    }

    const blocks = Number(event.extendedProps?.blocks || 1)
    const minutes = blocks * 30

    selectedEventId = event.id
    selectedAppointmentId.value = event.id

    panelEmpty.classList.add('hidden')
    panelContent.classList.remove('hidden')

    pMember.textContent = event.extendedProps?.member_name || event.title || '-'
    pWhen.textContent = formatDateRange(event.start, event.end)
    pDuration.textContent = `${minutes} dk`
    pStatus.textContent = event.extendedProps?.status || '-'
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
    if (window.KTNotify?.show) {
        window.KTNotify.show({
            type: 'error',
            message,
            placement: 'top-end',
            duration: 2500,
        })
        return
    }

    alert(message)
}

function notifySuccess(message) {
    if (window.KTNotify?.show) {
        window.KTNotify.show({
            type: 'success',
            message,
            placement: 'top-end',
            duration: 1800,
        })
        return
    }

    alert(message)
}

export default async function init(ctx) {
    const root = ctx?.root || document
    const el = qs(root, '#appointmentsCalendar')
    if (!el) return

    const providerSelect = qs(root, '#providerSelect')
    const btnCancelAppointment = qs(root, '#btnCancelAppointment')
    const cancelReason = qs(root, '#cancelReason')

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
        selectable: false,
        editable: true,
        eventStartEditable: true,
        eventDurationEditable: true,

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

        events: async (fetchInfo, success, failure) => {
            try {
                const url = buildEventsUrl(providerSelect?.value, fetchInfo.startStr, fetchInfo.endStr)
                const res = await fetch(url, { headers: { Accept: 'application/json' } })
                if (!res.ok) throw new Error(`HTTP ${res.status}`)
                const data = await res.json()
                success(Array.isArray(data) ? data : [])
            } catch (e) {
                failure(e)
            }
        },

        eventClick: (info) => {
            fillDetailPanel(root, info.event)
        },

        eventDrop: async (info) => {
            try {
                await postJson(`/admin/appointments/${info.event.id}/transfer`, {
                    new_provider_id: providerSelect?.value || null,
                    new_start_at: toLocalIsoString(info.event.start),
                    blocks: calcBlocks(info.event.start, info.event.end),
                })

                fillDetailPanel(root, info.event)
                calendar?.refetchEvents()
                notifySuccess('Randevu saati güncellendi.')
            } catch (e) {
                info.revert()
                notifyError(e.message || 'Randevu taşınamadı.')
            }
        },

        eventResize: async (info) => {
            try {
                await postJson(`/admin/appointments/${info.event.id}/resize`, {
                    blocks: calcBlocks(info.event.start, info.event.end),
                })

                fillDetailPanel(root, info.event)
                calendar?.refetchEvents()
                notifySuccess('Randevu süresi güncellendi.')
            } catch (e) {
                info.revert()
                notifyError(e.message || 'Randevu süresi güncellenemedi.')
            }
        },
    })

    calendar.render()

    if (providerSelect) {
        providerSelect.addEventListener('change', () => {
            resetDetailPanel(root)
            calendar?.refetchEvents()
        })
    }

    if (btnCancelAppointment) {
        btnCancelAppointment.addEventListener('click', async () => {
            if (!selectedEventId) {
                notifyError('Önce bir randevu seç.')
                return
            }

            const ok = window.confirm('Seçili randevuyu iptal etmek istiyor musun?')
            if (!ok) return

            try {
                await postJson(`/admin/appointments/${selectedEventId}/cancel`, {
                    reason: cancelReason?.value?.trim() || null,
                })

                resetDetailPanel(root)
                calendar?.refetchEvents()
                notifySuccess('Randevu iptal edildi.')
            } catch (e) {
                notifyError(e.message || 'Randevu iptal edilemedi.')
            }
        })
    }

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

    selectedEventId = null
}
