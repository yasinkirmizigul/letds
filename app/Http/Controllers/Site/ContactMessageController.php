<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreContactMessageRequest;
use App\Models\Admin\User\User;
use App\Models\ContactMessage;
use App\Models\Member;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContactMessageController extends Controller
{
    public function create(): View
    {
        $member = auth('member')->user();
        $recipientId = request()->integer('recipient');

        $recipients = User::query()
            ->adminAccessible()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        if (!$recipients->contains('id', $recipientId)) {
            $recipientId = null;
        }

        return view('site.contact-messages.create', [
            'member' => $member,
            'recipients' => $recipients,
            'priorityOptions' => ContactMessage::priorityOptionsSorted(),
            'contactChannelOptions' => ContactMessage::CONTACT_CHANNEL_OPTIONS,
            'selectedRecipientId' => $recipientId,
        ]);
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $recipient = User::query()
            ->adminAccessible()
            ->whereKey($request->integer('recipient_user_id'))
            ->firstOrFail(['id', 'name']);

        /** @var Member|null $member */
        $member = $request->user('member');

        $payload = [
            'recipient_user_id' => $recipient->id,
            'recipient_name' => $recipient->name,
            'subject' => $request->string('subject')->trim()->toString(),
            'priority' => $request->string('priority')->trim()->toString(),
            'message' => $request->string('message')->trim()->toString(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        if ($request->isMemberSubmission() && $member) {
            $payload = array_merge($payload, [
                'member_id' => $member->id,
                'sender_type' => ContactMessage::SENDER_TYPE_MEMBER,
                'sender_name' => $member->name,
                'sender_surname' => $member->surname,
                'sender_email' => $member->email,
                'sender_phone' => $member->phone,
                'preferred_channels' => array_values(array_filter([
                    filled($member->email) ? ContactMessage::CONTACT_CHANNEL_EMAIL : null,
                    filled($member->phone) ? ContactMessage::CONTACT_CHANNEL_PHONE : null,
                ])),
            ]);
        } else {
            $payload = array_merge($payload, [
                'sender_type' => ContactMessage::SENDER_TYPE_GUEST,
                'sender_name' => $request->string('name')->trim()->toString(),
                'sender_surname' => $request->string('surname')->trim()->toString(),
                'sender_email' => $request->filled('email') ? $request->string('email')->trim()->toString() : null,
                'sender_phone' => $request->filled('phone') ? $request->string('phone')->trim()->toString() : null,
                'preferred_channels' => $request->input('contact_channels', []),
            ]);
        }

        ContactMessage::create($payload);

        return redirect()
            ->route('site.contact-messages.create')
            ->with('ok', 'Mesajınız başarıyla iletildi.');
    }
}
