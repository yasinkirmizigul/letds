@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="service-reviews.show">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">{{ $review->serviceTypeLabel() }}</span>
                <div>
                    <h1 class="text-xl font-semibold">{{ $review->service_title }}</h1>
                    <div class="text-sm text-muted-foreground">{{ $review->service_reference ?: 'Referans bulunmuyor' }}</div>
                </div>
            </div>
            <a href="{{ route('admin.service-reviews.index') }}" class="kt-btn kt-btn-light">
                <i class="ki-filled ki-left"></i>
                Listeye Dön
            </a>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Genel Puan</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $review->overall_rating ?: '-' }}<span class="text-base text-muted-foreground"> / 5</span></div>
            </div>
            <div class="app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Durum</div>
                <div class="mt-3"><span class="{{ $review->statusBadgeClass() }}">{{ $review->statusLabel() }}</span></div>
            </div>
            <div class="app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Hizmet Tarihi</div>
                <div class="mt-2 text-lg font-semibold">{{ $review->service_completed_at?->format('d.m.Y H:i') ?: '-' }}</div>
            </div>
            <div class="app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Yanıt Tarihi</div>
                <div class="mt-2 text-lg font-semibold">{{ $review->submitted_at?->format('d.m.Y H:i') ?: '-' }}</div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="grid gap-6">
                <div class="kt-card">
                    <div class="kt-card-header py-5"><h2 class="kt-card-title">Anket Yanıtları</h2></div>
                    <div class="kt-card-content grid gap-4 p-6">
                        @forelse($review->items as $item)
                            <div class="app-surface-card app-surface-card--soft p-5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="font-semibold text-foreground">{{ $item->question_text }}</div>
                                        <div class="mt-1 text-xs text-muted-foreground">{{ $item->questionTypeLabel() }}</div>
                                    </div>
                                    <div class="text-base font-semibold text-primary">
                                        @php($answer = $item->answerValue())
                                        @if($item->question_type === 'yes_no')
                                            {{ $answer === 'yes' ? 'Evet' : ($answer === 'no' ? 'Hayır' : 'Yanıt yok') }}
                                        @elseif($item->question_type === 'scale' && $answer)
                                            {{ $answer }} / 5
                                        @else
                                            {{ filled($answer) ? $answer : 'Yanıt yok' }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="border border-dashed border-border px-6 py-10 text-center text-sm text-muted-foreground">
                                Bu değerlendirmede ek anket sorusu bulunmuyor.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="kt-card">
                    <div class="kt-card-header py-5"><h2 class="kt-card-title">Üye Notu</h2></div>
                    <div class="kt-card-content p-6 text-sm leading-7 text-foreground">
                        {{ $review->public_comment ?: 'Üye ek bir not bırakmadı.' }}
                    </div>
                </div>
            </section>

            <aside class="kt-card self-start">
                <div class="kt-card-header py-5"><h2 class="kt-card-title">Hizmet Bağlamı</h2></div>
                <div class="kt-card-content grid gap-5 p-6 text-sm">
                    <div>
                        <div class="text-xs uppercase text-muted-foreground">Hizmeti Alan Üye</div>
                        <div class="mt-2 font-semibold text-foreground">{{ $review->member?->full_name ?: 'Silinen üye' }}</div>
                        <div class="mt-1 text-muted-foreground">{{ $review->member?->email }}</div>
                        @if($review->member)
                            <a href="{{ route('admin.members.show', $review->member) }}" class="mt-3 inline-flex text-primary hover:underline">Üye profilini aç</a>
                        @endif
                    </div>
                    <div class="border-t border-border pt-5">
                        <div class="text-xs uppercase text-muted-foreground">Hizmeti Veren Kullanıcı</div>
                        <div class="mt-2 font-semibold text-foreground">{{ $review->provider?->name ?: 'Genel / Mağaza' }}</div>
                        <div class="mt-1 text-muted-foreground">{{ $review->provider?->title }}</div>
                    </div>
                    <div class="border-t border-border pt-5">
                        <div class="text-xs uppercase text-muted-foreground">Değerlendirme Daveti</div>
                        <div class="mt-2 font-medium text-foreground">{{ $review->invited_at?->format('d.m.Y H:i') ?: '-' }}</div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection
