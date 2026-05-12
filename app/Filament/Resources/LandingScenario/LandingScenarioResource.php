<?php

namespace App\Filament\Resources\LandingScenario;

use App\Filament\Resources\LandingScenario\Pages\EditLandingScenario;
use App\Filament\Resources\LandingScenario\Pages\ListLandingScenarios;
use App\Filament\Resources\LandingScenario\Schemas\LandingScenarioForm;
use App\Filament\Resources\LandingScenario\Tables\LandingScenarioTable;
use App\Models\LandingContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Сценарии территории (6 фиксированных slug'ов).
 *
 * Использует ту же таблицу landing_contents, что и объекты, но фильтруется по
 * section_key вида `scenario.<slug>`. Slug'и зашиты на фронте — создавать/
 * удалять записи нельзя.
 */
class LandingScenarioResource extends Resource
{
    protected static ?string $model = LandingContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Тексты лендинга';

    protected static ?string $navigationLabel = 'Сценарии территории';

    protected static ?string $modelLabel = 'Сценарий территории';

    protected static ?string $pluralModelLabel = 'Сценарии территории';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?int $navigationSort = 120;

    protected static ?string $slug = 'landing/scenarios';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('section_key', 'like', 'scenario.%')
            ->orderBy('section_key');
    }

    public static function form(Schema $schema): Schema
    {
        return LandingScenarioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LandingScenarioTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandingScenarios::route('/'),
            'edit' => EditLandingScenario::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('landing.read') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
