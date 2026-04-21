<?php

namespace App\Http\Controllers\Admin\Message;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\User;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;

class ContactMessageController extends Controller
{
    public function index(): View
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user?->canAccessAdmin(), 403);

        $messages = ContactMessage::query()
            ->with([
                'recipient:id,name,email',
                'member:id,name,surname,email,phone',
            ])
            ->visibleToUser($user)
            ->orderByDesc('created_at')
            ->get();

        $recipientOptions = $user->isSuperAdmin()
            ? User::query()
                ->adminAccessible()
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        return view('admin.pages.messages.index', [
            'pageTitle' => 'Mesajlar',
            'messages' => $messages,
            'recipientOptions' => $recipientOptions,
            'priorityOptions' => ContactMessage::priorityOptionsSorted(),
            'stats' => [
                'total' => $messages->count(),
                'unread' => $messages->whereNull('read_at')->count(),
                'highPriority' => $messages->filter(fn (ContactMessage $message) => in_array($message->priority, [
                    ContactMessage::PRIORITY_HIGH,
                    ContactMessage::PRIORITY_URGENT,
                ], true))->count(),
                'guest' => $messages->where('sender_type', ContactMessage::SENDER_TYPE_GUEST)->count(),
            ],
            'isSuperAdmin' => $user->isSuperAdmin(),
        ]);
    }

    public function show(ContactMessage $contactMessage): View
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user?->canAccessAdmin(), 403);
        abort_unless($contactMessage->isVisibleToUser($user), 403);

        $contactMessage->load([
            'recipient:id,name,email',
            'member:id,name,surname,email,phone',
        ]);

        if (!$contactMessage->isRead()) {
            $contactMessage->forceFill(['read_at' => now()])->save();
        }

        return view('admin.pages.messages.show', [
            'pageTitle' => 'Mesaj Detayı',
            'message' => $contactMessage,
        ]);
    }
}
