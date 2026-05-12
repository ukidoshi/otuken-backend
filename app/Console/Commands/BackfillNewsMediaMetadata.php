<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\News;
use App\Services\Media\EditorJsMediaMetadata;
use Illuminate\Console\Command;
use Throwable;

class BackfillNewsMediaMetadata extends Command
{
    /** @var string */
    protected $signature = 'news:media:backfill
        {--news= : ID одной новости, иначе все}
        {--regenerate-posters : Пересоздавать постеры даже если они существуют}
        {--dry-run : Не сохранять, только показать сводку}';

    /** @var string */
    protected $description = 'Дописывает в content_blocks (image/video) метаданные: width, height, mime, size, duration, poster_url.';

    public function handle(EditorJsMediaMetadata $metadata): int
    {
        $regenerate = (bool) $this->option('regenerate-posters');
        $dryRun = (bool) $this->option('dry-run');

        $query = News::query();
        if ($id = $this->option('news')) {
            $query->whereKey($id);
        }

        $total = (int) $query->count();
        if ($total === 0) {
            $this->info('Нет записей для обработки.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Обработка %d новостей%s%s.', $total,
            $dryRun ? ' (dry-run)' : '',
            $regenerate ? ', с пересозданием постеров' : ''
        ));

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $touchedBlocks = 0;
        $errors = 0;

        $query->cursor()->each(function (News $news) use ($metadata, $regenerate, $dryRun, &$updated, &$touchedBlocks, &$errors, $bar): void {
            try {
                $raw = $news->getTranslations('content_blocks');
                if (! is_array($raw)) {
                    $bar->advance();

                    return;
                }

                $dirty = false;
                foreach ($raw as $locale => $value) {
                    if (! is_array($value)) {
                        continue;
                    }

                    $before = json_encode($value);
                    $enriched = $metadata->enrichContent($value, $regenerate);
                    $after = json_encode($enriched);

                    if ($before !== $after) {
                        $touchedBlocks += $this->countMediaBlocks($enriched);
                        $news->setTranslation('content_blocks', (string) $locale, $enriched);
                        $dirty = true;
                    }
                }

                if ($dirty) {
                    $updated++;
                    if (! $dryRun) {
                        $news->save();
                    }
                }
            } catch (Throwable $e) {
                $errors++;
                $this->newLine();
                $this->error(sprintf('News #%d: %s', $news->getKey(), $e->getMessage()));
            } finally {
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info(sprintf('Готово. Изменено новостей: %d, обновлено медиа-блоков: %d, ошибок: %d.',
            $updated, $touchedBlocks, $errors));

        if ($dryRun) {
            $this->warn('Это был dry-run, ничего не сохранено.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $content
     */
    private function countMediaBlocks(array $content): int
    {
        $blocks = $content['blocks'] ?? $content;
        if (! is_array($blocks)) {
            return 0;
        }

        $n = 0;
        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }
            $type = (string) ($block['type'] ?? '');
            if ($type === 'image' || $type === 'video') {
                $n++;
            }
        }

        return $n;
    }
}
