<?php

namespace Tests\Feature\Feature;

use App\Enums\NewsStatus;
use App\Models\News;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_has_full_news_permissions_and_directory_only(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('user');
        $news = News::factory()->create(['status' => NewsStatus::Draft]);

        $this->assertTrue($user->can('publish', $news));
        $this->assertTrue($user->can('archive', $news));
        $this->assertTrue($user->can('create', News::class));
        $this->assertTrue($user->can('users.directory'));
        $this->assertFalse($user->can('users.manage'));
    }
}
