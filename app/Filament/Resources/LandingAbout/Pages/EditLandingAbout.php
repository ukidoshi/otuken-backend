<?php

namespace App\Filament\Resources\LandingAbout\Pages;

use App\Filament\Resources\LandingAbout\LandingAboutResource;
use App\Models\LandingContent;
use App\Services\LandingContent\SiteContentCache;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLandingAbout extends EditRecord
{
    protected static string $resource = LandingAboutResource::class;

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

    protected function afterSave(): void
    {
        Notification::make()
            ->title('Сохранено')
            ->success()
            ->body('Изменения появятся на сайте в течение '.round(SiteContentCache::TTL_SECONDS / 60).' минут.')
            ->send();
    }

    public function getTitle(): string
    {
        return 'Страница «О нас»';
    }
}
