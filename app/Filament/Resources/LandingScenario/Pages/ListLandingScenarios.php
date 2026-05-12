<?php

namespace App\Filament\Resources\LandingScenario\Pages;

use App\Filament\Resources\LandingScenario\LandingScenarioResource;
use Filament\Resources\Pages\ListRecords;

class ListLandingScenarios extends ListRecords
{
    protected static string $resource = LandingScenarioResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
