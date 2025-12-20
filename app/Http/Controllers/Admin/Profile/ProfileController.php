<?php

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Controller;
use App\Models\Admin\Media\Media;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.pages.profile.edit');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ]);

        $u = $request->user();

        // İstersen sadece image kabul ettir:
        if ($request->filled('avatar_media_id')) {
            $m = Media::findOrFail($request->integer('avatar_media_id'));
            if (!str_starts_with((string)$m->mime_type, 'image/')) {
                return back()->with('error', 'Avatar sadece görsel olabilir.');
            }
        }

        $u->avatar_media_id = $request->filled('avatar_media_id')
            ? $request->integer('avatar_media_id')
            : null;

        $u->save();

        return back()->with('success', 'Avatar güncellendi.');
    }
}
