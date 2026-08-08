<?php

namespace App\Http\Controllers\Site\Gallery;

use App\Http\Controllers\Controller;
use App\Models\Admin\Gallery\Gallery;
use App\Support\Site\SiteLocalization;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $galleries = Gallery::query()
            ->publiclyVisible()
            ->whereHas('items.media', fn (Builder $query) => $query->where('mime_type', 'like', 'image/%'))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->with(['coverItem.media'])
            ->withCount('items')
            ->latest('published_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('site.galleries.index', [
            'pageTitle' => 'Galeri',
            'metaDescription' => 'Projelerden, süreçlerden ve tamamlanan çalışmalardan seçkiler.',
            'galleries' => $galleries,
            'search' => $search,
        ]);
    }

    public function show(string $slug): View
    {
        $gallery = Gallery::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->whereHas('items.media', fn (Builder $query) => $query->where('mime_type', 'like', 'image/%'))
            ->with(['items.media'])
            ->firstOrFail();

        $relatedGalleries = Gallery::query()
            ->publiclyVisible()
            ->whereKeyNot($gallery->getKey())
            ->whereHas('items.media', fn (Builder $query) => $query->where('mime_type', 'like', 'image/%'))
            ->with(['coverItem.media'])
            ->withCount('items')
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('site.galleries.show', [
            'pageTitle' => $gallery->name,
            'metaDescription' => $gallery->description,
            'canonicalUrl' => SiteLocalization::localizedRoute('site.galleries.show', [
                'slug' => $gallery->slug,
            ]),
            'gallery' => $gallery,
            'relatedGalleries' => $relatedGalleries,
        ]);
    }
}
