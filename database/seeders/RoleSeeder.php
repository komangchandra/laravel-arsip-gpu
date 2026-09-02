<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            'super-admin',
            'director',
            'manager',
            'ktt',
            'sr-staff',
            'staff',
            'sr-staff-haul',
            'staff-haul',
        ] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
