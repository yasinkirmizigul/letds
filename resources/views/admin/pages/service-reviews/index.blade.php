@extends('admin.layouts.main.app')

@section('content')
    <div
        class="kt-container-fixed max-w-[96%] grid gap-6"
        data-page="service-reviews.index"
        data-review-trend='@json($trend)'
    >
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Hizmet Kalitesi</span>
                <div>
                    <h1 class="text-xl font-semibold">Değerlendirme Merkezi</h1>
                    <div class="text-sm text-muted-foreground">
                        Tamamlanan hizmetlerin yıldız puanlarını, anket yanıtlarını ve kullanıcı performansını izleyin.
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                @perm('service_reviews.questions')
                    <a href="{{ route('admin.service-reviews.questions.index') }}" class="kt-btn kt-btn-light-primary">
                        <i class="ki-filled ki-questionnaire-tablet"></i>
                        Anket Soruları
                    </a>
                    <form method="POST" action="{{ route('admin.service-reviews.sync') }}">
                        @csrf
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-arrows-circle"></i>
                            Eksik Davetleri Oluştur
                        </button>
                    </form>
                @endperm
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Toplam Hizmet</div>
                <div class="mt-2 text-3xl font-semibold">{{ $stats['all'] }}</div>
            </div>
            <div class="app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Yanıtlanan</div>
                <div class="mt-2 text-3xl font-semibold text-success">{{ $stats['completed'] }}</div>
            </div>
            <div class="app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Yanıt Bekleyen</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['pending'] }}</div>
            </div>
            <div class="app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Genel Ortalama</div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-semibold text-warning">{{ number_format($stats['average'], 2, ',', '.') }}</span>
                    <span class="text-sm text-muted-foreground">/ 5</span>
                </div>
            </div>
            <div class="app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Cevaplanma Oranı</div>
                <div class="mt-2 text-3xl font-semibold text-primary">%{{ number_format($stats['response_rate'], 1, ',', '.') }}</div>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header py-5">
                <div>
                    <h2 class="kt-card-title">Rapor Filtreleri</h2>
                    <div class="text-sm text-muted-foreground">İstatistikler ve kayıt listesi bu seçimlere göre birlikte güncellenir.</div>
                </div>
            </div>
            <form method="GET" class="kt-card-content grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="grid gap-2 xl:col-span-2">
                    <label class="kt-form-label">Arama</label>
                    <input name="q" value="{{ $filters['q'] }}" class="kt-input" placeholder="Hizmet, referans veya üye ara">
                </div>
                <div class="grid gap-2">
                    <label class="kt-form-label">Hizmeti Veren Kullanıcı</label>
                    <select name="provider_id" class="kt-select" data-kt-select="true">
                        <option value="">Tüm kullanıcılar</option>
                        <option value="unassigned" @selected($filters['provider_id'] === 'unassigned')>Kullanıcı atanmamış</option>
                        @foreach($providers as $provider)
                            <option value="{{ $provider->id }}" @selected((string) $filters['provider_id'] === (string) $provider->id)>
                                {{ $provider->name }}{{ $provider->title ? ' · ' . $provider->title : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-2">
                    <label class="kt-form-label">Hizmeti Alan Üye</label>
                    <select name="member_id" class="kt-select" data-kt-select="true">
                        <option value="">Tüm üyeler</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" @selected((int) $filters['member_id'] === (int) $member->id)>
                                {{ $member->full_name ?: $member->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-2">
                    <label class="kt-form-label">Hizmet Türü</label>
                    <select name="service_type" class="kt-select">
                        <option value="">Tüm hizmetler</option>
                        <option value="appointment" @selected($filters['service_type'] === 'appointment')>Randevu</option>
                        <option value="order" @selected($filters['service_type'] === 'order')>Ürün / Sipariş</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <label class="kt-form-label">Yanıt Durumu</label>
                    <select name="status" class="kt-select">
                        <option value="">Tüm durumlar</option>
                        <option value="pending" @selected($filters['status'] === 'pending')>Yanıt bekliyor</option>
                        <option value="completed" @selected($filters['status'] === 'completed')>Tamamlandı</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <label class="kt-form-label">Yıldız Puanı</label>
                    <select name="rating" class="kt-select">
                        <option value="">Tüm puanlar</option>
                        @foreach(range(5, 1) as $rating)
                            <option value="{{ $rating }}" @selected((int) $filters['rating'] === $rating)>{{ $rating }} yıldız</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <label class="kt-form-label">Başlangıç</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="kt-input">
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label">Bitiş</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="kt-input">
                    </div>
                </div>
                <div class="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-4">
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-filter"></i>
                        Uygula
                    </button>
                    <a href="{{ route('admin.service-reviews.index') }}" class="kt-btn kt-btn-light">Temizle</a>
                </div>
            </form>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(360px,0.65fr)]">
            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h2 class="kt-card-title">8 Haftalık Eğilim</h2>
                        <div class="text-sm text-muted-foreground">Ortalama yıldız puanı ve haftalık yanıt adedi.</div>
                    </div>
                </div>
                <div class="kt-card-content p-6">
                    <div id="serviceReviewTrendChart" class="h-[260px]"></div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h2 class="kt-card-title">Yıldız Dağılımı</h2>
                        <div class="text-sm text-muted-foreground">Yanıtlanan değerlendirmelerin dağılımı.</div>
                    </div>
                </div>
                <div class="kt-card-content grid gap-4 p-6">
                    @foreach(range(5, 1) as $rating)
                        @php($count = $distribution[$rating] ?? 0)
                        <div class="grid grid-cols-[72px_minmax(0,1fr)_40px] items-center gap-3">
                            <div class="flex items-center gap-1 font-medium text-foreground">
                                <span>{{ $rating }}</span><span class="text-warning">★</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-warning" style="width: {{ ($count / $distributionMax) * 100 }}%"></div>
                            </div>
                            <div class="text-right text-sm text-muted-foreground">{{ $count }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if(count($questionStats) > 0)
            <section class="grid gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-foreground">Soru Bazlı Sonuçlar</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Aktif filtrelere göre en çok yanıtlanan anket soruları.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($questionStats as $questionStat)
                        <article class="app-surface-card p-5">
                            <div class="flex items-center justify-between gap-3 text-xs text-muted-foreground">
                                <span>{{ $questionStat['type'] }}</span>
                                <span>{{ $questionStat['count'] }} yanıt</span>
                            </div>
                            <h3 class="mt-3 text-sm font-semibold leading-6 text-foreground">{{ $questionStat['question'] }}</h3>
                            <div class="mt-4 text-lg font-semibold text-primary">{{ $questionStat['summary'] }}</div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="kt-card">
            <div class="kt-card-header py-5">
                <div>
                    <h2 class="kt-card-title">Değerlendirme Kayıtları</h2>
                    <div class="text-sm text-muted-foreground">Her tamamlanan hizmet için tek değerlendirme kabul edilir.</div>
                </div>
            </div>
            <div class="kt-card-content p-6">
                @if($reviews->count())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead>
                            <tr class="text-left text-muted-foreground">
                                <th class="px-4 py-3 font-medium">Hizmet</th>
                                <th class="px-4 py-3 font-medium">Üye</th>
                                <th class="px-4 py-3 font-medium">Kullanıcı</th>
                                <th class="px-4 py-3 font-medium">Puan</th>
                                <th class="px-4 py-3 font-medium">Durum</th>
                                <th class="px-4 py-3 font-medium">Tarih</th>
                                <th class="px-4 py-3 text-right font-medium">İşlem</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                            @foreach($reviews as $review)
                                <tr>
                                    <td class="px-4 py-4 align-top">
                                        <div class="font-semibold text-foreground">{{ $review->service_title }}</div>
                                        <div class="mt-1 text-xs text-muted-foreground">{{ $review->serviceTypeLabel() }} · {{ $review->service_reference ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="font-medium text-foreground">{{ $review->member?->full_name ?: 'Silinen üye' }}</div>
                                        <div class="mt-1 text-xs text-muted-foreground">{{ $review->member?->email }}</div>
                                    </td>
                                    <td class="px-4 py-4 align-top text-muted-foreground">
                                        {{ $review->provider?->name ?: 'Genel / Mağaza' }}
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        @if($review->overall_rating)
                                            <div class="flex items-center gap-1 text-warning" aria-label="{{ $review->overall_rating }} yıldız">
                                                @foreach(range(1, 5) as $star)
                                                    <span class="{{ $star <= $review->overall_rating ? 'text-warning' : 'text-muted-foreground/30' }}">★</span>
                                                @endforeach
                                                <span class="ms-1 font-semibold text-foreground">{{ $review->overall_rating }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted-foreground">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 align-top"><span class="{{ $review->statusBadgeClass() }}">{{ $review->statusLabel() }}</span></td>
                                    <td class="px-4 py-4 align-top text-muted-foreground">
                                        {{ $review->submitted_at?->format('d.m.Y H:i') ?: $review->service_completed_at?->format('d.m.Y H:i') ?: '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-right align-top">
                                        <a href="{{ route('admin.service-reviews.show', $review) }}" class="kt-btn kt-btn-sm kt-btn-light-primary">İncele</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-5">{{ $reviews->links() }}</div>
                @else
                    <div class="border border-dashed border-border px-6 py-12 text-center">
                        <div class="text-lg font-semibold text-foreground">Bu filtrelerde değerlendirme bulunamadı.</div>
                        <div class="mt-2 text-sm text-muted-foreground">Filtreleri temizleyebilir veya tamamlanan hizmetleri eşitleyebilirsiniz.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
