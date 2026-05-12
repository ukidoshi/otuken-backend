<?php

namespace App\Filament\Resources\LandingHome\Pages;

use App\Filament\Resources\LandingHome\LandingHomeResource;
use Filament\Resources\Pages\ListRecords;

class ListLandingHome extends ListRecords
{
    protected static string $resource = LandingHomeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
