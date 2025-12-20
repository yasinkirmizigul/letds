<?php

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Profil ana sayfası
     */
    public function index()
    {
        return view('admin.pages.profile.index', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Profil düzenleme formu
     */
    public function edit()
    {
        return view('admin.pages.profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Profil bilgilerini güncelle
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'surname' => ['nullable', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'company' => ['nullable', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:150'],
        ]);

        $user->update($data);

        return redirect()
            ->route('admin.profile.index')
            ->with('success', 'Profil bilgileri güncellendi.');
    }

    /**
     * Avatar güncelle
     */
    public function updateAvatar(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // Eski avatarı sil (varsa)
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update([
            'avatar' => $path,
        ]);

        return back()->with('success', 'Avatar güncellendi.');
    }
}
