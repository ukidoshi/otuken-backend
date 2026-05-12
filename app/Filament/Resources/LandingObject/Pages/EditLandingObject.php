<?php

namespace App\Filament\Resources\LandingObject\Pages;

use App\Filament\Resources\LandingObject\LandingObjectResource;
use App\Models\LandingContent;
use Filament\Resources\Pages\EditRecord;

class EditLandingObject extends EditRecord
{
    protected static string $resource = LandingObjectResource::class;

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
            // slug показываем в форме как readonly, но сами не пишем его в content
            // (см. mutateFormDataBeforeSave). Это нужно, чтобы пустые локали
            // не выглядели «заполненными» из-за единственного технического поля.
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
            // slug в content не сохраняем — он всегда восстанавливается из section_key.
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

        return $title !== '' ? "Объект: {$title}" : 'Объект комплекса';
    }
}
