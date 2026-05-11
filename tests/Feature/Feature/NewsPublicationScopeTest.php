<?php

namespace Tests\Feature\Feature;

use App\Enums\NewsStatus;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsPublicationScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_scope_returns_only_currently_published_news(): void
    {
        News::factory()->create(['status' => NewsStatus::Draft]);
        News::factory()->create(['status' => NewsStatus::Published, 'publish_at' => now()->subHour(), 'unpublish_at' => now()->addHour()]);
        News::factory()->create(['status' => NewsStatus::Published, 'publish_at' => now()->addHour()]);

        $news = News::query()->publiclyVisible()->get();

        $this->assertCount(1, $news);
    }
}
