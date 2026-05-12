<?php

namespace App\Filament\Resources\LandingScenario\Pages;

use App\Filament\Resources\LandingScenario\LandingScenarioResource;
use App\Models\LandingContent;
use Filament\Resources\Pages\EditRecord;

class EditLandingScenario extends EditRecord
{
    protected static string $resource = LandingScenarioResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $translations = $this->record->getTranslations('content');
        $slug = $this->record->slug();

        $data['content'] = [];
        foreach (LandingContent::locales() as $locale) {
            $payload = is_array($translations[$locale] ?? null) ? $translations[$locale] : [];
            $payload['slug'] = $slug;
            $data['content'][$locale] = $payload;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $content = $data['content'] ?? [];
        $normalized = [];

        foreach (LandingContent::locales() as $locale) {
            $value = $content[$locale] ?? [];
            if (! is_array($value)) {
                $value = [];
            }
            unset($value['slug']);
            $normalized[$locale] = $value;
        }

        $data['content'] = $normalized;

        return $data;
    }

    public function getTitle(): string
    {
        $ru = $this->record->getTranslation('content', 'ru', false);
        $title = is_array($ru) && isset($ru['title']) ? (string) $ru['title'] : '';

        return $title !== '' ? "Сценарий: {$title}" : 'Сценарий территории';
    }
}
