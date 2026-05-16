<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $newsPermissions = [
            'news.create',
            'news.read',
            'news.update',
            'news.delete',
            'news.publish',
            'news.unpublish',
            'news.archive',
            'news.approve',
            'news.preview',
        ];

        $landingPermissions = [
            'landing.read',
            'landing.update',
        ];

        $permissions = [
            ...$newsPermissions,
            ...$landingPermissions,
            'users.directory',
            'users.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Permission::query()->whereIn('name', ['landing.main', 'landing.catalog'])->delete();

        $userRole = Role::findOrCreate('user', 'web');
        $userRole->syncPermissions([
            ...$newsPermissions,
            ...$landingPermissions,
            'users.directory',
        ]);

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions($permissions);

        $legacyRoleNames = ['smm', 'editor'];
        User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $legacyRoleNames))
            ->each(function (User $model) use ($legacyRoleNames, $userRole): void {
                foreach ($legacyRoleNames as $legacy) {
                    if ($model->hasRole($legacy)) {
                        $model->removeRole($legacy);
                    }
                }
                if (! $model->hasRole('admin')) {
                    $model->assignRole($userRole);
                }
            });

        Role::query()->whereIn('name', ['smm', 'editor'])->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
