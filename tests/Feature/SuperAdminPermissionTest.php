<?php

use App\Models\User;
use App\Support\ApplicationPermissions;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('grants every application permission to super admin', function () {
    $this->seed(PermissionSeeder::class);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    foreach (ApplicationPermissions::ALL as $permission) {
        expect($superAdmin->can($permission))->toBeTrue("Super admin tidak memiliki permission {$permission}.");
    }
});

it('can run the permission seeder repeatedly', function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(PermissionSeeder::class);

    expect(Permission::count())
        ->toBe(count(ApplicationPermissions::ALL));
});
