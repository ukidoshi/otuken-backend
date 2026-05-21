<?php

namespace App\Console\Commands;

use App\Services\LandingContent\LandingContentLocaleSync;
use Illuminate\Console\Command;

class SyncLandingLocalesFromRussianCommand extends Command
{
    protected $signature = 'landing:sync-locales-from-ru
                            {--force : Разрешить на production}';

    protected $description = 'Скопировать тексты лендинга из RU в TUV и EN (полная замена)';

    public function handle(): int
    {
        if ($this->getLaravel()->environment('production') && ! $this->option('force')) {
            $this->error('На production добавьте --force');

            return self::FAILURE;
        }

        $this->warn('Тувинский и английский будут полностью заменены копией русского для всех записей landing_contents.');

        if ($this->input->isInteractive() && ! $this->confirm('Продолжить?', true)) {
            return self::SUCCESS;
        }

        $updated = LandingContentLocaleSync::mirrorAll();

        $this->info("Обновлено записей: {$updated}");
        $this->comment('Кэш site-content сброшен.');

        return self::SUCCESS;
    }
}
