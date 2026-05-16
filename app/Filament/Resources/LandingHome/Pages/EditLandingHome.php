<?php

namespace App\Filament\Resources\LandingHome\Pages;

use App\Filament\Resources\LandingHome\LandingHomeResource;
use App\Models\LandingContent;
use App\Services\LandingContent\SiteContentCache;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLandingHome extends EditRecord
{
    protected static string $resource = LandingHomeResource::class;

    /** @var array{objects: array<string, mixed>, scenarios: array<string, mixed>}|null */
    protected ?array $catalogSnapshot = null;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $translations = $this->record->getTranslations('content');

        $data['content'] = [];
        foreach (LandingContent::locales() as $locale) {
            $data['content'][$locale] = is_array($translations[$locale] ?? null)
                ? $translations[$locale]
                : [];
        }

        $data['images'] = $this->record->images;

        $ru = $this->record->localized('ru');
        $data['festival_visible'] = (bool) (($ru['festival']['visible'] ?? true));

        $data['catalog_objects'] = [];
        foreach (LandingContent::objectSlugs() as $slug => $_label) {
            $objectRecord = LandingContent::query()
                ->where('section_key', 'object.'.$slug)
                ->first();
            if (! $objectRecord) {
                continue;
            }
            $data['catalog_objects'][$slug] = [
                'images' => $objectRecord->images ?? [],
                'content' => [],
            ];
            foreach (LandingContent::locales() as $locale) {
                $loc = $objectRecord->getTranslation('content', $locale, false)
                    ?? [];
                $loc = is_array($loc) ? $loc : [];
                $loc['slug'] = $slug;
                $data['catalog_objects'][$slug]['content'][$locale] = $loc;
            }
        }

        $data['catalog_scenarios'] = [];
        foreach (LandingContent::scenarioSlugs() as $slug => $_label) {
            $scenarioRecord = LandingContent::query()
                ->where('section_key', 'scenario.'.$slug)
                ->first();
            if (! $scenarioRecord) {
                continue;
            }
            $data['catalog_scenarios'][$slug] = [
                'images' => $scenarioRecord->images ?? [],
                'content' => [],
            ];
            foreach (LandingContent::locales() as $locale) {
                $loc = $scenarioRecord->getTranslation('content', $locale, false)
                    ?? [];
                $loc = is_array($loc) ? $loc : [];
                $loc['slug'] = $slug;
                $data['catalog_scenarios'][$slug]['content'][$locale] = $loc;
            }
        }

        foreach (LandingContent::objectSlugs() as $slug => $_label) {
            if (isset($data['catalog_objects'][$slug])) {
                continue;
            }
            $data['catalog_objects'][$slug] = [
                'images' => [],
                'content' => [],
            ];
            foreach (LandingContent::locales() as $locale) {
                $data['catalog_objects'][$slug]['content'][$locale] = ['slug' => $slug];
            }
        }

        foreach (LandingContent::scenarioSlugs() as $slug => $_label) {
            if (isset($data['catalog_scenarios'][$slug])) {
                continue;
            }
            $data['catalog_scenarios'][$slug] = [
                'images' => [],
                'content' => [],
            ];
            foreach (LandingContent::locales() as $locale) {
                $data['catalog_scenarios'][$slug]['content'][$locale] = ['slug' => $slug];
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->catalogSnapshot = [
            'objects' => $data['catalog_objects'] ?? [],
            'scenarios' => $data['catalog_scenarios'] ?? [],
        ];
        unset($data['catalog_objects'], $data['catalog_scenarios']);

        $visible = (bool) ($data['festival_visible'] ?? true);
        unset($data['festival_visible']);

        $content = $data['content'] ?? [];
        $normalized = [];

        foreach (LandingContent::locales() as $locale) {
            $value = $content[$locale] ?? [];
            $value = is_array($value) ? $value : [];

            $value['festival'] ??= [];
            if (! is_array($value['festival'])) {
                $value['festival'] = [];
            }
            $value['festival']['visible'] = $visible;

            $normalized[$locale] = $value;
        }

        $data['content'] = $normalized;

        return $data;
    }

    protected function afterSave(): void
    {
        $this->persistCatalog($this->catalogSnapshot);
        $this->catalogSnapshot = null;

        Notification::make()
            ->title('Сохранено')
            ->success()
            ->body('Изменения появятся на сайте в течение '.round(SiteContentCache::TTL_SECONDS / 60).' минут.')
            ->send();
    }

    /**
     * @param  array{objects: array<string, mixed>, scenarios: array<string, mixed>}|null  $snapshot
     */
    protected function persistCatalog(?array $snapshot): void
    {
        if ($snapshot === null) {
            return;
        }

        foreach (LandingContent::objectSlugs() as $slug => $_label) {
            $payload = $snapshot['objects'][$slug] ?? null;
            if (! is_array($payload)) {
                continue;
            }
            $record = LandingContent::query()
                ->where('section_key', 'object.'.$slug)
                ->first();
            if (! $record) {
                continue;
            }
            $record->images = $payload['images'] ?? [];
            foreach (LandingContent::locales() as $locale) {
                $localeContent = $payload['content'][$locale] ?? [];
                $localeContent = is_array($localeContent) ? $localeContent : [];
                unset($localeContent['slug']);
                $record->setTranslation('content', $locale, $localeContent);
            }
            $record->save();
        }

        foreach (LandingContent::scenarioSlugs() as $slug => $_label) {
            $payload = $snapshot['scenarios'][$slug] ?? null;
            if (! is_array($payload)) {
                continue;
            }
            $record = LandingContent::query()
                ->where('section_key', 'scenario.'.$slug)
                ->first();
            if (! $record) {
                continue;
            }
            $record->images = $payload['images'] ?? [];
            foreach (LandingContent::locales() as $locale) {
                $localeContent = $payload['content'][$locale] ?? [];
                $localeContent = is_array($localeContent) ? $localeContent : [];
                unset($localeContent['slug']);
                $record->setTranslation('content', $locale, $localeContent);
            }
            $record->save();
        }
    }

    public function getTitle(): string
    {
        return 'Главная страница';
    }
}
