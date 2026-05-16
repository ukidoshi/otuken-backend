<?php

namespace App\Filament\Resources\LandingEvent;

use App\Filament\Resources\LandingEvent\Pages\EditLandingEvent;
use App\Filament\Resources\LandingEvent\Pages\ListLandingEvents;
use App\Filament\Resources\LandingEvent\Schemas\LandingEventForm;
use App\Filament\Resources\LandingEvent\Tables\LandingEventTable;
use App\Models\LandingContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * События комплекса «Өтүкен».
 *
 * Используют ту же таблицу landing_contents, что и страницы/объекты/сценарии,
 * с префиксом `event.<slug>`. Slug захардкожен на фронте — создавать/удалять
 * нельзя. Галерея фото хранится в колонке `images` (общая для всех локалей).
 */
class LandingEventResource extends Resource
{
    protected static ?string $model = LandingContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Каталог лендинга (тех.)';

    protected static ?string $navigationLabel = 'События (отдельно)';

    protected static ?string $modelLabel = 'Событие';

    protected static ?string $pluralModelLabel = 'События';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?int $navigationSort = 220;

    protected static ?string $slug = 'landing/events';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('section_key', 'like', 'event.%')
            ->orderBy('section_key');
    }

    public static function form(Schema $schema): Schema
    {
        return LandingEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LandingEventTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandingEvents::route('/'),
            'edit' => EditLandingEvent::route('/{record}/edit'),
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
