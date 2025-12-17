<?php

namespace App\Http\Controllers\Admin\BlogPost;

use App\Http\Controllers\Controller;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPostController extends Controller
{
    public function index()
    {
        $posts = BlogPost::query()
            ->with('categories:id,name,slug')
            ->latest('id')
            ->paginate(15);

        return view('admin.pages.blog.index', [
            'pageTitle' => 'Blog'
        ], compact('posts'));
    }

    public function create()
    {
        $categories = Category::query()->orderBy('name')->get(['id','name']);
        return view('admin.pages.blog.create', [
            'pageTitle' => 'Yazı Oluştur'
        ], compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        // slug backend garanti
        $data['slug'] = $this->ensureSlug($data['slug'] ?? null, $data['title']);

        // image upload
        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('blog', 'public');
            $data['featured_image_path'] = $path;
        }

        // publish fields
        $data['is_published'] = (bool)($data['is_published'] ?? false);
        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        if (!$data['is_published']) {
            $data['published_at'] = null;
        }

        $post = BlogPost::create($data);

        $post->categories()->sync($data['category_ids'] ?? []);

        return redirect()->route('admin.blog.index')
            ->with('success', 'Blog yazısı oluşturuldu.');
    }

    public function edit(BlogPost $blogPost)
    {
        abort_unless($blogPost->exists, 404);

        $blogPost->load('categories:id');
        $categories = Category::query()->orderBy('name')->get(['id','name']);

        return view('admin.pages.blog.edit', [
            'pageTitle'  => 'Yazı Düzenle',
            'blogPost'   => $blogPost,
            'categories' => $categories,
        ]);
    }
    public function update(Request $request, BlogPost $blogPost)
    {
        $data = $this->validated($request, isUpdate: true);

        $data['slug'] = $this->ensureSlug($data['slug'] ?? null, $data['title'], $blogPost->id);

        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('blog', 'public');
            $data['featured_image_path'] = $path;
        }

        $data['is_published'] = (bool)($data['is_published'] ?? false);
        if ($data['is_published'] && !$blogPost->published_at) {
            $data['published_at'] = now();
        }
        if (!$data['is_published']) {
            $data['published_at'] = null;
        }

        $blogPost->update($data);
        $blogPost->categories()->sync($data['category_ids'] ?? []);

        return redirect()->route('admin.blog.index')
            ->with('success', 'Blog yazısı güncellendi.');
    }

    public function togglePublish(Request $request, BlogPost $blogPost)
    {
        $validated = $request->validate([
            'is_published' => ['required', 'boolean'],
        ]);

        $isPublished = (bool) $validated['is_published'];

        $blogPost->is_published = $isPublished;

        if ($isPublished && !$blogPost->published_at) {
            $blogPost->published_at = now();
        }

        // isPublished false ise published_at'ı koruyorsun, okay.

        $blogPost->save(); // artık UPDATE olur

        return response()->json([
            'ok' => true,
            'is_published' => (bool) $blogPost->is_published,
            'badge_html' => $blogPost->is_published
                ? '<span class="badge badge-light-success">Yayında</span>'
                : '<span class="badge badge-light">Taslak</span>',
            'published_at' => optional($blogPost->published_at)->format('d.m.Y H:i'),
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

    private function validated(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'title' => ['required','string','max:255'],
            'slug' => ['nullable','string','max:255'],
            'content' => ['nullable','string'],

            'meta_keywords' => ['nullable','string','max:500'],
            'meta_description' => ['nullable','string','max:255'],

            'category_ids' => ['nullable','array'],
            'category_ids.*' => ['integer','exists:categories,id'],

            'featured_image' => ['nullable','image','max:4096'],

            'is_published' => ['nullable','boolean'],
        ]);
    }

    private function ensureSlug(?string $slug, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $title);
        if ($base === '') $base = 'post';

        $candidate = $base;
        $i = 2;

        while (BlogPost::query()
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()
        ) {
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }
}
