<?php

namespace App\Filament\Resources\LandingEvent\Pages;

use App\Filament\Resources\LandingEvent\LandingEventResource;
use Filament\Resources\Pages\ListRecords;

class ListLandingEvents extends ListRecords
{
    protected static string $resource = LandingEventResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
