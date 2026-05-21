<?php

namespace App\Filament\Resources\LandingHome\Pages;

use App\Filament\Concerns\HasHeaderSaveAction;
use App\Filament\Resources\LandingHome\LandingHomeResource;
use App\Models\LandingContent;
use App\Services\LandingContent\SiteContentCache;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLandingHome extends EditRecord
{
    use HasHeaderSaveAction;

    protected static string $resource = LandingHomeResource::class;

    /** @var array{objects: array<string, mixed>, scenarios: array<string, mixed>, events: array<string, mixed>}|null */
    protected ?array $catalogSnapshot = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $translations = $this->record->getTranslations('content');

        $data['content'] = [];
        foreach (LandingContent::locales() as $locale) {
            $localeData = is_array($translations[$locale] ?? null)
                ? $translations[$locale]
                : [];
            $data['content'][$locale] = self::normalizeHomeLocale($localeData);
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

        $data['catalog_events'] = [];
        foreach (LandingContent::eventSlugs() as $slug => $_label) {
            $eventRecord = LandingContent::query()
                ->where('section_key', 'event.'.$slug)
                ->first();
            $data['catalog_events'][$slug] = ['content' => []];
            foreach (LandingContent::locales() as $locale) {
                $loc = [];
                if ($eventRecord) {
                    $loc = $eventRecord->getTranslation('content', $locale, false) ?? [];
                    $loc = is_array($loc) ? $loc : [];
                }
                $data['catalog_events'][$slug]['content'][$locale] = [
                    'title' => $loc['title'] ?? '',
                    'dateText' => $loc['dateText'] ?? '',
                ];
            }
        }

        return $data;
    }

    /**
     * Перенос устаревших полей первого экрана в hero.*.
     *
     * @param  array<string, mixed>  $localeData
     * @return array<string, mixed>
     */
    protected static function normalizeHomeLocale(array $localeData): array
    {
        if (! isset($localeData['hero']) || ! is_array($localeData['hero'])) {
            $localeData['hero'] = [];
        }

        $hero = $localeData['hero'];
        if (trim((string) ($hero['title'] ?? '')) === '' && isset($localeData['introTitle'])) {
            $hero['title'] = $localeData['introTitle'];
        }
        if (trim((string) ($hero['lead'] ?? '')) === '' && isset($localeData['introText'])) {
            $hero['lead'] = $localeData['introText'];
        }
        if (trim((string) ($hero['badge'] ?? '')) === '' && isset($localeData['heroBadge'])) {
            $hero['badge'] = $localeData['heroBadge'];
        }

        $localeData['hero'] = $hero;

        return $localeData;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->catalogSnapshot = [
            'objects' => $data['catalog_objects'] ?? [],
            'scenarios' => $data['catalog_scenarios'] ?? [],
            'events' => $data['catalog_events'] ?? [],
        ];
        unset($data['catalog_objects'], $data['catalog_scenarios'], $data['catalog_events']);

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
     * @param  array{objects: array<string, mixed>, scenarios: array<string, mixed>, events: array<string, mixed>}|null  $snapshot
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

        foreach (LandingContent::eventSlugs() as $slug => $_label) {
            $payload = $snapshot['events'][$slug] ?? null;
            if (! is_array($payload)) {
                continue;
            }
            $record = LandingContent::query()
                ->where('section_key', 'event.'.$slug)
                ->first();
            if (! $record) {
                continue;
            }
            foreach (LandingContent::locales() as $locale) {
                $localeContent = $payload['content'][$locale] ?? [];
                $localeContent = is_array($localeContent) ? $localeContent : [];
                $existing = $record->getTranslation('content', $locale, false) ?? [];
                $existing = is_array($existing) ? $existing : [];
                $record->setTranslation('content', $locale, array_merge($existing, [
                    'title' => $localeContent['title'] ?? $existing['title'] ?? '',
                    'dateText' => $localeContent['dateText'] ?? $existing['dateText'] ?? '',
                ]));
            }
            $record->save();
        }
    }

    public function getTitle(): string
    {
        return 'Главная страница';
    }
}
