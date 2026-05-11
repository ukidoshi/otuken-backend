<?php

use App\Services\AiTranslationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('news:test-tuv-translation', function (): int {
    if (! config('services.openrouter.api_key')) {
        $this->error('Задайте OPENROUTER_API_KEY в .env');

        return 1;
    }

    $this->info('Модели: '.implode(', ', array_values(array_filter((array) config('services.openrouter.models', []))) ?: [(string) config('services.openrouter.model')]));
    $this->newLine();

    $samples = [
        'title' => 'Демо-новость: запуск редакции',
        'lead' => 'Публичные эндпоинты отдают список и деталь новости с учётом локали.',
        'list_item' => 'Проверка публикации',
    ];

    $out = app(AiTranslationService::class)->translateRuToTuv($samples);

    foreach ($samples as $key => $ru) {
        $this->line("<fg=cyan>[{$key}]</>");
        $this->line('  RU:  '.$ru);
        $this->line('  TUV: '.($out[$key] ?? '(пусто — смотрите логи)'));
        $this->newLine();
    }

    $this->comment('Оцените качество тувинского. Если ок — в .env: OPENROUTER_ENABLE_TUV_STUDIO_TRANSLATION=true');
    $this->comment('Если модель «не знает» язык — оставьте false и кнопка RU→TUV в студии не появится.');

    return 0;
})->purpose('Проверка RU→тывинский через текущую модель OpenRouter');
