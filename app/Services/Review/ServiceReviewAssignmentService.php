<?php

namespace App\Services\Review;

use App\Models\Admin\Ecommerce\EcommerceOrder;
use App\Models\Admin\Project\Project;
use App\Models\Appointment\Appointment;
use App\Models\Review\ServiceReview;
use App\Models\Review\ServiceReviewQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ServiceReviewAssignmentService
{
    public function assignForAppointment(Appointment $appointment): ?ServiceReview
    {
        if (! $this->isReady() || $appointment->status !== Appointment::STATUS_COMPLETED) {
            return null;
        }

        $appointment->loadMissing('provider:id,name,title');

        $review = ServiceReview::query()->firstOrCreate(
            [
                'reviewable_type' => $appointment->getMorphClass(),
                'reviewable_id' => $appointment->getKey(),
            ],
            [
                'member_id' => $appointment->member_id,
                'provider_user_id' => $appointment->provider_id,
                'service_type' => ServiceReview::SERVICE_APPOINTMENT,
                'service_title' => 'Randevu · '.($appointment->start_at?->format('d.m.Y H:i') ?: '#'.$appointment->id),
                'service_reference' => 'Randevu #'.$appointment->id,
                'status' => ServiceReview::STATUS_PENDING,
                'service_completed_at' => $appointment->end_at ?? $appointment->start_at,
                'invited_at' => now(),
            ]
        );

        if ($review->isPending()) {
            $review->fill([
                'member_id' => $appointment->member_id,
                'provider_user_id' => $appointment->provider_id,
                'service_title' => 'Randevu · '.($appointment->start_at?->format('d.m.Y H:i') ?: '#'.$appointment->id),
                'service_reference' => 'Randevu #'.$appointment->id,
                'service_completed_at' => $appointment->end_at ?? $appointment->start_at,
            ]);

            if ($review->isDirty()) {
                $review->save();
            }
        }

        return $review;
    }

    public function assignForOrder(EcommerceOrder $order): ?ServiceReview
    {
        if (! $this->isReady() || ! $order->member_id || $order->status !== EcommerceOrder::STATUS_COMPLETED) {
            return null;
        }

        $order->loadMissing('items:id,order_id,product_title');
        $productTitles = $order->items
            ->pluck('product_title')
            ->filter()
            ->unique()
            ->values();
        $title = $productTitles->take(2)->implode(', ');

        if ($productTitles->count() > 2) {
            $title .= ' +'.($productTitles->count() - 2);
        }

        $providerUserId = $order->histories()
            ->where('to_status', EcommerceOrder::STATUS_COMPLETED)
            ->whereNotNull('user_id')
            ->latest('id')
            ->value('user_id');

        $review = ServiceReview::query()->firstOrCreate(
            [
                'reviewable_type' => $order->getMorphClass(),
                'reviewable_id' => $order->getKey(),
            ],
            [
                'member_id' => $order->member_id,
                'provider_user_id' => $providerUserId,
                'service_type' => ServiceReview::SERVICE_ORDER,
                'service_title' => $title !== '' ? $title : 'Sipariş '.$order->order_number,
                'service_reference' => $order->order_number,
                'status' => ServiceReview::STATUS_PENDING,
                'service_completed_at' => $order->delivered_at ?? $order->updated_at,
                'invited_at' => now(),
            ]
        );

        if ($review->isPending()) {
            $review->fill([
                'member_id' => $order->member_id,
                'provider_user_id' => $providerUserId ?: $review->provider_user_id,
                'service_title' => $title !== '' ? $title : 'Sipariş '.$order->order_number,
                'service_reference' => $order->order_number,
                'service_completed_at' => $order->delivered_at ?? $order->updated_at,
            ]);

            if ($review->isDirty()) {
                $review->save();
            }
        }

        return $review;
    }

    public function assignForProject(Project $project): ?ServiceReview
    {
        $completedStatuses = [
            Project::STATUS_DELIVERED,
            Project::STATUS_APPROVED,
            Project::STATUS_CLOSED,
        ];

        if (! $this->isReady() || ! $project->member_id || ! in_array($project->status, $completedStatuses, true)) {
            return null;
        }

        $project->loadMissing('appointment:id,provider_id');
        $review = ServiceReview::query()->firstOrCreate(
            [
                'reviewable_type' => $project->getMorphClass(),
                'reviewable_id' => $project->getKey(),
            ],
            [
                'member_id' => $project->member_id,
                'provider_user_id' => $project->appointment?->provider_id,
                'service_type' => ServiceReview::SERVICE_PROJECT,
                'service_title' => $project->title,
                'service_reference' => 'Proje #'.$project->id,
                'status' => ServiceReview::STATUS_PENDING,
                'service_completed_at' => $project->updated_at ?? now(),
                'invited_at' => now(),
            ]
        );

        if ($review->isPending()) {
            $review->fill([
                'member_id' => $project->member_id,
                'provider_user_id' => $project->appointment?->provider_id ?: $review->provider_user_id,
                'service_title' => $project->title,
                'service_completed_at' => $project->updated_at ?? now(),
            ]);

            if ($review->isDirty()) {
                $review->save();
            }
        }

        return $review;
    }

    public function syncCompletedServices(?int $memberId = null): int
    {
        if (! $this->isReady()) {
            return 0;
        }

        $created = 0;

        Appointment::query()
            ->where('status', Appointment::STATUS_COMPLETED)
            ->when($memberId, fn ($query) => $query->where('member_id', $memberId))
            ->with('provider:id,name,title')
            ->orderBy('id')
            ->chunkById(200, function ($appointments) use (&$created): void {
                foreach ($appointments as $appointment) {
                    $review = $this->assignForAppointment($appointment);
                    $created += $review?->wasRecentlyCreated ? 1 : 0;
                }
            });

        EcommerceOrder::query()
            ->whereNotNull('member_id')
            ->where('status', EcommerceOrder::STATUS_COMPLETED)
            ->when($memberId, fn ($query) => $query->where('member_id', $memberId))
            ->with('items:id,order_id,product_title')
            ->orderBy('id')
            ->chunkById(200, function ($orders) use (&$created): void {
                foreach ($orders as $order) {
                    $review = $this->assignForOrder($order);
                    $created += $review?->wasRecentlyCreated ? 1 : 0;
                }
            });

        Project::query()
            ->whereNotNull('member_id')
            ->whereIn('status', [
                Project::STATUS_DELIVERED,
                Project::STATUS_APPROVED,
                Project::STATUS_CLOSED,
            ])
            ->when($memberId, fn ($query) => $query->where('member_id', $memberId))
            ->with('appointment:id,provider_id')
            ->orderBy('id')
            ->chunkById(200, function ($projects) use (&$created): void {
                foreach ($projects as $project) {
                    $review = $this->assignForProject($project);
                    $created += $review?->wasRecentlyCreated ? 1 : 0;
                }
            });

        return $created;
    }

    public function ensureQuestionSnapshot(ServiceReview $review): ServiceReview
    {
        if ($review->questions_locked_at || ! $review->isPending()) {
            return $review->loadMissing('items');
        }

        return DB::transaction(function () use ($review): ServiceReview {
            $locked = ServiceReview::query()->lockForUpdate()->findOrFail($review->id);

            if (! $locked->questions_locked_at && $locked->isPending()) {
                $questions = ServiceReviewQuestion::query()->active()->ordered()->get();

                foreach ($questions as $question) {
                    $locked->items()->create([
                        'question_id' => $question->id,
                        'question_text' => $question->question,
                        'question_type' => $question->type,
                        'question_options' => $question->options,
                        'is_required' => $question->is_required,
                        'sort_order' => $question->sort_order,
                    ]);
                }

                $locked->forceFill(['questions_locked_at' => now()])->save();
            }

            return $locked->fresh('items');
        });
    }

    private function isReady(): bool
    {
        return Schema::hasTable('service_reviews')
            && Schema::hasTable('service_review_questions')
            && Schema::hasTable('service_review_items');
    }
}
