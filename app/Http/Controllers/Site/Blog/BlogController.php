<?php

namespace App\Http\Controllers\Site\Blog;

use App\Http\Controllers\Controller;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use App\Support\Site\SiteLocalization;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $locale = SiteLocalization::currentLocale();
        $search = trim($request->string('q')->toString());
        $categorySlug = trim($request->string('category')->toString());

        $featuredPost = null;

        if ($search === '' && $categorySlug === '' && ! $request->integer('page')) {
            $featuredPost = $this->baseQuery()
                ->featured()
                ->latest('featured_at')
                ->latest('published_at')
                ->first();
        }

        $posts = $this->baseQuery()
            ->when($featuredPost, fn (Builder $query) => $query->whereKeyNot($featuredPost->getKey()))
            ->search($search)
            ->when($categorySlug !== '', function (Builder $query) use ($categorySlug, $locale): void {
                $query->whereHas('categories', function (Builder $categoryQuery) use ($categorySlug, $locale): void {
                    $categoryQuery
                        ->where('is_active', true)
                        ->where(function (Builder $slugQuery) use ($categorySlug, $locale): void {
                            $slugQuery
                                ->where('slug', $categorySlug)
                                ->orWhereHas('translations', fn (Builder $translationQuery) => $translationQuery
                                    ->where('locale', $locale)
                                    ->where('slug', $categorySlug));
                        });
                });
            })
            ->latest('published_at')
            ->latest('id')
            ->paginate(9)
            ->appends($request->except('fragment'));

        if ($request->boolean('fragment')) {
            return response()->json([
                'html' => view('site.blog.partials.results', compact('posts'))->render(),
                'total' => $posts->total(),
            ]);
        }

        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('blogPosts', fn (Builder $query) => $query->publiclyVisible())
            ->with('translations')
            ->withCount([
                'blogPosts as published_posts_count' => fn (Builder $query) => $query->publiclyVisible(),
            ])
            ->orderBy('name')
            ->get();

        return view('site.blog.index', [
            'pageTitle' => 'Blog',
            'metaDescription' => 'Güncel yazılar, rehberler ve uzman görüşleri.',
            'posts' => $posts,
            'featuredPost' => $featuredPost,
            'categories' => $categories,
            'search' => $search,
            'categorySlug' => $categorySlug,
        ]);
    }

    public function show(string $slug): View
    {
        $locale = SiteLocalization::currentLocale();
        $post = $this->baseQuery()
            ->where(function (Builder $query) use ($slug, $locale): void {
                $query
                    ->where('slug', $slug)
                    ->orWhereHas('translations', fn (Builder $translationQuery) => $translationQuery
                        ->where('locale', $locale)
                        ->where('slug', $slug));
            })
            ->firstOrFail();

        $categoryIds = $post->categories->pluck('id');
        $relatedPosts = $this->baseQuery()
            ->whereKeyNot($post->getKey())
            ->when($categoryIds->isNotEmpty(), fn (Builder $query) => $query
                ->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery
                    ->whereIn('categories.id', $categoryIds)))
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('site.blog.show', [
            'pageTitle' => $post->localizedValue('meta_title') ?: $post->localizedValue('title'),
            'metaDescription' => $post->localizedValue('meta_description') ?: $post->excerptPreview(160),
            'canonicalUrl' => SiteLocalization::localizedRoute('site.blog.show', [
                'slug' => $post->localizedValue('slug'),
            ], $locale),
            'openGraphType' => 'article',
            'post' => $post,
            'relatedPosts' => $relatedPosts,
        ]);
    }

    private function baseQuery(): Builder
    {
        return BlogPost::query()
            ->publiclyVisible()
            ->with([
                'translations',
                'categories' => fn ($query) => $query->where('is_active', true)->with('translations'),
                'featuredMedia',
                'galleries' => fn ($query) => $query->publiclyVisible(),
                'galleries.items.media',
                'author:id,name',
            ]);
    }
}
