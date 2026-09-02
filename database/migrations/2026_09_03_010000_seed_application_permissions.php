<?php

use App\Support\ApplicationPermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(ApplicationPermissions::ALL)
            ->map(fn (string $name) => Permission::findOrCreate($name, 'web'));

        Role::findOrCreate('super-admin', 'web')->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ApplicationPermissions::ALL)
            ->get();

        Role::query()
            ->where('name', 'super-admin')
            ->where('guard_name', 'web')
            ->first()
            ?->revokePermissionTo($permissions);

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ApplicationPermissions::ALL)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
