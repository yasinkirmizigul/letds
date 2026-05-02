@extends('admin.layouts.main.app')

@php
    $appointmentStatusLabels = [
        \App\Models\Appointment\Appointment::STATUS_BOOKED => ['label' => 'Planlandı', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-primary'],
        \App\Models\Appointment\Appointment::STATUS_COMPLETED => ['label' => 'Tamamlandı', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-success'],
        \App\Models\Appointment\Appointment::STATUS_CANCELLED_BY_MEMBER => ['label' => 'Üye İptali', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning'],
        \App\Models\Appointment\Appointment::STATUS_CANCELLED_BY_PROVIDER => ['label' => 'Yönetici İptali', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning'],
        \App\Models\Appointment\Appointment::STATUS_TRANSFERRED => ['label' => 'Transfer', 'badge' => 'kt-badge kt-badge-sm kt-badge-light'],
        \App\Models\Appointment\Appointment::STATUS_NO_SHOW => ['label' => 'Katılmadı', 'badge' => 'kt-badge kt-badge-sm kt-badge-light-danger'],
    ];

    $appointmentStatusVariants = [
        \App\Models\Appointment\Appointment::STATUS_BOOKED => 'primary',
        \App\Models\Appointment\Appointment::STATUS_COMPLETED => 'success',
        \App\Models\Appointment\Appointment::STATUS_CANCELLED_BY_MEMBER => 'warning',
        \App\Models\Appointment\Appointment::STATUS_CANCELLED_BY_PROVIDER => 'danger',
        \App\Models\Appointment\Appointment::STATUS_TRANSFERRED => 'info',
        \App\Models\Appointment\Appointment::STATUS_NO_SHOW => 'danger',
    ];

    $memberAppointmentTimelineItems = $member->appointments
        ->groupBy(fn ($appointment) => $appointment->start_at?->format('Y-m-d H:i') ?: 'appointment-' . $appointment->id)
        ->map(function ($slotAppointments) use ($appointmentStatusLabels, $appointmentStatusVariants) {
            $slotAppointments = $slotAppointments->sortBy('id')->values();
            $appointment = $slotAppointments->first();
            $appointmentCount = $slotAppointments->count();
            $statusMeta = $appointmentStatusLabels[$appointment->status] ?? [
                'label' => $appointment->status,
                'badge' => 'kt-badge kt-badge-sm kt-badge-light',
            ];
            $startLabel = $appointment->start_at?->format('d.m.Y H:i') ?: '-';
            $endLabel = $slotAppointments
                ->pluck('end_at')
                ->filter()
                ->sort()
                ->last()
                ?->format('d.m.Y H:i');
            $slotDetails = $slotAppointments
                ->map(function ($slotAppointment) use ($appointmentStatusLabels) {
                    $slotStatusMeta = $appointmentStatusLabels[$slotAppointment->status] ?? [
                        'label' => $slotAppointment->status,
                        'badge' => 'kt-badge kt-badge-sm kt-badge-light',
                    ];

                    return collect([
                        '#' . $slotAppointment->id,
                        $slotAppointment->start_at?->format('H:i') . '-' . $slotAppointment->end_at?->format('H:i'),
                        $slotStatusMeta['label'],
                        $slotAppointment->provider?->name ?: 'Atanmamış uzman',
                    ])->filter()->implode(' | ');
                })
                ->implode("\n");

            return [
                'id' => 'appointment-slot-' . $appointment->id,
                'start' => $appointment->start_at?->toIso8601String() ?: $appointment->created_at?->toIso8601String(),
                'end' => $slotAppointments->pluck('end_at')->filter()->sort()->last()?->toIso8601String(),
                'title' => $appointment->start_at?->format('H:i') ?: $statusMeta['label'],
                'nodeTitle' => $appointment->start_at?->format('H:i') ?: $statusMeta['label'],
                'avatarDay' => $appointment->start_at?->format('d') ?: '--',
                'avatarMonth' => $appointment->start_at?->locale('tr')->translatedFormat('M') ?: '',
                'subtitle' => $appointment->provider?->name ?: 'Atanmamış uzman',
                'description' => $appointmentCount > 1 ? $appointmentCount . ' kayıt' : ($endLabel ? 'Bitiş: ' . $endLabel : null),
                'date' => $startLabel,
                'status' => $appointmentCount > 1 ? $appointmentCount . ' kayıt' : $statusMeta['label'],
                'badgeClass' => $statusMeta['badge'],
                'variant' => $appointmentStatusVariants[$appointment->status] ?? 'default',
                'icon' => 'ki-filled ki-calendar-8',
                'count' => $appointmentCount,
                'tooltip' => collect([
                    'Tarih: ' . $startLabel,
                    $endLabel ? 'Bitiş: ' . $endLabel : null,
                    'Toplam: ' . $appointmentCount . ' kayıt',
                    $slotDetails,
                ])->filter()->implode("\n"),
            ];
        })
        ->sortByDesc('start')
        ->values()
        ->all();

    $messagePriorityVariants = [
        \App\Models\ContactMessage::PRIORITY_LOW => 'default',
        \App\Models\ContactMessage::PRIORITY_NORMAL => 'primary',
        \App\Models\ContactMessage::PRIORITY_HIGH => 'warning',
        \App\Models\ContactMessage::PRIORITY_URGENT => 'danger',
    ];

    $memberMessageTimelineItems = $member->contactMessages
        ->map(function ($message) use ($messagePriorityVariants) {
            $createdLabel = $message->created_at?->format('d.m.Y H:i') ?: '-';
            $priorityLabel = \App\Models\ContactMessage::priorityLabel($message->priority);

            return [
                'id' => 'message-' . $message->id,
                'start' => $message->created_at?->toIso8601String(),
                'title' => $message->subject,
                'nodeTitle' => $priorityLabel,
                'avatarDay' => $message->created_at?->format('d') ?: '--',
                'avatarMonth' => $message->created_at?->locale('tr')->translatedFormat('M') ?: '',
                'subtitle' => $createdLabel,
                'description' => $message->sender_full_name ?: 'Bilinmeyen gönderici',
                'date' => $createdLabel,
                'status' => $priorityLabel,
                'badgeClass' => \App\Models\ContactMessage::priorityBadgeClass($message->priority),
                'variant' => $messagePriorityVariants[$message->priority] ?? 'default',
                'icon' => 'ki-filled ki-messages',
                'url' => route('admin.messages.show', $message),
                'tooltip' => collect([
                    'Konu: ' . $message->subject,
                    'Tarih: ' . $createdLabel,
                    'Gönderen: ' . ($message->sender_full_name ?: 'Bilinmeyen gönderici'),
                    'Öncelik: ' . $priorityLabel,
                ])->filter()->implode("\n"),
            ];
        })
        ->values()
        ->all();
@endphp

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="members.show" data-form-accordions="true">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="{{ $member->statusBadgeClass() }}">{{ $member->statusLabel() }}</span>
                    @if($member->hasDocument())
                        <span class="kt-badge kt-badge-sm kt-badge-light-primary">Belge Yüklü</span>
                    @endif
                </div>

                <div>
                    <h1 class="text-2xl font-semibold text-foreground">{{ $member->full_name ?: 'Adsız Üye' }}</h1>
                    <p class="text-sm text-muted-foreground">
                        #{{ $member->id }} • {{ $member->email }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.members.index') }}" class="kt-btn kt-btn-light">Listeye Dön</a>

                @if($member->hasDocument() && $member->documentExists())
                    <a href="{{ route('admin.members.document', $member) }}" target="_blank" class="kt-btn kt-btn-light">Belgeyi Aç</a>
                    <a href="{{ route('admin.members.document.download', $member) }}" class="kt-btn kt-btn-light">Belgeyi İndir</a>
                @endif

                <form method="POST" action="{{ route('admin.members.toggleStatus', $member) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="kt-btn kt-btn-light">
                        {{ $member->is_active ? 'Askıya Al' : 'Aktif Et' }}
                    </button>
                </form>

                <button type="submit" form="member-update-form" class="kt-btn kt-btn-primary">Kaydet</button>
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Toplam Randevu</div>
                <div class="mt-2 text-3xl font-semibold">{{ $member->appointments_count }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Aktif Randevu</div>
                <div class="mt-2 text-3xl font-semibold text-primary">{{ $member->active_appointments_count }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Mesaj Kaydı</div>
                <div class="mt-2 text-3xl font-semibold text-success">{{ $member->contact_messages_count }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Son Giriş</div>
                <div class="mt-2 text-lg font-semibold">{{ optional($member->last_login_at)->format('d.m.Y H:i') ?: 'Henüz giriş yok' }}</div>
            </div>
        </div>

        <form id="member-update-form" method="POST" action="{{ route('admin.members.update', $member) }}" enctype="multipart/form-data" class="grid gap-6">
            @csrf
            @method('PUT')

            @include('admin.pages.members.partials._form', ['member' => $member])

            <div class="flex items-center justify-between gap-3">
                <button type="submit" form="member-delete-form" class="kt-btn kt-btn-danger" onclick="return confirm('Bu üyelik kaydı silinsin mi?')">
                    Üyeliği Sil
                </button>

                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.members.index') }}" class="kt-btn kt-btn-light">İptal</a>
                    <button type="submit" class="kt-btn kt-btn-primary">Kaydet</button>
                </div>
            </div>
        </form>

        <form id="member-delete-form" method="POST" action="{{ route('admin.members.destroy', $member) }}">
            @csrf
            @method('DELETE')
        </form>

        <div class="grid gap-5">
            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Belge Önizleme</h3>
                        <div class="text-sm text-muted-foreground">
                            Üyenin yüklediği dosyayı yönetimli şekilde görüntüleyin.
                        </div>
                    </div>
                </div>
                <div class="kt-card-content p-6">
                    @if($member->hasDocument() && $member->documentExists())
                        @if($member->documentIsImage())
                            <img src="{{ route('admin.members.document', $member) }}" alt="{{ $member->documentName() }}" class="max-h-[520px] w-full rounded-3xl object-contain bg-muted/20">
                        @elseif($member->documentIsPdf())
                            <iframe src="{{ route('admin.members.document', $member) }}" class="h-[520px] w-full rounded-3xl border border-border"></iframe>
                        @else
                            <div class="rounded-3xl border border-dashed border-border px-6 py-10">
                                <div class="text-lg font-semibold text-foreground">{{ $member->documentName() }}</div>
                                <div class="mt-2 text-sm text-muted-foreground">
                                    Bu belge tarayıcı önizlemesi için uygun değil. İndirme bağlantısını kullanarak açabilirsiniz.
                                </div>
                            </div>
                        @endif

                        <div class="mt-4 grid gap-2 text-sm text-muted-foreground">
                            <div>Dosya adı: <span class="font-medium text-foreground">{{ $member->documentName() }}</span></div>
                            <div>Tür: <span class="font-medium text-foreground">{{ $member->file_mime_type ?: ($member->documentExtension() ?: '-') }}</span></div>
                            <div>Boyut: <span class="font-medium text-foreground">{{ $member->documentSizeLabel() ?: '-' }}</span></div>
                        </div>
                    @else
                        <div class="rounded-3xl border border-dashed border-border px-6 py-12 text-center">
                            <div class="text-lg font-semibold">Belge yüklenmemiş.</div>
                            <div class="mt-2 text-sm text-muted-foreground">
                                Üye kayıt formundan veya bu ekrandan belge ekleyebilirsin.
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Randevu Geçmişi</h3>
                        <div class="text-sm text-muted-foreground">
                            Üyenin tüm randevu hareketleri.
                        </div>
                    </div>
                </div>
                <div class="kt-card-content p-6">
                    <div
                        id="memberAppointmentsTimeline"
                        class="app-history-timeline app-history-timeline--compact"
                        style="--app-history-timeline-height: 230px"
                        data-history-timeline
                        data-history-timeline-compact="true"
                        data-history-timeline-height="230px"
                        data-history-timeline-locale="tr"
                        data-history-timeline-vertical-scroll="false"
                        data-history-timeline-view="day"
                        data-history-timeline-views="day,week,month"
                        data-history-timeline-empty="Henüz randevu kaydı bulunmuyor."
                        data-history-timeline-source="#memberAppointmentsTimelineData"
                    >
                        @if(count($memberAppointmentTimelineItems))
                            <div class="app-history-fallback">
                                @foreach($memberAppointmentTimelineItems as $timelineItem)
                                    <div class="app-history-fallback__row app-history-fallback__row--{{ $timelineItem['variant'] }}">
                                        <div
                                            class="app-history-node app-history-node--{{ $timelineItem['variant'] }}"
                                            title="{{ $timelineItem['tooltip'] }}"
                                            data-history-tooltip="{{ $timelineItem['tooltip'] }}"
                                        >
                                            @if(($timelineItem['count'] ?? 0) > 1)
                                                <span class="app-history-node__count">{{ $timelineItem['count'] }}</span>
                                            @endif
                                            <span class="app-history-node__title">{{ $timelineItem['nodeTitle'] }}</span>
                                            <span class="app-history-node__avatar" aria-hidden="true">
                                                <span class="app-history-node__day">{{ $timelineItem['avatarDay'] }}</span>
                                                <span class="app-history-node__month">{{ $timelineItem['avatarMonth'] }}</span>
                                                <i class="{{ $timelineItem['icon'] }}"></i>
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="app-history-empty">Henüz randevu kaydı bulunmuyor.</div>
                        @endif
                    </div>
                    <script type="application/json" id="memberAppointmentsTimelineData">@json($memberAppointmentTimelineItems)</script>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">İletişim Geçmişi</h3>
                        <div class="text-sm text-muted-foreground">
                            Üyenin tüm iletişim kayıtları.
                        </div>
                    </div>
                </div>
                <div class="kt-card-content p-6">
                    <div
                        id="memberMessagesTimeline"
                        class="app-history-timeline app-history-timeline--compact"
                        style="--app-history-timeline-height: 230px"
                        data-history-timeline
                        data-history-timeline-compact="true"
                        data-history-timeline-height="230px"
                        data-history-timeline-locale="tr"
                        data-history-timeline-vertical-scroll="false"
                        data-history-timeline-view="day"
                        data-history-timeline-views="day,week,month"
                        data-history-timeline-empty="Henüz iletişim kaydı bulunmuyor."
                        data-history-timeline-source="#memberMessagesTimelineData"
                    >
                        @if(count($memberMessageTimelineItems))
                            <div class="app-history-fallback">
                                @foreach($memberMessageTimelineItems as $timelineItem)
                                    <div class="app-history-fallback__row app-history-fallback__row--{{ $timelineItem['variant'] }}">
                                        <a
                                            href="{{ $timelineItem['url'] }}"
                                            class="app-history-node app-history-node--{{ $timelineItem['variant'] }}"
                                            title="{{ $timelineItem['tooltip'] }}"
                                            data-history-tooltip="{{ $timelineItem['tooltip'] }}"
                                        >
                                            <span class="app-history-node__title">{{ $timelineItem['nodeTitle'] }}</span>
                                            <span class="app-history-node__avatar" aria-hidden="true">
                                                <span class="app-history-node__day">{{ $timelineItem['avatarDay'] }}</span>
                                                <span class="app-history-node__month">{{ $timelineItem['avatarMonth'] }}</span>
                                                <i class="{{ $timelineItem['icon'] }}"></i>
                                            </span>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="app-history-empty">Henüz iletişim kaydı bulunmuyor.</div>
                        @endif
                    </div>
                    <script type="application/json" id="memberMessagesTimelineData">@json($memberMessageTimelineItems)</script>
                </div>
            </div>
        </div>
    </div>
@endsection
