<?php

namespace App\Filament\Resources\LandingHome\Pages;

use App\Filament\Resources\LandingHome\LandingHomeResource;
use App\Models\LandingContent;
use Filament\Resources\Pages\EditRecord;

class EditLandingHome extends EditRecord
{
    protected static string $resource = LandingHomeResource::class;

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

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $content = $data['content'] ?? [];
        $normalized = [];

        foreach (LandingContent::locales() as $locale) {
            $value = $content[$locale] ?? [];
            $normalized[$locale] = is_array($value) ? $value : [];
        }

        $data['content'] = $normalized;

        return $data;
    }

    public function getTitle(): string
    {
        return 'Главная страница лендинга';
    }
}
