<?php

namespace App\Filament\Resources\News\Pages;

use App\Filament\Resources\News\NewsResource;
use App\Services\AiSeoGeneratorService;
use App\Services\AiTranslationService;
use Filament\Resources\Pages\CreateRecord;

class CreateNews extends CreateRecord
{
    protected static string $resource = NewsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $shouldGenerate = (bool) ($data['generate_seo_with_ai'] ?? true);
        $shouldTranslateEn = (bool) ($data['auto_translate_en_with_ai'] ?? true);
        unset($data['generate_seo_with_ai']);
        unset($data['auto_translate_en_with_ai']);

        $data['author_id'] = auth()->id();

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
