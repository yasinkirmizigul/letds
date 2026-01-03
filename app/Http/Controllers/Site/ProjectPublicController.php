<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Admin\Media\Media;
use App\Models\Admin\Project\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProjectPublicController extends Controller
{
    public function show(Request $request, string $slug)
    {
        // Sadece ACTIVE yayınla (draft/archived public'te görünmesin)
        $project = Project::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        // Featured image: mediables(collection=featured) üzerinden
        $featuredMedia = $this->getFeaturedMedia($project);

        // Galleries: galleryables(slot + sort_order) üzerinden
        $galleries = $this->getProjectGalleries($project->id);

        // Main/Sidebar ayır
        $mainGalleries = array_values(array_filter($galleries, fn($g) => $g['slot'] === 'main'));
        $sidebarGalleries = array_values(array_filter($galleries, fn($g) => $g['slot'] === 'sidebar'));

        // (Opsiyonel) kategori isimleri — ilişkine güvenmeyip pivot'tan çekiyorum
        $categories = $this->getProjectCategories($project->id);

        // SEO: meta_title/meta_description/meta_keywords varsa onları kullan
        $seo = [
            'title' => $project->meta_title ?: $project->title,
            'description' => $project->meta_description ?: null,
            'keywords' => $project->meta_keywords ?: null,
            'canonical' => url('/projects/' . $project->slug),
            'og_image' => $featuredMedia['url'] ?? null,
        ];

        return view('site.projects.show', [
            'project' => $project,
            'seo' => $seo,
            'featuredMedia' => $featuredMedia,
            'categories' => $categories,
            'mainGalleries' => $mainGalleries,
            'sidebarGalleries' => $sidebarGalleries,
        ]);
    }

    private function getFeaturedMedia(Project $project): ?array
    {
        $mediaId = DB::table('mediables')
            ->where('mediable_type', Project::class)
            ->where('mediable_id', $project->id)
            ->where('collection', 'featured')
            ->orderBy('order')
            ->value('media_id');

        if (!$mediaId) return null;

        /** @var Media|null $m */
        $m = Media::query()->find($mediaId);
        if (!$m) return null;

        $path = trim($m->directory, '/') . '/' . $m->filename;
        $url = Storage::disk($m->disk ?: 'public')->url($path);

        return [
            'id' => (int) $m->id,
            'url' => $url,
            'mime' => $m->mime_type,
            'original_name' => $m->original_name,
        ];
    }

    private function getProjectGalleries(int $projectId): array
    {
        // galleries + pivot (slot, sort_order)
        $rows = DB::table('galleryables as ga')
            ->join('galleries as g', 'g.id', '=', 'ga.gallery_id')
            ->where('ga.galleryable_type', Project::class)
            ->where('ga.galleryable_id', $projectId)
            ->orderBy('ga.slot')
            ->orderBy('ga.sort_order')
            ->get([
                'g.id',
                'g.name',
                'g.slug',
                'g.description',
                'ga.slot',
                'ga.sort_order',
            ]);

        return $rows->map(fn($r) => [
            'id' => (int) $r->id,
            'name' => (string) $r->name,
            'slug' => (string) $r->slug,
            'description' => $r->description,
            'slot' => (string) $r->slot,
            'sort_order' => (int) $r->sort_order,
        ])->all();
    }

    private function getProjectCategories(int $projectId): array
    {
        // categorizables polymorphic: categorizable_type + categorizable_id
        // Blog ile aynı mantık
        $rows = DB::table('categorizables as cz')
            ->join('categories as c', 'c.id', '=', 'cz.category_id')
            ->where('cz.categorizable_type', Project::class)
            ->where('cz.categorizable_id', $projectId)
            ->whereNull('c.deleted_at')
            ->orderBy('c.name')
            ->get(['c.id', 'c.name', 'c.slug']);

        return $rows->map(fn($r) => [
            'id' => (int) $r->id,
            'name' => (string) $r->name,
            'slug' => (string) ($r->slug ?? ''),
        ])->all();
    }
}
