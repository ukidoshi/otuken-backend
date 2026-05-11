<?php

namespace App\Filament\Resources\User\Pages;

use App\Filament\Resources\User\UserResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        if (auth()->user()?->can('users.manage')) {
            return [
                CreateAction::make()
                    ->label('Добавить пользователя'),
            ];
        }

        return [
            Action::make('explainAddUser')
                ->label('Добавить пользователя')
                ->icon(Heroicon::OutlinedPlus)
                ->color('primary')
                ->modalHeading('Добавление пользователей')
                ->modalDescription('Чтобы добавить новых пользователей — напишите Начыну.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Закрыть'),
        ];
    }
}
