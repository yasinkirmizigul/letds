<?php

namespace Tests\Feature;

use App\Services\Admin\Media\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaServiceWebpTest extends TestCase
{
    use RefreshDatabase;

    public function test_jpeg_upload_is_stored_only_as_required_webp_variants(): void
    {
        Storage::fake('public');

        $media = app(MediaService::class)->store(
            UploadedFile::fake()->image('analysis-background.jpg', 1200, 800)
        );

        $this->assertSame('image/webp', $media->mime_type);
        $this->assertStringEndsWith('.webp', $media->path);
        $this->assertSame($media->path, $media->variants['original']);
        $this->assertSame($media->path, $media->variants['optimized']);
        $this->assertStringEndsWith('_thumb.webp', $media->variants['thumb']);
        $this->assertSame(1200, $media->width);
        $this->assertSame(800, $media->height);
        $this->assertGreaterThan(0, $media->size);

        Storage::disk('public')->assertExists($media->path);
        Storage::disk('public')->assertExists($media->variants['thumb']);
        $this->assertCount(2, array_unique($media->variants));
        $this->assertCount(2, Storage::disk('public')->allFiles());
    }
}
