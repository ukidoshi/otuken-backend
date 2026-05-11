<?php

namespace App\Filament\Resources\News\Pages;

use App\Filament\Resources\News\NewsResource;
use App\Filament\Widgets\NewsActualityHighlightWidget;
use App\Models\News;
use App\Services\AiSeoGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListNews extends ListRecords
{
    protected static string $resource = NewsResource::class;

    public function booted(): void
    {
        News::archiveExpiredByUnpublishDate();
    }

    /**
     * @return array<class-string>
     */
    protected function getFooterWidgets(): array
    {
        return [
            NewsActualityHighlightWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('checkAi')
                ->label('Проверить AI (OpenRouter)')
                ->icon('heroicon-o-bolt')
                ->color('gray')
                ->action(function (): void {
                    $result = app(AiSeoGeneratorService::class)->healthCheck();

                    $notification = Notification::make()
                        ->title($result['ok'] ? 'AI интеграция работает' : 'AI интеграция недоступна')
                        ->body($result['message']);

                    if ($result['ok']) {
                        $notification->success()->send();
                    } else {
                        $notification->danger()->send();
                    }
                }),
            CreateAction::make()
                ->label('Создать новость'),
        ];
    }
}
