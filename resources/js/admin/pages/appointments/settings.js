import { get, request } from '@/core/http'
import { clearDateInputValue, getDateInputValue, setDateInputValue, todayMachineDate } from '@/core/date-input'
import { showConfirmDialog, showToastMessage } from '@/core/swal-alert'

const DAY_DEFS = [
    { value: 1, label: 'Pazartesi', short: 'Pzt' },
    { value: 2, label: 'Salı', short: 'Sal' },
    { value: 3, label: 'Çarşamba', short: 'Çar' },
    { value: 4, label: 'Perşembe', short: 'Per' },
    { value: 5, label: 'Cuma', short: 'Cum' },
    { value: 6, label: 'Cumartesi', short: 'Cmt' },
    { value: 0, label: 'Pazar', short: 'Paz' },
]

const BLOCK_META = {
    manual: {
        label: 'Genel kapalı',
        badgeClass: 'kt-badge kt-badge-sm kt-badge-light-warning',
    },
    break: {
        label: 'Mola',
        badgeClass: 'kt-badge kt-badge-sm kt-badge-light-success',
    },
    meeting: {
        label: 'Toplantı',
        badgeClass: 'kt-badge kt-badge-sm kt-badge-light-primary',
    },
    off: {
        label: 'İzin',
        badgeClass: 'kt-badge kt-badge-sm kt-badge-light-danger',
    },
}

const state = {
    currentProviderId: null,
    provider: null,
    hours: [],
    timeOffs: [],
    blackouts: [],
    availability: [],
}

function qs(root, selector) {
    return (root || document).querySelector(selector)
}

function qsa(root, selector) {
    return Array.from((root || document).querySelectorAll(selector))
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;')
}

function notify(type, message) {
    showToastMessage(type === 'error' ? 'error' : 'success', message, {
        title: type === 'error' ? 'İşlem başarısız' : 'İşlem tamamlandı',
        duration: 2400,
    })
}

async function fetchJson(url) {
    return get(url, { ignoreGlobalError: true })
}

async function sendJson(url, method, data = null) {
    return request(url, { method, data, ignoreGlobalError: true })
}

function normaliseDayOfWeek(value) {
    const numeric = Number(value)
    return numeric === 7 ? 0 : numeric
}

function formatDateTime(value) {
    if (!value) return '-'

    return new Intl.DateTimeFormat('tr-TR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value))
}

function formatDateRange(startAt, endAt) {
    if (!startAt || !endAt) return '-'
    return `${formatDateTime(startAt)} - ${formatDateTime(endAt)}`
}

function formatMinutes(totalMinutes) {
    const safe = Math.max(0, Number(totalMinutes || 0))
    const hours = Math.floor(safe / 60)
    const minutes = safe % 60

    if (hours && minutes) return `${hours} sa ${minutes} dk`
    if (hours) return `${hours} saat`
    return `${minutes} dk`
}

function minutesBetween(startTime, endTime) {
    if (!startTime || !endTime) return 0

    const [startHour, startMinute] = String(startTime).split(':').map(Number)
    const [endHour, endMinute] = String(endTime).split(':').map(Number)

    return Math.max(0, ((endHour * 60) + endMinute) - ((startHour * 60) + startMinute))
}

function durationLabel(startAt, endAt) {
    if (!startAt || !endAt) return '-'
    const minutes = Math.max(0, Math.round((new Date(endAt).getTime() - new Date(startAt).getTime()) / 60000))
    return formatMinutes(minutes)
}

function blockMeta(type) {
    return BLOCK_META[type] || BLOCK_META.manual
}

function getSelectedProviderMeta(selectEl) {
    const option = selectEl?.selectedOptions?.[0]

    return {
        id: option?.value || '',
        name: option?.dataset?.providerName || option?.textContent?.trim() || '-',
        title: option?.dataset?.providerTitle || 'Ünvan tanımsız',
    }
}

function setSelectValue(selectEl, value) {
    if (!selectEl) return

    const nextValue = value === null || value === undefined ? '' : String(value)
    const matchedOption = Array.from(selectEl.options).find((option) => String(option.value) === nextValue)
    if (!matchedOption) return

    selectEl.value = matchedOption.value

    try {
        const KT = window.KTSelect || window.ktSelect
        const instance =
            (KT?.getInstance && KT.getInstance(selectEl)) ||
            (KT?.getOrCreateInstance && KT.getOrCreateInstance(selectEl)) ||
            selectEl.ktSelectInstance

        if (instance?.setValue) instance.setValue(matchedOption.value)
        if (instance?.update) instance.update()
        if (instance?.render) instance.render()
    } catch (_) {}
}

function setButtonBusy(button, busy, idleLabel, busyLabel) {
    if (!button) return

    button.disabled = busy
    button.classList.toggle('opacity-60', busy)
    button.classList.toggle('pointer-events-none', busy)
    button.textContent = busy ? busyLabel : idleLabel
}

function applyWorkingHourRowState(row) {
    if (!row) return

    const enabled = row.querySelector('[data-day-enabled]')?.checked ?? false
    const startInput = row.querySelector('[data-day-start]')
    const endInput = row.querySelector('[data-day-end]')
    const statusBadge = row.querySelector('[data-day-status]')

    if (startInput) startInput.disabled = !enabled
    if (endInput) endInput.disabled = !enabled

    row.classList.toggle('opacity-60', !enabled)

    if (statusBadge) {
        statusBadge.className = enabled
            ? 'kt-badge kt-badge-sm kt-badge-light-success'
            : 'kt-badge kt-badge-sm kt-badge-light'
        statusBadge.textContent = enabled ? 'Açık' : 'Kapalı'
    }
}

function renderWorkingHours(root, hours = []) {
    const tbody = qs(root, '#workingHoursBody')
    if (!tbody) return

    const keyed = new Map(
        hours.map((item) => [normaliseDayOfWeek(item.day_of_week), item])
    )

    tbody.innerHTML = DAY_DEFS.map((day) => {
        const row = keyed.get(day.value)
        const startTime = row?.start_time ? String(row.start_time).slice(0, 5) : ''
        const endTime = row?.end_time ? String(row.end_time).slice(0, 5) : ''
        const totalMinutes = row?.is_enabled ? minutesBetween(startTime, endTime) : 0

        return `
            <tr data-day-row="${day.value}">
                <td>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-primary/10 text-primary font-semibold">
                            ${escapeHtml(day.short)}
                        </span>
                        <div>
                            <div class="font-medium text-foreground">${escapeHtml(day.label)}</div>
                            <div class="text-xs text-muted-foreground">Haftalık plan parcasi</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex items-center gap-3">
                        <label class="inline-flex items-center gap-2 text-sm text-foreground">
                            <input type="checkbox" class="kt-checkbox kt-checkbox-sm" data-day-enabled="${day.value}" ${row?.is_enabled ? 'checked' : ''}>
                            Çalışma açık
                        </label>
                        <span data-day-status class="${row?.is_enabled ? 'kt-badge kt-badge-sm kt-badge-light-success' : 'kt-badge kt-badge-sm kt-badge-light'}">
                            ${row?.is_enabled ? 'Açık' : 'Kapalı'}
                        </span>
                    </div>
                </td>
                <td>
                    <input type="time" class="kt-input w-full" data-day-start="${day.value}" value="${escapeHtml(startTime)}">
                </td>
                <td>
                    <input type="time" class="kt-input w-full" data-day-end="${day.value}" value="${escapeHtml(endTime)}">
                </td>
                <td>
                    <span class="text-sm font-medium text-foreground" data-day-duration="${day.value}">
                        ${row?.is_enabled ? escapeHtml(formatMinutes(totalMinutes)) : '-'}
                    </span>
                </td>
            </tr>
        `
    }).join('')

    qsa(tbody, '[data-day-row]').forEach((row) => applyWorkingHourRowState(row))
    refreshScheduleSummary(root)
}

function collectDays(root) {
    return DAY_DEFS.map((day) => ({
        day_of_week: day.value,
        is_enabled: Boolean(qs(root, `[data-day-enabled="${day.value}"]`)?.checked),
        start_time: qs(root, `[data-day-start="${day.value}"]`)?.value || null,
        end_time: qs(root, `[data-day-end="${day.value}"]`)?.value || null,
    }))
}

function renderOverviewStats(root, overrides = {}) {
    const providerCount = Number(root.dataset.providerCount || 0)
    const enabledDays = overrides.enabledDays ?? state.hours.filter((item) => item.is_enabled).length
    const weeklyMinutes = overrides.weeklyMinutes ?? state.hours
        .filter((item) => item.is_enabled)
        .reduce((total, item) => total + minutesBetween(item.start_time, item.end_time), 0)

    const statProviders = qs(root, '#settingsStatProviders')
    const statEnabledDays = qs(root, '#settingsStatEnabledDays')
    const statEnabledDaysHint = qs(root, '#settingsStatEnabledDaysHint')
    const statTimeOffs = qs(root, '#settingsStatTimeOffs')
    const statBlackouts = qs(root, '#settingsStatBlackouts')
    const timeOffBadge = qs(root, '#timeOffCountBadge')
    const blackoutBadge = qs(root, '#blackoutCountBadge')

    if (statProviders) statProviders.textContent = String(providerCount)
    if (statEnabledDays) statEnabledDays.textContent = String(enabledDays)
    if (statEnabledDaysHint) {
        statEnabledDaysHint.textContent = enabledDays > 0
            ? `${formatMinutes(weeklyMinutes)} toplam kapasite`
            : 'Çalışma günü tanimli degil'
    }
    if (statTimeOffs) statTimeOffs.textContent = String(state.timeOffs.length)
    if (statBlackouts) statBlackouts.textContent = String(state.blackouts.length)
    if (timeOffBadge) timeOffBadge.textContent = `${state.timeOffs.length} kayıt`
    if (blackoutBadge) blackoutBadge.textContent = `${state.blackouts.length} kayıt`
}

function refreshScheduleSummary(root) {
    const days = collectDays(root)
    const enabledDays = days.filter((day) => day.is_enabled)
    const weeklyMinutes = enabledDays.reduce((total, day) => total + minutesBetween(day.start_time, day.end_time), 0)
    const coverage = enabledDays.length >= 6 ? 'Yüksek kapsama' : enabledDays.length >= 4 ? 'Dengeli kapsama' : enabledDays.length > 0 ? 'Sinirli kapsama' : 'Kapalı takvim'

    days.forEach((day) => {
        const durationEl = qs(root, `[data-day-duration="${day.day_of_week}"]`)
        if (durationEl) {
            durationEl.textContent = day.is_enabled && day.start_time && day.end_time
                ? formatMinutes(minutesBetween(day.start_time, day.end_time))
                : '-'
        }
    })

    const enabledDaysEl = qs(root, '#scheduleEnabledDaysCount')
    const weeklyHoursEl = qs(root, '#scheduleWeeklyHours')
    const coverageEl = qs(root, '#scheduleCoverageLabel')
    const statusBadge = qs(root, '#workingHoursStatusBadge')

    if (enabledDaysEl) enabledDaysEl.textContent = `${enabledDays.length} / 7`
    if (weeklyHoursEl) weeklyHoursEl.textContent = formatMinutes(weeklyMinutes)
    if (coverageEl) coverageEl.textContent = coverage
    if (statusBadge) statusBadge.textContent = enabledDays.length > 0 ? `${enabledDays.length} gün açık` : 'Tüm günler kapalı'

    renderOverviewStats(root, { enabledDays: enabledDays.length, weeklyMinutes })
}

function renderProviderSummary(root) {
    const providerName = qs(root, '#settingsProviderName')
    const providerTitle = qs(root, '#settingsProviderTitle')
    const weeklyHours = qs(root, '#settingsWeeklyHours')
    const nextTimeOff = qs(root, '#settingsNextTimeOff')
    const coverageText = qs(root, '#settingsCoverageText')
    const planBadge = qs(root, '#settingsPlanBadge')

    if (providerName) providerName.textContent = state.provider?.name || '-'
    if (providerTitle) providerTitle.textContent = state.provider?.title || 'Ünvan tanımsız'

    const enabledDays = state.hours.filter((item) => item.is_enabled).length
    const weeklyMinutes = state.hours
        .filter((item) => item.is_enabled)
        .reduce((total, item) => total + minutesBetween(item.start_time, item.end_time), 0)

    const upcomingTimeOff = state.timeOffs.find((item) => item.end_at && new Date(item.end_at) > new Date())

    if (weeklyHours) {
        weeklyHours.textContent = enabledDays > 0
            ? `${enabledDays} açık gün | ${formatMinutes(weeklyMinutes)}`
            : 'Çalışma plani tanımsız'
    }

    if (nextTimeOff) {
        nextTimeOff.textContent = upcomingTimeOff
            ? `${blockMeta(upcomingTimeOff.block_type).label} | ${formatDateRange(upcomingTimeOff.start_at, upcomingTimeOff.end_at)}`
            : 'Planlanmış blokaj yok'
    }

    if (coverageText) {
        if (enabledDays === 0) {
            coverageText.textContent = 'Seçili kişi için hiç açık gün tanimli degil. Randevu alınabilmesi için önce haftalık plan kaydedilmelidir.'
        } else if (state.timeOffs.length > 0) {
            coverageText.textContent = `${enabledDays} açık gün ve ${state.timeOffs.length} kişisel blokaj kaydı var. Global blackoutlar uygunlük testinde otomatik hesaba katilir.`
        } else {
            coverageText.textContent = `${enabledDays} açık gün içeren bir plan aktif. Global blackout dışında kişi bazlı ek kapatma bulunmuyor.`
        }
    }

    if (planBadge) {
        planBadge.className = enabledDays > 0
            ? 'kt-badge kt-badge-sm kt-badge-light-success'
            : 'kt-badge kt-badge-sm kt-badge-light'
        planBadge.textContent = enabledDays > 0 ? 'Plan aktif' : 'Plan tanımsız'
    }
}

function renderTimeOffs(root, items = []) {
    const list = qs(root, '#timeOffList')
    if (!list) return

    if (!items.length) {
        list.innerHTML = `
            <div class="rounded-2xl border border-dashed border-border bg-muted/15 px-4 py-5 text-sm text-muted-foreground">
                Seçili kişi için kişisel kapalı zaman kaydı yok.
            </div>
        `
        return
    }

    list.innerHTML = items.map((item) => {
        const meta = blockMeta(item.block_type)

        return `
            <article class="rounded-3xl border border-border bg-background px-4 py-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="${meta.badgeClass}">${meta.label}</span>
                            <span class="kt-badge kt-badge-sm kt-badge-light">${escapeHtml(durationLabel(item.start_at, item.end_at))}</span>
                        </div>
                        <div class="mt-3 font-medium text-foreground">${escapeHtml(item.reason || 'Açıklama girilmedi')}</div>
                        <div class="mt-2 text-sm leading-6 text-muted-foreground">${escapeHtml(formatDateRange(item.start_at, item.end_at))}</div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-light-primary" data-timeoff-edit="${item.id}">
                            Düzenle
                        </button>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-light-danger" data-timeoff-delete="${item.id}">
                            Sil
                        </button>
                    </div>
                </div>
            </article>
        `
    }).join('')
}

function renderBlackouts(root, items = []) {
    const list = qs(root, '#blackoutList')
    if (!list) return

    if (!items.length) {
        list.innerHTML = `
            <div class="rounded-2xl border border-dashed border-border bg-muted/15 px-4 py-5 text-sm text-muted-foreground">
                Global blackout tanimli degil.
            </div>
        `
        return
    }

    list.innerHTML = items.map((item) => `
        <article class="rounded-3xl border border-border bg-background px-4 py-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="kt-badge kt-badge-sm kt-badge-light-danger">Global</span>
                        <span class="kt-badge kt-badge-sm kt-badge-light">${escapeHtml(durationLabel(item.start_at, item.end_at))}</span>
                    </div>
                    <div class="mt-3 font-medium text-foreground">${escapeHtml(item.label)}</div>
                    <div class="mt-2 text-sm leading-6 text-muted-foreground">${escapeHtml(formatDateRange(item.start_at, item.end_at))}</div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-light-primary" data-blackout-edit="${item.id}">
                        Düzenle
                    </button>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-light-danger" data-blackout-delete="${item.id}">
                        Sil
                    </button>
                </div>
            </div>
        </article>
    `).join('')
}

function renderAvailability(root, items = []) {
    const list = qs(root, '#availabilityList')
    const slotCount = qs(root, '#availabilitySlotCount')
    const summary = qs(root, '#availabilitySummaryText')
    const badge = qs(root, '#availabilityCountBadge')

    if (slotCount) slotCount.textContent = String(items.length)

    if (badge) {
        badge.className = items.length > 0
            ? 'kt-badge kt-badge-sm kt-badge-light-success'
            : 'kt-badge kt-badge-sm kt-badge-light'
        badge.textContent = items.length > 0 ? `${items.length} slot bulundu` : 'Bos slot yok'
    }

    if (summary) {
        summary.textContent = items.length > 0
            ? `Seçilen kombinasyon için ${items.length} başlangıç saati uygun görünüyor.`
            : 'Seçilen tarih ve süre kombinasyonu için uygun saat bulunamadı.'
    }

    if (!list) return

    if (!items.length) {
        list.innerHTML = `
            <div class="rounded-2xl border border-dashed border-border bg-muted/15 px-4 py-5 text-sm text-muted-foreground">
                Uygun slot bulunamadı. Çalışma plani, kişisel blokajlar veya global blackout bu günü kapatiyor olabilir.
            </div>
        `
        return
    }

    list.innerHTML = items.map((item) => {
        const start = formatDateTime(item.start_at)
        const end = formatDateTime(item.end_at)

        return `
            <span class="inline-flex items-center gap-2 rounded-full border border-success/20 bg-success/10 px-3 py-2 text-sm font-medium text-success">
                <i class="ki-outline ki-time text-base"></i>
                ${escapeHtml(start.slice(-5))} - ${escapeHtml(end.slice(-5))}
            </span>
        `
    }).join('')
}

function resetTimeOffForm(root) {
    const editingId = qs(root, '#timeOffEditingId')
    const start = qs(root, '#timeOffStart')
    const end = qs(root, '#timeOffEnd')
    const reason = qs(root, '#timeOffReason')
    const type = qs(root, '#timeOffType')
    const button = qs(root, '#btnAddTimeOff')
    const cancel = qs(root, '#btnCancelTimeOffEdit')

    if (editingId) editingId.value = ''
    clearDateInputValue(start)
    clearDateInputValue(end)
    if (reason) reason.value = ''
    if (type) setSelectValue(type, 'manual')
    if (button) button.textContent = 'Kişisel blok ekle'
    if (cancel) cancel.classList.add('hidden')
}

function populateTimeOffForm(root, item) {
    const editingId = qs(root, '#timeOffEditingId')
    const start = qs(root, '#timeOffStart')
    const end = qs(root, '#timeOffEnd')
    const reason = qs(root, '#timeOffReason')
    const type = qs(root, '#timeOffType')
    const button = qs(root, '#btnAddTimeOff')
    const cancel = qs(root, '#btnCancelTimeOffEdit')

    if (editingId) editingId.value = String(item.id)
    setDateInputValue(start, item.start_at || '')
    setDateInputValue(end, item.end_at || '')
    if (reason) reason.value = item.reason || ''
    if (type) setSelectValue(type, item.block_type || 'manual')
    if (button) button.textContent = 'Kişisel blok kaydini güncelle'
    if (cancel) cancel.classList.remove('hidden')
}

function resetBlackoutForm(root) {
    const editingId = qs(root, '#blackoutEditingId')
    const label = qs(root, '#blackoutLabel')
    const start = qs(root, '#blackoutStart')
    const end = qs(root, '#blackoutEnd')
    const button = qs(root, '#btnAddBlackout')
    const cancel = qs(root, '#btnCancelBlackoutEdit')

    if (editingId) editingId.value = ''
    if (label) label.value = ''
    clearDateInputValue(start)
    clearDateInputValue(end)
    if (button) button.textContent = 'Global blackout ekle'
    if (cancel) cancel.classList.add('hidden')
}

function populateBlackoutForm(root, item) {
    const editingId = qs(root, '#blackoutEditingId')
    const label = qs(root, '#blackoutLabel')
    const start = qs(root, '#blackoutStart')
    const end = qs(root, '#blackoutEnd')
    const button = qs(root, '#btnAddBlackout')
    const cancel = qs(root, '#btnCancelBlackoutEdit')

    if (editingId) editingId.value = String(item.id)
    if (label) label.value = item.label || ''
    setDateInputValue(start, item.start_at || '')
    setDateInputValue(end, item.end_at || '')
    if (button) button.textContent = 'Global blackout güncelle'
    if (cancel) cancel.classList.remove('hidden')
}

function applyTemplateValue(root, dayOfWeek, enabled, startTime = '', endTime = '') {
    const row = qs(root, `[data-day-row="${dayOfWeek}"]`)
    const enabledInput = qs(root, `[data-day-enabled="${dayOfWeek}"]`)
    const startInput = qs(root, `[data-day-start="${dayOfWeek}"]`)
    const endInput = qs(root, `[data-day-end="${dayOfWeek}"]`)

    if (enabledInput) enabledInput.checked = enabled
    if (startInput) startInput.value = enabled ? startTime : ''
    if (endInput) endInput.value = enabled ? endTime : ''
    applyWorkingHourRowState(row)
}

function applyScheduleTemplate(root, template) {
    if (template === 'clear_all') {
        DAY_DEFS.forEach((day) => applyTemplateValue(root, day.value, false))
        refreshScheduleSummary(root)
        return
    }

    if (template === 'weekday_standard') {
        DAY_DEFS.forEach((day) => {
            const isWeekday = [1, 2, 3, 4, 5].includes(day.value)
            applyTemplateValue(root, day.value, isWeekday, '09:00', '18:00')
        })
        refreshScheduleSummary(root)
        return
    }

    if (template === 'clinic_extended') {
        DAY_DEFS.forEach((day) => {
            if ([1, 2, 3, 4, 5].includes(day.value)) {
                applyTemplateValue(root, day.value, true, '10:00', '19:00')
                return
            }

            if (day.value === 6) {
                applyTemplateValue(root, day.value, true, '10:00', '15:00')
                return
            }

            applyTemplateValue(root, day.value, false)
        })
        refreshScheduleSummary(root)
    }
}

async function loadProviderData(root) {
    if (!state.currentProviderId) return

    const data = await fetchJson(`/admin/appointments/providers/${state.currentProviderId}/schedule`)

    state.provider = data?.provider || getSelectedProviderMeta(qs(root, '#settingsProviderSelect'))
    state.hours = Array.isArray(data?.hours) ? data.hours : []
    state.timeOffs = Array.isArray(data?.time_offs) ? data.time_offs : []

    renderWorkingHours(root, state.hours)
    renderTimeOffs(root, state.timeOffs)
    renderProviderSummary(root)
    renderOverviewStats(root)
}

async function loadBlackouts(root) {
    const data = await fetchJson('/admin/appointments/blackouts')
    state.blackouts = Array.isArray(data) ? data : []
    renderBlackouts(root, state.blackouts)
    renderOverviewStats(root)
}

function findTimeOffById(id) {
    return state.timeOffs.find((item) => Number(item.id) === Number(id)) || null
}

function findBlackoutById(id) {
    return state.blackouts.find((item) => Number(item.id) === Number(id)) || null
}

export default async function init(ctx) {
    const root = ctx?.root || document
    const providerSelect = qs(root, '#settingsProviderSelect')
    const btnSaveWorkingHours = qs(root, '#btnSaveWorkingHours')
    const btnAddTimeOff = qs(root, '#btnAddTimeOff')
    const btnAddBlackout = qs(root, '#btnAddBlackout')
    const btnCheckAvailability = qs(root, '#btnCheckAvailability')
    const btnCancelTimeOffEdit = qs(root, '#btnCancelTimeOffEdit')
    const btnCancelBlackoutEdit = qs(root, '#btnCancelBlackoutEdit')

    if (!providerSelect) return

    state.currentProviderId = providerSelect.value

    const availabilityDate = qs(root, '#availabilityDate')
    if (availabilityDate && !availabilityDate.value) {
        setDateInputValue(availabilityDate, todayMachineDate())
    }

    await Promise.all([
        loadBlackouts(root),
        loadProviderData(root),
    ])

    providerSelect.addEventListener('change', async () => {
        state.currentProviderId = providerSelect.value
        state.provider = getSelectedProviderMeta(providerSelect)
        resetTimeOffForm(root)
        renderAvailability(root, [])
        await loadProviderData(root)
    })

    btnSaveWorkingHours?.addEventListener('click', async () => {
        try {
            setButtonBusy(btnSaveWorkingHours, true, 'Çalışma saatlerini kaydet', 'Kaydediliyor...')
            await sendJson(`/admin/appointments/providers/${state.currentProviderId}/schedule`, 'POST', {
                days: collectDays(root),
            })
            await loadProviderData(root)
            notify('success', 'Çalışma saatleri kaydedildi.')
        } catch (error) {
            notify('error', error.message || 'Çalışma saatleri kaydedilemedi.')
        } finally {
            setButtonBusy(btnSaveWorkingHours, false, 'Çalışma saatlerini kaydet', 'Kaydediliyor...')
        }
    })

    btnAddTimeOff?.addEventListener('click', async () => {
        const editingId = qs(root, '#timeOffEditingId')?.value
        const payload = {
            start_at: getDateInputValue(qs(root, '#timeOffStart')),
            end_at: getDateInputValue(qs(root, '#timeOffEnd')),
            reason: qs(root, '#timeOffReason')?.value || null,
            block_type: qs(root, '#timeOffType')?.value || 'manual',
        }

        const isEditing = Boolean(editingId)
        const idleLabel = isEditing ? 'Kişisel blok kaydini güncelle' : 'Kişisel blok ekle'
        const busyLabel = isEditing ? 'Güncelleniyor...' : 'Ekleniyor...'

        try {
            setButtonBusy(btnAddTimeOff, true, idleLabel, busyLabel)
            if (isEditing) {
                await sendJson(`/admin/appointments/providers/${state.currentProviderId}/time-offs/${editingId}`, 'PUT', payload)
            } else {
                await sendJson(`/admin/appointments/providers/${state.currentProviderId}/time-offs`, 'POST', payload)
            }
            resetTimeOffForm(root)
            await loadProviderData(root)
            notify('success', isEditing ? 'Kişisel kapalı zaman güncellendi.' : 'Kişisel kapalı zaman eklendi.')
        } catch (error) {
            notify('error', error.message || 'Kişisel kapalı zaman kaydedilemedi.')
        } finally {
            setButtonBusy(btnAddTimeOff, false, idleLabel, busyLabel)
        }
    })

    btnCancelTimeOffEdit?.addEventListener('click', () => resetTimeOffForm(root))

    btnAddBlackout?.addEventListener('click', async () => {
        const editingId = qs(root, '#blackoutEditingId')?.value
        const payload = {
            label: qs(root, '#blackoutLabel')?.value,
            start_at: getDateInputValue(qs(root, '#blackoutStart')),
            end_at: getDateInputValue(qs(root, '#blackoutEnd')),
        }

        const isEditing = Boolean(editingId)
        const idleLabel = isEditing ? 'Global blackout güncelle' : 'Global blackout ekle'
        const busyLabel = isEditing ? 'Güncelleniyor...' : 'Ekleniyor...'

        try {
            setButtonBusy(btnAddBlackout, true, idleLabel, busyLabel)
            if (isEditing) {
                await sendJson(`/admin/appointments/blackouts/${editingId}`, 'PUT', payload)
            } else {
                await sendJson('/admin/appointments/blackouts', 'POST', payload)
            }
            resetBlackoutForm(root)
            await loadBlackouts(root)
            notify('success', isEditing ? 'Global blackout güncellendi.' : 'Global blackout eklendi.')
        } catch (error) {
            notify('error', error.message || 'Global blackout kaydedilemedi.')
        } finally {
            setButtonBusy(btnAddBlackout, false, idleLabel, busyLabel)
        }
    })

    btnCancelBlackoutEdit?.addEventListener('click', () => resetBlackoutForm(root))

    btnCheckAvailability?.addEventListener('click', async () => {
        try {
            setButtonBusy(btnCheckAvailability, true, 'Uygun saatleri getir', 'Hesaplanıyor...')
            const date = getDateInputValue(qs(root, '#availabilityDate'))
            const blocks = qs(root, '#availabilityBlocks')?.value || 1

            const data = await fetchJson(
                `/admin/appointments/availability?provider_id=${encodeURIComponent(state.currentProviderId)}&date=${encodeURIComponent(date)}&blocks=${encodeURIComponent(blocks)}`
            )

            state.availability = Array.isArray(data) ? data : []
            renderAvailability(root, state.availability)
        } catch (error) {
            notify('error', error.message || 'Uygunlük hesaplanamadi.')
        } finally {
            setButtonBusy(btnCheckAvailability, false, 'Uygun saatleri getir', 'Hesaplanıyor...')
        }
    })

    root.addEventListener('change', (event) => {
        if (
            event.target.matches('[data-day-enabled]') ||
            event.target.matches('[data-day-start]') ||
            event.target.matches('[data-day-end]')
        ) {
            const row = event.target.closest('[data-day-row]')
            applyWorkingHourRowState(row)
            refreshScheduleSummary(root)
        }
    })

    root.addEventListener('click', async (event) => {
        const templateButton = event.target.closest('[data-schedule-template]')
        if (templateButton) {
            applyScheduleTemplate(root, templateButton.getAttribute('data-schedule-template'))
            return
        }

        const timeOffEdit = event.target.closest('[data-timeoff-edit]')
        if (timeOffEdit) {
            const item = findTimeOffById(timeOffEdit.getAttribute('data-timeoff-edit'))
            if (item) populateTimeOffForm(root, item)
            return
        }

        const timeOffDelete = event.target.closest('[data-timeoff-delete]')
        if (timeOffDelete) {
            const id = timeOffDelete.getAttribute('data-timeoff-delete')
            const item = findTimeOffById(id)
            const confirmed = await showConfirmDialog({
                type: 'warning',
                title: 'Kişisel blok silinsin mi?',
                message: item ? `${blockMeta(item.block_type).label} kaydı kaldırilacak.` : 'Seçili kayıt silinecek.',
                confirmButtonText: 'Blokaji sil',
                cancelButtonText: 'Vazgeç',
            })

            if (!confirmed) return

            try {
                await sendJson(`/admin/appointments/providers/${state.currentProviderId}/time-offs/${id}`, 'DELETE')
                resetTimeOffForm(root)
                await loadProviderData(root)
                notify('success', 'Kişisel kapalı zaman silindi.')
            } catch (error) {
                notify('error', error.message || 'Kişisel kapalı zaman silinemedi.')
            }
            return
        }

        const blackoutEdit = event.target.closest('[data-blackout-edit]')
        if (blackoutEdit) {
            const item = findBlackoutById(blackoutEdit.getAttribute('data-blackout-edit'))
            if (item) populateBlackoutForm(root, item)
            return
        }

        const blackoutDelete = event.target.closest('[data-blackout-delete]')
        if (blackoutDelete) {
            const id = blackoutDelete.getAttribute('data-blackout-delete')
            const item = findBlackoutById(id)
            const confirmed = await showConfirmDialog({
                type: 'warning',
                title: 'Global blackout silinsin mi?',
                message: item ? `${item.label} kaydı tüm takvimlerden kaldırılacak.` : 'Seçili blackout kaydı silinecek.',
                confirmButtonText: 'Kaydı sil',
                cancelButtonText: 'Vazgeç',
            })

            if (!confirmed) return

            try {
                await sendJson(`/admin/appointments/blackouts/${id}`, 'DELETE')
                resetBlackoutForm(root)
                await loadBlackouts(root)
                notify('success', 'Global kapalı zaman silindi.')
            } catch (error) {
                notify('error', error.message || 'Global kapalı zaman silinemedi.')
            }
        }
    })
}
