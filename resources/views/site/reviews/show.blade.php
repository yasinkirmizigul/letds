@extends('site.layouts.main.app')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-10 lg:px-6">
        <div class="flex flex-col gap-5 border-b border-border pb-8 md:flex-row md:items-end md:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="kt-badge kt-badge-sm kt-badge-light-primary">{{ $review->serviceTypeLabel() }}</span>
                    <span class="{{ $review->statusBadgeClass() }}">{{ $review->statusLabel() }}</span>
                </div>
                <h1 class="mt-4 font-display text-3xl font-semibold text-foreground">{{ $review->service_title }}</h1>
                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                    <span>{{ $review->service_reference ?: 'Referans yok' }}</span>
                    <span>{{ $review->service_completed_at?->format('d.m.Y H:i') ?: '-' }}</span>
                    @if($review->provider)<span>Hizmeti veren: {{ $review->provider->name }}</span>@endif
                </div>
            </div>
            <a href="{{ route('member.reviews.index', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light">Tüm Değerlendirmeler</a>
        </div>

        @if(session('success'))
            <div class="mt-6 border border-success/30 bg-success/10 px-5 py-4 text-sm text-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="mt-6 border border-danger/30 bg-danger/10 px-5 py-4 text-sm text-danger">
                {{ $errors->first() }}
            </div>
        @endif

        @if($review->isPending())
            <form method="POST" action="{{ route('member.reviews.store', ['serviceReview' => $review, 'site_locale' => $siteCurrentLocale]) }}" class="mt-8 grid gap-8">
                @csrf

                <fieldset class="border border-border bg-background p-6 md:p-8">
                    <legend class="px-2 text-lg font-semibold text-foreground">Genel deneyiminiz</legend>
                    <p class="mb-5 text-sm leading-7 text-muted-foreground">Bu hizmeti genel olarak 1 ile 5 yıldız arasında değerlendirin.</p>
                    @include('site.reviews._star-input', [
                        'name' => 'overall_rating',
                        'id' => 'overall_rating',
                        'selected' => old('overall_rating'),
                        'required' => true,
                        'label' => 'Genel hizmet puanı',
                    ])
                    @error('overall_rating')<div class="mt-3 text-sm text-danger">{{ $message }}</div>@enderror
                </fieldset>

                @foreach($review->items as $item)
                    <fieldset class="border border-border bg-background p-6 md:p-8">
                        <legend class="max-w-[90%] px-2 text-base font-semibold leading-6 text-foreground">
                            {{ $item->question_text }} @if($item->is_required)<span class="text-danger">*</span>@endif
                        </legend>

                        <div class="mt-3">
                            @if($item->question_type === 'scale')
                                @include('site.reviews._star-input', [
                                    'name' => 'answers[' . $item->id . ']',
                                    'id' => 'answer_' . $item->id,
                                    'selected' => old('answers.' . $item->id),
                                    'required' => $item->is_required,
                                    'label' => $item->question_text,
                                ])
                            @elseif($item->question_type === 'yes_no')
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach(['yes' => 'Evet', 'no' => 'Hayır'] as $value => $label)
                                        <label class="flex cursor-pointer items-center gap-3 border border-border px-4 py-4 hover:border-primary">
                                            <input type="radio" name="answers[{{ $item->id }}]" value="{{ $value }}" class="kt-radio" @checked(old('answers.' . $item->id) === $value) @required($item->is_required)>
                                            <span class="font-medium text-foreground">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @elseif($item->question_type === 'single_choice')
                                <div class="grid gap-3">
                                    @foreach($item->question_options ?: [] as $option)
                                        <label class="flex cursor-pointer items-center gap-3 border border-border px-4 py-4 hover:border-primary">
                                            <input type="radio" name="answers[{{ $item->id }}]" value="{{ $option }}" class="kt-radio" @checked(old('answers.' . $item->id) === $option) @required($item->is_required)>
                                            <span class="text-foreground">{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <textarea name="answers[{{ $item->id }}]" rows="4" class="kt-input min-h-[112px]" maxlength="1500" @required($item->is_required) placeholder="Yanıtınızı yazın">{{ old('answers.' . $item->id) }}</textarea>
                            @endif
                        </div>
                        @error('answers.' . $item->id)<div class="mt-3 text-sm text-danger">{{ $message }}</div>@enderror
                    </fieldset>
                @endforeach

                <div class="border border-border bg-background p-6 md:p-8">
                    <label for="public_comment" class="text-base font-semibold text-foreground">Eklemek istediğiniz bir not var mı?</label>
                    <p class="mt-2 text-sm text-muted-foreground">İsteğe bağlıdır. En fazla 2000 karakter kullanabilirsiniz.</p>
                    <textarea id="public_comment" name="public_comment" rows="5" maxlength="2000" class="kt-input mt-4 min-h-[132px]" placeholder="Deneyiminizle ilgili paylaşmak istediklerinizi yazın">{{ old('public_comment') }}</textarea>
                    @error('public_comment')<div class="mt-3 text-sm text-danger">{{ $message }}</div>@enderror
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-border pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-muted-foreground">Gönderildikten sonra bu değerlendirme değiştirilemez.</div>
                    <button type="submit" class="kt-btn kt-btn-primary">Değerlendirmeyi Gönder</button>
                </div>
            </form>
        @else
            <div class="mt-8 grid gap-6">
                <section class="border border-border bg-background p-6 md:p-8">
                    <div class="text-sm text-muted-foreground">Genel Puanınız</div>
                    <div class="mt-3 flex items-center gap-3">
                        <div class="text-3xl text-warning" aria-label="{{ $review->overall_rating }} yıldız">
                            @foreach(range(1, 5) as $star)<span class="{{ $star <= $review->overall_rating ? 'text-warning' : 'text-muted-foreground/30' }}">★</span>@endforeach
                        </div>
                        <div class="text-2xl font-semibold text-foreground">{{ $review->overall_rating }} / 5</div>
                    </div>
                    <div class="mt-2 text-xs text-muted-foreground">{{ $review->submitted_at?->format('d.m.Y H:i') }}</div>
                </section>

                @foreach($review->items as $item)
                    <section class="border border-border bg-background p-6">
                        <div class="text-sm font-semibold text-foreground">{{ $item->question_text }}</div>
                        @php($answer = $item->answerValue())
                        <div class="mt-3 text-base text-primary">
                            @if($item->question_type === 'yes_no')
                                {{ $answer === 'yes' ? 'Evet' : ($answer === 'no' ? 'Hayır' : 'Yanıt yok') }}
                            @elseif($item->question_type === 'scale' && $answer)
                                {{ $answer }} / 5
                            @else
                                {{ filled($answer) ? $answer : 'Yanıt yok' }}
                            @endif
                        </div>
                    </section>
                @endforeach

                <section class="border border-border bg-background p-6">
                    <div class="text-sm font-semibold text-foreground">Notunuz</div>
                    <div class="mt-3 text-sm leading-7 text-muted-foreground">{{ $review->public_comment ?: 'Ek not bırakmadınız.' }}</div>
                </section>
            </div>
        @endif
    </div>
@endsection
