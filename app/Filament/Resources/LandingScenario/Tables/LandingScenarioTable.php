<?php

namespace App\Filament\Resources\LandingScenario\Tables;

use App\Models\LandingContent;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LandingScenarioTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_ru')
                    ->label('Название (RU)')
                    ->getStateUsing(function (LandingContent $record): string {
                        $ru = $record->getTranslation('content', 'ru', false);

                        return is_array($ru) && isset($ru['title']) ? (string) $ru['title'] : '—';
                    })
                    ->description(fn (LandingContent $record): string => 'slug: '.($record->slug() ?? '—')),
                TextColumn::make('locales_filled')
                    ->label('Языки с контентом')
                    ->getStateUsing(function (LandingContent $record): string {
                        $filled = [];
                        foreach (LandingContent::locales() as $locale) {
                            $value = $record->getTranslation('content', $locale, false);
                            if (is_array($value) && $value !== []) {
                                $filled[] = strtoupper($locale);
                            }
                        }

                        return $filled === [] ? '—' : implode(' · ', $filled);
                    })
                    ->badge(),
                TextColumn::make('images_count')
                    ->label('Фото')
                    ->getStateUsing(function (LandingContent $record): string {
                        $count = count(LandingContent::normalizePaths($record->images));

                        return $count === 0 ? '—' : (string) $count;
                    })
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y H:i')
                    ->since(),
            ])
            ->recordActions([
                EditAction::make()->label('Редактировать'),
            ])
            ->paginated(false);
    }
}
