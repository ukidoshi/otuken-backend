<?php

namespace Database\Seeders;

use App\Models\LandingContent;
use App\Services\LandingContent\LandingContentLocaleSync;
use Database\Seeders\Data\EventsSeedData;
use Database\Seeders\Data\ObjectsSeedData;
use Database\Seeders\Data\SitePagesSeedData;
use Illuminate\Database\Seeder;

/**
 * Заливка дефолтных текстов лендинга «Өтүкен».
 *
 * Идемпотентен: для каждой section_key делает updateOrCreate, но **не** перезатирает
 * уже существующие переводы — записывает baseline только в локаль `ru` и только если
 * её ещё нет. Это значит, что повторный прогон seeder'а не сотрёт правки админа.
 */
class LandingContentsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SitePagesSeedData::all() as $sectionKey => $ru) {
            $this->upsertRussian($sectionKey, $ru);
        }

        foreach (ObjectsSeedData::all() as $object) {
            $this->upsertRussian('object.'.$object['slug'], $object);
        }

        foreach (EventsSeedData::all() as $event) {
            $this->upsertRussian('event.'.$event['slug'], $event);
        }
    }

    /**
     * Идемпотентно дописать RU-локаль для раздела.
     *
     * Правила:
     *   - после RU те же тексты копируются в tuv и en (см. LandingContentLocaleSync);
     *   - в ru добавляем только те top-level ключи, которых ещё нет в БД,
     *     ранее заполненные ключи (например, отредактированный title) оставляем
     *     как есть. Так seeder спокойно прогоняется поверх свежей миграции,
     *     которая может вводить новые блоки (about / festival / …), не затирая
     *     уже отредактированные SEO-поля.
     *
     * @param  array<string, mixed>  $russianContent
     */
    private function upsertRussian(string $sectionKey, array $russianContent): void
    {
        $record = LandingContent::query()->firstOrNew(['section_key' => $sectionKey]);

        $translations = $record->exists ? ($record->getTranslations('content') ?: []) : [];
        $existingRu = $translations['ru'] ?? [];
        if (! is_array($existingRu)) {
            $existingRu = [];
        }

        foreach ($russianContent as $key => $defaultValue) {
            if (! array_key_exists($key, $existingRu)) {
                $existingRu[$key] = $defaultValue;
            }
        }

        $translations['ru'] = $existingRu;
        $translations = LandingContentLocaleSync::mirrorIntoTranslations($translations);

        $record->section_key = $sectionKey;
        $record->setTranslations('content', $translations);
        $record->save();
    }
}
