<?php

namespace App\Http\Controllers\Site\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Review\ServiceReview;
use App\Models\Review\ServiceReviewQuestion;
use App\Services\Review\ServiceReviewAssignmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceReviewController extends Controller
{
    public function index(Request $request, ServiceReviewAssignmentService $assignmentService): View
    {
        /** @var Member $member */
        $member = $request->user('member');
        $assignmentService->syncCompletedServices($member->id);

        $reviews = ServiceReview::query()
            ->where('member_id', $member->id)
            ->with('provider:id,name,title')
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [ServiceReview::STATUS_PENDING])
            ->latest('service_completed_at')
            ->paginate(12);

        return view('site.reviews.index', [
            'pageTitle' => 'Değerlendirmelerim',
            'member' => $member,
            'reviews' => $reviews,
            'stats' => [
                'pending' => ServiceReview::query()->where('member_id', $member->id)->pending()->count(),
                'completed' => ServiceReview::query()->where('member_id', $member->id)->completed()->count(),
                'average' => round((float) ServiceReview::query()
                    ->where('member_id', $member->id)
                    ->completed()
                    ->avg('overall_rating'), 1),
            ],
        ]);
    }

    public function show(
        Request $request,
        ServiceReview $serviceReview,
        ServiceReviewAssignmentService $assignmentService
    ): View {
        $this->authorizeMember($request, $serviceReview);
        $serviceReview = $assignmentService->ensureQuestionSnapshot($serviceReview);
        $serviceReview->loadMissing('provider:id,name,title');

        return view('site.reviews.show', [
            'pageTitle' => $serviceReview->isPending() ? 'Hizmeti Değerlendir' : 'Değerlendirme Detayı',
            'review' => $serviceReview,
        ]);
    }

    public function store(
        Request $request,
        ServiceReview $serviceReview,
        ServiceReviewAssignmentService $assignmentService
    ): RedirectResponse {
        $this->authorizeMember($request, $serviceReview);
        $serviceReview = $assignmentService->ensureQuestionSnapshot($serviceReview);

        if (! $serviceReview->isPending()) {
            throw ValidationException::withMessages([
                'review' => 'Bu hizmet daha önce değerlendirilmiş.',
            ]);
        }

        $rules = [
            'overall_rating' => ['required', 'integer', 'between:1,5'],
            'public_comment' => ['nullable', 'string', 'max:2000'],
            'answers' => ['nullable', 'array'],
        ];

        foreach ($serviceReview->items as $item) {
            $key = 'answers.'.$item->id;
            $itemRules = [$item->is_required ? 'required' : 'nullable'];

            $itemRules = match ($item->question_type) {
                ServiceReviewQuestion::TYPE_SCALE => [...$itemRules, 'integer', 'between:1,5'],
                ServiceReviewQuestion::TYPE_YES_NO => [...$itemRules, Rule::in(['yes', 'no'])],
                ServiceReviewQuestion::TYPE_SINGLE_CHOICE => [
                    ...$itemRules,
                    Rule::in($item->question_options ?: []),
                ],
                default => [...$itemRules, 'string', 'max:1500'],
            };

            $rules[$key] = $itemRules;
        }

        $validated = $request->validate($rules, [
            'overall_rating.required' => 'Genel yıldız puanını seçin.',
            'overall_rating.between' => 'Yıldız puanı 1 ile 5 arasında olmalıdır.',
            'answers.*.required' => 'Bu soruyu yanıtlamanız gerekiyor.',
        ]);

        DB::transaction(function () use ($serviceReview, $validated): void {
            $locked = ServiceReview::query()->lockForUpdate()->findOrFail($serviceReview->id);

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'review' => 'Bu hizmet daha önce değerlendirilmiş.',
                ]);
            }

            $answers = $validated['answers'] ?? [];
            foreach ($locked->items()->get() as $item) {
                $value = $answers[$item->id] ?? null;
                $item->update([
                    'answer' => filled($value) ? ['value' => $value] : null,
                ]);
            }

            $locked->update([
                'status' => ServiceReview::STATUS_COMPLETED,
                'overall_rating' => (int) $validated['overall_rating'],
                'public_comment' => filled($validated['public_comment'] ?? null)
                    ? trim((string) $validated['public_comment'])
                    : null,
                'submitted_at' => now(),
            ]);
        });

        return redirect()
            ->route('member.reviews.show', [
                'serviceReview' => $serviceReview,
                'site_locale' => app()->getLocale(),
            ])
            ->with('success', 'Değerlendirmeniz kaydedildi. Teşekkür ederiz.');
    }

    private function authorizeMember(Request $request, ServiceReview $review): void
    {
        /** @var Member|null $member */
        $member = $request->user('member');
        abort_unless($member && (int) $review->member_id === (int) $member->id, 404);
    }
}
