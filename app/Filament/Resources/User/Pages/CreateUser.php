<?php

namespace App\Filament\Resources\User\Pages;

use App\Filament\Resources\User\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $roleForSync = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->roleForSync = $data['role'] ?? 'user';
        unset($data['role']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncRoles([$this->roleForSync ?? 'user']);
    }
}
