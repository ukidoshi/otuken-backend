<?php

namespace App\Filament\Resources\LandingHome;

use App\Filament\Resources\LandingHome\Pages\EditLandingHome;
use App\Filament\Resources\LandingHome\Pages\ListLandingHome;
use App\Filament\Resources\LandingHome\Schemas\LandingHomeForm;
use App\Filament\Resources\LandingHome\Tables\LandingHomeTable;
use App\Models\LandingContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * «Главная страница лендинга» — единая форма для записи `site_pages.home`:
 * SEO, вступление, FAQ, блоки about / festival / objects_section /
 * scenarios_section и общая галерея фестиваля.
 */
class LandingHomeResource extends Resource
{
    protected static ?string $model = LandingContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'Тексты лендинга';

    protected static ?string $navigationLabel = 'Главная страница лендинга';

    protected static ?string $modelLabel = 'Главная страница лендинга';

    protected static ?string $pluralModelLabel = 'Главная страница лендинга';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?int $navigationSort = 100;

    protected static ?string $slug = 'landing/home';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('section_key', 'site_pages.home');
    }

    public static function form(Schema $schema): Schema
    {
        return LandingHomeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LandingHomeTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandingHome::route('/'),
            'edit' => EditLandingHome::route('/{record}/edit'),
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
