<?php

namespace App\Filament\Resources\LandingObject\Pages;

use App\Filament\Resources\LandingObject\LandingObjectResource;
use Filament\Resources\Pages\ListRecords;

class ListLandingObjects extends ListRecords
{
    protected static string $resource = LandingObjectResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
