<?php

namespace App\Http\Controllers\Admin\Review;

use App\Http\Controllers\Controller;
use App\Models\Review\ServiceReviewQuestion;
use App\Support\Audit\AuditEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceReviewQuestionController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.service-reviews.questions', [
            'questions' => ServiceReviewQuestion::query()->ordered()->get(),
            'typeOptions' => ServiceReviewQuestion::typeOptions(),
            'stats' => [
                'all' => ServiceReviewQuestion::query()->count(),
                'active' => ServiceReviewQuestion::query()->active()->count(),
                'required' => ServiceReviewQuestion::query()->where('is_required', true)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayload($request);
        $payload['sort_order'] ??= ((int) ServiceReviewQuestion::query()->max('sort_order')) + 10;
        $question = ServiceReviewQuestion::query()->create($payload);

        AuditEvent::log('service_review_questions.create', ['question_id' => $question->id]);

        return back()->with('success', 'Anket sorusu eklendi.');
    }

    public function update(Request $request, ServiceReviewQuestion $question): RedirectResponse
    {
        $payload = $this->validatedPayload($request);
        if ($payload['sort_order'] === null) {
            unset($payload['sort_order']);
        }

        $question->update($payload);
        AuditEvent::log('service_review_questions.update', ['question_id' => $question->id]);

        return back()->with('success', 'Anket sorusu güncellendi.');
    }

    public function destroy(ServiceReviewQuestion $question): RedirectResponse
    {
        $question->delete();
        AuditEvent::log('service_review_questions.delete', ['question_id' => $question->id]);

        return back()->with('success', 'Anket sorusu kaldırıldı. Geçmiş cevaplar korunuyor.');
    }

    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'type' => ['required', Rule::in(array_keys(ServiceReviewQuestion::typeOptions()))],
            'options_text' => ['nullable', 'string', 'max:5000'],
            'is_required' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $options = collect(preg_split('/\r\n|\r|\n/', (string) ($validated['options_text'] ?? '')))
            ->map(fn ($option) => trim((string) $option))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($validated['type'] === ServiceReviewQuestion::TYPE_SINGLE_CHOICE && count($options) < 2) {
            throw ValidationException::withMessages([
                'options_text' => 'Tek seçim sorusu için en az iki seçenek girin.',
            ]);
        }

        return [
            'question' => trim((string) $validated['question']),
            'type' => $validated['type'],
            'options' => $validated['type'] === ServiceReviewQuestion::TYPE_SINGLE_CHOICE ? $options : null,
            'is_required' => (bool) $validated['is_required'],
            'is_active' => (bool) $validated['is_active'],
            'sort_order' => $validated['sort_order'] ?? null,
        ];
    }
}
