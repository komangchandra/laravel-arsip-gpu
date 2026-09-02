<?php

namespace Database\Seeders;

use App\Support\ApplicationPermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (ApplicationPermissions::ALL as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $superAdmin->syncPermissions(
            Permission::query()->where('guard_name', 'web')->get()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
