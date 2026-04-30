<?php

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Controller;
use App\Models\Admin\Media\Media;
use App\Models\Admin\User\User;
use App\Support\Audit\AuditWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        return $this->renderProfile($request->user(), true);
    }

    public function show(Request $request, User $user)
    {
        $viewer = $request->user();

        abort_unless($viewer && ($viewer->is($user) || $viewer->isSuperAdmin()), 403);

        return $this->renderProfile($user, $viewer->is($user));
    }

    public function edit(Request $request)
    {
        $user = $request->user()->loadMissing(['avatarMedia', 'roles:id,name,slug,priority']);

        return view('admin.pages.profile.edit', [
            'pageTitle' => 'Profil Düzenle',
            'user' => $user,
            'blankAvatarUrl' => asset('assets/media/blank.png'),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $this->normalizeUrlInputs($request, ['website_url', 'linkedin_url']);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'title' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:150'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'linkedin_url' => ['nullable', 'url', 'max:500'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'skills_text' => ['nullable', 'string', 'max:1000'],
            'password' => ['nullable', 'string', 'min:8', 'max:190', 'confirmed'],
        ], [
            'password.string' => 'Yeni şifre metin formatında olmalı.',
            'password.min' => 'Yeni şifre en az :min karakter olmalı.',
            'password.max' => 'Yeni şifre en fazla :max karakter olabilir.',
            'password.confirmed' => 'Şifre tekrarı yeni şifre ile aynı olmalı.',
        ], [
            'password' => 'yeni şifre',
            'password_confirmation' => 'şifre tekrarı',
        ]);

        $emailChanged = $user->email !== $data['email'];

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'title' => $data['title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'location' => $data['location'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            'linkedin_url' => $data['linkedin_url'] ?? null,
            'bio' => $data['bio'] ?? null,
            'skills' => $this->normalizeSkills($data['skills_text'] ?? ''),
        ]);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $passwordChanged = !empty($data['password']);

        if ($passwordChanged) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        AuditWriter::system('profile.updated', [
            'user_id' => $user->id,
            'email_changed' => $emailChanged,
            'password_changed' => $passwordChanged,
        ]);

        if ($passwordChanged) {
            $message = 'Şifreniz başarıyla değiştirildi. Güvenlik için çıkış yapıp tekrar giriş yapmanız gerekiyor.';
            $redirectUrl = route('login');

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'password_changed' => true,
                    'redirect_url' => $redirectUrl,
                ]);
            }

            return redirect()
                ->to($redirectUrl)
                ->with('success', $message);
        }

        return redirect()
            ->route('admin.profile.index')
            ->with('success', 'Profil bilgileri güncellendi.');
    }

    public function updateAvatar(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'avatar_media_id' => [
                'nullable',
                'integer',
                Rule::exists('media', 'id')->where(fn ($query) => $query
                    ->where('mime_type', 'like', 'image/%')
                    ->whereNull('deleted_at')),
            ],
            'avatar_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'avatar_remove' => ['nullable', 'boolean'],
        ]);

        $removeRequested = (bool) ($data['avatar_remove'] ?? false);
        $file = $request->file('avatar_file');
        $mediaId = $data['avatar_media_id'] ?? null;

        if (!$removeRequested && !$file && !$mediaId) {
            throw ValidationException::withMessages([
                'avatar_file' => 'Avatar için bir görsel seçmelisin.',
            ]);
        }

        DB::transaction(function () use ($user, $removeRequested, $file, $mediaId) {
            if ($removeRequested) {
                $this->clearAvatar($user);
                return;
            }

            if ($file) {
                $media = $this->storeAvatarFile($file);
                $this->attachAvatarMedia($user, $media->id);
                return;
            }

            $this->attachAvatarMedia($user, (int) $mediaId);
        });

        return back()->with('success', 'Avatar güncellendi.');
    }

    public function removeAvatar(Request $request)
    {
        DB::transaction(fn () => $this->clearAvatar($request->user()));

        return back()->with('success', 'Avatar kaldırıldı.');
    }

    private function renderProfile(User $user, bool $canEditProfile)
    {
        $user->loadMissing(['avatarMedia', 'roles:id,name,slug,priority']);

        return view('admin.pages.profile.index', [
            'pageTitle' => $canEditProfile ? 'Profil Sayfası' : $user->name . ' Profili',
            'profileUser' => $user,
            'user' => $user,
            'canEditProfile' => $canEditProfile,
        ]);
    }

    private function attachAvatarMedia(User $user, int $mediaId): void
    {
        if ((int) $user->avatar_media_id !== $mediaId) {
            $this->purgeUserAvatarMedia($user);
        }

        $user->forceFill([
            'avatar_media_id' => $mediaId,
            'avatar' => null,
        ])->save();

        AuditWriter::system('profile.avatar.updated', [
            'user_id' => $user->id,
            'avatar_media_id' => $mediaId,
        ]);
    }

    private function clearAvatar(User $user): void
    {
        $oldMediaId = $user->avatar_media_id;
        $oldAvatarPath = $user->avatar;

        $this->purgeUserAvatarMedia($user);

        $user->forceFill([
            'avatar_media_id' => null,
            'avatar' => null,
        ])->save();

        AuditWriter::system('profile.avatar.removed', [
            'user_id' => $user->id,
            'old_avatar_media_id' => $oldMediaId,
            'old_avatar' => $oldAvatarPath,
        ]);
    }

    private function storeAvatarFile($file): Media
    {
        $uuid = (string) Str::uuid();
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $disk = 'public';
        $path = "uploads/avatars/{$uuid}.{$ext}";

        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        $width = null;
        $height = null;
        $mime = $file->getMimeType() ?: $file->getClientMimeType();

        if (is_string($mime) && str_starts_with($mime, 'image/')) {
            $info = @getimagesize($file->getRealPath());
            if (is_array($info)) {
                $width = $info[0] ?? null;
                $height = $info[1] ?? null;
            }
        }

        return Media::create([
            'uuid' => $uuid,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $this->sanitizeOriginalName($file->getClientOriginalName()),
            'mime_type' => $mime,
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'title' => null,
            'alt' => null,
            'meta' => ['source' => 'admin_profile_avatar'],
        ]);
    }

    private function purgeUserAvatarMedia(User $user): void
    {
        if (!$user->avatar_media_id) {
            if (!empty($user->avatar)
                && Str::startsWith($user->avatar, 'uploads/avatars/')
                && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            return;
        }

        $media = Media::query()->find((int) $user->avatar_media_id);
        if (!$media) {
            return;
        }

        $isSharedByAnotherUser = User::query()
            ->whereKeyNot($user->id)
            ->where('avatar_media_id', $media->id)
            ->exists();

        $isUsedByContent = DB::table('mediables')->where('media_id', $media->id)->exists();
        $isAvatarUpload = Str::startsWith((string) $media->path, 'uploads/avatars/');

        if ($isSharedByAnotherUser || $isUsedByContent || !$isAvatarUpload) {
            return;
        }

        if ($media->disk && $media->path && Storage::disk($media->disk)->exists($media->path)) {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->delete();
    }

    private function normalizeSkills(string $skills): ?array
    {
        $normalized = collect(preg_split('/[,;\r\n]+/u', $skills) ?: [])
            ->map(fn ($skill) => trim((string) $skill))
            ->filter()
            ->map(fn ($skill) => Str::limit($skill, 60, ''))
            ->unique(fn ($skill) => mb_strtolower($skill))
            ->take(30)
            ->values()
            ->all();

        return $normalized === [] ? null : $normalized;
    }

    private function normalizeUrlInputs(Request $request, array $fields): void
    {
        $normalized = [];

        foreach ($fields as $field) {
            $value = trim((string) $request->input($field, ''));

            if ($value !== '' && !Str::startsWith($value, ['http://', 'https://'])) {
                $value = 'https://' . $value;
            }

            $normalized[$field] = $value !== '' ? $value : null;
        }

        $request->merge($normalized);
    }

    private function sanitizeOriginalName(string $name): string
    {
        $name = basename(trim($name));
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?: 'dosya';
        $name = trim($name, '.-');

        return $name !== '' ? $name : 'dosya';
    }
}
