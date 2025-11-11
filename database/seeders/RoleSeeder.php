<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'super-admin']);
        Role::create(['name' => 'director']);
        Role::create(['name' => 'manager']);
        Role::create(['name' => 'ktt']);
        Role::create(['name' => 'sr-staff']);
        Role::create(['name' => 'staff']);
        Role::create(['name' => 'sr-staff-haul']);
        Role::create(['name' => 'staff-haul']);
    }
}
