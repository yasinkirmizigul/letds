@php
    $questionId = $question?->id ?: 'new';
    $selectedType = old('type', $question?->type ?: \App\Models\Review\ServiceReviewQuestion::TYPE_SCALE);
    $optionsText = old('options_text', $question?->options ? implode(PHP_EOL, $question->options) : '');
@endphp

<div class="grid gap-2">
    <label class="kt-form-label" for="review_question_{{ $questionId }}">Soru</label>
    <textarea id="review_question_{{ $questionId }}" name="question" rows="3" class="kt-input min-h-[88px]" required>{{ old('question', $question?->question) }}</textarea>
</div>

<div class="grid gap-4 md:grid-cols-2">
    <div class="grid gap-2">
        <label class="kt-form-label" for="review_question_type_{{ $questionId }}">Yanıt Türü</label>
        <select id="review_question_type_{{ $questionId }}" name="type" class="kt-select" data-review-question-type required>
            @foreach($typeOptions as $value => $label)
                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid gap-2">
        <label class="kt-form-label" for="review_question_sort_{{ $questionId }}">Sıra</label>
        <input id="review_question_sort_{{ $questionId }}" type="number" min="0" name="sort_order" value="{{ old('sort_order', $question?->sort_order) }}" class="kt-input" placeholder="Otomatik">
    </div>
</div>

<div class="grid gap-2" data-review-question-options>
    <label class="kt-form-label" for="review_question_options_{{ $questionId }}">Seçenekler</label>
    <textarea id="review_question_options_{{ $questionId }}" name="options_text" rows="4" class="kt-input min-h-[104px]" placeholder="Her satıra bir seçenek">{{ $optionsText }}</textarea>
    <div class="text-xs text-muted-foreground">Yalnızca “Tek seçim” türünde kullanılır; en az iki seçenek girin.</div>
</div>

<div class="grid gap-3 sm:grid-cols-2">
    <label class="app-surface-card app-surface-card--soft flex items-start gap-3 p-4">
        <input type="hidden" name="is_required" value="0">
        <input type="checkbox" name="is_required" value="1" class="kt-checkbox mt-1" @checked(old('is_required', $question?->is_required ?? false))>
        <span><span class="block font-medium text-foreground">Zorunlu yanıt</span><span class="text-xs text-muted-foreground">Boş bırakılamaz.</span></span>
    </label>
    <label class="app-surface-card app-surface-card--soft flex items-start gap-3 p-4">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="kt-checkbox mt-1" @checked(old('is_active', $question?->is_active ?? true))>
        <span><span class="block font-medium text-foreground">Aktif soru</span><span class="text-xs text-muted-foreground">Yeni anketlere eklenir.</span></span>
    </label>
</div>
