<?php

namespace App\Filament\Resources\News\Tables;

use App\Enums\NewsStatus;
use App\Models\News;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_ru')
                    ->label('Заголовок')
                    ->getStateUsing(fn (News $record): string => (string) $record->getTranslation('title', 'ru', true))
                    ->searchable(true, function (Builder $query, string $search): void {
                        $needle = '%'.addcslashes($search, '%_\\').'%';
                        $query->where('title->ru', 'like', $needle);
                    }),
                TextColumn::make('status')->label('Статус')->badge(),
                TextColumn::make('locale')->label('Язык материала'),
                TextColumn::make('author.name')->label('Автор'),
                TextColumn::make('publish_at')->label('Дата публикации')->dateTime(),
                TextColumn::make('is_actuality_highlight')
                    ->label('Закрепленная')
                    ->badge()
                    ->formatStateUsing(fn (?bool $state): string => $state ? 'Да' : '—')
                    ->color(fn (?bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(collect(NewsStatus::cases())->mapWithKeys(fn (NewsStatus $s) => [$s->value => $s->getLabel()])->all()),
                SelectFilter::make('locale')
                    ->label('Язык материала')
                    ->options([
                        'ru' => 'Русский (ru)',
                        'tuv' => 'Тыва (tuv)',
                        'en' => 'Английский (en)',
                    ]),
                SelectFilter::make('author_id')
                    ->label('Автор')
                    ->relationship('author', 'name'),
                Filter::make('period')
                    ->label('Дата публикации: ±1 месяц от сегодня')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('publish_at', [now()->subMonth(), now()->addMonth()])),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('publish')
                    ->label('Опубликовать')
                    ->visible(fn ($record): bool => auth()->user()?->can('publish', $record) ?? false)
                    ->action(fn ($record): mixed => $record->update(['status' => NewsStatus::Published])),
                Action::make('unpublish')
                    ->label('Снять с публикации')
                    ->visible(fn ($record): bool => auth()->user()?->can('unpublish', $record) ?? false)
                    ->action(fn ($record): mixed => $record->update(['status' => NewsStatus::Hidden])),
                Action::make('archive')
                    ->label('В архив')
                    ->visible(fn ($record): bool => auth()->user()?->can('archive', $record) ?? false)
                    ->action(fn ($record): mixed => $record->update(['status' => NewsStatus::Archived])),
                Action::make('duplicate')
                    ->label('Дублировать')
                    ->action(function ($record): void {
                        $copy = $record->replicate(['slug']);
                        $copy->slug = $record->slug.'-copy-'.now()->timestamp;
                        $copy->status = NewsStatus::Draft;
                        $copy->save();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
