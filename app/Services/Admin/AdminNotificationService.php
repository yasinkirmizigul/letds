<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminNotification;
use App\Models\Admin\Ecommerce\EcommerceOrder;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AdminNotificationService
{
    public function notifyUser(?User $user, array $payload): ?AdminNotification
    {
        if (!$user || !(bool) $user->is_active) {
            return null;
        }

        return AdminNotification::query()->create(array_merge([
            'user_id' => $user->id,
            'type' => AdminNotification::TYPE_SYSTEM,
            'severity' => AdminNotification::SEVERITY_INFO,
        ], $payload));
    }

    public function notifyUsers(iterable $users, array $payload): Collection
    {
        return collect($users)
            ->map(fn (User $user) => $this->notifyUser($user, $payload))
            ->filter()
            ->values();
    }

    public function notifyBackoffice(array $payload, ?string $permission = null): Collection
    {
        $users = User::query()
            ->adminAccessible()
            ->with('roles.permissions')
            ->get()
            ->filter(function (User $user) use ($permission) {
                return $permission === null || $user->canAccess($permission);
            });

        return $this->notifyUsers($users, $payload);
    }

    public function fromContactMessage(ContactMessage $message): void
    {
        $message->loadMissing('recipient');

        $payload = [
            'type' => AdminNotification::TYPE_MESSAGE,
            'severity' => in_array($message->priority, [ContactMessage::PRIORITY_HIGH, ContactMessage::PRIORITY_URGENT], true)
                ? AdminNotification::SEVERITY_WARNING
                : AdminNotification::SEVERITY_INFO,
            'title' => 'Yeni mesaj alındı',
            'body' => trim(($message->sender_full_name ?: $message->sender_email ?: 'Ziyaretçi') . ' · ' . $message->subject),
            'action_label' => 'Mesajı aç',
            'action_url' => Route::has('admin.messages.show') ? route('admin.messages.show', $message) : null,
            'source_type' => ContactMessage::class,
            'source_id' => $message->id,
        ];

        if ($message->recipient) {
            $this->notifyUser($message->recipient, $payload);
            return;
        }

        $this->notifyBackoffice($payload);
    }

    public function fromAppointment(Appointment $appointment, string $event = 'created'): void
    {
        $appointment->loadMissing(['member', 'provider']);

        $labels = [
            'created' => 'Yeni randevu oluşturuldu',
            'transferred' => 'Randevu taşındı',
            'resized' => 'Randevu süresi güncellendi',
            'cancelled_by_provider' => 'Randevu panelden iptal edildi',
            'cancelled_by_member' => 'Üye randevuyu iptal etti',
            'rescheduled_by_member' => 'Üye randevuyu yeniden planladı',
        ];

        $payload = [
            'type' => AdminNotification::TYPE_APPOINTMENT,
            'severity' => str_contains($event, 'cancelled')
                ? AdminNotification::SEVERITY_WARNING
                : AdminNotification::SEVERITY_INFO,
            'title' => $labels[$event] ?? 'Randevu güncellendi',
            'body' => trim(($appointment->member?->full_name ?: 'Üye') . ' · ' . optional($appointment->start_at)->format('d.m.Y H:i')),
            'action_label' => 'Takvime git',
            'action_url' => Route::has('admin.appointments.calendar')
                ? route('admin.appointments.calendar', ['date' => optional($appointment->start_at)->format('Y-m-d')])
                : null,
            'source_type' => Appointment::class,
            'source_id' => $appointment->id,
        ];

        $recipients = new EloquentCollection();

        if ($appointment->provider) {
            $recipients->push($appointment->provider);
        }

        User::query()
            ->adminAccessible()
            ->with('roles.permissions')
            ->get()
            ->filter(fn (User $user) => $user->canAccess('appointments.view'))
            ->each(fn (User $user) => $recipients->push($user));

        $this->notifyUsers($recipients->unique('id'), $payload);
    }

    public function fromOrder(EcommerceOrder $order, string $event = 'created'): void
    {
        $payload = [
            'type' => AdminNotification::TYPE_ORDER,
            'severity' => in_array($order->payment_status, [EcommerceOrder::PAYMENT_FAILED, EcommerceOrder::PAYMENT_UNPAID], true)
                ? AdminNotification::SEVERITY_WARNING
                : AdminNotification::SEVERITY_INFO,
            'title' => $event === 'created' ? 'Yeni sipariş oluşturuldu' : 'Sipariş güncellendi',
            'body' => trim($order->order_number . ' · ' . $order->customer_name . ' · ' . $order->money()),
            'action_label' => 'Siparişi aç',
            'action_url' => Route::has('admin.ecommerce.orders.show') ? route('admin.ecommerce.orders.show', $order) : null,
            'source_type' => EcommerceOrder::class,
            'source_id' => $order->id,
        ];

        $this->notifyBackoffice($payload, 'ecommerce_orders.view');
    }
}
