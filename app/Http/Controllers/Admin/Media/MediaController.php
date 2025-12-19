<?php
namespace App\Http\Controllers\Admin\Media;

use App\Http\Controllers\Controller;
use App\Models\Admin\Media\Media;
use App\Services\Admin\Media\MediaService;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(private readonly MediaService $mediaService) {}

    public function index()
    {
        return view('admin.pages.media.index');
    }

    public function list(Request $request)
    {
        $q = Media::query()->latest('id');

        if ($type = $request->string('type')->toString()) {
            if ($type === 'image') $q->where('mime_type', 'like', 'image/%');
            if ($type === 'video') $q->where('mime_type', 'like', 'video/%');
            if ($type === 'doc')   $q->where('mime_type', 'not like', 'image/%')->where('mime_type', 'not like', 'video/%');
        }

        if ($search = trim((string)$request->get('q'))) {
            $q->where(function ($w) use ($search) {
                $w->where('original_name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('alt', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->get('perpage', 24);
        $rows = $q->paginate($perPage);

        return response()->json([
            'data' => $rows->getCollection()->map(fn (Media $m) => [
                'id' => $m->id,
                'uuid' => $m->uuid,
                'url' => $m->url(),
                'original_name' => $m->original_name,
                'mime_type' => $m->mime_type,
                'size' => $m->size,
                'width' => $m->width,
                'height' => $m->height,
                'is_image' => $m->isImage(),
                'created_at' => $m->created_at?->toDateTimeString(),
            ]),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'total' => $rows->total(),
                'per_page' => $rows->perPage(),
            ],
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required','file','max:20480'], // 20MB
            'title' => ['nullable','string','max:255'],
            'alt' => ['nullable','string','max:255'],
        ]);

        $media = $this->mediaService->store($request->file('file'), $request->only(['title','alt']));

        return response()->json([
            'ok' => true,
            'media' => [
                'id' => $media->id,
                'url' => $media->url(),
                'original_name' => $media->original_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'is_image' => $media->isImage(),
            ]
        ]);
    }

    public function destroy(Media $media)
    {
        $this->mediaService->delete($media);

        return response()->json(['ok' => true]);
    }
}
