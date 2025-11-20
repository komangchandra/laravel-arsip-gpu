<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create([
            'name' => 'BA Rental Tambang', 
            'description' => '-'
        ]);

        Category::create([
            'name' => 'BA Rental ISP', 
            'description' => '-'
        ]);

        Category::create([
            'name' => 'BA Rental PORT', 
            'description' => '-'
        ]);

        Category::create([
            'name' => 'BA Hauling', 
            'description' => '-'
        ]);

        Category::create([
            'name' => 'BA Penerimaan', 
            'description' => '-'
        ]);

        Category::create([
            'name' => 'BA Barged', 
            'description' => '-'
        ]);
    }
}
