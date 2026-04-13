import { Calendar } from '@fullcalendar/core'
import interactionPlugin from '@fullcalendar/interaction'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'

let calendar = null
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
function qs(root, sel) {
    return (root || document).querySelector(sel)
}

function qsa(root, sel) {
    return Array.from((root || document).querySelectorAll(sel))
}

function csrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

    if (!token) {
        throw new Error('CSRF token bulunamadı. Admin layout içine meta[name="csrf-token"] eklenmeli.')
    }

    return token
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
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(data),
    })

    const payload = await res.json().catch(() => ({}))

    if (!res.ok) {
        const msg =
            payload?.message ||
            payload?.errors?.slot?.[0] ||
            payload?.errors?.appointment?.[0] ||
            payload?.errors?.reason?.[0] ||
            'İşlem başarısız.'
        const err = new Error(msg)
        err.payload = payload
        throw err
    }

    return payload
}

async function fetchJson(url) {
    const res = await fetch(url, {
        headers: { Accept: 'application/json' },
    })

    const payload = await res.json().catch(() => ({}))

    if (!res.ok) {
        throw new Error(payload?.message || 'İşlem başarısız.')
    }

    return payload
}

async function loadBlockDetail(id) {
    return await fetchJson(`/admin/appointments/blocks/${id}`)
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
                    ${item.is_current ? 'Aktif' : item.status}
                </div>
            </div>
            <div class="mt-1 text-xs text-gray-500 dark:text-zinc-400">
                ${item.provider_name || '-'} • ${item.member_name || '-'}
            </div>
        </div>
    `).join('')
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

function buildEventContent(info) {
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
function setNativeSelectValue(selectEl, value) {
    if (!selectEl) return

    const nextValue = value || 'manual'
    const exists = Array.from(selectEl.options).some((opt) => opt.value === nextValue)

    selectEl.value = exists ? nextValue : 'manual'

    Array.from(selectEl.options).forEach((opt) => {
        opt.selected = opt.value === selectEl.value
    })

    selectEl.dispatchEvent(new Event('input', { bubbles: true }))
    selectEl.dispatchEvent(new Event('change', { bubbles: true }))
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

function fillDetailPanel(root, event) {
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

export default async function init(ctx) {
    const root = ctx?.root || document
    const el = qs(root, '#appointmentsCalendar')
    if (!el) return

    const providerSelect = qs(root, '#providerSelect')
    const btnCancelAppointment = qs(root, '#btnCancelAppointment')
    const cancelReason = qs(root, '#cancelReason')

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

        eventAllow(dropInfo) {
            return dropInfo.start >= new Date()
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

        eventClick: async (info) => {
            fillDetailPanel(root, info.event)

            if (info.event.extendedProps?.entity_type === 'time_off') {
                try {
                    const block = await loadBlockDetail(info.event.extendedProps.entity_id)

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
            }
        },

        eventDidMount(info) {
            const entityType = info.event.extendedProps?.entity_type || 'appointment'

            if (entityType === 'time_off' && info.el) {
                info.el.addEventListener('contextmenu', (e) => {
                    e.preventDefault()
                    fillDetailPanel(root, info.event)
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

    if (providerSelect) {
        providerSelect.addEventListener('change', () => {
            closeContextMenu(root)
            resetDetailPanel(root)
            calendar?.refetchEvents()
        })
    }

    if (btnSaveBlock) {
        btnSaveBlock.addEventListener('click', async () => {
            if (isSavingBlock) return

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
            if (!selectedEventId) {
                notifyError('Önce bir kayıt seç.')
                return
            }

            const event = calendar?.getEventById(selectedEventId)
            const entityType = event?.extendedProps?.entity_type || 'appointment'

            const ok = window.confirm(
                entityType === 'time_off'
                    ? 'Seçili blokajı silmek istiyor musun?'
                    : 'Seçili randevuyu iptal etmek istiyor musun?'
            )

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

    if (ctxEditBlock) {
        ctxEditBlock.addEventListener('click', async () => {
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
            if (!activeContextEvent) return

            const ok = window.confirm('Seçili blokajı silmek istiyor musun?')
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

    selectedEventId = null
    blockModalMode = 'create'
    activeContextEvent = null
    isSavingBlock = false
}
