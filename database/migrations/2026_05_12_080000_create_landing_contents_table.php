<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_contents', function (Blueprint $table): void {
            $table->id();

            // Идентификатор раздела/записи лендинга:
            //   - site_pages.home / site_pages.complex / site_pages.location / ...
            //   - object.<slug>
            //   - event.<slug>
            $table->string('section_key', 128)->unique();

            // Мультиязычный контент. Spatie Translatable сохраняет сюда
            // структуру вида {"ru": {...}, "tuv": {...}, "en": {...}}.
            // Каждый локализованный объект — JSON-структура раздела
            // (поля, массивы sections/faq/cards/list/relatedLinks и т. п.).
            $table->json('content')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_contents');
    }
};
