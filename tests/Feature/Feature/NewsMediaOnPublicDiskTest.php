<?php

namespace Tests\Feature\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsMediaOnPublicDiskTest extends TestCase
{
    use RefreshDatabase;

    public function test_cover_media_file_exists_on_public_disk(): void
    {
        Storage::fake('public');

        $news = News::factory()->create();

        $tmp = tempnam(sys_get_temp_dir(), 'cover');
        $this->assertNotFalse($tmp);
        file_put_contents($tmp, str_repeat("\xFF", 64));

        $news->addMedia($tmp)->toMediaCollection('cover_image', 'public');

        $media = $news->getFirstMedia('cover_image');
        $this->assertNotNull($media);
        $this->assertSame('public', $media->disk);

        $relativePath = $media->id.'/'.$media->file_name;
        Storage::disk('public')->assertExists($relativePath);

        $url = $media->getUrl();
        $this->assertStringContainsString('/storage/', $url);
    }
}
