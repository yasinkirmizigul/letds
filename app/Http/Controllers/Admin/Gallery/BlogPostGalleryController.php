<?php

namespace App\Http\Controllers\Admin\Gallery;

use App\Http\Controllers\Controller;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Gallery\Gallery;
use App\Support\Audit\AuditEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlogPostGalleryController extends Controller
{
    public function index(BlogPost $blogPost): JsonResponse
    {
        $rows = DB::table('galleryables')
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->orderBy('slot')
            ->orderBy('sort_order')
            ->get(['id','gallery_id','slot','sort_order']);

        $galleryIds = $rows->pluck('gallery_id')->unique()->values()->all();
        $galleries = Gallery::query()->whereIn('id', $galleryIds)->get()->keyBy('id');

        $data = $rows->map(function ($r) use ($galleries) {
            $g = $galleries->get($r->gallery_id);

            return [
                'pivot_id' => (int) $r->id,
                'gallery_id' => (int) $r->gallery_id,
                'slot' => (string) $r->slot,
                'sort_order' => (int) $r->sort_order,
                'gallery' => $g ? [
                    'id' => $g->id,
                    'name' => $g->name,
                    'slug' => $g->slug,
                    'description' => $g->description,
                ] : null,
            ];
        })->values();

        return response()->json(['ok' => true, 'data' => $data]);
    }

    public function attach(Request $request, BlogPost $blogPost): JsonResponse
    {
        $data = $request->validate([
            'gallery_id' => ['required','integer','exists:galleries,id'],
            'slot' => ['nullable','string','max:30'], // main/sidebar
        ]);

        $slot = $data['slot'] ?: 'main';

        // aynı galeri aynı slota zaten bağlı mı?
        $exists = DB::table('galleryables')
            ->where('gallery_id', $data['gallery_id'])
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->where('slot', $slot)
            ->exists();

        if ($exists) {
            return response()->json(['ok' => true, 'already' => true]);
        }

        $max = (int) DB::table('galleryables')
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->where('slot', $slot)
            ->max('sort_order');

        DB::table('galleryables')->insert([
            'gallery_id' => $data['gallery_id'],
            'galleryable_type' => BlogPost::class,
            'galleryable_id' => $blogPost->id,
            'slot' => $slot,
            'sort_order' => $max + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditEvent::log('blog.gallery.attach', [
            'blog_post_id' => $blogPost->id,
            'gallery_id' => (int) $data['gallery_id'],
            'slot' => $slot,
        ]);

        return response()->json(['ok' => true]);
    }

    public function detach(Request $request, BlogPost $blogPost): JsonResponse
    {
        $data = $request->validate([
            'gallery_id' => ['required','integer'],
            'slot' => ['nullable','string','max:30'],
        ]);

        $slot = $data['slot'] ?: 'main';

        DB::table('galleryables')
            ->where('gallery_id', $data['gallery_id'])
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->where('slot', $slot)
            ->delete();

        AuditEvent::log('blog.gallery.detach', [
            'blog_post_id' => $blogPost->id,
            'gallery_id' => (int) $data['gallery_id'],
            'slot' => $slot,
        ]);

        return response()->json(['ok' => true]);
    }

    public function setSlot(Request $request, BlogPost $blogPost): JsonResponse
    {
        $data = $request->validate([
            'gallery_id' => ['required','integer','exists:galleries,id'],
            'from_slot' => ['required','string','max:30'],
            'to_slot' => ['required','string','max:30'],
        ]);

        if ($data['from_slot'] === $data['to_slot']) {
            return response()->json(['ok' => true]);
        }

        $row = DB::table('galleryables')
            ->where('gallery_id', $data['gallery_id'])
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->where('slot', $data['from_slot'])
            ->first(['id']);

        if (!$row) return response()->json(['ok' => false, 'error' => 'not_found'], 404);

        // hedef slota zaten bağlıysa (unique constraint var) -> önce sil, sonra taşı
        $existsTarget = DB::table('galleryables')
            ->where('gallery_id', $data['gallery_id'])
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->where('slot', $data['to_slot'])
            ->exists();

        if ($existsTarget) {
            DB::table('galleryables')->where('id', $row->id)->delete();
            return response()->json(['ok' => true, 'merged' => true]);
        }

        $max = (int) DB::table('galleryables')
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->where('slot', $data['to_slot'])
            ->max('sort_order');

        DB::table('galleryables')->where('id', $row->id)->update([
            'slot' => $data['to_slot'],
            'sort_order' => $max + 1,
            'updated_at' => now(),
        ]);

        AuditEvent::log('blog.gallery.slot', [
            'blog_post_id' => $blogPost->id,
            'gallery_id' => (int) $data['gallery_id'],
            'from' => $data['from_slot'],
            'to' => $data['to_slot'],
        ]);

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, BlogPost $blogPost): JsonResponse
    {
        $data = $request->validate([
            'slot' => ['required','string','max:30'],
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer'],
        ]);

        $slot = $data['slot'];
        $ids = $data['ids'];

        // sadece bu blog + slot içindekileri sıralayacağız
        $existing = DB::table('galleryables')
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->where('slot', $slot)
            ->whereIn('gallery_id', $ids)
            ->pluck('gallery_id')
            ->all();

        $existingSet = array_flip($existing);

        $order = 0;
        foreach ($ids as $gid) {
            if (!isset($existingSet[$gid])) continue;
            $order++;

            DB::table('galleryables')
                ->where('galleryable_type', BlogPost::class)
                ->where('galleryable_id', $blogPost->id)
                ->where('slot', $slot)
                ->where('gallery_id', $gid)
                ->update([
                    'sort_order' => $order,
                    'updated_at' => now(),
                ]);
        }

        AuditEvent::log('blog.gallery.reorder', [
            'blog_post_id' => $blogPost->id,
            'slot' => $slot,
            'gallery_ids' => $ids,
        ]);

        return response()->json(['ok' => true]);
    }
}
