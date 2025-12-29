<?php

namespace App\Http\Controllers\Admin\Gallery;

use App\Http\Controllers\Controller;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Gallery\Gallery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Audit\AuditEvent;

class BlogPostGalleryController extends Controller
{
    public function index(BlogPost $blogPost): JsonResponse
    {
        $rows = DB::table('galleryables')
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->orderBy('slot')
            ->orderBy('sort_order')
            ->get(['id', 'gallery_id', 'slot', 'sort_order']);

        $galleryIds = $rows->pluck('gallery_id')->unique()->values()->all();

        $galleries = Gallery::query()
            ->whereIn('id', $galleryIds)
            ->get(['id', 'name', 'slug', 'description'])
            ->keyBy('id');

        $data = $rows->map(function ($r) use ($galleries) {
            $g = $galleries->get($r->gallery_id);

            return [
                'pivot_id' => (int) $r->id,
                'gallery_id' => (int) $r->gallery_id,
                'slot' => (string) $r->slot,
                'sort_order' => (int) $r->sort_order,
                'gallery' => $g ? [
                    'id' => (int) $g->id,
                    'name' => (string) $g->name,
                    'slug' => (string) $g->slug,
                    'description' => $g->description,
                ] : null,
            ];
        })->values();

        return response()->json(['ok' => true, 'data' => $data]);
    }

    public function attach(Request $request, BlogPost $blogPost): JsonResponse
    {
        $data = $request->validate([
            'gallery_id' => ['required', 'integer', 'exists:galleries,id'],
            'slot' => ['nullable', 'string', 'max:30'],
        ]);

        $slot = $data['slot'] ?: 'main';

        $existing = DB::table('galleryables')
            ->where('gallery_id', $data['gallery_id'])
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->first(['id', 'slot']);

        if ($existing) {
            // varsa sadece slot değiştirip en sona at
            if ($existing->slot !== $slot) {
                $max = (int) DB::table('galleryables')
                    ->where('galleryable_type', BlogPost::class)
                    ->where('galleryable_id', $blogPost->id)
                    ->where('slot', $slot)
                    ->max('sort_order');

                DB::table('galleryables')->where('id', $existing->id)->update([
                    'slot' => $slot,
                    'sort_order' => $max + 1,
                    'updated_at' => now(),
                ]);

                AuditEvent::log('blog.gallery.attach', [
                    'blog_post_id' => $blogPost->id,
                    'gallery_id' => (int) $data['gallery_id'],
                    'slot' => $slot,
                    'moved_from' => (string) $existing->slot,
                ]);
            }

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
            'gallery_id' => ['required', 'integer'],
        ]);

        $deleted = DB::table('galleryables')
            ->where('gallery_id', $data['gallery_id'])
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->delete();

        AuditEvent::log('blog.gallery.detach', [
            'blog_post_id' => $blogPost->id,
            'gallery_id' => (int) $data['gallery_id'],
            'deleted' => (int) $deleted,
        ]);

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, BlogPost $blogPost): JsonResponse
    {
        $data = $request->validate([
            'main_ids' => ['present', 'array'],
            'main_ids.*' => ['integer'],
            'sidebar_ids' => ['present', 'array'],
            'sidebar_ids.*' => ['integer'],
        ]);

        $mainIds = array_values(array_unique($data['main_ids'] ?? []));
        $sideIds = array_values(array_unique($data['sidebar_ids'] ?? []));

        $dup = array_values(array_intersect($mainIds, $sideIds));
        if (!empty($dup)) {
            return response()->json([
                'ok' => false,
                'message' => 'Aynı galeri hem main hem sidebar içinde olamaz.',
                'dup' => $dup,
            ], 422);
        }

        $attached = DB::table('galleryables')
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->pluck('gallery_id')
            ->all();

        $attachedSet = array_flip($attached);
        $invalid = [];
        foreach (array_merge($mainIds, $sideIds) as $gid) {
            if (!isset($attachedSet[$gid])) $invalid[] = $gid;
        }

        if (!empty($invalid)) {
            return response()->json([
                'ok' => false,
                'message' => 'Blog’a bağlı olmayan galeri ID geldi.',
                'invalid' => array_values(array_unique($invalid)),
            ], 422);
        }

        DB::transaction(function () use ($blogPost, $mainIds, $sideIds) {
            $now = now();

            $order = 0;
            foreach ($mainIds as $gid) {
                $order++;
                DB::table('galleryables')
                    ->where('galleryable_type', BlogPost::class)
                    ->where('galleryable_id', $blogPost->id)
                    ->where('gallery_id', $gid)
                    ->update([
                        'slot' => 'main',
                        'sort_order' => $order,
                        'updated_at' => $now,
                    ]);
            }

            $order = 0;
            foreach ($sideIds as $gid) {
                $order++;
                DB::table('galleryables')
                    ->where('galleryable_type', BlogPost::class)
                    ->where('galleryable_id', $blogPost->id)
                    ->where('gallery_id', $gid)
                    ->update([
                        'slot' => 'sidebar',
                        'sort_order' => $order,
                        'updated_at' => $now,
                    ]);
            }
        });

        AuditEvent::log('blog.gallery.reorder', [
            'blog_post_id' => $blogPost->id,
            'main_ids' => $mainIds,
            'sidebar_ids' => $sideIds,
        ]);

        return response()->json(['ok' => true]);
    }
}
