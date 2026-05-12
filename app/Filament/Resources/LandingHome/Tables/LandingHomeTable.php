<?php

namespace App\Filament\Resources\LandingHome\Tables;

use App\Models\LandingContent;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LandingHomeTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('section_key')
                    ->label('Раздел')
                    ->formatStateUsing(fn (): string => 'Главная страница (/) — расширенные блоки + фото фестиваля'),
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
                    ->label('Фото фестиваля')
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
