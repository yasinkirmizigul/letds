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
@endphp

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="members.show">
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

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
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

            <div class="grid gap-5">
                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <div>
                            <h3 class="kt-card-title">Son Randevular</h3>
                            <div class="text-sm text-muted-foreground">
                                Üyenin en güncel randevu hareketleri.
                            </div>
                        </div>
                    </div>
                    <div class="kt-card-content grid gap-4 p-6">
                        @forelse($member->appointments as $appointment)
                            @php
                                $statusMeta = $appointmentStatusLabels[$appointment->status] ?? ['label' => $appointment->status, 'badge' => 'kt-badge kt-badge-sm kt-badge-light'];
                            @endphp
                            <div class="rounded-2xl app-surface-card px-4 py-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium text-foreground">{{ optional($appointment->start_at)->format('d.m.Y H:i') ?: '-' }}</div>
                                        <div class="mt-1 text-sm text-muted-foreground">
                                            {{ $appointment->provider?->name ?: 'Atanmamış uzman' }}
                                        </div>
                                    </div>
                                    <span class="{{ $statusMeta['badge'] }}">{{ $statusMeta['label'] }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground">
                                Henüz randevu kaydı bulunmuyor.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <div>
                            <h3 class="kt-card-title">Son Mesajlar</h3>
                            <div class="text-sm text-muted-foreground">
                                İletişim geçmişinden son kayıtlar.
                            </div>
                        </div>
                    </div>
                    <div class="kt-card-content grid gap-4 p-6">
                        @forelse($member->contactMessages as $message)
                            <a href="{{ route('admin.messages.show', $message) }}" class="rounded-2xl app-surface-card px-4 py-4 transition hover:-translate-y-0.5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium text-foreground">{{ $message->subject }}</div>
                                        <div class="mt-1 text-sm text-muted-foreground">{{ $message->created_at->format('d.m.Y H:i') }}</div>
                                    </div>
                                    <span class="{{ \App\Models\ContactMessage::priorityBadgeClass($message->priority) }}">
                                        {{ \App\Models\ContactMessage::priorityLabel($message->priority) }}
                                    </span>
                                </div>
                            </a>
                        @empty
                            <div class="rounded-2xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground">
                                Henüz iletişim kaydı bulunmuyor.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
