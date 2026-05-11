<?php

namespace Database\Factories;

use App\Enums\NewsStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\News>
 */
class NewsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ['ru' => 'Новость '.$this->faker->unique()->word(), 'en' => null, 'tuv' => null],
            'slug' => $this->faker->unique()->slug(),
            'excerpt' => ['ru' => $this->faker->sentence()],
            'content_blocks' => ['ru' => [['type' => 'paragraph', 'text' => $this->faker->paragraph()]]],
            'status' => NewsStatus::Draft,
            'publish_at' => null,
            'unpublish_at' => null,
            'locale' => 'ru',
            'seo_title' => ['ru' => $this->faker->sentence(3)],
            'seo_description' => ['ru' => $this->faker->sentence(8)],
            'seo_image_alt' => $this->faker->words(3, true),
            'canonical' => null,
            'author_id' => User::factory(),
            'published_by_id' => null,
            'approved_at' => null,
            'is_actuality_highlight' => false,
        ];
    }
}
