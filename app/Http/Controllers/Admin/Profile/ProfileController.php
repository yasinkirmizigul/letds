<?php

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Controller;
use App\Models\Admin\Media\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index()
    {
        return view('admin.pages.profile.index', [
            'user' => auth()->user(),
        ]);
    }

    public function edit()
    {
        return view('admin.pages.profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($data);

        return redirect()
            ->route('admin.profile.index')
            ->with('success', 'Profil bilgileri güncellendi.');
    }

    /**
     * Avatar güncelle:
     * - file upload (avatar)
     * - veya media_id ile mevcut medyayı avatar yap
     */
    public function updateAvatar(Request $request)
    {
        $user = auth()->user();

        // 1) Media seçimi ile avatar (picker)
        if ($request->filled('media_id')) {
            $media = Media::query()
                ->whereKey($request->input('media_id'))
                ->firstOrFail();

            if (!$media->isImage()) {
                return back()->with('error', 'Seçilen medya bir görsel değil.');
            }

            if (Schema::hasColumn('users', 'avatar_media_id')) {
                $user->update(['avatar_media_id' => $media->id]);
                return back()->with('success', 'Avatar güncellendi (medyadan).');
            }

            // avatar_media_id yoksa mecburen fallback (avatar_url üretmezsen)
            return back()->with('error', 'users.avatar_media_id kolonu yok. Medyadan avatar seçimi için şema güncelle.');
        }

        // 2) File upload ile avatar
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $file = $request->file('avatar');

        // Dosyayı disk'e yaz
        $disk = 'public';
        $path = $file->store('avatars', $disk);

        // Eğer avatar_media_id varsa: media kaydı oluştur, user.avatar_media_id set et
        if (Schema::hasColumn('users', 'avatar_media_id')) {

            // Eski avatar_media_id varsa sadece referansı değiştiriyoruz (dosyayı silmek istersen ayrıca yönet)
            [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

            $media = Media::create([
                'uuid'          => (string) Str::uuid(),
                'disk'          => $disk,
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
                'width'         => $width,
                'height'        => $height,
                'title'         => null,
                'alt'           => null,
                'meta'          => [],
            ]);

            $user->update([
                'avatar_media_id' => $media->id,
            ]);

            return back()->with('success', 'Avatar güncellendi (yükleme).');
        }

        // avatar_media_id yoksa: eski ProfileController mantığına düş
        if (Schema::hasColumn('users', 'avatar')) {
            // Eski avatar path'i sil (varsa)
            if (!empty($user->avatar) && Storage::disk($disk)->exists($user->avatar)) {
                Storage::disk($disk)->delete($user->avatar);
            }

            $user->update(['avatar' => $path]);

            return back()->with('success', 'Avatar güncellendi.');
        }

        // Hiçbiri yoksa: dosyayı da geri al (çöp bırakma)
        Storage::disk($disk)->delete($path);

        return back()->with('error', 'Avatar alanı yok (users.avatar_media_id veya users.avatar). Şemayı güncelle.');
    }

    public function removeAvatar()
    {
        $user = auth()->user();

        // avatar_media_id varsa sadece null yapıyoruz
        if (Schema::hasColumn('users', 'avatar_media_id')) {
            $user->update(['avatar_media_id' => null]);
            return back()->with('success', 'Avatar kaldırıldı.');
        }

        // avatar varsa dosyayı da sil
        if (Schema::hasColumn('users', 'avatar')) {
            $disk = 'public';
            if (!empty($user->avatar) && Storage::disk($disk)->exists($user->avatar)) {
                Storage::disk($disk)->delete($user->avatar);
            }

            $user->update(['avatar' => null]);
            return back()->with('success', 'Avatar kaldırıldı.');
        }

        return back()->with('error', 'Avatar alanı yok. Şemayı güncelle.');
    }
}
