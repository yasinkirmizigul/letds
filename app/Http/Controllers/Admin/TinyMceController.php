<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TinyMceController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'image', 'max:5120'], // 5MB
        ]);

        $path = $request->file('file')->store('blog/content', 'public');

        return response()->json([
            'location' => asset('storage/' . $path), // TinyMCE bunu bekler
        ]);
    }
}
