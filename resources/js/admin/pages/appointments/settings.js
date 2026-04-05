let currentProviderId = null

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

async function fetchJson(url) {
    const res = await fetch(url, { headers: { Accept: 'application/json' } })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    return res.json()
}

async function sendJson(url, method, data = null) {
    const res = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: data ? JSON.stringify(data) : null,
    })

    const payload = await res.json().catch(() => ({}))
    if (!res.ok) {
        throw new Error(payload?.message || 'İşlem başarısız.')
    }
    return payload
}

function notify(type, message) {
    if (window.KTNotify?.show) {
        window.KTNotify.show({ type, message, placement: 'top-end', duration: 2000 })
        return
    }
    alert(message)
}

function dayLabel(day) {
    const map = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi']
    return map[day] || day
}

function renderWorkingHours(root, hours = []) {
    const tbody = qs(root, '#workingHoursBody')
    if (!tbody) return

    const keyed = new Map(hours.map((x) => [Number(x.day_of_week), x]))

    let html = ''
    for (let day = 0; day <= 6; day++) {
        const row = keyed.get(day)
        html += `
      <tr>
        <td>${dayLabel(day)}</td>
        <td>
          <input type="checkbox" data-day-enabled="${day}" ${row?.is_enabled ? 'checked' : ''}>
        </td>
        <td>
          <input type="time" class="kt-input" data-day-start="${day}" value="${row?.start_time ? row.start_time.slice(0,5) : ''}">
        </td>
        <td>
          <input type="time" class="kt-input" data-day-end="${day}" value="${row?.end_time ? row.end_time.slice(0,5) : ''}">
        </td>
      </tr>
    `
    }

    tbody.innerHTML = html
}

function renderTimeOffs(root, items = []) {
    const el = qs(root, '#timeOffList')
    if (!el) return

    if (!items.length) {
        el.innerHTML = `<div class="text-sm text-gray-500">Kayıt yok.</div>`
        return
    }

    el.innerHTML = items.map((item) => `
    <div class="border rounded-xl p-3 flex items-center justify-between gap-3">
      <div>
        <div class="font-medium">${item.reason || 'Açıklama yok'}</div>
        <div class="text-sm text-gray-500">new Date(item.start_at).toLocaleString('tr-TR')</div>
      </div>
      <button type="button" class="kt-btn kt-btn-light-danger" data-timeoff-delete="${item.id}">Sil</button>
    </div>
  `).join('')
}

function renderBlackouts(root, items = []) {
    const el = qs(root, '#blackoutList')
    if (!el) return

    if (!items.length) {
        el.innerHTML = `<div class="text-sm text-gray-500">Kayıt yok.</div>`
        return
    }

    el.innerHTML = items.map((item) => `
    <div class="border rounded-xl p-3 flex items-center justify-between gap-3">
      <div>
        <div class="font-medium">${item.label}</div>
        <div class="text-sm text-gray-500">new Date(item.start_at).toLocaleString('tr-TR')</div>
      </div>
      <button type="button" class="kt-btn kt-btn-light-danger" data-blackout-delete="${item.id}">Sil</button>
    </div>
  `).join('')
}

function renderAvailability(root, items = []) {
    const el = qs(root, '#availabilityList')
    if (!el) return

    if (!items.length) {
        el.innerHTML = `<div class="text-sm text-gray-500">Uygun saat bulunamadı.</div>`
        return
    }

    el.innerHTML = items.map((item) => {
        const date = new Date(item.start_at)
        return `<span class="kt-badge kt-badge-outline">${date.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' })}</span>`
    }).join('')
}

async function loadProviderData(root) {
    if (!currentProviderId) return

    const data = await fetchJson(`/admin/appointments/providers/${currentProviderId}/schedule`)
    renderWorkingHours(root, data.hours || [])
    renderTimeOffs(root, data.time_offs || [])
}

async function loadBlackouts(root) {
    const data = await fetchJson('/admin/appointments/blackouts')
    renderBlackouts(root, data || [])
}

function collectDays(root) {
    const rows = []
    for (let day = 0; day <= 6; day++) {
        rows.push({
            day_of_week: day,
            is_enabled: !!qs(root, `[data-day-enabled="${day}"]`)?.checked,
            start_time: qs(root, `[data-day-start="${day}"]`)?.value || null,
            end_time: qs(root, `[data-day-end="${day}"]`)?.value || null,
        })
    }
    return rows
}

export default async function init(ctx) {
    const root = ctx?.root || document
    const providerSelect = qs(root, '#settingsProviderSelect')
    const btnSaveWorkingHours = qs(root, '#btnSaveWorkingHours')
    const btnAddTimeOff = qs(root, '#btnAddTimeOff')
    const btnAddBlackout = qs(root, '#btnAddBlackout')
    const btnCheckAvailability = qs(root, '#btnCheckAvailability')

    if (!providerSelect) return

    currentProviderId = providerSelect.value

    await loadProviderData(root)
    await loadBlackouts(root)

    providerSelect.addEventListener('change', async () => {
        currentProviderId = providerSelect.value
        await loadProviderData(root)
    })

    btnSaveWorkingHours?.addEventListener('click', async () => {
        try {
            await sendJson(`/admin/appointments/providers/${currentProviderId}/schedule`, 'POST', {
                days: collectDays(root),
            })
            notify('success', 'Çalışma saatleri kaydedildi.')
        } catch (e) {
            notify('error', e.message)
        }
    })

    btnAddTimeOff?.addEventListener('click', async () => {
        try {
            await sendJson(`/admin/appointments/providers/${currentProviderId}/time-offs`, 'POST', {
                start_at: qs(root, '#timeOffStart')?.value,
                end_at: qs(root, '#timeOffEnd')?.value,
                reason: qs(root, '#timeOffReason')?.value || null,
            })
            await loadProviderData(root)
            notify('success', 'Kişisel kapalı zaman eklendi.')
        } catch (e) {
            notify('error', e.message)
        }
    })

    btnAddBlackout?.addEventListener('click', async () => {
        try {
            await sendJson('/admin/appointments/blackouts', 'POST', {
                label: qs(root, '#blackoutLabel')?.value,
                start_at: qs(root, '#blackoutStart')?.value,
                end_at: qs(root, '#blackoutEnd')?.value,
            })
            await loadBlackouts(root)
            notify('success', 'Global kapalı zaman eklendi.')
        } catch (e) {
            notify('error', e.message)
        }
    })

    btnCheckAvailability?.addEventListener('click', async () => {
        try {
            const date = qs(root, '#availabilityDate')?.value
            const blocks = qs(root, '#availabilityBlocks')?.value || 1
            const data = await fetchJson(`/admin/appointments/availability?provider_id=${currentProviderId}&date=${encodeURIComponent(date)}&blocks=${blocks}`)
            renderAvailability(root, data || [])
        } catch (e) {
            notify('error', e.message)
        }
    })

    root.addEventListener('click', async (e) => {
        const timeOffDelete = e.target.closest('[data-timeoff-delete]')
        if (timeOffDelete) {
            try {
                await sendJson(`/admin/appointments/providers/${currentProviderId}/time-offs/${timeOffDelete.getAttribute('data-timeoff-delete')}`, 'DELETE')
                await loadProviderData(root)
                notify('success', 'Kişisel kapalı zaman silindi.')
            } catch (err) {
                notify('error', err.message)
            }
            return
        }

        const blackoutDelete = e.target.closest('[data-blackout-delete]')
        if (blackoutDelete) {
            try {
                await sendJson(`/admin/appointments/blackouts/${blackoutDelete.getAttribute('data-blackout-delete')}`, 'DELETE')
                await loadBlackouts(root)
                notify('success', 'Global kapalı zaman silindi.')
            } catch (err) {
                notify('error', err.message)
            }
        }
    })
}
