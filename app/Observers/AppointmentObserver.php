<?php

namespace App\Observers;

use App\Models\Appointment\Appointment;
use App\Services\Review\ServiceReviewAssignmentService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class AppointmentObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Appointment $appointment): void
    {
        if (
            $appointment->status === Appointment::STATUS_COMPLETED
            && ($appointment->wasRecentlyCreated || $appointment->wasChanged(['status', 'member_id', 'provider_id']))
        ) {
            app(ServiceReviewAssignmentService::class)->assignForAppointment($appointment);
        }
    }
}
