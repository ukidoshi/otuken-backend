<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.directory');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.manage');
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if (! $user->can('users.manage')) {
            return false;
        }

        if ($model->hasRole('admin') && ! User::role('admin')->whereKeyNot($model->getKey())->exists()) {
            return false;
        }

        return true;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('users.manage');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->can('users.manage');
    }
}
