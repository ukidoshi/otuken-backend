<?php

namespace App\Filament\Resources\LandingObject;

use App\Filament\Resources\LandingObject\Pages\EditLandingObject;
use App\Filament\Resources\LandingObject\Pages\ListLandingObjects;
use App\Filament\Resources\LandingObject\Schemas\LandingObjectForm;
use App\Filament\Resources\LandingObject\Tables\LandingObjectTable;
use App\Models\LandingContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LandingObjectResource extends Resource
{
    protected static ?string $model = LandingContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = 'Каталог лендинга (тех.)';

    protected static ?string $navigationLabel = 'Объекты (отдельно)';

    protected static ?string $modelLabel = 'Объект комплекса';

    protected static ?string $pluralModelLabel = 'Объекты комплекса';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?int $navigationSort = 200;

    protected static ?string $slug = 'landing/objects';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('section_key', 'like', 'object.%')
            ->orderBy('section_key');
    }

    public static function form(Schema $schema): Schema
    {
        return LandingObjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LandingObjectTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandingObjects::route('/'),
            'edit' => EditLandingObject::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('landing.read') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
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
