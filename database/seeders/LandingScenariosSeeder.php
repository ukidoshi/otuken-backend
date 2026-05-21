<?php

namespace Database\Seeders;

use App\Models\LandingContent;
use App\Services\LandingContent\LandingContentLocaleSync;
use Database\Seeders\Data\ScenariosSeedData;
use Illuminate\Database\Seeder;

/**
 * Заливка дефолтных текстов «Сценариев территории» (6 фиксированных slug'ов).
 *
 * Вынесен в отдельный сидер, чтобы можно было прогнать только его — например,
 * после деплоя новой схемы сценариев без необходимости трогать страницы,
 * объекты и события (LandingContentsSeeder).
 *
 * Идемпотентен: для каждого slug'а делает firstOrNew, в ru добавляет только
 * те top-level ключи, которых ещё нет — заполненные правки админа не трогаем.
 */
class LandingScenariosSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ScenariosSeedData::all() as $scenario) {
            $this->upsertRussian('scenario.'.$scenario['slug'], $scenario);
        }
    }

    /**
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
