<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AdminEngineeringPermissionSeeder extends Seeder
{
    public const EMAIL = 'admin.engineering@gorbyputrautama.com';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::query()->where('email', self::EMAIL)->first();
        if (! $user) {
            $this->command?->warn('User '.self::EMAIL.' tidak ditemukan; permission tidak diberikan.');

            return;
        }

        $user->givePermissionTo([
            Permission::findOrCreate('documents.view', 'web'),
            Permission::findOrCreate('documents.update', 'web'),
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
