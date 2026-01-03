<?php

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Controller;
use App\Models\Admin\Media\Media;
use App\Support\Audit\AuditEvent;
use App\Support\Audit\AuditWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Profil özet sayfası (index)
     */
    public function index()
    {
        return view('admin.pages.profile.index', [
            'pageTitle' => 'Profil Sayfası',
            'user' => auth()->user(),
        ]);
    }

    /**
     * Profil düzenleme formu
     */
    public function edit()
    {
        return view('admin.pages.profile.edit', [
            'pageTitle' => 'Profil Düzenle',
            'user' => auth()->user(),
        ]);
    }

    /**
     * Profil bilgilerini güncelle
     * (Sen "benim alanlar" dediğin için sadece temel alanları tutuyorum.)
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            // İstersen şifre değişimi:
            'password' => ['nullable', 'string', 'min:8', 'max:190'],
        ]);

        // boş password gelirse dokunma
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = bcrypt($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.profile.edit')
            ->with('success', 'Profil bilgileri güncellendi.');
    }

    /**
     * Avatar güncelle
     * - users.avatar_media_id -> media.id
     * - Eski avatar media kaydını ve dosyasını temizler
     */
    public function updateAvatar(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'avatar' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $file = $request->file('avatar');

        return DB::transaction(function () use ($user, $file) {

            // 1) Eski avatarı temizle (varsa)
            $this->purgeUserAvatarMedia($user);

            // 2) Yeni media oluştur
            $uuid = (string) Str::uuid();
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');

            $disk = 'public'; // senin Storage url() mantığına uyuyor
            $path = "uploads/avatars/{$uuid}.{$ext}";

            Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

            // width/height (image ise)
            $width = null;
            $height = null;
            $mime = $file->getMimeType();

            if (is_string($mime) && str_starts_with($mime, 'image/')) {
                $info = @getimagesize($file->getRealPath());
                if (is_array($info)) {
                    $width = $info[0] ?? null;
                    $height = $info[1] ?? null;
                }
            }

            $media = Media::create([
                'uuid' => $uuid,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mime,
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'title' => null,
                'alt' => null,
                'meta' => null,
            ]);

            // 3) User’a bağla
            $user->forceFill([
                'avatar_media_id' => $media->id,
                'avatar' => null, // eski string path alanını boş bırak (istersen kaldır)
            ])->save();

            return back()->with('success', 'Avatar güncellendi.');
        });
    }

    /**
     * Avatar kaldır (media + dosya temizlenir)
     */
    public function removeAvatar()
    {
        $user = auth()->user();
        if (!$user) abort(403);

        DB::transaction(function () use ($user) {
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
        });

        return back()->with('success', 'Avatar kaldırıldı.');
    }

    /**
     * Kullanıcının avatar media kaydını + dosyasını siler
     */
    private function purgeUserAvatarMedia($user): void
    {
        if (!$user->avatar_media_id) {
            if (!empty($user->avatar) && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            return;
        }

        $mediaId = (int) $user->avatar_media_id;

        $media = Media::query()->find($mediaId);
        if (!$media) {
            return;
        }

        // mediables üzerinde kullanılıyorsa silme (reuse güvenliği)
        $usageCount = DB::table('mediables')->where('media_id', $mediaId)->count();
        if ($usageCount > 0) {
            return;
        }

        if ($media->disk && $media->path && Storage::disk($media->disk)->exists($media->path)) {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->delete();
    }

}
