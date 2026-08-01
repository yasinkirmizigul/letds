<?php

namespace App\Observers;

use App\Models\Admin\Ecommerce\EcommerceOrder;
use App\Services\Review\ServiceReviewAssignmentService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class EcommerceOrderObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(EcommerceOrder $order): void
    {
        if (
            $order->status === EcommerceOrder::STATUS_COMPLETED
            && ($order->wasRecentlyCreated || $order->wasChanged(['status', 'member_id']))
        ) {
            app(ServiceReviewAssignmentService::class)->assignForOrder($order);
        }
    }
}
