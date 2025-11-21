<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Komang Chandra Winata - Super Admin
        $komang = User::create([
            'name' => 'Komang Chandra Winata',
            'email' => 'komangchandraaa1@gmail.com',
            'password' => Hash::make('Empire8855!'),
            'jabatan' => 'Developer And Maintenance System',
        ]);
        $komang->assignRole('super-admin');

        // Wayan Sujasman - Director
        $wayan = User::create([
            'name' => 'Wayan Sujasman',
            'email' => 'wayans@atlas-coal.co.id',
            'password' => Hash::make('@Atlas2025'),
            'jabatan' => 'Direktur Utama',
        ]);
        $wayan->assignRole('director');

        // Ferry Juanda - Manager
        $ferry = User::create([
            'name' => 'Ferry Juanda B',
            'email' => 'ferry.juanda@gorbyputrautama.com',
            'password' => Hash::make('@Kemang43'),
            'jabatan' => 'Operation General Manager',
        ]);
        $ferry->assignRole('manager');

        // Ananda Wahyu - KTT
        $wahyu = User::create([
            'name' => 'Ananda Wahyu Tambunan',
            'email' => 'ananda.wahyu@gorbyputrautama.com',
            'password' => Hash::make('@Kemang43'),
            'jabatan' => 'Kepala Teknik Tambang',
        ]);
        $wahyu->assignRole('ktt');

        //  Arif Rahman - KTT
        $arif = User::create([
            'name' => 'Arif Rahman',
            'email' => 'mineplan@gorbyputrautama.com',
            'password' => Hash::make('@Kemang43'),
            'jabatan' => 'Kepala Teknik Tambang',
        ]);
        $arif->assignRole('ktt');

        // Johan P Barus - Sr. Staff Mine Engineer
        $johan = User::create([
            'name' => 'Johan P Barus',
            'email' => '02_mineplan@gorbyputrautama.com',
            'password' => Hash::make('@Kemang43'),
            'jabatan' => 'Sr. Mine Engineer',
        ]);
        $johan->assignRole('sr-staff');

        // Defri Pratama - Dept. Head Hauling- Sr. Staff Hauling
        $defri = User::create([
            'name' => 'Defri Pratama',
            'email' => 'defri.pratama@gorbyputrautama.com',
            'password' => Hash::make('@Kemang43'),
            'jabatan' => 'Dept. Head Hauling',
        ]);
        $defri->assignRole('sr-staff-haul');

        // Rafli Ronaldi - Mine Engineer
        $rafli = User::create([
            'name' => 'Rafli Ronaldi',
            'email' => '03_mineplan@gorbyputrautama.com',
            'password' => Hash::make('@Kemang43'),
            'jabatan' => 'Mine Engineer',
        ]);
        $rafli->assignRole('sr-staff');

        // Admin Engineering - Staff
        $adminEng = User::create([
            'name' => 'Admin Engineering',
            'email' => 'admin.engineering@gorbyputrautama.com',
            'password' => Hash::make('@Kemang43'),
            'jabatan' => 'Staff Engineering',
        ]);
        $adminEng->assignRole('staff');

        // Admin Hauling - Staff-Haul
        $adminHaul = User::create([
            'name' => 'Admin Hauling',
            'email' => 'adminhauling@gorbyputrautama.com',
            'password' => Hash::make('@Kemang43'),
            'jabatan' => 'Staff Hauling',
        ]);
        $adminHaul->assignRole('staff-haul');
    }
}
