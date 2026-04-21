<?php

namespace App\Http\Requests\Site;

use App\Models\Admin\User\User;
use App\Models\ContactMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function isMemberSubmission(): bool
    {
        return $this->user('member') !== null;
    }

    protected function prepareForValidation(): void
    {
        $channels = $this->input('contact_channels', []);

        if (!is_array($channels)) {
            $channels = [$channels];
        }

        $channels = array_values(array_unique(array_filter(array_map(function ($value) {
            $value = trim((string) $value);
            return $value !== '' ? strtolower($value) : null;
        }, $channels))));

        $this->merge([
            'recipient_user_id' => $this->filled('recipient_user_id') ? (int) $this->input('recipient_user_id') : null,
            'contact_channels' => $channels,
            'priority' => trim((string) $this->input('priority', ContactMessage::PRIORITY_NORMAL)),
            'subject' => trim((string) $this->input('subject', '')),
            'message' => trim((string) $this->input('message', '')),
            'name' => trim((string) $this->input('name', '')),
            'surname' => trim((string) $this->input('surname', '')),
            'email' => trim((string) $this->input('email', '')),
            'phone' => trim((string) $this->input('phone', '')),
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'recipient_user_id' => ['required', 'integer', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:190'],
            'priority' => ['required', 'string', Rule::in(array_keys(ContactMessage::PRIORITY_OPTIONS))],
            'message' => ['required', 'string', 'max:5000'],
        ];

        if ($this->isMemberSubmission()) {
            return $rules;
        }

        $selectedChannels = $this->input('contact_channels', []);
        $wantsEmail = in_array(ContactMessage::CONTACT_CHANNEL_EMAIL, $selectedChannels, true);
        $wantsPhone = in_array(ContactMessage::CONTACT_CHANNEL_PHONE, $selectedChannels, true);

        return array_merge($rules, [
            'name' => ['required', 'string', 'max:100'],
            'surname' => ['required', 'string', 'max:100'],
            'contact_channels' => ['required', 'array', 'min:1'],
            'contact_channels.*' => ['string', Rule::in(array_keys(ContactMessage::CONTACT_CHANNEL_OPTIONS))],
            'email' => [$wantsEmail ? 'required' : 'nullable', 'email', 'max:190'],
            'phone' => [$wantsPhone ? 'required' : 'nullable', 'string', 'max:50'],
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $recipientId = $this->input('recipient_user_id');

            if (!$recipientId) {
                return;
            }

            $recipientExists = User::query()
                ->adminAccessible()
                ->whereKey($recipientId)
                ->exists();

            if (!$recipientExists) {
                $validator->errors()->add('recipient_user_id', 'Mesaj göndereceğiniz kullanıcı bulunamadı.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'recipient_user_id' => 'alıcı kullanıcı',
            'name' => 'ad',
            'surname' => 'soyad',
            'email' => 'e-posta',
            'phone' => 'telefon',
            'contact_channels' => 'iletişim tercihi',
            'subject' => 'konu',
            'priority' => 'öncelik',
            'message' => 'mesaj',
        ];
    }

    public function messages(): array
    {
        return [
            'contact_channels.required' => 'Lütfen en az bir iletişim tercihi seçin.',
            'contact_channels.min' => 'Lütfen en az bir iletişim tercihi seçin.',
        ];
    }
}
