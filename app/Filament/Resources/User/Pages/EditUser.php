<?php

namespace App\Filament\Resources\User\Pages;

use App\Filament\Resources\User\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $roleForSync = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->record->roles->first()?->name ?? 'user';

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $newRole = $data['role'] ?? 'user';
        unset($data['role']);

        $this->assertNotRemovingLastAdmin($newRole);

        $this->roleForSync = $newRole;

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncRoles([$this->roleForSync ?? 'user']);
    }

    private function assertNotRemovingLastAdmin(string $newRole): void
    {
        if ($newRole === 'admin') {
            return;
        }

        if (! $this->record->hasRole('admin')) {
            return;
        }

        if (User::role('admin')->whereKeyNot($this->record->getKey())->exists()) {
            return;
        }

        Notification::make()
            ->danger()
            ->title('Нельзя снять роль администратора с единственного администратора.')
            ->send();

        throw new Halt;
    }
}
