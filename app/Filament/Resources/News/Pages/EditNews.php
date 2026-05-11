<?php

namespace App\Filament\Resources\News\Pages;

use App\Filament\Resources\News\NewsResource;
use App\Services\AiSeoGeneratorService;
use App\Services\AiTranslationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditNews extends EditRecord
{
    protected static string $resource = NewsResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->getRecord()->loadMissing('media');

        return parent::mutateFormDataBeforeFill($data);
    }

    public function detachCoverImage(): void
    {
        $this->record->clearMediaCollection('cover_image');
        $this->record->refresh();
        $this->fillForm();

        Notification::make()
            ->title('Обложка отвязана')
            ->success()
            ->send();
    }

    public function detachSeoImage(): void
    {
        $this->record->clearMediaCollection('seo_image');
        $this->record->refresh();
        $this->fillForm();

        Notification::make()
            ->title('SEO-изображение отвязано')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('contentStudio')
                ->label('Контент-студия')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => route('admin.news.studio.edit', ['news' => $this->record, 'locale' => 'ru'])),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $shouldGenerate = (bool) ($data['generate_seo_with_ai'] ?? false);
        $shouldTranslateEn = (bool) ($data['auto_translate_en_with_ai'] ?? false);
        unset($data['generate_seo_with_ai']);
        unset($data['auto_translate_en_with_ai']);

        if ($shouldTranslateEn) {
            $this->fillEnglishTranslations($data);
        }

        if (! $shouldGenerate) {
            return $data;
        }

        $generated = app(AiSeoGeneratorService::class)->generate(
            (array) ($data['title'] ?? []),
            (array) ($data['excerpt'] ?? [])
        );

        foreach (['ru', 'tuv', 'en'] as $locale) {
            $data['seo_title'][$locale] = ($data['seo_title'][$locale] ?? '') !== ''
                ? $data['seo_title'][$locale]
                : $generated['seo_title'][$locale];
            $data['seo_description'][$locale] = ($data['seo_description'][$locale] ?? '') !== ''
                ? $data['seo_description'][$locale]
                : $generated['seo_description'][$locale];
        }

        return $data;
    }

    private function fillEnglishTranslations(array &$data): void
    {
        $source = [
            'title' => (string) ($data['title']['ru'] ?? ''),
            'excerpt' => (string) ($data['excerpt']['ru'] ?? ''),
        ];

        $translated = app(AiTranslationService::class)->translateRuToEn($source);

        if (($data['title']['en'] ?? '') === '' && ($translated['title'] ?? '') !== '') {
            $data['title']['en'] = $translated['title'];
        }

        if (($data['excerpt']['en'] ?? '') === '' && ($translated['excerpt'] ?? '') !== '') {
            $data['excerpt']['en'] = $translated['excerpt'];
        }
    }
}
