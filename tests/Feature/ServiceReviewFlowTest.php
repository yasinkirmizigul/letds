<?php

namespace Tests\Feature;

use App\Models\Admin\Ecommerce\EcommerceOrder;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Models\Member;
use App\Models\Review\ServiceReview;
use App\Models\Review\ServiceReviewQuestion;
use App\Services\Review\ServiceReviewAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceReviewFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_service_creates_one_review_and_locks_question_snapshot(): void
    {
        [$member, $provider] = $this->actors();
        $question = ServiceReviewQuestion::query()->create([
            'question' => 'Hizmet zamanında başladı mı?',
            'type' => ServiceReviewQuestion::TYPE_YES_NO,
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $appointment = Appointment::query()->create([
            'provider_id' => $provider->id,
            'member_id' => $member->id,
            'start_at' => now()->subHour(),
            'end_at' => now()->subMinutes(30),
            'blocks' => 1,
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        $service = app(ServiceReviewAssignmentService::class);
        $first = $service->assignForAppointment($appointment);
        $second = $service->assignForAppointment($appointment);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second?->id);
        $this->assertDatabaseCount('service_reviews', 1);

        $locked = $service->ensureQuestionSnapshot($first);
        $this->assertSame('Hizmet zamanında başladı mı?', $locked->items->first()?->question_text);

        $question->update(['question' => 'Değiştirilen soru']);
        $lockedAgain = $service->ensureQuestionSnapshot($locked->fresh());
        $this->assertSame('Hizmet zamanında başladı mı?', $lockedAgain->items->first()?->question_text);
    }

    public function test_member_can_submit_review_only_once(): void
    {
        [$member, $provider] = $this->actors();
        ServiceReviewQuestion::query()->create([
            'question' => 'Tekrar tercih eder misiniz?',
            'type' => ServiceReviewQuestion::TYPE_YES_NO,
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $appointment = Appointment::query()->create([
            'provider_id' => $provider->id,
            'member_id' => $member->id,
            'start_at' => now()->subHour(),
            'end_at' => now()->subMinutes(30),
            'blocks' => 1,
            'status' => Appointment::STATUS_COMPLETED,
        ]);
        $service = app(ServiceReviewAssignmentService::class);
        $review = $service->ensureQuestionSnapshot($service->assignForAppointment($appointment));
        $item = $review->items->firstOrFail();

        $this->actingAs($member, 'member')
            ->get(route('member.reviews.show', $review))
            ->assertOk()
            ->assertSee('Hizmeti Değerlendir')
            ->assertSee('Tekrar tercih eder misiniz?');

        $this->actingAs($member, 'member')
            ->post(route('member.reviews.store', $review), [
                'overall_rating' => 5,
                'public_comment' => 'Çok memnun kaldım.',
                'answers' => [$item->id => 'yes'],
            ])
            ->assertRedirect();

        $review->refresh();
        $this->assertSame(ServiceReview::STATUS_COMPLETED, $review->status);
        $this->assertSame(5, $review->overall_rating);
        $this->assertSame('yes', $item->fresh()->answerValue());

        $this->actingAs($member, 'member')
            ->post(route('member.reviews.store', $review), [
                'overall_rating' => 1,
                'answers' => [$item->id => 'no'],
            ])
            ->assertSessionHasErrors('review');

        $this->assertSame(5, $review->fresh()->overall_rating);
    }

    public function test_completed_order_creates_a_review_for_the_purchasing_member(): void
    {
        [$member, $provider] = $this->actors();
        $order = EcommerceOrder::query()->create([
            'member_id' => $member->id,
            'status' => EcommerceOrder::STATUS_COMPLETED,
            'customer_name' => $member->full_name,
            'customer_email' => $member->email,
            'delivered_at' => now()->subHour(),
        ]);
        $order->items()->create([
            'product_title' => 'Bakim Paketi',
            'quantity' => 1,
        ]);
        $order->histories()->create([
            'user_id' => $provider->id,
            'to_status' => EcommerceOrder::STATUS_COMPLETED,
        ]);

        $review = app(ServiceReviewAssignmentService::class)->assignForOrder($order->fresh());

        $this->assertNotNull($review);
        $this->assertSame($member->id, $review->member_id);
        $this->assertSame($provider->id, $review->provider_user_id);
        $this->assertSame('Bakim Paketi', $review->service_title);
        $this->assertSame(ServiceReview::SERVICE_ORDER, $review->service_type);
    }

    private function actors(): array
    {
        $provider = User::query()->create([
            'name' => 'Test Hizmet Yetkilisi',
            'email' => 'provider-'.uniqid().'@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $member = Member::query()->create([
            'name' => 'Test',
            'surname' => 'Üye',
            'email' => 'member-'.uniqid().'@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        return [$member, $provider];
    }
}
