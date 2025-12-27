<?php

namespace App\Http\Controllers\Admin\Gallery;

use App\Http\Controllers\Controller;
use App\Models\Admin\Gallery\Gallery;
use App\Models\Admin\Gallery\GalleryItem;
use App\Models\Admin\Media\Media;
use App\Support\Audit\AuditEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryItemsController extends Controller
{
    private function mediaPayload(Media $m): array
    {
        return [
            'id'            => $m->id,
            'uuid'          => $m->uuid,
            'url'           => $m->url(),
            'thumb_url'     => $m->thumbUrl(),
            'original_name' => $m->original_name,
            'mime_type'     => $m->mime_type,
            'size'          => (int) $m->size,
            'width'         => $m->width,
            'height'        => $m->height,
            'is_image'      => $m->isImage(),
            'created_at'    => $m->created_at?->toDateTimeString(),
        ];
    }

    public function items(Gallery $gallery): JsonResponse
    {
        $items = $gallery->items()->with('media')->get();

        return response()->json([
            'ok' => true,
            'data' => $items->map(fn (GalleryItem $it) => [
                'id'         => $it->id,
                'sort_order' => (int) $it->sort_order,
                'caption'    => $it->caption,
                'alt'        => $it->alt,
                'link_url'   => $it->link_url,
                'link_target'=> $it->link_target,
                'media'      => $it->media ? $this->mediaPayload($it->media) : null,
            ])->values(),
        ]);
    }

    public function store(Request $request, Gallery $gallery): JsonResponse
    {
        $data = $request->validate([
            'media_ids' => ['required','array','min:1'],
            'media_ids.*' => ['integer','exists:media,id'],
        ]);

        $existing = $gallery->items()->pluck('media_id')->all();

        $max = (int) $gallery->items()->max('sort_order');
        $created = [];

        foreach ($data['media_ids'] as $mid) {
            if (in_array($mid, $existing, true)) continue;

            $max++;

            $created[] = $gallery->items()->create([
                'media_id'    => $mid,
                'sort_order'  => $max,
            ])->id;
        }

        AuditEvent::log('gallery.items.add', [
            'gallery_id' => $gallery->id,
            'item_ids'   => $created,
            'media_ids'  => $data['media_ids'],
        ]);

        return response()->json(['ok' => true, 'created_ids' => $created]);
    }

    public function update(Request $request, Gallery $gallery, GalleryItem $item): JsonResponse
    {
        abort_if($item->gallery_id !== $gallery->id, 404);

        $data = $request->validate([
            'caption' => ['nullable','string','max:255'],
            'alt' => ['nullable','string','max:255'],
            'link_url' => ['nullable','string','max:2048'],
            'link_target' => ['nullable','string','max:20'],
        ]);

        $item->update($data);

        AuditEvent::log('gallery.items.update', [
            'gallery_id' => $gallery->id,
            'item_id'    => $item->id,
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Gallery $gallery, GalleryItem $item): JsonResponse
    {
        abort_if($item->gallery_id !== $gallery->id, 404);

        $itemId = $item->id;
        $item->delete();

        AuditEvent::log('gallery.items.remove', [
            'gallery_id' => $gallery->id,
            'item_id'    => $itemId,
        ]);

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, Gallery $gallery): JsonResponse
    {
        $data = $request->validate([
            'ids'   => ['required','array','min:1'],
            'ids.*' => ['integer'],
        ]);

        $ids = $data['ids'];

        $items = $gallery->items()->whereIn('id', $ids)->get()->keyBy('id');

        $order = 0;
        foreach ($ids as $id) {
            if (!isset($items[$id])) continue;
            $order++;
            $items[$id]->update(['sort_order' => $order]);
        }

        AuditEvent::log('gallery.items.reorder', [
            'gallery_id' => $gallery->id,
            'ids'        => $ids,
        ]);

        return response()->json(['ok' => true]);
    }
}
