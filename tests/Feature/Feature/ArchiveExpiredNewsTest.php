<?php

namespace Tests\Feature\Feature;

use App\Enums\NewsStatus;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveExpiredNewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_archives_published_news_when_unpublish_at_is_in_the_past(): void
    {
        $expired = News::factory()->create([
            'status' => NewsStatus::Published,
            'publish_at' => now()->subWeek(),
            'unpublish_at' => now()->subMinute(),
        ]);

        $stillPublished = News::factory()->create([
            'status' => NewsStatus::Published,
            'publish_at' => now()->subWeek(),
            'unpublish_at' => now()->addHour(),
        ]);

        $draft = News::factory()->create([
            'status' => NewsStatus::Draft,
            'unpublish_at' => now()->subDay(),
        ]);

        $count = News::archiveExpiredByUnpublishDate();

        $this->assertSame(1, $count);

        $this->assertSame(NewsStatus::Archived, $expired->fresh()->status);
        $this->assertSame(NewsStatus::Published, $stillPublished->fresh()->status);
        $this->assertSame(NewsStatus::Draft, $draft->fresh()->status);
    }

    public function test_public_api_hides_expired_even_before_archive_runs(): void
    {
        News::factory()->create([
            'status' => NewsStatus::Published,
            'publish_at' => now()->subHour(),
            'unpublish_at' => now()->subMinute(),
        ]);

        $visible = News::query()->publiclyVisible()->count();

        $this->assertSame(0, $visible);
    }
}
