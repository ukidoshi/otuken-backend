<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\News\NewsResource;
use App\Models\News;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PublicNewsStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected ?string $heading = 'Новости на сайте';

    protected ?string $description = 'Сколько материалов сейчас отдаётся в публичной ленте и API.';

    public static function canView(): bool
    {
        return NewsResource::canViewAny();
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $visible = News::query()->publiclyVisible()->count();

        return [
            Stat::make('Показывается сейчас', number_format($visible, 0, ',', ' '))
                ->description('Опубликовано, дата выхода наступила, снятие с публикации ещё не наступило')
                ->descriptionIcon(Heroicon::OutlinedEye)
                ->color('success')
                ->url(NewsResource::getUrl()),
        ];
    }
}
