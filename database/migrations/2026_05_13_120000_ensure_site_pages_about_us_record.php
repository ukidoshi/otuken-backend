<?php

use App\Models\LandingContent;
use Illuminate\Database\Migrations\Migration;

/**
 * Запись «О нас» для редактора: один фиксированный section_key.
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = LandingContent::query()->where('section_key', 'site_pages.about_us')->exists();
        if ($exists) {
            return;
        }

        $record = new LandingContent;
        $record->section_key = 'site_pages.about_us';
        foreach (LandingContent::locales() as $locale) {
            $record->setTranslation('content', $locale, []);
        }
        $record->save();
    }

    public function down(): void
    {
        LandingContent::query()->where('section_key', 'site_pages.about_us')->delete();
    }
};
