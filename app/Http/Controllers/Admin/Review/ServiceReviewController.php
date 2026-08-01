<?php

namespace App\Http\Controllers\Admin\Review;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\User;
use App\Models\Member;
use App\Models\Review\ServiceReview;
use App\Models\Review\ServiceReviewItem;
use App\Models\Review\ServiceReviewQuestion;
use App\Services\Review\ServiceReviewAssignmentService;
use App\Support\Audit\AuditEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceReviewController extends Controller
{
    public function index(Request $request, ServiceReviewAssignmentService $assignmentService): View
    {
        $assignmentService->syncCompletedServices();

        /** @var User $actor */
        $actor = $request->user();
        $filters = $this->filters($request);
        $contextQuery = $this->applyContextFilters(ServiceReview::query(), $filters, $actor);
        $listQuery = clone $contextQuery;

        $reviews = $listQuery
            ->with(['member:id,name,surname,email', 'provider:id,name,title'])
            ->latest('service_completed_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $total = (clone $contextQuery)->count();
        $completed = (clone $contextQuery)->completed()->count();
        $average = (float) (clone $contextQuery)->completed()->avg('overall_rating');

        $distribution = collect(range(1, 5))
            ->mapWithKeys(fn (int $rating) => [
                $rating => (clone $contextQuery)->completed()->where('overall_rating', $rating)->count(),
            ])
            ->all();

        return view('admin.pages.service-reviews.index', [
            'reviews' => $reviews,
            'filters' => $filters,
            'providers' => $this->providerOptions(),
            'members' => Member::query()->orderBy('name')->orderBy('surname')->get(['id', 'name', 'surname', 'email']),
            'stats' => [
                'all' => $total,
                'completed' => $completed,
                'pending' => max(0, $total - $completed),
                'average' => round($average, 2),
                'response_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            ],
            'distribution' => $distribution,
            'distributionMax' => max(1, ...array_values($distribution)),
            'trend' => $this->trendData(clone $contextQuery),
            'questionStats' => $this->questionStats($filters, $actor),
        ]);
    }

    public function show(Request $request, ServiceReview $serviceReview): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->assertCanView($actor, $serviceReview);

        $serviceReview->load([
            'member:id,name,surname,email,phone',
            'provider:id,name,title,email',
            'items',
        ]);

        return view('admin.pages.service-reviews.show', [
            'review' => $serviceReview,
        ]);
    }

    public function sync(Request $request, ServiceReviewAssignmentService $assignmentService): RedirectResponse
    {
        $created = $assignmentService->syncCompletedServices();

        AuditEvent::log('service_reviews.sync', ['created' => $created]);

        return back()->with(
            'success',
            $created > 0
                ? "{$created} tamamlanmış hizmet için değerlendirme daveti oluşturuldu."
                : 'Eksik değerlendirme daveti bulunamadı.'
        );
    }

    private function filters(Request $request): array
    {
        $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
        $dateFrom = trim((string) $request->string('date_from'));
        $dateTo = trim((string) $request->string('date_to'));

        return [
            'q' => trim((string) $request->string('q')),
            'provider_id' => trim((string) $request->string('provider_id')),
            'member_id' => max(0, $request->integer('member_id')),
            'service_type' => trim((string) $request->string('service_type')),
            'status' => trim((string) $request->string('status')),
            'rating' => max(0, $request->integer('rating')),
            'date_from' => preg_match($datePattern, $dateFrom) ? $dateFrom : '',
            'date_to' => preg_match($datePattern, $dateTo) ? $dateTo : '',
        ];
    }

    private function applyContextFilters(Builder $query, array $filters, User $actor): Builder
    {
        if (! $actor->isAdmin() && $actor->hasRole('provider')) {
            $query->where('provider_user_id', $actor->id);
        }

        return $query
            ->when($filters['q'] !== '', function (Builder $builder) use ($filters): void {
                $term = $filters['q'];
                $builder->where(function (Builder $search) use ($term): void {
                    $search
                        ->where('service_title', 'like', "%{$term}%")
                        ->orWhere('service_reference', 'like', "%{$term}%")
                        ->orWhereHas('member', fn (Builder $memberQuery) => $memberQuery
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('surname', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%"));
                });
            })
            ->when($filters['provider_id'] === 'unassigned', fn (Builder $builder) => $builder->whereNull('provider_user_id'))
            ->when(ctype_digit($filters['provider_id']) && (int) $filters['provider_id'] > 0, fn (Builder $builder) => $builder->where('provider_user_id', (int) $filters['provider_id']))
            ->when($filters['member_id'] > 0, fn (Builder $builder) => $builder->where('member_id', $filters['member_id']))
            ->when(in_array($filters['service_type'], [ServiceReview::SERVICE_APPOINTMENT, ServiceReview::SERVICE_ORDER], true), fn (Builder $builder) => $builder->where('service_type', $filters['service_type']))
            ->when(in_array($filters['status'], [ServiceReview::STATUS_PENDING, ServiceReview::STATUS_COMPLETED], true), fn (Builder $builder) => $builder->where('status', $filters['status']))
            ->when($filters['rating'] >= 1 && $filters['rating'] <= 5, fn (Builder $builder) => $builder->where('overall_rating', $filters['rating']))
            ->when($filters['date_from'] !== '', fn (Builder $builder) => $builder->whereDate('service_completed_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn (Builder $builder) => $builder->whereDate('service_completed_at', '<=', $filters['date_to']));
    }

    private function providerOptions()
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('slug', ['provider', 'admin', 'superadmin']))
            ->orderBy('name')
            ->get(['id', 'name', 'title']);
    }

    private function trendData(Builder $contextQuery): array
    {
        $start = now()->startOfWeek()->subWeeks(7);
        $rows = $contextQuery
            ->completed()
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', $start)
            ->get(['submitted_at', 'overall_rating']);

        $labels = [];
        $averages = [];
        $counts = [];

        foreach (range(0, 7) as $week) {
            $weekStart = $start->copy()->addWeeks($week);
            $weekEnd = $weekStart->copy()->endOfWeek();
            $weekRows = $rows->filter(fn (ServiceReview $review) => $review->submitted_at?->between($weekStart, $weekEnd));

            $labels[] = $weekStart->format('d.m');
            $averages[] = round((float) $weekRows->avg('overall_rating'), 2);
            $counts[] = $weekRows->count();
        }

        return compact('labels', 'averages', 'counts');
    }

    private function questionStats(array $filters, User $actor): array
    {
        $items = ServiceReviewItem::query()
            ->whereNotNull('answer')
            ->whereHas('review', function (Builder $query) use ($filters, $actor): void {
                $this->applyContextFilters($query, $filters, $actor)->completed();
            })
            ->get(['question_text', 'question_type', 'answer']);

        return $items
            ->groupBy(fn (ServiceReviewItem $item) => $item->question_type.'|'.$item->question_text)
            ->map(function ($group): array {
                /** @var ServiceReviewItem $first */
                $first = $group->first();
                $values = $group->map(fn (ServiceReviewItem $item) => $item->answerValue())->filter(fn ($value) => filled($value));
                $summary = match ($first->question_type) {
                    ServiceReviewQuestion::TYPE_SCALE => number_format((float) $values->avg(), 1, ',', '.').' / 5 ortalama',
                    ServiceReviewQuestion::TYPE_YES_NO => $values->count() > 0
                        ? '% '.round(($values->filter(fn ($value) => $value === 'yes')->count() / $values->count()) * 100).' evet'
                        : 'Henüz yanıt yok',
                    ServiceReviewQuestion::TYPE_SINGLE_CHOICE => $values->countBy()->sortDesc()->keys()->first() ?: 'Henüz yanıt yok',
                    default => $values->count().' metin yanıtı',
                };

                return [
                    'question' => $first->question_text,
                    'type' => $first->questionTypeLabel(),
                    'count' => $values->count(),
                    'summary' => $summary,
                ];
            })
            ->sortByDesc('count')
            ->take(8)
            ->values()
            ->all();
    }

    private function assertCanView(User $actor, ServiceReview $review): void
    {
        if (! $actor->isAdmin() && $actor->hasRole('provider')) {
            abort_unless((int) $review->provider_user_id === (int) $actor->id, 403);
        }
    }
}
