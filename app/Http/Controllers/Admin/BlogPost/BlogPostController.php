<?php

namespace App\Http\Controllers\Admin\BlogPost;

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Controller;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use App\Support\CategoryTree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPostController extends Controller
{

    public function index(Request $request)
    {
        // ✅ q
        $q = trim((string) $request->query('q', ''));

        // ✅ perPage (admin UX) - güvenli limit
        $perPage = (int) $request->query('perpage', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        // ✅ category_ids sanitize (int + distinct + >0)
        $raw = $request->query('category_ids', []);
        $categoryIds = is_array($raw) ? $raw : [$raw];

        $categoryIds = array_values(array_unique(array_filter(array_map(function ($v) {
            $i = (int) $v;
            return $i > 0 ? $i : null;
        }, $categoryIds))));

        // ✅ category options (tek kaynak)
        $categoryOptions = CategoryTree::options();

        // ✅ posts query
        $posts = BlogPost::query()
            ->with([
                'author:id,name',
                'categories:id,name,slug,parent_id'
            ])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                });
            })
            // OR filtre: seçilen kategorilerden herhangi biri varsa gelsin
            ->when(!empty($categoryIds), function ($query) use ($categoryIds) {
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.pages.blog.index', [
            'posts' => $posts,
            'q' => $q,
            'perPage' => $perPage,
            'categoryOptions' => $categoryOptions,
            'selectedCategoryIds' => $categoryIds,
            'pageTitle' => 'Blog',
        ]);
    }


    public function create()
    {
        $categories = Category::query()->orderBy('name')->get(['id','name','parent_id']);
        $categoryOptions = CategoryTree::options();

        return view('admin.pages.blog.create', compact('categories', 'categoryOptions'));
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

        $post->categories()->sync(array_map('intval', $request->input('category_ids', [])));

        return redirect()->route('admin.blog.index')
            ->with('success', 'Blog yazısı oluşturuldu.');
    }

    public function edit(BlogPost $blogPost)
    {
        abort_unless($blogPost->exists, 404);

        $blogPost->load('categories:id');

        $selectedCategoryIds = $blogPost->categories->pluck('id')->all();

        // DÜZ LİSTE (multi-select için ideal)
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id','name']);

        return view('admin.pages.blog.edit', compact(
            'blogPost',
            'categories',
            'selectedCategoryIds'
        ));
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
        $blogPost->categories()->sync(array_map('intval', $request->input('category_ids', [])));


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

    public function destroy(BlogPost $blogPost)
    {
        if ($blogPost->featured_image_path) {
            Storage::disk('public')->delete($blogPost->featured_image_path);
        }

        $blogPost->categories()->detach(); // isteğe bağlı ama temiz
        $blogPost->delete();

        return redirect()
            ->route('admin.blog.index')
            ->with('success', 'Blog yazısı silindi.');
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
