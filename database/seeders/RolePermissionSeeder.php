<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Support\Access\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (PermissionCatalog::definitions() as $name => $label) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (UserRole::cases() as $roleEnum) {
            $role = Role::findOrCreate($roleEnum->value, 'web');
            $perms = PermissionCatalog::defaultMatrix()[$roleEnum->value] ?? [];
            $role->syncPermissions($perms);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        if ($this->command) {
            $this->command->info('Role tetap + permission matrix default tersimpan.');
        }
    }
}
