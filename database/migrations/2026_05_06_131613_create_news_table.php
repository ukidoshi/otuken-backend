<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->string('slug')->unique();
            $table->json('excerpt')->nullable();
            $table->json('content_blocks')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'published', 'hidden', 'archived'])->default('draft');
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('unpublish_at')->nullable();
            $table->string('locale', 8)->default('ru');
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->string('seo_image_alt')->nullable();
            $table->string('canonical')->nullable();
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('published_by_id')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'publish_at', 'unpublish_at']);
            $table->index('locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
