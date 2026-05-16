<?php

namespace App\Filament\Resources\LandingAbout;

use App\Filament\Resources\LandingAbout\Pages\EditLandingAbout;
use App\Filament\Resources\LandingAbout\Pages\ListLandingAbout;
use App\Filament\Resources\LandingAbout\Schemas\LandingAboutForm;
use App\Filament\Resources\LandingAbout\Tables\LandingAboutTable;
use App\Models\LandingContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Тексты страницы «О нас» (/o-nas) — одна запись site_pages.about_us.
 */
class LandingAboutResource extends Resource
{
    protected static ?string $model = LandingContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Контент лендинга';

    protected static ?string $navigationLabel = 'Страница «О нас»';

    protected static ?string $modelLabel = 'О нас';

    protected static ?string $pluralModelLabel = 'О нас';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'landing/about';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('section_key', 'site_pages.about_us');
    }

    public static function form(Schema $schema): Schema
    {
        return LandingAboutForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LandingAboutTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandingAbout::route('/'),
            'edit' => EditLandingAbout::route('/{record}/edit'),
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
