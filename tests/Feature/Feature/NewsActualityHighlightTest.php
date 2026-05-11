<?php

namespace Tests\Feature\Feature;

use App\Enums\NewsStatus;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsActualityHighlightTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_news_can_have_actuality_highlight_after_model_save(): void
    {
        $first = News::factory()->create([
            'status' => NewsStatus::Published,
            'publish_at' => now()->subDay(),
            'is_actuality_highlight' => true,
        ]);
        $second = News::factory()->create([
            'status' => NewsStatus::Published,
            'publish_at' => now()->subDay(),
            'is_actuality_highlight' => false,
        ]);

        $second->is_actuality_highlight = true;
        $second->save();

        $this->assertFalse($first->fresh()->is_actuality_highlight);
        $this->assertTrue($second->fresh()->is_actuality_highlight);
        $this->assertSame(1, News::query()->where('is_actuality_highlight', true)->count());
    }

    public function test_actuality_api_returns_highlighted_public_news(): void
    {
        News::factory()->create([
            'status' => NewsStatus::Published,
            'publish_at' => now()->subDay(),
            'is_actuality_highlight' => false,
        ]);
        $hero = News::factory()->create([
            'status' => NewsStatus::Published,
            'publish_at' => now()->subDay(),
            'is_actuality_highlight' => true,
        ]);

        $response = $this->getJson('/api/v1/news/actuality');

        $response->assertOk();
        $this->assertNotNull($response->json('data'));
        $response->assertJsonPath('data.slug', $hero->fresh()->slug);
        $response->assertJsonPath('data.actuality_highlight', true);
    }

    public function test_actuality_api_returns_null_when_not_visible(): void
    {
        News::factory()->create([
            'status' => NewsStatus::Draft,
            'is_actuality_highlight' => true,
        ]);

        $response = $this->getJson('/api/v1/news/actuality');

        $response->assertOk();
        $response->assertJsonPath('data', null);
    }
}
