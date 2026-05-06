@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="messages.index">
        @include('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Destek Operasyonu</span>
                <div>
                    <h1 class="text-xl font-semibold text-foreground">Mesaj ve Talep Kutusu</h1>
                    <div class="text-sm text-muted-foreground">
                        Gelen mesajları okuyun, atayın, SLA tarihi verin ve çözüm durumunu takip edin.
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Toplam</div>
                <div class="mt-2 text-3xl font-semibold text-foreground">{{ $stats['total'] }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Okunmamış</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['unread'] }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Açık talep</div>
                <div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['open'] }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Süresi geçen</div>
                <div class="mt-2 text-3xl font-semibold text-danger">{{ $stats['overdue'] }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Yüksek / Acil</div>
                <div class="mt-2 text-3xl font-semibold text-danger">{{ $stats['highPriority'] }}</div>
            </div>
        </div>

        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-4">
                <div>
                    <h3 class="kt-card-title">Mesaj Havuzu</h3>
                    <div class="text-sm text-muted-foreground">
                        Öncelik, okunma, atama ve iş akışı durumuna göre filtreleme yapabilirsiniz.
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <input
                        id="messagesSearch"
                        type="text"
                        class="kt-input kt-input-sm w-full md:w-[260px]"
                        placeholder="Konu, gönderen, iletişim bilgisi ara..."
                    />

                    <select id="messagesPriorityFilter" class="kt-select w-full md:w-[160px]" data-kt-select="true">
                        <option value="">Tüm öncelikler</option>
                        @foreach($priorityOptions as $priorityKey => $priorityOption)
                            <option value="{{ $priorityKey }}">{{ $priorityOption['label'] }}</option>
                        @endforeach
                    </select>

                    <select id="messagesReadFilter" class="kt-select w-full md:w-[150px]" data-kt-select="true">
                        <option value="">Tüm okuma</option>
                        <option value="unread">Okunmamış</option>
                        <option value="read">Okunmuş</option>
                    </select>

                    <select id="messagesStatusFilter" class="kt-select w-full md:w-[170px]" data-kt-select="true">
                        <option value="">Tüm iş akışları</option>
                        @foreach($statusOptions as $statusKey => $statusOption)
                            <option value="{{ $statusKey }}">{{ $statusOption['label'] }}</option>
                        @endforeach
                    </select>

                    <select id="messagesAssigneeFilter" class="kt-select w-full md:w-[180px]" data-kt-select="true">
                        <option value="">Tüm atamalar</option>
                        <option value="unassigned">Atanmamış</option>
                        @foreach($assigneeOptions as $assigneeOption)
                            <option value="{{ $assigneeOption->id }}">{{ $assigneeOption->name }}</option>
                        @endforeach
                    </select>

                    @if($isSuperAdmin)
                        <select id="messagesRecipientFilter" class="kt-select w-full md:w-[200px]" data-kt-select="true">
                            <option value="">Tüm alıcılar</option>
                            @foreach($recipientOptions as $recipientOption)
                                <option value="{{ $recipientOption->id }}">{{ $recipientOption->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>

            <div class="kt-card-content">
                <div class="grid" id="messages_dt">
                    <div class="kt-scrollable-x-auto overflow-y-hidden">
                        <table class="kt-table table-auto kt-table-border w-full" id="messages_table">
                            <thead>
                            <tr>
                                <th class="w-[160px]">Durum</th>
                                <th class="min-w-[320px]">Konu ve Mesaj</th>
                                <th class="min-w-[240px]">Gönderen</th>
                                <th class="min-w-[190px]">İletişim</th>
                                <th class="w-[140px]">Öncelik</th>
                                <th class="min-w-[240px]">Sahiplik</th>
                                <th class="min-w-[170px]">Tarih</th>
                                <th class="w-[70px]"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($messages as $message)
                                <tr
                                    data-priority="{{ $message->priority }}"
                                    data-recipient-id="{{ $message->recipient_user_id ?? '' }}"
                                    data-status="{{ $message->status }}"
                                    data-assigned-id="{{ $message->assigned_user_id ?? 'unassigned' }}"
                                    data-read="{{ $message->isRead() ? 'read' : 'unread' }}"
                                >
                                    <td>
                                        <div class="flex flex-col gap-2">
                                            <span class="kt-badge kt-badge-sm {{ $message->isRead() ? 'kt-badge-light-success' : 'kt-badge-light-warning' }}">
                                                {{ $message->isRead() ? 'Okundu' : 'Okunmadı' }}
                                            </span>
                                            <span class="{{ \App\Models\ContactMessage::statusBadgeClass($message->status) }}">
                                                {{ \App\Models\ContactMessage::statusLabel($message->status) }}
                                            </span>
                                            @if($message->isOverdue())
                                                <span class="kt-badge kt-badge-sm kt-badge-light-danger">SLA geçti</span>
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <div class="flex flex-col gap-1">
                                            <a href="{{ route('admin.messages.show', $message) }}" class="font-medium text-foreground hover:text-primary">
                                                {{ $message->subject }}
                                            </a>
                                            <span class="text-sm text-muted-foreground">
                                                {{ \Illuminate\Support\Str::limit($message->message, 110) }}
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="flex flex-col gap-1">
                                            <span class="font-medium text-foreground">{{ $message->sender_full_name }}</span>
                                            <span class="text-sm text-muted-foreground">{{ $message->sender_email ?: 'E-posta bilgisi yok' }}</span>
                                            <span class="text-sm text-muted-foreground">{{ $message->sender_phone ?: 'Telefon bilgisi yok' }}</span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="flex flex-col gap-1">
                                            <span class="font-medium text-foreground">{{ $message->preferredChannelsLabel() }}</span>
                                            <span class="{{ \App\Models\ContactMessage::senderTypeBadgeClass($message->sender_type) }}">
                                                {{ \App\Models\ContactMessage::senderTypeLabel($message->sender_type) }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <span class="{{ \App\Models\ContactMessage::priorityBadgeClass($message->priority) }}">
                                            {{ \App\Models\ContactMessage::priorityLabel($message->priority) }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="flex flex-col gap-1">
                                            <span class="font-medium text-foreground">{{ $message->assignedUser?->name ?: 'Atanmamış' }}</span>
                                            <span class="text-sm text-muted-foreground">Alıcı: {{ $message->recipient_display_name }}</span>
                                            <span class="text-xs text-muted-foreground">
                                                Son tarih: {{ $message->due_at?->format('d.m.Y H:i') ?: 'Belirlenmedi' }}
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="flex flex-col gap-1 text-sm">
                                            <span class="font-medium text-foreground">{{ $message->created_at->format('d.m.Y H:i') }}</span>
                                            <span class="text-muted-foreground">
                                                {{ $message->isRead() ? 'Okunma: ' . $message->read_at?->format('d.m.Y H:i') : 'Henüz okunmadı' }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <a href="{{ route('admin.messages.show', $message) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-primary" title="Mesajı aç">
                                            <i class="ki-filled ki-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <template id="dt-empty-messages">
                            <tr data-kt-empty-row="true">
                                <td colspan="8" class="py-12">
                                    <div class="flex flex-col items-center justify-center gap-2 text-center text-muted-foreground">
                                        <i class="ki-outline ki-sms text-4xl mb-2"></i>
                                        <div class="font-medium text-secondary-foreground">Henüz mesaj bulunmuyor.</div>
                                        <div class="text-sm">Yeni gelen mesajlar burada listelenecek.</div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template id="dt-zero-messages">
                            <tr data-kt-zero-row="true">
                                <td colspan="8" class="py-12">
                                    <div class="flex flex-col items-center justify-center gap-2 text-center text-muted-foreground">
                                        <i class="ki-outline ki-search-list text-4xl mb-2"></i>
                                        <div class="font-medium text-secondary-foreground">Filtrelere uygun mesaj bulunamadı.</div>
                                        <div class="text-sm">Arama veya filtre kriterlerini değiştirip tekrar deneyin.</div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </div>

                    <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                        <div class="flex items-center gap-2 order-2 md:order-1">
                            Göster
                            <select class="kt-select w-16" id="messagesPageSize"></select>
                            / sayfa
                        </div>

                        <div class="flex items-center gap-4 order-1 md:order-2">
                            <span id="messagesInfo"></span>
                            <div class="kt-datatable-pagination" id="messagesPagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
