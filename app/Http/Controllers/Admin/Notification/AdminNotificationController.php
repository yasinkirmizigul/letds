<?php

namespace App\Http\Controllers\Admin\Notification;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminNotification;
use App\Models\Admin\User\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $status = (string) $request->string('status', 'active');
        $type = (string) $request->string('type', 'all');

        $notifications = AdminNotification::query()
            ->visibleTo($user)
            ->when($status === 'unread', fn ($query) => $query->active()->unread())
            ->when($status === 'read', fn ($query) => $query->whereNotNull('read_at')->whereNull('dismissed_at'))
            ->when($status === 'dismissed', fn ($query) => $query->whereNotNull('dismissed_at'))
            ->when($status === 'active', fn ($query) => $query->active())
            ->when($type !== 'all', fn ($query) => $query->where('type', $type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.pages.notifications.index', [
            'pageTitle' => 'Bildirim Merkezi',
            'notifications' => $notifications,
            'status' => $status,
            'type' => $type,
            'stats' => [
                'active' => AdminNotification::query()->visibleTo($user)->active()->count(),
                'unread' => AdminNotification::query()->visibleTo($user)->active()->unread()->count(),
                'dismissed' => AdminNotification::query()->visibleTo($user)->whereNotNull('dismissed_at')->count(),
            ],
            'typeOptions' => [
                'all' => 'Tüm bildirimler',
                AdminNotification::TYPE_MESSAGE => 'Mesajlar',
                AdminNotification::TYPE_APPOINTMENT => 'Randevular',
                AdminNotification::TYPE_ORDER => 'Siparişler',
                AdminNotification::TYPE_PAYMENT => 'Ödemeler',
                AdminNotification::TYPE_INVENTORY => 'Stok',
                AdminNotification::TYPE_SYSTEM => 'Sistem',
            ],
        ]);
    }

    public function read(Request $request, AdminNotification $notification): JsonResponse|RedirectResponse
    {
        $this->authorizeNotification($request, $notification);
        $notification->markRead();

        return $this->respond($request, 'Bildirim okundu olarak işaretlendi.');
    }

    public function dismiss(Request $request, AdminNotification $notification): JsonResponse|RedirectResponse
    {
        $this->authorizeNotification($request, $notification);
        $notification->dismiss();

        return $this->respond($request, 'Bildirim kapatıldı.');
    }

    public function readAll(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        AdminNotification::query()
            ->visibleTo($user)
            ->active()
            ->unread()
            ->update(['read_at' => now()]);

        return $this->respond($request, 'Tüm aktif bildirimler okundu olarak işaretlendi.');
    }

    private function authorizeNotification(Request $request, AdminNotification $notification): void
    {
        abort_unless((int) $notification->user_id === (int) $request->user()?->id, 403);
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'redirect_url' => route('admin.notifications.index'),
            ]);
        }

        return back()->with('success', $message);
    }
}
