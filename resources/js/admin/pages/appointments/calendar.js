// resources/js/admin/pages/appointments/calendar.js
import { Calendar } from '@fullcalendar/core'
import interactionPlugin from '@fullcalendar/interaction'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'

let calendar = null

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

function formatDateRange(start, end) {
    if (!start || !end) return '-'

    const fmt = new Intl.DateTimeFormat('tr-TR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })

    return `${fmt.format(start)} - ${fmt.format(end)}`
}

function fillDetailPanel(root, event) {
    const panelEmpty = qs(root, '#panelEmpty')
    const panelContent = qs(root, '#panelContent')
    const pMember = qs(root, '#pMember')
    const pWhen = qs(root, '#pWhen')
    const pDuration = qs(root, '#pDuration')
    const pStatus = qs(root, '#pStatus')

    if (!panelEmpty || !panelContent || !pMember || !pWhen || !pDuration || !pStatus) {
        return
    }

    const blocks = Number(event.extendedProps?.blocks || 1)
    const minutes = blocks * 30

    panelEmpty.classList.add('hidden')
    panelContent.classList.remove('hidden')

    pMember.textContent = event.extendedProps?.member_name || event.title || '-'
    pWhen.textContent = formatDateRange(event.start, event.end)
    pDuration.textContent = `${minutes} dk`
    pStatus.textContent = event.extendedProps?.status || '-'
}

export default async function init(ctx) {
    const root = ctx?.root || document
    const el = qs(root, '#appointmentsCalendar')
    if (!el) return

    const providerSelect = qs(root, '#providerSelect')

    calendar = new Calendar(el, {
        plugins: [interactionPlugin, dayGridPlugin, timeGridPlugin],

        initialView: 'timeGridWeek',
        height: 'auto',

        slotDuration: '00:30:00',
        snapDuration: '00:30:00',
        nowIndicator: true,

        // Aşama 2.5: sadece görüntüleme
        selectable: false,
        editable: false,

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
        }
    })

    calendar.render()

    if (providerSelect) {
        providerSelect.addEventListener('change', () => {
            calendar?.refetchEvents()
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
}
