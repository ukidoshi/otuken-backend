<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\News\NewsResource;
use App\Models\News;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class NewsActualityHighlightWidget extends Widget
{
    protected static bool $isDiscovered = false;

    /**
     * Иначе по умолчанию виджет грузится лениво и долго показывает плейсхолдер (иконки загрузки).
     */
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.news-actuality-highlight-widget';

    public ?string $newsId = null;

    public static function canView(): bool
    {
        return NewsResource::canViewAny();
    }

    public function mount(): void
    {
        $id = News::query()->where('is_actuality_highlight', true)->value('id');
        $this->newsId = $id !== null ? (string) $id : null;
    }

    public function save(): void
    {
        Gate::authorize('viewAny', News::class);

        $this->validate([
            'newsId' => ['nullable', 'string', Rule::exists('news', 'id')],
        ]);

        if (filled($this->newsId)) {
            $news = News::query()->findOrFail($this->newsId);
            Gate::authorize('update', $news);
        }

        DB::transaction(function (): void {
            News::query()->update(['is_actuality_highlight' => false]);
            if (filled($this->newsId)) {
                News::query()->whereKey($this->newsId)->update(['is_actuality_highlight' => true]);
            }
        });

        Notification::make()
            ->title('Закрепленная новость обновлена')
            ->success()
            ->send();
    }

    /**
     * @return array<string, string>
     */
    public function getNewsOptionsProperty(): array
    {
        return News::query()
            ->orderByDesc('publish_at')
            ->orderByDesc('id')
            ->limit(250)
            ->get()
            ->mapWithKeys(fn (News $n): array => [
                (string) $n->id => $n->getTranslation('title', 'ru', true).' · '.$n->status->getLabel(),
            ])
            ->all();
    }
}
