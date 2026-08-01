@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="service-reviews.questions">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Anket Tasarımı</span>
                <div>
                    <h1 class="text-xl font-semibold">Değerlendirme Soruları</h1>
                    <div class="text-sm text-muted-foreground">Üyenin genel yıldız puanına ek olarak cevaplayacağı kısa soruları yönetin.</div>
                </div>
            </div>
            <a href="{{ route('admin.service-reviews.index') }}" class="kt-btn kt-btn-light">
                <i class="ki-filled ki-chart-line-up"></i>
                İstatistiklere Dön
            </a>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="app-stat-card p-5"><div class="text-sm text-muted-foreground">Toplam Soru</div><div class="mt-2 text-3xl font-semibold">{{ $stats['all'] }}</div></div>
            <div class="app-stat-card p-5"><div class="text-sm text-muted-foreground">Aktif</div><div class="mt-2 text-3xl font-semibold text-success">{{ $stats['active'] }}</div></div>
            <div class="app-stat-card p-5"><div class="text-sm text-muted-foreground">Zorunlu</div><div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['required'] }}</div></div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[400px_minmax(0,1fr)]">
            <div class="kt-card self-start">
                <div class="kt-card-header py-5">
                    <div>
                        <h2 class="kt-card-title">Yeni Soru</h2>
                        <div class="text-sm text-muted-foreground">Genel 5 yıldız puanı her ankette otomatik bulunur.</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.service-reviews.questions.store') }}" class="kt-card-content grid gap-4 p-6" data-review-question-form>
                    @csrf
                    @include('admin.pages.service-reviews._question-fields', [
                        'question' => null,
                        'typeOptions' => $typeOptions,
                    ])
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-plus"></i>
                        Soruyu Ekle
                    </button>
                </form>
            </div>

            <section class="grid gap-4">
                @forelse($questions as $question)
                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="kt-badge kt-badge-sm kt-badge-light-primary">{{ $question->typeLabel() }}</span>
                                    @if($question->is_required)<span class="kt-badge kt-badge-sm kt-badge-light-warning">Zorunlu</span>@endif
                                    <span class="{{ $question->is_active ? 'kt-badge kt-badge-sm kt-badge-light-success' : 'kt-badge kt-badge-sm kt-badge-light' }}">
                                        {{ $question->is_active ? 'Aktif' : 'Pasif' }}
                                    </span>
                                </div>
                                <h2 class="mt-3 text-base font-semibold text-foreground">{{ $question->question }}</h2>
                            </div>
                            <form method="POST" action="{{ route('admin.service-reviews.questions.destroy', $question) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-danger" onclick="return confirm('Bu soru kaldırılsın mı? Geçmiş cevaplar korunacaktır.')">
                                    <i class="ki-filled ki-trash"></i>
                                    Sil
                                </button>
                            </form>
                        </div>
                        <form method="POST" action="{{ route('admin.service-reviews.questions.update', $question) }}" class="kt-card-content grid gap-4 p-6" data-review-question-form>
                            @csrf
                            @method('PUT')
                            @include('admin.pages.service-reviews._question-fields', [
                                'question' => $question,
                                'typeOptions' => $typeOptions,
                            ])
                            <div class="flex justify-end">
                                <button type="submit" class="kt-btn kt-btn-light-primary">
                                    <i class="ki-filled ki-check"></i>
                                    Güncelle
                                </button>
                            </div>
                        </form>
                    </div>
                @empty
                    <div class="kt-card p-10 text-center">
                        <div class="text-lg font-semibold text-foreground">Henüz ek anket sorusu yok.</div>
                        <div class="mt-2 text-sm text-muted-foreground">Üyeler yine de hizmete 1–5 yıldız verebilir ve not bırakabilir.</div>
                    </div>
                @endforelse
            </section>
        </div>
    </div>
@endsection
