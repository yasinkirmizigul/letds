<?php

namespace App\Services\Project;

use App\Models\Admin\Project\Project;
use App\Models\Appointment\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MemberProjectWorkflowService
{
    public function ensureForCompletedAppointment(Appointment $appointment): ?Project
    {
        return DB::transaction(function () use ($appointment): ?Project {
            $appointment = Appointment::query()
                ->with('member:id,name,surname')
                ->lockForUpdate()
                ->findOrFail($appointment->id);

            if ($appointment->status !== Appointment::STATUS_COMPLETED || ! $appointment->member_id) {
                return null;
            }

            $memberName = $appointment->member?->full_name ?: 'Üye';
            $project = Project::query()->firstOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'member_id' => $appointment->member_id,
                    'title' => $memberName.' Projesi',
                    'slug' => $this->uniqueSlug($memberName.' proje '.$appointment->id),
                    'content' => 'Randevu sonrasında oluşturulan müşteri projesi.',
                    'status' => Project::STATUS_APPOINTMENT_DONE,
                ]
            );

            if (! $project->member_id) {
                $project->forceFill(['member_id' => $appointment->member_id])->save();
            }

            return $project;
        });
    }

    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'uye-projesi';
        $slug = $base;
        $suffix = 2;

        while (Project::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
