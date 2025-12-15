<?php

namespace App\Http\Controllers\Admin\BlogPost;

use App\Http\Controllers\Controller;
use App\Models\BlogPost\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogPostController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $posts = BlogPost::query()
            ->with('author')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pages.blog.index', compact('posts', 'q'));
    }

    public function create()
    {
        return view('admin.pages.blog.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:190'],
            'slug'           => ['nullable', 'string', 'max:190', 'unique:blog_posts,slug'],
            'content'        => ['nullable', 'string'],
            'featured_image' => ['nullable', 'image', 'max:2048'], // 2MB
            'is_published'   => ['nullable', 'boolean'],
            'published_at'   => ['nullable', 'date'],
        ]);

        // Slug: boşsa title'dan üret, çakışırsa -2, -3...
        $slug = $validated['slug'] ?? Str::slug($validated['title']);
        $slug = $slug !== '' ? $slug : Str::random(8);

        $base = $slug;
        $i = 2;
        while (BlogPost::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        // Görseli public disk altındaki /blog klasörüne at
        $imagePath = null;
        if ($request->hasFile('featured_image')) {
            $imagePath = $request->file('featured_image')->store('blog', 'public');
        }

        $isPublished = (bool) ($validated['is_published'] ?? false);

        $post = BlogPost::create([
            'user_id'        => auth()->id(),
            'title'          => $validated['title'],
            'slug'           => $slug,
            'content'        => $validated['content'] ?? null,
            'featured_image' => $imagePath,
            'is_published'   => $isPublished,
            'published_at'   => $validated['published_at'] ?? ($isPublished ? now() : null),
        ]);

        return redirect()
            ->route('admin.blog.edit', $post)
            ->with('ok', 'Blog yazısı oluşturuldu.');
    }

    public function edit(BlogPost $blog)
    {
        // view içinde $blog kullanıyoruz
        return view('admin.pages.blog.edit', compact('blog'));
    }

    public function update(Request $request, BlogPost $blog)
    {
        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:190'],
            'slug'           => ['required', 'string', 'max:190', Rule::unique('blog_posts', 'slug')->ignore($blog->id)],
            'content'        => ['nullable', 'string'],
            'featured_image' => ['nullable', 'image', 'max:2048'],
            'is_published'   => ['nullable', 'boolean'],
            'published_at'   => ['nullable', 'date'],
        ]);

        // Yeni görsel geldiyse: eskisini sil + yenisini kaydet
        if ($request->hasFile('featured_image')) {
            if ($blog->featured_image) {
                Storage::disk('public')->delete($blog->featured_image);
            }
            $blog->featured_image = $request->file('featured_image')->store('blog', 'public');
        }

        $isPublished = (bool) ($validated['is_published'] ?? false);

        $blog->update([
            'title'        => $validated['title'],
            'slug'         => $validated['slug'],
            'content'      => $validated['content'] ?? null,
            'is_published' => $isPublished,
            'published_at' => $validated['published_at']
                ?? ($isPublished ? ($blog->published_at ?? now()) : null),
        ]);

        // featured_image alanı store kısmında set edildi, update array'ine eklemedik; save et
        if ($request->hasFile('featured_image')) {
            $blog->save();
        }

        return back()->with('ok', 'Blog yazısı güncellendi.');
    }

    public function togglePublish(Request $request, BlogPost $blog)
    {
        $validated = $request->validate([
            'is_published' => ['required', 'boolean'],
        ]);

        $isPublished = (bool) $validated['is_published'];

        $blog->is_published = $isPublished;

        if ($isPublished && !$blog->published_at) {
            $blog->published_at = now();
        }

        if (!$isPublished) {
            // İstersen yayın tarihini koru; ben koruyorum.
            // İlla null olsun dersen: $blog->published_at = null;
        }

        $blog->save();

        return response()->json([
            'ok' => true,
            'is_published' => (bool) $blog->is_published,
            'badge_html' => $blog->is_published
                ? '<span class="badge badge-light-success">Yayında</span>'
                : '<span class="badge badge-light">Taslak</span>',
            'published_at' => optional($blog->published_at)->format('d.m.Y H:i'),
        ]);
    }


    public function destroy(BlogPost $blog)
    {
        if ($blog->featured_image) {
            Storage::disk('public')->delete($blog->featured_image);
        }

        $blog->delete();

        return redirect()
            ->route('admin.blog.index')
            ->with('ok', 'Blog yazısı silindi.');
    }
}
