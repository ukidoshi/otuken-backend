<?php

namespace Tests\Feature\Feature;

use App\Enums\NewsStatus;
use App\Models\News;
use App\Models\User;
use App\Services\NewsPreviewTokenService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_news_endpoints_return_published_news(): void
    {
        $news = News::factory()->create([
            'status' => NewsStatus::Published,
            'publish_at' => now()->subMinute(),
            'title' => ['ru' => 'Русский заголовок'],
        ]);
        News::factory()->create(['status' => NewsStatus::Draft]);

        $this->getJson('/api/v1/news?locale=ru')
            ->assertOk()
            ->assertJsonFragment(['slug' => $news->slug]);

        $this->getJson('/api/v1/news/'.$news->slug.'?locale=ru')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Русский заголовок']);
    }

    public function test_public_news_uses_translations_for_en_and_tuv_not_row_locale_column(): void
    {
        $news = News::factory()->create([
            'status' => NewsStatus::Published,
            'publish_at' => now()->subMinute(),
            'locale' => 'ru',
            'title' => [
                'ru' => 'Русский заголовок',
                'en' => 'English headline',
                'tuv' => 'Тыва заголовок',
            ],
        ]);

        $this->getJson('/api/v1/news?locale=en')
            ->assertOk()
            ->assertJsonFragment(['slug' => $news->slug, 'title' => 'English headline']);

        $this->getJson('/api/v1/news?locale=EN')
            ->assertOk()
            ->assertJsonFragment(['slug' => $news->slug, 'title' => 'English headline']);

        $this->getJson('/api/v1/news/'.$news->slug.'?locale=tuv')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Тыва заголовок']);
    }

    public function test_preview_with_token_works(): void
    {
        $news = News::factory()->create(['status' => NewsStatus::Draft]);
        $token = app(NewsPreviewTokenService::class)->generate($news);

        $this->getJson('/api/v1/preview/news/'.$news->id.'?token='.$token)
            ->assertOk()
            ->assertJsonPath('data.slug', $news->slug);
    }

    public function test_preview_with_sanctum_auth_works(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $news = News::factory()->create(['status' => NewsStatus::Draft]);
        $user = User::factory()->create();
        $user->assignRole('user');
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/v1/preview/news/'.$news->id)
            ->assertOk();
    }
}
