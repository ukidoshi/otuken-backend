<?php

namespace App\Filament\Resources\LandingAbout\Tables;

use App\Filament\Resources\LandingAbout\LandingAboutResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LandingAboutTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('section_key')
                    ->label('Ключ')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(fn ($record): string => LandingAboutResource::getUrl('edit', ['record' => $record]));
    }
}
