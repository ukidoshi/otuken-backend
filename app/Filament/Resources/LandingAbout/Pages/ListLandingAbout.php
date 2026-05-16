<?php

namespace App\Filament\Resources\LandingAbout\Pages;

use App\Filament\Resources\LandingAbout\LandingAboutResource;
use Filament\Resources\Pages\ListRecords;

class ListLandingAbout extends ListRecords
{
    protected static string $resource = LandingAboutResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function mount(): void
    {
        parent::mount();

        $record = static::getResource()::getEloquentQuery()->first();
        if ($record !== null) {
            $this->redirect(static::getResource()::getUrl('edit', ['record' => $record]));
        }
    }
}
