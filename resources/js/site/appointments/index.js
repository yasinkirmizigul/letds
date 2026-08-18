import { get, post } from '@/core/http';
import { clearDateInputValue, getDateInputValue, setDateInputValue, todayMachineDate } from '@/core/date-input';
import { showConfirmDialog, showToastMessage } from '@/core/swal-alert';
import { initMetronicPickers } from '@/core/metronic-pickers';

let providerId = null;
let selectedSlot = null;
let selectedDate = null;
let currentMonth = new Date();
let isMonthLoading = false;
let isDayLoading = false;
let isSubmitting = false;
let isCancelling = false;
let isRescheduleMode = false;
let currentStep = 1;

document.addEventListener('DOMContentLoaded', () => {
    initMetronicPickers(document);

    const providerEl = document.getElementById('provider');
    const dateEl = document.getElementById('date');
    const meetingMethodEl = document.getElementById('meetingMethod');
    const memberNoteEl = document.getElementById('appointmentMemberNote');
    const cancelBtn = document.getElementById('cancelBtn');
    const rescheduleBtn = document.getElementById('rescheduleBtn');
    const prevMonthBtn = document.getElementById('prevMonthBtn');
    const nextMonthBtn = document.getElementById('nextMonthBtn');

    providerId = providerEl ? providerEl.value : null;

    bindActiveAppointmentActions(cancelBtn, rescheduleBtn);
    bindStepperControls();
    syncMeetingMethodDescription();
    syncAppointmentPreview();
    showAppointmentStep(1, false);

    meetingMethodEl?.addEventListener('change', () => {
        syncMeetingMethodDescription();
        syncAppointmentPreview();
    });
    memberNoteEl?.addEventListener('input', syncAppointmentPreview);

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
            syncAppointmentPreview();
            await renderCalendar();
        });
    }

    if (dateEl) {
        dateEl.addEventListener('change', () => {
            selectedDate = getDateInputValue(dateEl) || null;
            loadSlots();
            highlightSelectedDate();
            syncAppointmentPreview();
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
            showAppointmentStep(1);
            renderCalendar();
        });
    }
}

function bindStepperControls() {
    document.getElementById('appointmentStep1Next')?.addEventListener('click', () => {
        if (!getAppointmentPreference().meeting_method_id) {
            showAppointmentAlert('warning', 'Görüşme yöntemi gerekli', 'Devam etmek için görüşme yöntemlerinden birini seçin.');
            return;
        }

        showAppointmentStep(2);
    });
    document.getElementById('appointmentStep2Back')?.addEventListener('click', () => showAppointmentStep(1));
    document.getElementById('appointmentStep2Next')?.addEventListener('click', () => {
        if (!selectedSlot) {
            showAppointmentAlert('warning', 'Saat seçimi gerekli', 'Devam etmek için uygun saatlerden birini seçin.');
            return;
        }

        syncAppointmentPreview();
        showAppointmentStep(3);
    });
    document.getElementById('appointmentStep3Back')?.addEventListener('click', () => showAppointmentStep(2));
    document.getElementById('appointmentSubmit')?.addEventListener('click', () => {
        if (isRescheduleMode) {
            confirmReschedule();
            return;
        }

        confirmBooking();
    });
}

function showAppointmentStep(step, shouldScroll = true) {
    currentStep = Math.min(3, Math.max(1, Number(step) || 1));

    document.querySelectorAll('[data-appointment-step-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', Number(panel.dataset.appointmentStepPanel) !== currentStep);
    });

    document.querySelectorAll('[data-appointment-step]').forEach((indicator) => {
        const indicatorStep = Number(indicator.dataset.appointmentStep);
        indicator.dataset.state = indicatorStep < currentStep ? 'complete' : (indicatorStep === currentStep ? 'active' : 'upcoming');
        indicator.setAttribute('aria-current', indicatorStep === currentStep ? 'step' : 'false');
    });

    if (shouldScroll) {
        document.getElementById('booking-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function syncAppointmentPreview() {
    const provider = document.getElementById('provider');
    const nextButton = document.getElementById('appointmentStep2Next');
    const providerPreview = document.getElementById('appointmentPreviewProvider');
    const datePreview = document.getElementById('appointmentPreviewDate');
    const timePreview = document.getElementById('appointmentPreviewTime');
    const modePreview = document.getElementById('appointmentPreviewMode');
    const meetingMethodPreview = document.getElementById('appointmentPreviewMeetingMethod');
    const memberNotePreview = document.getElementById('appointmentPreviewMemberNote');
    const submitLabel = document.querySelector('[data-submit-label]');
    const preference = getAppointmentPreference();

    if (nextButton) nextButton.disabled = !selectedSlot;
    if (providerPreview) providerPreview.textContent = provider?.selectedOptions?.[0]?.textContent?.trim() || '-';
    if (datePreview) datePreview.textContent = selectedSlot ? formatAppointmentDate(selectedSlot.start_at) : '-';
    if (timePreview) timePreview.textContent = selectedSlot ? formatTime(selectedSlot.start_at) : '-';
    if (modePreview) modePreview.textContent = isRescheduleMode ? 'Randevuyu yeniden planla' : 'Yeni ön görüşme';
    if (meetingMethodPreview) meetingMethodPreview.textContent = preference.meeting_method_name || '-';
    if (memberNotePreview) memberNotePreview.textContent = preference.notes_member || 'Not eklenmedi';
    if (submitLabel) submitLabel.textContent = isRescheduleMode ? 'Yeni Saati Onayla' : 'Randevuyu Onayla';
}

function getAppointmentPreference() {
    const meetingMethod = document.getElementById('meetingMethod');

    return {
        meeting_method_id: meetingMethod?.value || null,
        meeting_method_name: meetingMethod?.selectedOptions?.[0]?.textContent?.trim() || null,
        notes_member: document.getElementById('appointmentMemberNote')?.value?.trim() || null,
    };
}

function syncMeetingMethodDescription() {
    const meetingMethod = document.getElementById('meetingMethod');
    const description = document.getElementById('meetingMethodDescription');
    if (!description) return;

    description.textContent = meetingMethod?.selectedOptions?.[0]?.dataset?.description
        || 'Bu yöntem için ek bir açıklama bulunmuyor.';
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
    clearDateInputValue(dateEl);
}

function updateCalendarTitle() {
    const titleEl = document.getElementById('calendarTitle');
    if (!titleEl) return;

    titleEl.textContent = currentMonth.toLocaleDateString('tr-TR', {
        year: 'numeric',
        month: 'long'
    });
}

function showAppointmentAlert(type, title, message) {
    showToastMessage(type, message, {
        title,
        duration: type === 'success' ? 1600 : 3200
    });
}

function reloadAfterToast(title, message) {
    showAppointmentAlert('success', title, message);

    window.setTimeout(() => {
        window.location.reload();
    }, 900);
}

async function renderCalendar() {
    const container = document.getElementById('calendar');
    if (!container || !providerId || isMonthLoading) return;

    isMonthLoading = true;
    updateCalendarTitle();
    container.innerHTML = `<div class="grid grid-cols-7 gap-2">${calendarSkeletonCells()}</div>`;

    try {
        const year = currentMonth.getFullYear();
        const month = currentMonth.getMonth();
        const monthStr = `${year}-${pad(month + 1)}-01`;
        const map = await get(`/member/appointments/days?provider_id=${encodeURIComponent(providerId)}&month=${monthStr}`, { ignoreGlobalError: true });
        const todayStr = todayDateString();

        // Hafta günü başlıkları site diline göre üretilir (1 Ocak 2024 = Pazartesi, Pzt-Paz sırası)
        const weekdayLocale = document.documentElement.lang || 'tr';
        const weekdayFormatter = new Intl.DateTimeFormat(weekdayLocale, { weekday: 'short' });
        const weekdays = Array.from({ length: 7 }, (_, i) => weekdayFormatter.format(new Date(2024, 0, 1 + i)));
        let html = `<div class="grid grid-cols-7 gap-2">`;
        html += weekdays
            .map((d) => `<div class="pb-1 text-center text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">${d}</div>`)
            .join('');
        const firstDay = new Date(year, month, 1).getDay() || 7;
        const totalDays = new Date(year, month + 1, 0).getDate();

        for (let i = 1; i < firstDay; i++) {
            html += `<div></div>`;
        }

        for (let day = 1; day <= totalDays; day++) {
            const dateStr = `${year}-${pad(month + 1)}-${pad(day)}`;
            const dayData = map[dateStr] || null;
            const hasAvailability = Boolean(dayData?.has_availability);
            const freeCount = dayData?.free_count ?? 0;
            const isPast = dateStr < todayStr;
            const isDisabled = isPast || !hasAvailability;

            html += `
                <button
                    type="button"
                    class="calendar-day app-calendar-day ${
                        isDisabled
                            ? 'is-disabled'
                            : 'is-available'
                    }"
                    ${!isDisabled ? `data-date="${dateStr}"` : 'disabled'}
                    title="${isDisabled ? 'Uygun slot yok' : `${freeCount} uygun slot`}"
                >
                    <div>${day}</div>
                    ${!isDisabled ? `<div class="mt-1 text-[11px] text-muted-foreground">${freeCount}</div>` : ''}
                </button>
            `;
        }

        html += `</div>`;
        container.innerHTML = html;

        container.querySelectorAll('[data-date]').forEach((el) => {
            el.addEventListener('click', () => selectDate(el.dataset.date));
        });

        highlightSelectedDate();
    } catch (error) {
        container.innerHTML = `<div class="text-sm text-danger">Takvim yüklenemedi.</div>`;
        showAppointmentAlert('error', 'Takvim yüklenemedi', 'Lütfen daha sonra tekrar deneyin.');
    } finally {
        isMonthLoading = false;
    }
}

function highlightSelectedDate() {
    document.querySelectorAll('.calendar-day').forEach((el) => {
        el.classList.remove('is-selected');
    });

    if (!selectedDate) return;

    const selectedEl = document.querySelector(`.calendar-day[data-date="${selectedDate}"]`);
    if (selectedEl) {
        selectedEl.classList.add('is-selected');
    }
}

async function loadSlots() {
    const date = getDateInputValue(document.getElementById('date'));
    const container = document.getElementById('slots');
    const empty = document.getElementById('slot-empty');

    if (!date || !container || isDayLoading) return;

    isDayLoading = true;
    selectedSlot = null;
    syncAppointmentPreview();
    container.innerHTML = slotSkeletonCells();
    empty?.classList.add('hidden');

    try {
        const data = await get(`/member/appointments/availability?provider_id=${encodeURIComponent(providerId)}&date=${encodeURIComponent(date)}`, { ignoreGlobalError: true });
        container.innerHTML = '';

        if (!Array.isArray(data) || !data.length) {
            empty?.classList.remove('hidden');
            return;
        }

        data.forEach((slot) => {
            container.appendChild(createSlotElement(slot));
        });
    } catch (error) {
        container.innerHTML = `<div class="text-sm text-danger">Saatler yüklenemedi.</div>`;
        showAppointmentAlert('error', 'Saatler yüklenemedi', 'Lütfen daha sonra tekrar deneyin.');
    } finally {
        isDayLoading = false;
    }
}

function selectDate(date) {
    const dateEl = document.getElementById('date');
    if (!dateEl) return;

    selectedDate = date;
    setDateInputValue(dateEl, date);
    highlightSelectedDate();
    loadSlots();
}

function createSlotElement(slot) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'app-slot-button text-center';
    el.innerText = formatTime(slot.start_at);
    el.addEventListener('click', () => selectSlot(el, slot));

    return el;
}

function selectSlot(el, slot) {
    if (isSubmitting) return;

    document.querySelectorAll('#slots > button').forEach((button) => {
        button.classList.remove('is-selected');
    });

    el.classList.add('is-selected');
    selectedSlot = slot;
    syncAppointmentPreview();
    showAppointmentStep(3);
}

async function confirmBooking() {
    const preference = getAppointmentPreference();
    if (!selectedSlot || !preference.meeting_method_id || isSubmitting) return;

    const ok = await showConfirmDialog({
        type: 'info',
        title: 'Randevu oluşturulsun mu?',
        message: `${formatTime(selectedSlot.start_at)} için randevu alınacak.`,
        confirmButtonText: 'Randevu al',
        cancelButtonText: 'Vazgeç'
    });

    if (!ok) {
        return;
    }

    isSubmitting = true;
    setAppointmentSubmitState(true);

    try {
        const data = await post('/member/appointments', {
            provider_id: providerId,
            start_at: selectedSlot.start_at,
            blocks: 1,
            meeting_method_id: preference.meeting_method_id,
            notes_member: preference.notes_member
        }, { ignoreGlobalError: true });

        if (!data.success) {
            showAppointmentAlert('error', 'Randevu oluşturulamadi', data.message || 'Lütfen başka bir saat deneyin.');
            await loadSlots();
            return;
        }

        reloadAfterToast('Randevu oluşturuldu', 'Randevunuz kaydedildi. Sayfa yenileniyor.');
    } catch (error) {
        showAppointmentAlert('error', 'Randevu oluşturulamadi', 'Lütfen daha sonra tekrar deneyin.');
    } finally {
        isSubmitting = false;
        setAppointmentSubmitState(false);
    }
}

async function cancelAppointment() {
    if (isCancelling || !window.__ACTIVE_APPOINTMENT_ID__) return;

    const ok = await showConfirmDialog({
        type: 'warning',
        title: 'Randevu iptal edilsin mi?',
        message: 'Bu işlem geri alınamaz.',
        confirmButtonText: 'İptal et',
        cancelButtonText: 'Vazgeç'
    });

    if (!ok) {
        return;
    }

    isCancelling = true;

    try {
        const data = await post(`/member/appointments/${window.__ACTIVE_APPOINTMENT_ID__}/cancel`, null, { ignoreGlobalError: true });

        if (!data.success) {
            showAppointmentAlert('error', 'Randevu iptal edilemedi', data.message || 'İptal işlemi tamamlanamadı.');
            return;
        }

        reloadAfterToast('Randevu iptal edildi', 'Randevunuz iptal edildi. Sayfa yenileniyor.');
    } catch (error) {
        showAppointmentAlert('error', 'Randevu iptal edilemedi', 'Lütfen daha sonra tekrar deneyin.');
    } finally {
        isCancelling = false;
    }
}

async function confirmReschedule() {
    const preference = getAppointmentPreference();
    if (!selectedSlot || !preference.meeting_method_id || isSubmitting || !window.__ACTIVE_APPOINTMENT_ID__) return;

    const ok = await showConfirmDialog({
        type: 'info',
        title: 'Randevu yeniden planlansın mı?',
        message: `${formatTime(selectedSlot.start_at)} saatine taşınacak.`,
        confirmButtonText: 'Yeniden planla',
        cancelButtonText: 'Vazgeç'
    });

    if (!ok) {
        return;
    }

    isSubmitting = true;
    setAppointmentSubmitState(true);

    try {
        const data = await post(`/member/appointments/${window.__ACTIVE_APPOINTMENT_ID__}/reschedule`, {
            provider_id: providerId,
            start_at: selectedSlot.start_at,
            blocks: 1,
            meeting_method_id: preference.meeting_method_id,
            notes_member: preference.notes_member
        }, { ignoreGlobalError: true });

        if (!data.success) {
            showAppointmentAlert('error', 'Randevu yeniden planlanamadı', data.message || 'Seçilen saat için yeniden planlama yapılamadı.');
            await loadSlots();
            return;
        }

        reloadAfterToast('Randevu güncellendi', 'Randevunuz yeniden planlandı. Sayfa yenileniyor.');
    } catch (error) {
        showAppointmentAlert('error', 'Randevu yeniden planlanamadı', 'Lütfen daha sonra tekrar deneyin.');
    } finally {
        isSubmitting = false;
        setAppointmentSubmitState(false);
    }
}

function todayDateString() {
    return todayMachineDate();
}

function pad(n) {
    return n < 10 ? `0${n}` : String(n);
}

function formatTime(dateStr) {
    return new Date(dateStr).toLocaleTimeString('tr-TR', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatAppointmentDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('tr-TR', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
}

function setAppointmentSubmitState(isBusy) {
    const button = document.getElementById('appointmentSubmit');
    if (!button) return;

    button.disabled = isBusy;
    button.setAttribute('aria-busy', isBusy ? 'true' : 'false');
    button.classList.toggle('is-loading', isBusy);
}

function calendarSkeletonCells() {
    return Array.from({ length: 35 })
        .map(() => '<div class="app-skeleton aspect-square rounded-xl"></div>')
        .join('');
}

function slotSkeletonCells() {
    return Array.from({ length: 6 })
        .map(() => '<div class="app-skeleton h-9 w-full rounded-full"></div>')
        .join('');
}
