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
            ->join('galleries', 'galleries.id', '=', 'galleryables.gallery_id')
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->orderBy('slot')
            ->orderBy('sort_order')
            ->select([
                'galleryables.id as pivot_id',
                'galleryables.gallery_id',
                'galleryables.slot',
                'galleryables.sort_order',
                'galleries.name',
                'galleries.slug',
                'galleries.deleted_at',
            ])
            ->get();

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function attach(Request $request, BlogPost $blogPost): JsonResponse
    {
        $data = $request->validate([
            'gallery_id' => ['required','integer','exists:galleries,id'],
            'slot'       => ['required','string','in:main,sidebar'],
        ]);

        $gallery = Gallery::findOrFail($data['gallery_id']);

        // soft-deleted galeri attach edilmesin
        if ($gallery->trashed()) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Silinmiş galeri bağlanamaz.']], 422);
        }

        $exists = DB::table('galleryables')
            ->where('gallery_id', $gallery->id)
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->where('slot', $data['slot'])
            ->exists();

        if ($exists) return response()->json(['ok' => true]); // idempotent

        $max = (int) DB::table('galleryables')
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->where('slot', $data['slot'])
            ->max('sort_order');

        DB::table('galleryables')->insert([
            'gallery_id' => $gallery->id,
            'galleryable_type' => BlogPost::class,
            'galleryable_id' => $blogPost->id,
            'slot' => $data['slot'],
            'sort_order' => $max + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditEvent::log('blog.gallery.attach', [
            'blog_post_id' => $blogPost->id,
            'gallery_id'   => $gallery->id,
            'slot'         => $data['slot'],
        ]);

        return response()->json(['ok' => true]);
    }

    public function detach(Request $request, BlogPost $blogPost): JsonResponse
    {
        $data = $request->validate([
            'gallery_id' => ['required','integer'],
            'slot'       => ['nullable','string','in:main,sidebar'],
        ]);

        $q = DB::table('galleryables')
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->where('gallery_id', $data['gallery_id']);

        if (!empty($data['slot'])) $q->where('slot', $data['slot']);

        $q->delete();

        AuditEvent::log('blog.gallery.detach', [
            'blog_post_id' => $blogPost->id,
            'gallery_id'   => $data['gallery_id'],
            'slot'         => $data['slot'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function setSlot(Request $request, BlogPost $blogPost): JsonResponse
    {
        $data = $request->validate([
            'gallery_id' => ['required','integer'],
            'from_slot'  => ['required','string','in:main,sidebar'],
            'to_slot'    => ['required','string','in:main,sidebar'],
        ]);

        if ($data['from_slot'] === $data['to_slot']) {
            return response()->json(['ok' => true]);
        }

        $row = DB::table('galleryables')
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->where('gallery_id', $data['gallery_id'])
            ->where('slot', $data['from_slot'])
            ->first();

        if (!$row) return response()->json(['ok' => false], 404);

        $max = (int) DB::table('galleryables')
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->where('slot', $data['to_slot'])
            ->max('sort_order');

        DB::table('galleryables')
            ->where('id', $row->id)
            ->update([
                'slot' => $data['to_slot'],
                'sort_order' => $max + 1,
                'updated_at' => now(),
            ]);

        AuditEvent::log('blog.gallery.slot', [
            'blog_post_id' => $blogPost->id,
            'gallery_id'   => $data['gallery_id'],
            'from'         => $data['from_slot'],
            'to'           => $data['to_slot'],
        ]);

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, BlogPost $blogPost): JsonResponse
    {
        $data = $request->validate([
            'slot' => ['required','string','in:main,sidebar'],
            'gallery_ids' => ['required','array','min:1'],
            'gallery_ids.*' => ['integer'],
        ]);

        $slot = $data['slot'];
        $ids  = $data['gallery_ids'];

        $rows = DB::table('galleryables')
            ->where('galleryable_type', BlogPost::class)
            ->where('galleryable_id', $blogPost->id)
            ->where('slot', $slot)
            ->whereIn('gallery_id', $ids)
            ->get()
            ->keyBy('gallery_id');

        $order = 0;
        foreach ($ids as $gid) {
            if (!isset($rows[$gid])) continue;
            $order++;
            DB::table('galleryables')->where('id', $rows[$gid]->id)->update([
                'sort_order' => $order,
                'updated_at' => now(),
            ]);
        }

        AuditEvent::log('blog.gallery.reorder', [
            'blog_post_id' => $blogPost->id,
            'slot'         => $slot,
            'gallery_ids'  => $ids,
        ]);

        return response()->json(['ok' => true]);
    }
}
