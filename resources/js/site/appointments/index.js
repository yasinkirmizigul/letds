let providerId = null;
let selectedSlot = null;
let selectedDate = null;
let currentMonth = new Date();
let isMonthLoading = false;
let isDayLoading = false;
let isSubmitting = false;
let isCancelling = false;
let isRescheduleMode = false;

document.addEventListener('DOMContentLoaded', () => {
    const providerEl = document.getElementById('provider');
    const dateEl = document.getElementById('date');
    const cancelBtn = document.getElementById('cancelBtn');
    const rescheduleBtn = document.getElementById('rescheduleBtn');
    const prevMonthBtn = document.getElementById('prevMonthBtn');
    const nextMonthBtn = document.getElementById('nextMonthBtn');

    providerId = providerEl ? providerEl.value : null;

    bindActiveAppointmentActions(cancelBtn, rescheduleBtn);

    if (prevMonthBtn) {
        prevMonthBtn.addEventListener('click', () => {
            currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);
            renderCalendar();
        });
    }

    if (nextMonthBtn) {
        nextMonthBtn.addEventListener('click', () => {
            currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);
            renderCalendar();
        });
    }

    if (providerEl) {
        providerEl.addEventListener('change', async () => {
            providerId = providerEl.value;
            selectedSlot = null;
            selectedDate = null;
            clearSlots();
            clearDate();
            await renderCalendar();
        });
    }

    if (dateEl) {
        dateEl.addEventListener('change', () => {
            selectedDate = dateEl.value || null;
            loadSlots();
            highlightSelectedDate();
        });
    }

    if (window.__HAS_ACTIVE_APPOINTMENT__ && !window.__RESCHEDULE_MODE__) {
        hideBookingUi();
        return;
    }

    renderCalendar();
});

function bindActiveAppointmentActions(cancelBtn, rescheduleBtn) {
    if (cancelBtn) {
        cancelBtn.addEventListener('click', cancelAppointment);
    }

    if (rescheduleBtn) {
        rescheduleBtn.addEventListener('click', () => {
            isRescheduleMode = true;
            window.__RESCHEDULE_MODE__ = true;
            showBookingUi();
            showRescheduleBanner();
            renderCalendar();
        });
    }
}

function hideBookingUi() {
    const bookingPanel = document.getElementById('booking-panel');
    if (bookingPanel) {
        bookingPanel.classList.add('hidden');
    }
}

function showBookingUi() {
    const bookingPanel = document.getElementById('booking-panel');
    if (bookingPanel) {
        bookingPanel.classList.remove('hidden');
    }
}

function showRescheduleBanner() {
    const banner = document.getElementById('reschedule-mode-banner');
    if (banner) {
        banner.classList.remove('hidden');
    }
}

function clearSlots() {
    const slots = document.getElementById('slots');
    const empty = document.getElementById('slot-empty');

    if (slots) {
        slots.innerHTML = '';
    }

    if (empty) {
        empty.classList.add('hidden');
    }
}

function clearDate() {
    const dateEl = document.getElementById('date');
    if (dateEl) {
        dateEl.value = '';
    }
}

function updateCalendarTitle() {
    const titleEl = document.getElementById('calendarTitle');
    if (!titleEl) return;

    titleEl.textContent = currentMonth.toLocaleDateString('tr-TR', {
        year: 'numeric',
        month: 'long'
    });
}

async function renderCalendar() {
    const container = document.getElementById('calendar');
    if (!container || !providerId || isMonthLoading) return;

    isMonthLoading = true;
    updateCalendarTitle();
    container.innerHTML = `<div class="text-sm text-gray-500">Takvim yükleniyor...</div>`;

    try {
        const year = currentMonth.getFullYear();
        const month = currentMonth.getMonth();
        const monthStr = `${year}-${pad(month + 1)}-01`;

        const res = await fetch(`/member/appointments/days?provider_id=${encodeURIComponent(providerId)}&month=${monthStr}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!res.ok) {
            throw new Error('Takvim verisi alınamadı.');
        }

        const map = await res.json();
        const todayStr = todayDateString();

        let html = `<div class="grid grid-cols-7 gap-2">`;
        const firstDay = new Date(year, month, 1).getDay() || 7;
        const totalDays = new Date(year, month + 1, 0).getDate();

        for (let i = 1; i < firstDay; i++) {
            html += `<div></div>`;
        }

        for (let d = 1; d <= totalDays; d++) {
            const dateStr = `${year}-${pad(month + 1)}-${pad(d)}`;
            const dayData = map[dateStr] || null;
            const has = !!dayData?.has_availability;
            const freeCount = dayData?.free_count ?? 0;
            const isPast = dateStr < todayStr;
            const isDisabled = isPast || !has;

            html += `
                <button
                    type="button"
                    class="calendar-day p-2 text-center border rounded transition ${
                isDisabled
                    ? 'bg-gray-100 opacity-50 cursor-not-allowed'
                    : 'bg-green-50 hover:bg-green-100 cursor-pointer'
            }"
                    ${!isDisabled ? `data-date="${dateStr}"` : 'disabled'}
                    title="${isDisabled ? 'Uygun slot yok' : `${freeCount} uygun slot`}"
                >
                    <div>${d}</div>
                    ${!isDisabled ? `<div class="text-[11px] text-gray-500 mt-1">${freeCount}</div>` : ''}
                </button>
            `;
        }

        html += `</div>`;
        container.innerHTML = html;

        container.querySelectorAll('[data-date]').forEach(el => {
            el.addEventListener('click', () => selectDate(el.dataset.date));
        });

        highlightSelectedDate();
    } catch (error) {
        container.innerHTML = `<div class="text-sm text-red-600">Takvim yüklenemedi.</div>`;
    } finally {
        isMonthLoading = false;
    }
}

function highlightSelectedDate() {
    document.querySelectorAll('.calendar-day').forEach(el => {
        el.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50');
    });

    if (!selectedDate) return;

    const selectedEl = document.querySelector(`.calendar-day[data-date="${selectedDate}"]`);
    if (selectedEl) {
        selectedEl.classList.add('ring-2', 'ring-blue-500', 'bg-blue-50');
    }
}

async function loadSlots() {
    const date = document.getElementById('date')?.value;
    const container = document.getElementById('slots');
    const empty = document.getElementById('slot-empty');

    if (!date || !container || isDayLoading) return;

    isDayLoading = true;
    selectedSlot = null;
    container.innerHTML = `<div class="text-sm text-gray-500">Saatler yükleniyor...</div>`;
    empty?.classList.add('hidden');

    try {
        const res = await fetch(`/member/appointments/availability?provider_id=${encodeURIComponent(providerId)}&date=${encodeURIComponent(date)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!res.ok) {
            throw new Error('Slot verisi alınamadı.');
        }

        const data = await res.json();
        container.innerHTML = '';

        if (!Array.isArray(data) || !data.length) {
            empty?.classList.remove('hidden');
            return;
        }

        data.forEach(slot => {
            container.appendChild(createSlotElement(slot));
        });
    } catch (error) {
        container.innerHTML = `<div class="text-sm text-red-600">Saatler yüklenemedi.</div>`;
    } finally {
        isDayLoading = false;
    }
}

function selectDate(date) {
    const dateEl = document.getElementById('date');
    if (!dateEl) return;

    selectedDate = date;
    dateEl.value = date;
    highlightSelectedDate();
    loadSlots();
}

function createSlotElement(slot) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'p-3 border rounded cursor-pointer text-center hover:bg-blue-50 transition';
    el.innerText = formatTime(slot.start_at);
    el.addEventListener('click', () => selectSlot(el, slot));

    return el;
}

function selectSlot(el, slot) {
    if (isSubmitting) return;

    document.querySelectorAll('#slots > button').forEach(x => {
        x.classList.remove('bg-blue-500', 'text-white', 'border-blue-500');
    });

    el.classList.add('bg-blue-500', 'text-white', 'border-blue-500');
    selectedSlot = slot;

    if (isRescheduleMode) {
        confirmReschedule();
        return;
    }

    confirmBooking();
}

async function confirmBooking() {
    if (!selectedSlot || isSubmitting) return;

    if (!confirm(`${formatTime(selectedSlot.start_at)} için randevu al?`)) {
        selectedSlot = null;
        return;
    }

    isSubmitting = true;

    try {
        const res = await fetch('/member/appointments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                provider_id: providerId,
                start_at: selectedSlot.start_at,
                blocks: 1
            })
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
            alert(data.message || 'Randevu oluşturulamadı.');
            await loadSlots();
            return;
        }

        window.location.reload();
    } catch (error) {
        alert('Randevu oluşturulamadı.');
    } finally {
        isSubmitting = false;
    }
}

async function cancelAppointment() {
    if (isCancelling || !window.__ACTIVE_APPOINTMENT_ID__) return;

    if (!confirm('Randevuyu iptal etmek istiyor musun?')) {
        return;
    }

    isCancelling = true;

    try {
        const res = await fetch(`/member/appointments/${window.__ACTIVE_APPOINTMENT_ID__}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
            alert(data.message || 'Randevu iptal edilemedi.');
            return;
        }

        window.location.reload();
    } catch (error) {
        alert('Randevu iptal edilemedi.');
    } finally {
        isCancelling = false;
    }
}

async function confirmReschedule() {
    if (!selectedSlot || isSubmitting || !window.__ACTIVE_APPOINTMENT_ID__) return;

    if (!confirm(`${formatTime(selectedSlot.start_at)} saatine yeniden planla?`)) {
        selectedSlot = null;
        return;
    }

    isSubmitting = true;

    try {
        const res = await fetch(`/member/appointments/${window.__ACTIVE_APPOINTMENT_ID__}/reschedule`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                provider_id: providerId,
                start_at: selectedSlot.start_at,
                blocks: 1
            })
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
            alert(data.message || 'Randevu yeniden planlanamadı.');
            await loadSlots();
            return;
        }

        window.location.reload();
    } catch (error) {
        alert('Randevu yeniden planlanamadı.');
    } finally {
        isSubmitting = false;
    }
}

function todayDateString() {
    const now = new Date();
    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

function pad(n) {
    return n < 10 ? '0' + n : String(n);
}

function formatTime(dateStr) {
    return new Date(dateStr).toLocaleTimeString('tr-TR', {
        hour: '2-digit',
        minute: '2-digit'
    });
}
