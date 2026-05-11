<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

class ArchiveExpiredNewsCommand extends Command
{
    protected $signature = 'news:archive-expired';

    protected $description = 'Архивировать новости с истёкшей датой «Снять с публикации» (статус «Опубликовано» → «В архиве»)';

    public function handle(): int
    {
        $count = News::archiveExpiredByUnpublishDate();

        if ($count > 0) {
            $this->info("В архив переведено новостей: {$count}");
        } else {
            $this->comment('Нет новостей для архивации по дате снятия с публикации.');
        }

        return self::SUCCESS;
    }
}
